<?php
// Registra una deuda con un proveedor previa a usar el sistema: se guarda como
// una fila más en `compras` (a crédito, marcada con deuda_anterior='1') pero
// sin líneas de detalle_compras ni movimiento de stock, porque no representa
// una compra de productos real.
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

$id_usuario   = intval($_SESSION['id_usr']);
$id_proveedor = intval($_POST['proveedor'] ?? 0);
$monto        = round(floatval($_POST['monto'] ?? 0), 2);
$fecha        = trim($_POST['fecha'] ?? '');
$observacion  = trim($_POST['observacion'] ?? '');

if ($id_proveedor <= 0 || $monto <= 0) {
    echo json_encode(['ok' => false, 'mensaje' => 'Datos inválidos']);
    exit;
}

if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) {
    $fecha = date('Y-m-d');
}
if ($observacion === '') {
    $observacion = 'Deuda anterior al sistema';
}

$conn = new mysqli('localhost', 'admin_renacer', 'ikm169uhn', 'admin_renacer');
$conn->set_charset('utf8');

if ($conn->connect_error) {
    echo json_encode(['ok' => false, 'mensaje' => 'DB: ' . $conn->connect_error]);
    exit;
}

$rp = $conn->query("SELECT id_proveedor FROM proveedores WHERE id_proveedor = $id_proveedor");
if (!$rp || !$rp->fetch_assoc()) {
    $conn->close();
    echo json_encode(['ok' => false, 'mensaje' => 'El proveedor no existe']);
    exit;
}

$fecha_esc = $conn->real_escape_string($fecha);
$obs_esc   = $conn->real_escape_string($observacion);

$ok = $conn->query("
    INSERT INTO compras
        (fecha, fecha_ingreso, fecha_despacho, guia, serie_f, numero_f, total, moneda,
         proveedor, usuario, observacion, exonerada, estado, forma_pago, fecha_compromiso_pago, deuda_anterior)
    VALUES
        ('$fecha_esc', '$fecha_esc', '$fecha_esc', '', 'DEUDA', '', $monto, '0',
         $id_proveedor, $id_usuario, '$obs_esc', 'no', '0', 'credito', NULL, '1')
");

$conn->close();

if (!$ok) {
    echo json_encode(['ok' => false, 'mensaje' => 'No se pudo registrar la deuda']);
    exit;
}

echo json_encode(['ok' => true]);
