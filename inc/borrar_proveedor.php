<?php
// Borrado suave del proveedor (estado='0'), igual que ventas/compras: nunca se elimina
// físicamente para no romper el historial de compras que ya lo referencian.
session_start();
header('Content-Type: application/json');

if (empty($_SESSION['ingress'])) {
    echo json_encode(['ok' => false, 'mensaje' => 'Sin sesión']);
    exit;
}

$id = intval($_GET['id'] ?? 0);
if ($id <= 0) {
    echo json_encode(['ok' => false, 'mensaje' => 'Falta indicar el proveedor']);
    exit;
}

$conn = new mysqli('localhost', 'admin_renacer', 'ikm169uhn', 'admin_renacer');
$conn->set_charset('utf8');

if ($conn->connect_error) {
    echo json_encode(['ok' => false, 'mensaje' => 'DB: ' . $conn->connect_error]);
    exit;
}

$ok = $conn->query("UPDATE proveedores SET estado = '0' WHERE id_proveedor = $id");
$conn->close();

echo json_encode(['ok' => (bool)$ok, 'mensaje' => $ok ? 'ok' : 'No se pudo eliminar']);
