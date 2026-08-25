<?php
// Registra un adelanto en efectivo a un empleado, con su fecha real, dividido en una o
// varias cuotas (montos[]): cada cuota se descuenta en una planilla distinta, en el
// orden en que se vayan generando (ver inc/registrar_planilla_periodo.php), sin importar
// si cae dentro del rango de fechas de esa planilla o no salvo la primera. El tipo
// 'abarrotes' NO se crea aquí: se genera desde inc/registrar_venta.php.
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
$descripcion = trim($_POST['descripcion'] ?? '');
$usuario_id  = intval($_SESSION['id_usr']);

$montos_in = $_POST['montos'] ?? [];
if (!is_array($montos_in)) $montos_in = [$montos_in];
$montos = [];
foreach ($montos_in as $m) {
    $m = round(floatval($m), 2);
    if ($m > 0) $montos[] = $m;
}

if ($id_empleado <= 0 || !$tipo || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha) || count($montos) < 1) {
    echo json_encode(['ok' => false, 'mensaje' => 'Datos inválidos']);
    exit;
}
if (count($montos) > 24) {
    echo json_encode(['ok' => false, 'mensaje' => 'Demasiadas cuotas']);
    exit;
}

$importe = round(array_sum($montos), 2);
$partes  = count($montos);

$conn = new mysqli('localhost', 'admin_renacer', 'ikm169uhn', 'admin_renacer');
$conn->set_charset('utf8');

if ($conn->connect_error) {
    echo json_encode(['ok' => false, 'mensaje' => 'DB: ' . $conn->connect_error]);
    exit;
}

$tipo_esc = $conn->real_escape_string($tipo);
$fecha_esc = $conn->real_escape_string($fecha);
$desc_esc = $conn->real_escape_string($descripcion);

$ok = $conn->query("INSERT INTO movimientos_empleado (id_empleado, tipo, fecha, importe, descripcion, usuario, partes)
                     VALUES ($id_empleado, '$tipo_esc', '$fecha_esc', $importe, '$desc_esc', $usuario_id, $partes)");

if (!$ok) {
    $conn->close();
    echo json_encode(['ok' => false, 'mensaje' => 'No se pudo registrar el movimiento']);
    exit;
}

$id_movimiento = $conn->insert_id;
foreach ($montos as $i => $monto) {
    $numero_cuota = $i + 1;
    $conn->query("INSERT INTO movimiento_cuotas (id_movimiento, numero_cuota, monto)
                   VALUES ($id_movimiento, $numero_cuota, $monto)");
}

$conn->close();
echo json_encode(['ok' => true]);
