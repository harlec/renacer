<?php
// Borra un periodo de balance (solo la configuración del periodo; no toca ventas, compras ni gastos).
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
$id = intval($_GET['id'] ?? 0);
$conn = new mysqli('localhost', 'admin_renacer', 'ikm169uhn', 'admin_renacer');
$conn->set_charset('utf8');
if ($id <= 0 || $conn->connect_error) {
    echo json_encode(['ok' => false, 'mensaje' => 'Solicitud inválida']);
    exit;
}
$conn->query("DELETE FROM caja_periodos WHERE id_cperiodo = $id");
$ok = $conn->affected_rows > 0;
$conn->query("DELETE FROM caja_periodo_saldos WHERE id_cperiodo = $id");
echo json_encode(['ok' => $ok, 'mensaje' => 'No se pudo borrar el periodo']);
$conn->close();
