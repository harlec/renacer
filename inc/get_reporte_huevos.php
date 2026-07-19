<?php
ob_start();
ini_set('display_errors', '0');
error_reporting(0);
session_start();
ob_clean();
header('Content-Type: application/json');

if (!isset($_SESSION['id_usr'])) {
    echo json_encode(['data' => []]);
    exit;
}

$conn = new mysqli('localhost', 'admin_renacer', 'ikm169uhn', 'admin_renacer');
$conn->set_charset('utf8');

if ($conn->connect_error) {
    echo json_encode(['data' => []]);
    exit;
}

$producto_id = 888; // Huevos

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

$r = $conn->query("SELECT nom_prod FROM productos WHERE id_producto = $producto_id");
$row = $r ? $r->fetch_assoc() : null;
$nombre_producto = $row ? strtoupper($row['nom_prod']) : 'PRODUCTO';

// Se usa una subquery para sumar detalle_ventas SOLO dentro del rango de fechas
// antes de unir con las variantes — evita arrastrar ventas fuera del periodo.
$sql = "
    SELECT vp.id_vp, va.variante AS variante_nombre, vp.precio_vp,
           COALESCE(vd.cantidad_total, 0) AS cantidad_total
    FROM variante_p vp
    LEFT JOIN variantes va ON va.id_variante = vp.variante_vp
    LEFT JOIN (
        SELECT dv.id_vp, SUM(dv.cantidad) AS cantidad_total
        FROM detalle_ventas dv
        JOIN ventas v ON v.id_venta = dv.venta
        WHERE v.estado != '2' $where_fecha
        GROUP BY dv.id_vp
    ) vd ON vd.id_vp = vp.id_vp
    WHERE vp.producto_vp = $producto_id
    ORDER BY cantidad_total DESC
";

$result = $conn->query($sql);

$data = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $variante = $row['variante_nombre'] ? strtoupper($row['variante_nombre']) : 'SIN VARIANTE';
        $precio = number_format((float)$row['precio_vp'], 2);
        $nombre = $nombre_producto . ' ' . $variante . ' (S/' . $precio . ')';
        $cantidad = number_format((float)$row['cantidad_total'], 2);
        $data[] = [$nombre, $cantidad];
    }
}

echo json_encode(['data' => $data]);
