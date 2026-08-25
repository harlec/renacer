<?php
// Registra un adelanto en efectivo a un empleado, con su fecha real. Queda "pendiente"
// (id_detalle_aplicado = NULL) hasta que se genere un periodo de planilla que cubra esa
// fecha (ver inc/registrar_planilla_periodo.php), momento en el que se aplica
// automáticamente como descuento. El tipo 'abarrotes' NO se crea aquí: se genera desde
// inc/registrar_venta.php (venta real que descuenta stock), no por este formulario.
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

$tipos_validos = ['adelanto'];

$id_empleado = intval($_POST['id_empleado'] ?? 0);
$tipo        = in_array($_POST['tipo'] ?? '', $tipos_validos, true) ? $_POST['tipo'] : '';
$fecha       = $_POST['fecha'] ?? '';
$importe     = round(floatval($_POST['importe'] ?? 0), 2);
$descripcion = trim($_POST['descripcion'] ?? '');
$usuario_id  = intval($_SESSION['id_usr']);

if ($id_empleado <= 0 || !$tipo || $importe <= 0 || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) {
    echo json_encode(['ok' => false, 'mensaje' => 'Datos inválidos']);
    exit;
}

$conn = new mysqli('localhost', 'admin_renacer', 'ikm169uhn', 'admin_renacer');
$conn->set_charset('utf8');

if ($conn->connect_error) {
    echo json_encode(['ok' => false, 'mensaje' => 'DB: ' . $conn->connect_error]);
    exit;
}

$tipo_esc = $conn->real_escape_string($tipo);
$fecha_esc = $conn->real_escape_string($fecha);
$desc_esc = $conn->real_escape_string($descripcion);

$ok = $conn->query("INSERT INTO movimientos_empleado (id_empleado, tipo, fecha, importe, descripcion, usuario)
                     VALUES ($id_empleado, '$tipo_esc', '$fecha_esc', $importe, '$desc_esc', $usuario_id)");
$conn->close();

if (!$ok) {
    echo json_encode(['ok' => false, 'mensaje' => 'No se pudo registrar el movimiento']);
    exit;
}

echo json_encode(['ok' => true]);
