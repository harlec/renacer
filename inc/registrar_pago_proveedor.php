<?php
// Abona un monto contra el total adeudado a UN proveedor (varias compras a la
// vez), repartiendo en cascada FIFO: se cancela primero la factura con
// fecha_compromiso_pago (o fecha) más antigua, y el resto pasa a la siguiente.
// Espejo de inc/registrar_pago.php (ventas), pero con reparto FIFO en vez de
// proporcional, porque aquí el caso de uso es "abonar a la deuda acumulada".
ob_start();
ini_set('display_errors', '0');
error_reporting(0);
session_start();
ob_clean();
header('Content-Type: application/json');

if (!isset($_SESSION['id_usr'])) {
    echo json_encode(['ok' => false, 'mensaje' => 'Sin sesión']);
    exit;
}

$metodos_validos = ['efectivo', 'transferencia', 'deposito', 'cheque', 'otro'];

$id_proveedor = intval($_POST['proveedor'] ?? 0);
$monto        = round(floatval($_POST['monto'] ?? 0), 2);
$metodo       = in_array($_POST['metodo'] ?? '', $metodos_validos, true) ? $_POST['metodo'] : 'efectivo';
$usuario_id   = intval($_SESSION['id_usr']);

if ($id_proveedor <= 0 || $monto <= 0) {
    echo json_encode(['ok' => false, 'mensaje' => 'Datos inválidos']);
    exit;
}

$conn = new mysqli('localhost', 'admin_renacer', 'ikm169uhn', 'admin_renacer');
$conn->set_charset('utf8');

if ($conn->connect_error) {
    echo json_encode(['ok' => false, 'mensaje' => 'DB: ' . $conn->connect_error]);
    exit;
}

$r = $conn->query("
    SELECT c.id_compra, c.total - COALESCE(cp.pagado, 0) AS saldo
    FROM compras c
    LEFT JOIN (
        SELECT compra, SUM(monto) AS pagado FROM compra_pagos GROUP BY compra
    ) cp ON cp.compra = c.id_compra
    WHERE c.proveedor = $id_proveedor AND c.estado != '2'
    HAVING saldo > 0.01
    ORDER BY (c.fecha_compromiso_pago IS NULL), c.fecha_compromiso_pago ASC, c.fecha ASC
");

$pendientes = [];
if ($r) {
    while ($row = $r->fetch_assoc()) {
        $pendientes[] = ['id_compra' => (int)$row['id_compra'], 'saldo' => round((float)$row['saldo'], 2)];
    }
}

if (empty($pendientes)) {
    $conn->close();
    echo json_encode(['ok' => false, 'mensaje' => 'Este proveedor no tiene facturas pendientes']);
    exit;
}

$saldo_total = round(array_sum(array_column($pendientes, 'saldo')), 2);

if ($monto > $saldo_total + 0.01) {
    $conn->close();
    echo json_encode(['ok' => false, 'mensaje' => 'El monto (S/ ' . number_format($monto, 2) . ') supera la deuda total del proveedor (S/ ' . number_format($saldo_total, 2) . ')']);
    exit;
}

// Reparto FIFO en cascada: la más antigua se lleva lo que le corresponde de su
// propio saldo, y el resto va pasando a la siguiente hasta agotar el monto.
$restante = $monto;
$reparto = [];
foreach ($pendientes as $p) {
    if ($restante <= 0.001) break;
    $asignado = min($restante, $p['saldo']);
    $asignado = round($asignado, 2);
    if ($asignado > 0.001) {
        $reparto[] = ['id_compra' => $p['id_compra'], 'monto' => $asignado];
        $restante = round($restante - $asignado, 2);
    }
}

$conn->begin_transaction();
try {
    $fecha = date('Y-m-d H:i:s');
    $metodo_esc = $conn->real_escape_string($metodo);
    foreach ($reparto as $l) {
        $ok = $conn->query("INSERT INTO compra_pagos (compra, monto, metodo, usuario, fecha) VALUES ({$l['id_compra']}, {$l['monto']}, '$metodo_esc', $usuario_id, '$fecha')");
        if (!$ok) {
            throw new Exception('Insert falló: ' . $conn->error);
        }
    }
    $conn->commit();
    echo json_encode(['ok' => true]);
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['ok' => false, 'mensaje' => 'Error al registrar el pago: ' . $e->getMessage()]);
}

$conn->close();
