<?php
// Crea un periodo de balance con su caja inicial.
ob_start();
ini_set('display_errors', '0');
error_reporting(0);
session_start();
ob_clean();
header('Content-Type: application/json');

if (!isset($_SESSION['id_usr']) || ($_SESSION['type'] ?? '') !== 'admin') {
    echo json_encode(['ok' => false, 'mensaje' => 'Sin permiso']);
    exit;
}

$nombre = trim($_POST['nombre'] ?? '');
$ini    = $_POST['fecha_inicio'] ?? '';
$fin    = $_POST['fecha_fin'] ?? '';
include_once(__DIR__ . '/medios_pago.php');
$saldos = [];
foreach ($MEDIOS_PAGO as $k => $v) $saldos[$k] = round(floatval($_POST['saldo'][$k] ?? 0), 2);
$caja   = round(array_sum($saldos), 2);
$usuario = intval($_SESSION['id_usr']);

if ($nombre === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $ini) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $fin)) {
    echo json_encode(['ok' => false, 'mensaje' => 'Datos inválidos']);
    exit;
}
if (strtotime($fin) < strtotime($ini)) {
    echo json_encode(['ok' => false, 'mensaje' => 'La fecha fin no puede ser anterior a la fecha inicio']);
    exit;
}

$conn = new mysqli('localhost', 'admin_renacer', 'ikm169uhn', 'admin_renacer');
$conn->set_charset('utf8');
if ($conn->connect_error) {
    echo json_encode(['ok' => false, 'mensaje' => 'DB: ' . $conn->connect_error]);
    exit;
}

$st = $conn->prepare("INSERT INTO caja_periodos (nombre, fecha_inicio, fecha_fin, caja_inicial, usuario, fecha_registro) VALUES (?,?,?,?,?,NOW())");
$st->bind_param('sssdi', $nombre, $ini, $fin, $caja, $usuario);
if ($st->execute()) {
    $idp = $conn->insert_id;
    foreach ($saldos as $k => $m) {
        if ($m != 0) {
            $s2 = $conn->prepare("INSERT INTO caja_periodo_saldos (id_cperiodo, metodo, monto) VALUES (?,?,?)");
            $s2->bind_param('isd', $idp, $k, $m);
            $s2->execute();
        }
    }
    echo json_encode(['ok' => true, 'id' => $idp]);
} else {
    echo json_encode(['ok' => false, 'mensaje' => 'No se pudo guardar: ' . $conn->error]);
}
$conn->close();
