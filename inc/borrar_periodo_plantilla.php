<?php
// Borrado suave (estado='0'), mismo patrón que inc/borrar_producto.php.
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['id_usr'])) {
    echo json_encode(['ok' => false, 'mensaje' => 'Sin sesión']);
    exit;
}

$id = intval($_GET['id'] ?? $_POST['id'] ?? 0);
if ($id <= 0) {
    echo json_encode(['ok' => false, 'mensaje' => 'Falta indicar la plantilla']);
    exit;
}

$conn = new mysqli('localhost', 'admin_renacer', 'ikm169uhn', 'admin_renacer');
$conn->set_charset('utf8');

if ($conn->connect_error) {
    echo json_encode(['ok' => false, 'mensaje' => 'DB: ' . $conn->connect_error]);
    exit;
}

$conn->query("UPDATE planilla_periodo_plantillas SET estado = '0' WHERE id_plantilla = $id");
$conn->close();

echo json_encode(['ok' => true]);
