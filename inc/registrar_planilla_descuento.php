<?php
// Agrega una línea de descuento (fecha + importe) a un empleado dentro de una
// planilla ya generada. Calco de inc/registrar_pago_compra.php.
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

$tipos_validos = ['tardanza', 'abarrotes', 'adelanto', 'falta', 'prestamo'];

$id_detalle  = intval($_POST['id_detalle'] ?? 0);
$tipo        = in_array($_POST['tipo'] ?? '', $tipos_validos, true) ? $_POST['tipo'] : '';
$fecha       = $_POST['fecha'] ?? '';
$importe     = round(floatval($_POST['importe'] ?? 0), 2);
$descripcion = trim($_POST['descripcion'] ?? '');
$usuario_id  = intval($_SESSION['id_usr']);

if ($id_detalle <= 0 || !$tipo || $importe <= 0 || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) {
    echo json_encode(['ok' => false, 'mensaje' => 'Datos inválidos']);
    exit;
}

$conn = new mysqli('localhost', 'admin_renacer', 'ikm169uhn', 'admin_renacer');
$conn->set_charset('utf8');

if ($conn->connect_error) {
    echo json_encode(['ok' => false, 'mensaje' => 'DB: ' . $conn->connect_error]);
    exit;
}

$r = $conn->query("
    SELECT pd.id_detalle, pp.estado
    FROM planilla_detalle pd
    INNER JOIN planilla_periodos pp ON pp.id_periodo = pd.id_periodo
    WHERE pd.id_detalle = $id_detalle
");
$detalle = $r ? $r->fetch_assoc() : null;

if (!$detalle) {
    echo json_encode(['ok' => false, 'mensaje' => 'El detalle de planilla no existe']);
    exit;
}
if ($detalle['estado'] == 'cerrado') {
    echo json_encode(['ok' => false, 'mensaje' => 'Esta planilla ya está cerrada']);
    exit;
}

$tipo_esc  = $conn->real_escape_string($tipo);
$fecha_esc = $conn->real_escape_string($fecha);
$desc_esc  = $conn->real_escape_string($descripcion);

$ok = $conn->query("INSERT INTO planilla_descuentos (id_detalle, tipo, fecha, importe, descripcion, usuario)
                     VALUES ($id_detalle, '$tipo_esc', '$fecha_esc', $importe, '$desc_esc', $usuario_id)");

$conn->close();

if (!$ok) {
    echo json_encode(['ok' => false, 'mensaje' => 'No se pudo registrar el descuento']);
    exit;
}

echo json_encode(['ok' => true]);
