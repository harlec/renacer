<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['id_usr'])) {
    echo json_encode(['ok' => false, 'mensaje' => 'Sin sesión']);
    exit;
}

$nombre = strtoupper(trim($_POST['nombre'] ?? ''));
if ($nombre === '') {
    echo json_encode(['ok' => false, 'mensaje' => 'Falta el nombre del cargo']);
    exit;
}

$conn = new mysqli('localhost', 'admin_renacer', 'ikm169uhn', 'admin_renacer');
$conn->set_charset('utf8');

if ($conn->connect_error) {
    echo json_encode(['ok' => false, 'mensaje' => 'DB: ' . $conn->connect_error]);
    exit;
}

$nombre_esc = $conn->real_escape_string($nombre);

$r = $conn->query("SELECT id_cargo FROM cargos WHERE UPPER(TRIM(nombre)) = '$nombre_esc'");
if ($r && $r->num_rows) {
    echo json_encode(['ok' => false, 'mensaje' => 'Ese cargo ya existe']);
    exit;
}

$ok = $conn->query("INSERT INTO cargos (nombre, estado) VALUES ('$nombre_esc', '1')");
$conn->close();

echo json_encode(['ok' => (bool)$ok, 'mensaje' => $ok ? '' : 'No se pudo crear el cargo']);
