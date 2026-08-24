<?php
// Crea un periodo de planilla (quincena de fechas libres) y genera automáticamente
// una línea de planilla_detalle por cada empleado activo, con el sueldo del periodo
// (sueldo_mensual/15 * dias del periodo) y, si hay tardanzas registradas en
// Asistencia dentro del rango, un descuento automático por tardanza.
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

$fecha_inicio = $_POST['fecha_inicio'] ?? '';
$fecha_fin    = $_POST['fecha_fin'] ?? '';
$usuario_id   = intval($_SESSION['id_usr']);

if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha_inicio) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha_fin)) {
    echo json_encode(['ok' => false, 'mensaje' => 'Fechas inválidas']);
    exit;
}
if (strtotime($fecha_fin) < strtotime($fecha_inicio)) {
    echo json_encode(['ok' => false, 'mensaje' => 'La fecha fin no puede ser anterior a la fecha inicio']);
    exit;
}

$dias = (int) round((strtotime($fecha_fin) - strtotime($fecha_inicio)) / 86400) + 1;

$conn = new mysqli('localhost', 'admin_renacer', 'ikm169uhn', 'admin_renacer');
$conn->set_charset('utf8');

if ($conn->connect_error) {
    echo json_encode(['ok' => false, 'mensaje' => 'DB: ' . $conn->connect_error]);
    exit;
}

$ini_esc = $conn->real_escape_string($fecha_inicio);
$fin_esc = $conn->real_escape_string($fecha_fin);

$conn->query("INSERT INTO planilla_periodos (fecha_inicio, fecha_fin, dias, estado, fecha_creacion) VALUES ('$ini_esc', '$fin_esc', $dias, 'abierto', NOW())");
if ($conn->error) {
    echo json_encode(['ok' => false, 'mensaje' => 'No se pudo crear el periodo: ' . $conn->error]);
    exit;
}
$id_periodo = $conn->insert_id;

$empleados = $conn->query("SELECT id_empleado, sueldo_mensual FROM empleados WHERE estado = '1'");
if ($empleados) {
    while ($emp = $empleados->fetch_assoc()) {
        $id_empleado    = (int) $emp['id_empleado'];
        $sueldo_mensual = round((float) $emp['sueldo_mensual'], 2);
        $calculo_diario = round($sueldo_mensual / 15, 2);
        $sueldo_periodo = round($calculo_diario * $dias, 2);

        $conn->query("INSERT INTO planilla_detalle (id_periodo, id_empleado, sueldo_mensual, calculo_diario, sueldo_periodo)
                       VALUES ($id_periodo, $id_empleado, $sueldo_mensual, $calculo_diario, $sueldo_periodo)");
        if ($conn->error) continue;
        $id_detalle = $conn->insert_id;

        $rt = $conn->query("SELECT COALESCE(SUM(minutos_tardanza),0) AS total_min FROM asistencias WHERE id_empleado = $id_empleado AND fecha BETWEEN '$ini_esc' AND '$fin_esc'");
        $total_min = $rt ? (int) $rt->fetch_assoc()['total_min'] : 0;

        if ($total_min > 0) {
            $tarifa_hora = $calculo_diario / 7;
            $importe = round(($total_min / 60) * $tarifa_hora, 2);
            $desc = "Tardanzas del periodo ({$total_min} min)";
            $conn->query("INSERT INTO planilla_descuentos (id_detalle, tipo, fecha, importe, descripcion, usuario)
                           VALUES ($id_detalle, 'tardanza', '$fin_esc', $importe, '" . $conn->real_escape_string($desc) . "', $usuario_id)");
        }
    }
}

$conn->close();
echo json_encode(['ok' => true, 'id_periodo' => $id_periodo]);
