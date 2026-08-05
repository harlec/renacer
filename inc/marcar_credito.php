<?php
// Marca (o quita) una venta pendiente como "a crédito": el cliente pagará después,
// en la fecha indicada. Mientras esté marcada, sale de la cola normal de cobro
// (inc/get_pagos_pendientes.php) y aparece en la pestaña "Crédito".
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

$id_venta = intval($_POST['id_venta'] ?? 0);
$accion   = $_POST['accion'] ?? ''; // 'marcar' o 'quitar'
$fecha    = $_POST['fecha'] ?? '';

if ($id_venta <= 0 || !in_array($accion, ['marcar', 'quitar'], true)) {
    echo json_encode(['ok' => false, 'mensaje' => 'Datos inválidos']);
    exit;
}

if ($accion === 'marcar' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) {
    echo json_encode(['ok' => false, 'mensaje' => 'Fecha inválida']);
    exit;
}

$conn = new mysqli('localhost', 'admin_renacer', 'ikm169uhn', 'admin_renacer');
$conn->set_charset('utf8');

if ($conn->connect_error) {
    echo json_encode(['ok' => false, 'mensaje' => 'DB: ' . $conn->connect_error]);
    exit;
}

$r = $conn->query("SELECT estado FROM ventas WHERE id_venta = $id_venta");
$venta = $r ? $r->fetch_assoc() : null;

if (!$venta) {
    echo json_encode(['ok' => false, 'mensaje' => 'La venta no existe']);
    exit;
}
if ($venta['estado'] == '2') {
    echo json_encode(['ok' => false, 'mensaje' => 'Esta venta está anulada']);
    exit;
}

if ($accion === 'marcar') {
    $fecha_esc = $conn->real_escape_string($fecha);
    $ok = $conn->query("UPDATE ventas SET fecha_compromiso_pago = '$fecha_esc' WHERE id_venta = $id_venta");
} else {
    $ok = $conn->query("UPDATE ventas SET fecha_compromiso_pago = NULL WHERE id_venta = $id_venta");
}

$conn->close();

if (!$ok) {
    echo json_encode(['ok' => false, 'mensaje' => 'No se pudo actualizar']);
    exit;
}

echo json_encode(['ok' => true]);
