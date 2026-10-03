<?php
// Anula un gasto (no se borra físicamente, queda estado = 2 y deja de contar en el balance).
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
if ($id <= 0) {
    echo json_encode(['ok' => false, 'mensaje' => 'Falta indicar el gasto']);
    exit;
}

$conn = new mysqli('localhost', 'admin_renacer', 'ikm169uhn', 'admin_renacer');
$conn->set_charset('utf8');
if ($conn->connect_error) {
    echo json_encode(['ok' => false, 'mensaje' => 'DB: ' . $conn->connect_error]);
    exit;
}

$conn->query("UPDATE gastos SET estado = '2' WHERE id_gasto = $id");
echo json_encode(['ok' => $conn->affected_rows > 0, 'mensaje' => 'No se pudo anular el gasto']);
$conn->close();
