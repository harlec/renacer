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

$conn = new mysqli('localhost', 'admin_renacer', 'ikm169uhn', 'admin_renacer');
$conn->set_charset('utf8');

if ($conn->connect_error) {
    echo json_encode(['ok' => false, 'data' => []]);
    exit;
}

$id_usuario = intval($_SESSION['id_usr']);
$es_admin   = ($_SESSION['type'] == 'admin');
$where_user = $es_admin ? "" : "AND v.usuario = $id_usuario";

// Notas de venta pendientes: no facturadas (estado=1) ni anuladas (estado=2).
// El total se calcula igual que en el resto del sistema, sumando detalle_ventas.total.
$r = $conn->query("
    SELECT v.id_venta, v.fecha_ope, c.cliente AS nombre_cliente,
           COALESCE(SUM(dv.total), 0) AS total_real
    FROM ventas v
    LEFT JOIN detalle_ventas dv ON dv.venta = v.id_venta
    LEFT JOIN clientes c ON c.id_cliente = v.cliente
    WHERE v.estado = '0' $where_user
    GROUP BY v.id_venta
    ORDER BY v.fecha_ope DESC
    LIMIT 300
");

$data = [];
if ($r) {
    while ($row = $r->fetch_assoc()) {
        $data[] = [
            'id_venta' => (int)$row['id_venta'],
            'fecha'    => $row['fecha_ope'],
            'total'    => round((float)$row['total_real'], 2),
            'cliente'  => $row['nombre_cliente'] ?: 'Sin cliente',
        ];
    }
}

$conn->close();
echo json_encode(['ok' => true, 'data' => $data]);
