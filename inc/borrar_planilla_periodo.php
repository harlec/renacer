<?php
// Borra un periodo de planilla completo: sus descuentos, sus líneas de detalle por
// empleado, y el periodo en sí. Solo se permite mientras el periodo sigue 'abierto'
// (igual que inc/registrar_planilla_descuento.php bloquea agregar descuentos a un
// periodo 'cerrado').
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['id_usr'])) {
    echo json_encode(['respuesta' => false, 'mensaje' => 'Sin sesión']);
    exit;
}

$id_periodo = intval($_GET['id'] ?? 0);
if ($id_periodo <= 0) {
    echo json_encode(['respuesta' => false, 'mensaje' => 'Falta indicar el periodo']);
    exit;
}

$conn = new mysqli('localhost', 'admin_renacer', 'ikm169uhn', 'admin_renacer');
$conn->set_charset('utf8');

if ($conn->connect_error) {
    echo json_encode(['respuesta' => false, 'mensaje' => 'DB: ' . $conn->connect_error]);
    exit;
}

$r = $conn->query("SELECT estado FROM planilla_periodos WHERE id_periodo = $id_periodo");
$periodo = $r ? $r->fetch_assoc() : null;

if (!$periodo) {
    echo json_encode(['respuesta' => false, 'mensaje' => 'El periodo no existe']);
    exit;
}
if ($periodo['estado'] === 'cerrado') {
    echo json_encode(['respuesta' => false, 'mensaje' => 'Esta planilla ya está cerrada, no se puede borrar']);
    exit;
}

$conn->query("DELETE pdesc FROM planilla_descuentos pdesc
              INNER JOIN planilla_detalle pd ON pd.id_detalle = pdesc.id_detalle
              WHERE pd.id_periodo = $id_periodo");
$conn->query("DELETE FROM planilla_detalle WHERE id_periodo = $id_periodo");
$conn->query("DELETE FROM planilla_periodos WHERE id_periodo = $id_periodo");

$ok = !$conn->error;
$conn->close();

echo json_encode(['respuesta' => $ok, 'mensaje' => $ok ? 'entro' : 'No se pudo borrar la planilla']);
