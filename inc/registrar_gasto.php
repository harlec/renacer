<?php
// Registra un gasto (fijo, diario o puntual) en la tabla gastos.
ob_start();
ini_set('display_errors', '0');
error_reporting(0);
session_start();
ob_clean();
header('Content-Type: application/json');

if (!isset($_SESSION['id_usr']) || ($_SESSION['type'] ?? '') !== 'admin') {
    echo json_encode(['ok' => false, 'mensaje' => 'Sin permiso']);
    exit;
}

$tipos_validos   = ['fijo', 'diario', 'puntual'];
include_once(__DIR__ . '/medios_pago.php');
$metodos_validos = array_keys($MEDIOS_PAGO);

$fecha     = $_POST['fecha'] ?? '';
$tipo      = $_POST['tipo'] ?? '';
$categoria = trim($_POST['categoria'] ?? '');
$desc      = trim($_POST['descripcion'] ?? '');
$monto     = round(floatval($_POST['monto'] ?? 0), 2);
$metodo    = $_POST['metodo'] ?? 'efectivo';
$usuario   = intval($_SESSION['id_usr']);

if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha) || !in_array($tipo, $tipos_validos, true)
    || $categoria === '' || $monto <= 0 || !in_array($metodo, $metodos_validos, true)) {
    echo json_encode(['ok' => false, 'mensaje' => 'Datos inválidos']);
    exit;
}

$conn = new mysqli('localhost', 'admin_renacer', 'ikm169uhn', 'admin_renacer');
$conn->set_charset('utf8');
if ($conn->connect_error) {
    echo json_encode(['ok' => false, 'mensaje' => 'DB: ' . $conn->connect_error]);
    exit;
}

$st = $conn->prepare("INSERT INTO gastos (fecha, tipo, categoria, descripcion, monto, metodo, usuario, estado, fecha_registro) VALUES (?,?,?,?,?,?,?,'1',NOW())");
$st->bind_param('ssssdsi', $fecha, $tipo, $categoria, $desc, $monto, $metodo, $usuario);
if ($st->execute()) {
    echo json_encode(['ok' => true]);
} else {
    echo json_encode(['ok' => false, 'mensaje' => 'No se pudo guardar: ' . $conn->error]);
}
$conn->close();
