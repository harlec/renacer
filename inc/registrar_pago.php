<?php
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

$conn = new mysqli('localhost', 'admin_renacer', 'ikm169uhn', 'admin_renacer');
$conn->set_charset('utf8');

if ($conn->connect_error) {
    echo json_encode(['ok' => false, 'mensaje' => 'DB: ' . $conn->connect_error]);
    exit;
}

$venta_id   = intval($_POST['venta'] ?? 0);
$pagos      = json_decode($_POST['pagos'] ?? '[]', true);
$usuario_id = intval($_SESSION['id_usr']);
$metodos_validos = ['efectivo', 'yape', 'plin', 'tarjeta'];

if (!$venta_id || !is_array($pagos) || count($pagos) === 0) {
    echo json_encode(['ok' => false, 'mensaje' => 'Datos incompletos']);
    exit;
}

$r = $conn->query("SELECT total, COALESCE((SELECT SUM(monto) FROM venta_pagos WHERE venta = $venta_id), 0) AS pagado FROM ventas WHERE id_venta = $venta_id");
$row = $r ? $r->fetch_assoc() : null;
if (!$row) {
    echo json_encode(['ok' => false, 'mensaje' => 'Venta no encontrada']);
    exit;
}

$saldo_pendiente = round((float)$row['total'] - (float)$row['pagado'], 2);
$suma_nueva = 0;

foreach ($pagos as $p) {
    $metodo = $p['metodo'] ?? '';
    $monto  = round(floatval($p['monto'] ?? 0), 2);
    if (!in_array($metodo, $metodos_validos, true) || $monto <= 0) {
        echo json_encode(['ok' => false, 'mensaje' => 'Método o monto inválido']);
        exit;
    }
    $suma_nueva += $monto;
}

if (abs($suma_nueva - $saldo_pendiente) > 0.01) {
    echo json_encode(['ok' => false, 'mensaje' => 'La suma de los pagos (S/ ' . number_format($suma_nueva, 2) . ') no cubre el saldo pendiente (S/ ' . number_format($saldo_pendiente, 2) . ')']);
    exit;
}

$conn->begin_transaction();
try {
    $fecha = date('Y-m-d H:i:s');
    foreach ($pagos as $p) {
        $metodo = $conn->real_escape_string($p['metodo']);
        $monto  = round(floatval($p['monto']), 2);
        $conn->query("INSERT INTO venta_pagos (venta, metodo, monto, usuario, fecha) VALUES ($venta_id, '$metodo', $monto, $usuario_id, '$fecha')");
    }
    $conn->commit();
    echo json_encode(['ok' => true]);
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['ok' => false, 'mensaje' => 'Error al registrar el pago']);
}
