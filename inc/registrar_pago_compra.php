<?php
// Registra un abono (pago parcial o total) contra una compra pendiente, para
// inc/get o la pantalla cuentas_x_pagar.php. Espejo de inc/registrar_pago.php (ventas).
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

$id_compra = intval($_POST['id_compra'] ?? 0);
$monto     = round(floatval($_POST['monto'] ?? 0), 2);
$metodo    = in_array($_POST['metodo'] ?? '', $metodos_validos, true) ? $_POST['metodo'] : 'efectivo';
$usuario_id = intval($_SESSION['id_usr']);

if ($id_compra <= 0 || $monto <= 0) {
    echo json_encode(['ok' => false, 'mensaje' => 'Datos inválidos']);
    exit;
}

$conn = new mysqli('localhost', 'admin_renacer', 'ikm169uhn', 'admin_renacer');
$conn->set_charset('utf8');

if ($conn->connect_error) {
    echo json_encode(['ok' => false, 'mensaje' => 'DB: ' . $conn->connect_error]);
    exit;
}

$r = $conn->query("SELECT id_compra, estado, total FROM compras WHERE id_compra = $id_compra");
$compra = $r ? $r->fetch_assoc() : null;

if (!$compra) {
    echo json_encode(['ok' => false, 'mensaje' => 'La compra no existe']);
    exit;
}
if ($compra['estado'] == '2') {
    echo json_encode(['ok' => false, 'mensaje' => 'Esta compra está anulada']);
    exit;
}

$rp = $conn->query("SELECT COALESCE(SUM(monto), 0) AS pagado FROM compra_pagos WHERE compra = $id_compra");
$pagado = round((float)($rp ? $rp->fetch_assoc()['pagado'] : 0), 2);
$saldo = round((float)$compra['total'] - $pagado, 2);

if ($saldo <= 0.01) {
    echo json_encode(['ok' => false, 'mensaje' => 'Esta compra ya no tiene saldo pendiente']);
    exit;
}
if ($monto > $saldo + 0.01) {
    echo json_encode(['ok' => false, 'mensaje' => 'El monto (S/ ' . number_format($monto, 2) . ') supera el saldo pendiente (S/ ' . number_format($saldo, 2) . ')']);
    exit;
}

$metodo_esc = $conn->real_escape_string($metodo);
$fecha = date('Y-m-d H:i:s');
$ok = $conn->query("INSERT INTO compra_pagos (compra, monto, metodo, usuario, fecha) VALUES ($id_compra, $monto, '$metodo_esc', $usuario_id, '$fecha')");

$conn->close();

if (!$ok) {
    echo json_encode(['ok' => false, 'mensaje' => 'No se pudo registrar el pago']);
    exit;
}

echo json_encode(['ok' => true]);
