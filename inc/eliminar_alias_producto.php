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
    $idAlias = (int)($_POST['id_alias'] ?? 0);
    if ($idAlias <= 0) {
        throw new Exception('Apodo no válido');
    }

    $stmt = $conn->prepare("DELETE FROM producto_alias WHERE id_alias = ?");
    $stmt->bind_param('i', $idAlias);
    $stmt->execute();
    $stmt->close();

    echo json_encode(['ok' => true]);
} catch (Exception $e) {
    echo json_encode(['ok' => false, 'mensaje' => $e->getMessage()]);
} finally {
    $conn->close();
}
