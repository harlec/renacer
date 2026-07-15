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

$r = $conn->query("
    SELECT v.id_venta, v.fecha_ope, v.total, c.cliente AS nombre_cliente,
           COALESCE(vp.pagado, 0) AS pagado
    FROM ventas v
    LEFT JOIN clientes c ON c.id_cliente = v.cliente
    LEFT JOIN (
        SELECT venta, SUM(monto) AS pagado FROM venta_pagos GROUP BY venta
    ) vp ON vp.venta = v.id_venta
    WHERE v.estado != '2'
      AND DATE(v.fecha_ope) = CURDATE()
      AND COALESCE(vp.pagado, 0) < v.total
    ORDER BY v.fecha_ope DESC
");

$data = [];
if ($r) {
    while ($row = $r->fetch_assoc()) {
        $data[] = [
            'id_venta' => (int)$row['id_venta'],
            'hora'     => date('H:i', strtotime($row['fecha_ope'])),
            'total'    => round((float)$row['total'], 2),
            'saldo'    => round((float)$row['total'] - (float)$row['pagado'], 2),
            'cliente'  => $row['nombre_cliente'] ?: 'Sin cliente',
        ];
    }
}

echo json_encode(['ok' => true, 'data' => $data]);
