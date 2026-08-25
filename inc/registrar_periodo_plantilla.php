<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['id_usr'])) {
    echo json_encode(['ok' => false, 'mensaje' => 'Sin sesión']);
    exit;
}

$nombre        = trim($_POST['nombre'] ?? '');
$dia_inicio    = (int) ($_POST['dia_inicio'] ?? 0);
$dia_fin_tipo  = ($_POST['dia_fin_tipo'] ?? '') === 'fin_mes' ? 'fin_mes' : 'fijo';
$dia_fin       = $dia_fin_tipo === 'fijo' ? (int) ($_POST['dia_fin'] ?? 0) : null;

if ($nombre === '' || $dia_inicio < 1 || $dia_inicio > 31) {
    echo json_encode(['ok' => false, 'mensaje' => 'Nombre o día de inicio inválido']);
    exit;
}
if ($dia_fin_tipo === 'fijo' && ($dia_fin < 1 || $dia_fin > 31)) {
    echo json_encode(['ok' => false, 'mensaje' => 'Día de fin inválido']);
    exit;
}

$conn = new mysqli('localhost', 'admin_renacer', 'ikm169uhn', 'admin_renacer');
$conn->set_charset('utf8');

if ($conn->connect_error) {
    echo json_encode(['ok' => false, 'mensaje' => 'DB: ' . $conn->connect_error]);
    exit;
}

$nombre_esc = $conn->real_escape_string($nombre);
$dia_fin_sql = $dia_fin === null ? 'NULL' : (int) $dia_fin;

$conn->query("INSERT INTO planilla_periodo_plantillas (nombre, dia_inicio, dia_fin_tipo, dia_fin, estado)
              VALUES ('$nombre_esc', $dia_inicio, '$dia_fin_tipo', $dia_fin_sql, '1')");

if ($conn->error) {
    echo json_encode(['ok' => false, 'mensaje' => 'No se pudo crear la plantilla: ' . $conn->error]);
    exit;
}

$conn->close();
echo json_encode(['ok' => true]);
