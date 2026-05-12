<?php
session_start();
if (!isset($_SESSION['id_usr'])) { echo '[]'; exit; }

$conn = new mysqli('localhost', 'admin_renacer', 'ikm169uhn', 'admin_renacer');
$conn->set_charset('utf8');

$term = '%' . $conn->real_escape_string($_GET['term'] ?? '') . '%';
$r = $conn->query("SELECT cliente FROM clientes WHERE cliente LIKE '$term' ORDER BY cliente LIMIT 10");
$data = [];
while ($row = $r->fetch_assoc()) {
    $data[] = $row['cliente'];
}
$conn->close();
echo json_encode($data);
