<?php
// Guarda (reemplaza) los días de descanso programados de un empleado para un mes
// dado: borra los descansos existentes de ese empleado dentro del mes y vuelve a
// insertar los días seleccionados en el calendario. Espejo de inc/registrar_asistencia.php.
ob_start();
ini_set('display_errors', '0');
error_reporting(0);
session_start();
ob_clean();
header('Content-Type: application/json');

if (!isset($_SESSION['id_usr'])) {
    echo json_encode(['ok' => false, 'mensaje' => 'Sin sesión']);
    exit;
}

include('sdba/sdba.php');

$conn = new mysqli('localhost', 'admin_renacer', 'ikm169uhn', 'admin_renacer');
$conn->set_charset('utf8');

if ($conn->connect_error) {
    echo json_encode(['ok' => false, 'mensaje' => 'DB: ' . $conn->connect_error]);
    exit;
}

$id_empleado = intval($_POST['id_empleado'] ?? 0);
$mes         = $_POST['mes'] ?? '';
$dias        = $_POST['dias'] ?? [];
$usuario_id  = intval($_SESSION['id_usr']);

if ($id_empleado <= 0) {
    echo json_encode(['ok' => false, 'mensaje' => 'Colaborador inválido']);
    exit;
}
if (!preg_match('/^\d{4}-\d{2}$/', $mes)) {
    echo json_encode(['ok' => false, 'mensaje' => 'Mes inválido']);
    exit;
}
if (!is_array($dias)) {
    echo json_encode(['ok' => false, 'mensaje' => 'Datos inválidos']);
    exit;
}

list($anio, $mesNum) = explode('-', $mes);
$dias_en_mes = (int) date('t', strtotime("$anio-$mesNum-01"));
$fecha_ini = "$anio-$mesNum-01";
$fecha_fin = "$anio-$mesNum-" . str_pad((string)$dias_en_mes, 2, '0', STR_PAD_LEFT);

$conn->query("DELETE FROM empleado_descansos WHERE id_empleado = $id_empleado AND fecha BETWEEN '$fecha_ini' AND '$fecha_fin'");

$guardados = 0;
foreach ($dias as $d) {
    $d = (int) $d;
    if ($d < 1 || $d > $dias_en_mes) continue;
    $fecha = "$anio-$mesNum-" . str_pad((string)$d, 2, '0', STR_PAD_LEFT);
    $fecha_esc = $conn->real_escape_string($fecha);
    $conn->query("INSERT INTO empleado_descansos (id_empleado, fecha, usuario, fecha_registro) VALUES ($id_empleado, '$fecha_esc', $usuario_id, NOW())");
    if (!$conn->error) $guardados++;
}

$conn->close();
echo json_encode(['ok' => true, 'guardados' => $guardados]);
