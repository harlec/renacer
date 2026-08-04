<?php
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

$metodos_validos = ['efectivo', 'yape', 'plin', 'bbva', 'yape_susan', 'tarjeta'];

$id_pago = intval($_POST['id_pago'] ?? 0);
$metodo_nuevo = $_POST['metodo'] ?? '';
$usuario_id = intval($_SESSION['id_usr']);

if ($id_pago <= 0 || !in_array($metodo_nuevo, $metodos_validos, true)) {
    echo json_encode(['ok' => false, 'mensaje' => 'Datos inválidos']);
    exit;
}

$conn = new mysqli('localhost', 'admin_renacer', 'ikm169uhn', 'admin_renacer');
$conn->set_charset('utf8');

if ($conn->connect_error) {
    echo json_encode(['ok' => false, 'mensaje' => 'DB: ' . $conn->connect_error]);
    exit;
}

$r = $conn->query("SELECT id_pago, venta, metodo, monto FROM venta_pagos WHERE id_pago = $id_pago");
$actual = $r ? $r->fetch_assoc() : null;

if (!$actual) {
    echo json_encode(['ok' => false, 'mensaje' => 'El pago no existe']);
    exit;
}

if ($actual['metodo'] === $metodo_nuevo) {
    echo json_encode(['ok' => true]);
    exit;
}

$metodo_nuevo_esc = $conn->real_escape_string($metodo_nuevo);
$ok = $conn->query("UPDATE venta_pagos SET metodo = '$metodo_nuevo_esc' WHERE id_pago = $id_pago");

if (!$ok) {
    echo json_encode(['ok' => false, 'mensaje' => 'No se pudo actualizar: ' . $conn->error]);
    exit;
}

// Auditoría best-effort: si la tabla de logs no existe o falla, no debe bloquear
// la actualización del pago en sí, que ya quedó guardada arriba.
$observaciones = $conn->real_escape_string('Cambio de método de pago desde caja_pagos.php (venta v-' . $actual['venta'] . ')');
$datos_anteriores = $conn->real_escape_string(json_encode(['metodo' => $actual['metodo']]));
$datos_nuevos = $conn->real_escape_string(json_encode(['metodo' => $metodo_nuevo]));
$fecha_log = date('Y-m-d H:i:s');
$ip = $conn->real_escape_string($_SERVER['REMOTE_ADDR'] ?? '');
$conn->query("INSERT INTO log_ediciones (tabla_afectada, id_registro, accion, usuario_id, fecha_edicion, datos_anteriores, datos_nuevos, ip_usuario, observaciones)
              VALUES ('venta_pagos', $id_pago, 'EDIT', $usuario_id, '$fecha_log', '$datos_anteriores', '$datos_nuevos', '$ip', '$observaciones')");

$conn->close();
echo json_encode(['ok' => true]);
