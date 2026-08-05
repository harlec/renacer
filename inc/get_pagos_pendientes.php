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

// El total a cobrar se calcula igual que el recibo (SUM de detalle_ventas.total),
// no desde ventas.total — el carrito de venta.php puede acumular ese campo con
// centavos de diferencia por redondeos intermedios en JS.
$r = $conn->query("
    SELECT v.id_venta, v.fecha_ope, c.cliente AS nombre_cliente,
           COALESCE(SUM(dv.total), 0) AS total_real,
           COALESCE(MAX(vp.pagado), 0) AS pagado
    FROM ventas v
    LEFT JOIN detalle_ventas dv ON dv.venta = v.id_venta
    LEFT JOIN clientes c ON c.id_cliente = v.cliente
    LEFT JOIN (
        SELECT venta, SUM(monto) AS pagado FROM venta_pagos GROUP BY venta
    ) vp ON vp.venta = v.id_venta
    WHERE v.estado != '2'
      AND DATE(v.fecha_ope) = CURDATE()
      AND (c.cliente IS NULL OR UPPER(TRIM(c.cliente)) != 'FACTURA MANUAL')
      AND v.fecha_compromiso_pago IS NULL
    GROUP BY v.id_venta
    HAVING total_real - pagado > 0.01
    ORDER BY v.fecha_ope DESC
");

$data = [];
if ($r) {
    while ($row = $r->fetch_assoc()) {
        $total = round((float)$row['total_real'], 2);
        $data[] = [
            'id_venta' => (int)$row['id_venta'],
            'hora'     => date('H:i', strtotime($row['fecha_ope'])),
            'total'    => $total,
            'saldo'    => round($total - (float)$row['pagado'], 2),
            'cliente'  => $row['nombre_cliente'] ?: 'Sin cliente',
        ];
    }
}

// Resumen de lo ya cobrado hoy, por medio de pago
$resumen = ['efectivo' => 0, 'yape' => 0, 'plin' => 0, 'bbva' => 0, 'yape_susan' => 0, 'tarjeta' => 0, 'total' => 0];
$rr = $conn->query("SELECT metodo, SUM(monto) AS total FROM venta_pagos WHERE DATE(fecha) = CURDATE() GROUP BY metodo");
if ($rr) {
    while ($row = $rr->fetch_assoc()) {
        if (isset($resumen[$row['metodo']])) {
            $resumen[$row['metodo']] = round((float)$row['total'], 2);
        }
        $resumen['total'] += round((float)$row['total'], 2);
    }
}

echo json_encode(['ok' => true, 'data' => $data, 'resumen' => $resumen]);
