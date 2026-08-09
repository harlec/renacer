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
    SELECT COALESCE(c.nom_cat, 'Sin categoría') AS categoria,
           SUM(dv.cantidad) AS unidades,
           SUM(dv.total) AS monto
    FROM detalle_ventas dv
    JOIN ventas v ON v.id_venta = dv.venta
    LEFT JOIN productos p ON p.id_producto = dv.producto
    LEFT JOIN categorias c ON c.id_categoria = p.categoria
    WHERE v.estado != '2' $where_fecha
    GROUP BY categoria
    ORDER BY monto DESC
";

$result = $conn->query($sql);

$data = [];
$total_unidades = 0;
$total_monto = 0;
$categoria_top = null;
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $unidades = round((float)$row['unidades'], 2);
        $monto = round((float)$row['monto'], 2);
        // Monto se manda como número crudo (no con comas de miles) para que DataTables
        // lo ordene numéricamente; el formato con comas se aplica solo al mostrarlo (ver
        // el render de la columna en reporte_mv.php).
        $data[] = [$row['categoria'], $unidades, $monto];
        $total_unidades += $unidades;
        $total_monto += $monto;
        if ($categoria_top === null) $categoria_top = $row['categoria']; // ya viene ordenado por monto desc
    }
}

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
