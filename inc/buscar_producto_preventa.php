<?php
session_start();
require_once __DIR__ . '/preventa_control.php';
preventa_requerir_json();

header('Content-Type: application/json');

$conn = new mysqli('localhost', 'admin_renacer', 'ikm169uhn', 'admin_renacer');
$conn->set_charset('utf8');

$q = trim($_GET['q'] ?? '');
$rows = [];

if (mb_strlen($q) >= 2) {
    $like = '%' . $q . '%';
    $stmt = $conn->prepare("SELECT id_producto, nom_prod FROM productos WHERE estado = '1' AND nom_prod LIKE ? ORDER BY nom_prod LIMIT 20");
    $stmt->bind_param('s', $like);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) {
        $rows[] = $row;
    }
    $stmt->close();
}

$conn->close();
echo json_encode(['ok' => true, 'productos' => $rows]);
