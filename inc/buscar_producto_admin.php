<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['ingress']) || $_SESSION['ingress'] !== true) {
    echo json_encode([]);
    exit;
}

$conn = new mysqli('localhost', 'admin_renacer', 'ikm169uhn', 'admin_renacer');
$conn->set_charset('utf8');

$q = '%' . $conn->real_escape_string($_GET['q'] ?? '') . '%';
$r = $conn->query("SELECT id_producto, nom_prod FROM productos WHERE estado = '1' AND nom_prod LIKE '$q' ORDER BY nom_prod LIMIT 20");
$data = [];
while ($row = $r->fetch_assoc()) {
    $data[] = ['id' => (int)$row['id_producto'], 'text' => $row['nom_prod']];
}
$conn->close();
echo json_encode($data);
