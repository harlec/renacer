<?php
ob_start();
ini_set('display_errors', '0');
error_reporting(0);
session_start();
ob_clean();
header('Content-Type: application/json');

if (!isset($_SESSION['id_usr'])) {
    echo json_encode(['ok' => false, 'data' => []]);
    exit;
}

$categoria_id = intval($_GET['categoria'] ?? 0);
if ($categoria_id <= 0) {
    echo json_encode(['ok' => false, 'data' => []]);
    exit;
}

$conn = new mysqli('localhost', 'admin_renacer', 'ikm169uhn', 'admin_renacer');
$conn->set_charset('utf8');

if ($conn->connect_error) {
    echo json_encode(['ok' => false, 'data' => []]);
    exit;
}

// Mismos periodos predefinidos que inc/get_reporte_categorias.php.
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

// Productos de la categoría elegida, con lo vendido en el periodo — se listan también
// los que no tuvieron ventas en el periodo (0 unidades), para que el detalle refleje
// el catálogo completo de la categoría, no solo lo que se vendió. Como el JOIN a ventas
// es LEFT (para no perder esos productos en 0), la suma tiene que ser condicional a que
// el JOIN haya encontrado una venta dentro del periodo — si no, sumaría todo el histórico.
$sql = "
    SELECT p.nom_prod,
           COALESCE(SUM(CASE WHEN v.id_venta IS NOT NULL THEN dv.cantidad ELSE 0 END), 0) AS unidades,
           COALESCE(SUM(CASE WHEN v.id_venta IS NOT NULL THEN dv.total ELSE 0 END), 0) AS monto
    FROM productos p
    LEFT JOIN detalle_ventas dv ON dv.producto = p.id_producto
    LEFT JOIN ventas v ON v.id_venta = dv.venta AND v.estado != '2' $where_fecha
    WHERE p.categoria = $categoria_id
    GROUP BY p.id_producto, p.nom_prod
    ORDER BY monto DESC
";

$result = $conn->query($sql);

$data = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $data[] = [
            strtoupper($row['nom_prod']),
            round((float)$row['unidades'], 2),
            number_format((float)$row['monto'], 2),
        ];
    }
}

$conn->close();
echo json_encode(['ok' => true, 'data' => $data]);
