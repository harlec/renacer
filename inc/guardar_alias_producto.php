<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['ingress']) || $_SESSION['ingress'] !== true || ($_SESSION['type'] ?? '') !== 'admin') {
    http_response_code(403);
    echo json_encode(['ok' => false, 'mensaje' => 'Sesión no válida']);
    exit;
}

$conn = new mysqli('localhost', 'admin_renacer', 'ikm169uhn', 'admin_renacer');
$conn->set_charset('utf8');

try {
    $alias = trim($_POST['alias'] ?? '');
    $idProducto = (int)($_POST['id_producto'] ?? 0);

    if ($alias === '') {
        throw new Exception('Escribe el apodo que usa el cliente');
    }
    if ($idProducto <= 0) {
        throw new Exception('Elige a qué producto del catálogo se refiere');
    }

    $r = $conn->query("SELECT id_producto FROM productos WHERE id_producto = $idProducto AND estado = '1' LIMIT 1");
    if (!$r || !$r->fetch_assoc()) {
        throw new Exception('Ese producto no existe o no está activo');
    }

    $stmt = $conn->prepare("INSERT INTO producto_alias (alias, id_producto) VALUES (?, ?)");
    $stmt->bind_param('si', $alias, $idProducto);
    $stmt->execute();
    $stmt->close();

    echo json_encode(['ok' => true]);
} catch (Exception $e) {
    echo json_encode(['ok' => false, 'mensaje' => $e->getMessage()]);
} finally {
    $conn->close();
}
