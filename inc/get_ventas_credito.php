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

// Igual criterio de total que el resto de caja_pagos: SUM de detalle_ventas.total, no
// ventas.total. Una venta a crédito ya cobrada (parcial o total) sigue apareciendo aquí
// hasta que el saldo llegue a 0, momento en el que ya no tiene sentido seguir "a crédito".
$r = $conn->query("
    SELECT v.id_venta, v.fecha_compromiso_pago, c.cliente AS nombre_cliente,
           COALESCE(SUM(dv.total), 0) AS total_real,
           COALESCE(MAX(vp.pagado), 0) AS pagado
    FROM ventas v
    LEFT JOIN detalle_ventas dv ON dv.venta = v.id_venta
    LEFT JOIN clientes c ON c.id_cliente = v.cliente
    LEFT JOIN (
        SELECT venta, SUM(monto) AS pagado FROM venta_pagos GROUP BY venta
    ) vp ON vp.venta = v.id_venta
    WHERE v.estado != '2'
      AND v.id_empleado IS NULL
      AND v.fecha_compromiso_pago IS NOT NULL
    GROUP BY v.id_venta
    HAVING total_real - pagado > 0.01
    ORDER BY v.fecha_compromiso_pago ASC
");

$hoy = date('Y-m-d');
$data = [];
if ($r) {
    while ($row = $r->fetch_assoc()) {
        $total = round((float)$row['total_real'], 2);
        $data[] = [
            'id_venta'  => (int)$row['id_venta'],
            'cliente'   => $row['nombre_cliente'] ?: 'Sin cliente',
            'total'     => $total,
            'saldo'     => round($total - (float)$row['pagado'], 2),
            'fecha'     => $row['fecha_compromiso_pago'],
            'vencida'   => $row['fecha_compromiso_pago'] < $hoy,
        ];
    }
}

$conn->close();
echo json_encode(['ok' => true, 'data' => $data]);
