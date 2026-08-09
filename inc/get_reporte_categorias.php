<?php
ob_start();
ini_set('display_errors', '0');
error_reporting(0);
session_start();
ob_clean();
header('Content-Type: application/json');

if (!isset($_SESSION['id_usr'])) {
    echo json_encode(['ok' => false, 'data' => [], 'resumen' => []]);
    exit;
}

$conn = new mysqli('localhost', 'admin_renacer', 'ikm169uhn', 'admin_renacer');
$conn->set_charset('utf8');

if ($conn->connect_error) {
    echo json_encode(['ok' => false, 'data' => [], 'resumen' => []]);
    exit;
}

// Mismos periodos predefinidos que inc/get_reporte_huevos.php, para mantener la
// misma convención de filtro entre reportes de este estilo.
$periodo = $_GET['periodo'] ?? 'ultimo_dia';
$hoy = date('Y-m-d');
$manana = date('Y-m-d', strtotime($hoy . ' +1 day'));

switch ($periodo) {
    case 'ultimo_dia':
        $desde = $hoy;
        $hasta = $manana;
        break;
    case 'ayer':
        $desde = date('Y-m-d', strtotime($hoy . ' -1 day'));
        $hasta = $hoy;
        break;
    case 'ultima_semana':
        $desde = date('Y-m-d', strtotime($hoy . ' -6 days'));
        $hasta = $manana;
        break;
    case 'mes_actual':
        $desde = date('Y-m-01');
        $hasta = $manana;
        break;
    case 'mes_anterior':
        $desde = date('Y-m-01', strtotime($hoy . ' -1 month'));
        $hasta = date('Y-m-01');
        break;
    case 'ultimo_trimestre':
        $desde = date('Y-m-d', strtotime($hoy . ' -3 months'));
        $hasta = $manana;
        break;
    case 'todo_el_anio':
        $desde = date('Y-01-01');
        $hasta = $manana;
        break;
    case 'siempre':
        $desde = null;
        $hasta = null;
        break;
    default:
        $desde = $hoy;
        $hasta = $manana;
}

$where_fecha = '';
if ($desde !== null) {
    $desde_safe = $conn->real_escape_string($desde);
    $hasta_safe = $conn->real_escape_string($hasta);
    $where_fecha = "AND v.fecha_ope >= '$desde_safe' AND v.fecha_ope < '$hasta_safe'";
}

// Ventas agrupadas por categoría del producto. Los productos sin categoría asignada
// se agrupan bajo "Sin categoría" en vez de desaparecer del reporte.
$sql = "
    SELECT p.categoria AS id_categoria,
           SUM(dv.cantidad) AS unidades,
           SUM(dv.total) AS monto
    FROM detalle_ventas dv
    JOIN ventas v ON v.id_venta = dv.venta
    LEFT JOIN productos p ON p.id_producto = dv.producto
    WHERE v.estado != '2' $where_fecha
    GROUP BY p.categoria
    ORDER BY monto DESC
";

$result = $conn->query($sql);

// El nombre de la categoría se resuelve DESPUÉS de sumar, con un mapa aparte, en vez de
// unir con "categorias" antes del SUM — si esa tabla tiene filas duplicadas para el mismo
// id_categoria (visto en producción: la suma se disparaba a cifras absurdas), unirla antes
// de agregar multiplica cada venta una vez por cada fila duplicada.
$nombres_categoria = [];
$rc = $conn->query("SELECT DISTINCT id_categoria, nom_cat FROM categorias");
if ($rc) {
    while ($rowc = $rc->fetch_assoc()) {
        $nombres_categoria[$rowc['id_categoria']] = $rowc['nom_cat'];
    }
}

// Se reagrupa por NOMBRE (no solo por id_categoria) porque también puede haber varias
// filas de "categorias" con distinto id pero el mismo nom_cat (duplicados reales creados
// por error) — sin esto, esas dos categorías con el mismo nombre saldrían como dos filas
// separadas en vez de una sola.
$por_nombre = [];
$total_unidades = 0;
$total_monto = 0;
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $unidades = round((float)$row['unidades'], 2);
        $monto = round((float)$row['monto'], 2);
        $nombre_categoria = $row['id_categoria'] !== null && isset($nombres_categoria[$row['id_categoria']])
            ? $nombres_categoria[$row['id_categoria']]
            : 'Sin categoría';

        if (!isset($por_nombre[$nombre_categoria])) {
            $por_nombre[$nombre_categoria] = ['unidades' => 0, 'monto' => 0];
        }
        $por_nombre[$nombre_categoria]['unidades'] += $unidades;
        $por_nombre[$nombre_categoria]['monto'] += $monto;

        $total_unidades += $unidades;
        $total_monto += $monto;
    }
}

$data = [];
foreach ($por_nombre as $nombre => $vals) {
    // Monto se manda como número crudo (no con comas de miles) para que DataTables lo
    // ordene numéricamente; el formato con comas se aplica solo al mostrarlo (ver el
    // render de la columna en reporte_mv.php).
    $data[] = [$nombre, round($vals['unidades'], 2), round($vals['monto'], 2)];
}
usort($data, fn($a, $b) => $b[2] <=> $a[2]);
$categoria_top = count($data) ? $data[0][0] : null;

$conn->close();

echo json_encode([
    'ok' => true,
    'data' => $data,
    'resumen' => [
        'total_monto'    => round($total_monto, 2),
        'total_unidades' => round($total_unidades, 2),
        'categorias'     => count($data),
        'top'            => $categoria_top ?: '-',
    ],
]);
