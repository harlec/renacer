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

$estados_label = ['0' => 'Pendiente', '1' => 'Facturada', '2' => 'Anulada'];

// Ventas que tienen al menos un pago registrado hoy (aunque la venta en sí sea de otro día,
// como el caso de una venta a crédito que recién hoy se termina de cobrar).
$r = $conn->query("
    SELECT v.id_venta, v.estado, c.cliente AS nombre_cliente,
           COALESCE(SUM(dv.total), 0) AS total_real
    FROM ventas v
    LEFT JOIN detalle_ventas dv ON dv.venta = v.id_venta
    LEFT JOIN clientes c ON c.id_cliente = v.cliente
    WHERE v.id_venta IN (SELECT DISTINCT venta FROM venta_pagos WHERE DATE(fecha) = CURDATE())
    GROUP BY v.id_venta
    ORDER BY v.id_venta DESC
");

$ventas = [];
$orden = [];
if ($r) {
    while ($row = $r->fetch_assoc()) {
        $id = (int)$row['id_venta'];
        $orden[] = $id;
        $ventas[$id] = [
            'id_venta'     => $id,
            'cliente'      => $row['nombre_cliente'] ?: 'Sin cliente',
            'total'        => round((float)$row['total_real'], 2),
            'estado'       => $row['estado'],
            'estado_label' => $estados_label[$row['estado']] ?? $row['estado'],
            'pagos'        => [],
            'pagado'       => 0,
        ];
    }
}

$rp = $conn->query("
    SELECT id_pago, venta, metodo, monto, fecha
    FROM venta_pagos
    WHERE DATE(fecha) = CURDATE()
    ORDER BY venta DESC, id_pago ASC
");
if ($rp) {
    while ($row = $rp->fetch_assoc()) {
        $id = (int)$row['venta'];
        if (!isset($ventas[$id])) continue;
        $monto = round((float)$row['monto'], 2);
        $ventas[$id]['pagos'][] = [
            'id_pago' => (int)$row['id_pago'],
            'metodo'  => $row['metodo'],
            'monto'   => $monto,
            'hora'    => date('H:i', strtotime($row['fecha'])),
        ];
        $ventas[$id]['pagado'] = round($ventas[$id]['pagado'] + $monto, 2);
    }
}

$conn->close();

$data = [];
foreach ($orden as $id) $data[] = $ventas[$id];

echo json_encode(['ok' => true, 'data' => $data]);
