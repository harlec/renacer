<?php
// Guarda (upsert) la asistencia diaria de uno o varios empleados: hora real de
// entrada/salida, calculando minutos de tardanza contra el horario programado
// del empleado y las horas trabajadas. Espejo de inc/registrar_pago_compra.php.
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
include('config_facturacion.php');
include('horario_helpers.php');

$conn = new mysqli('localhost', 'admin_renacer', 'ikm169uhn', 'admin_renacer');
$conn->set_charset('utf8');

if ($conn->connect_error) {
    echo json_encode(['ok' => false, 'mensaje' => 'DB: ' . $conn->connect_error]);
    exit;
}

$fecha = $_POST['fecha'] ?? '';
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) {
    echo json_encode(['ok' => false, 'mensaje' => 'Fecha inválida']);
    exit;
}

$ids        = $_POST['id_empleado'] ?? [];
$entradas   = $_POST['entrada'] ?? [];
$salidas    = $_POST['salida'] ?? [];
$faltos     = $_POST['falto'] ?? [];
$usuario_id = intval($_SESSION['id_usr']);
$guardados  = 0;

$descanso_ids = [];
$rd = $conn->query("SELECT id_empleado FROM empleado_descansos WHERE fecha = '$fecha'");
if ($rd) {
    while ($dr = $rd->fetch_assoc()) $descanso_ids[] = (int) $dr['id_empleado'];
}

foreach ($ids as $i => $id_empleado) {
    $id_empleado = intval($id_empleado);
    if ($id_empleado <= 0) continue;
    if (in_array($id_empleado, $descanso_ids, true)) continue; // día de descanso programado, no se marca falta ni asistencia

    $falto        = isset($faltos[$i]) && $faltos[$i] == '1';
    $entrada_real = trim($entradas[$i] ?? '');
    $salida_real  = trim($salidas[$i] ?? '');

    if (!$falto && !$entrada_real && !$salida_real) continue; // fila sin cambios

    list($prog_ingreso, $prog_salida) = obtener_horario_programado($conn, $id_empleado, $fecha);

    $minutos_tardanza = 0;
    $horas_trabajadas = null;
    $observacion = null;

    if ($falto) {
        $observacion  = 'FALTO';
        $entrada_real = '';
        $salida_real  = '';
    } else {
        if ($entrada_real) {
            if ($prog_ingreso) {
                $diff = (strtotime($entrada_real) - strtotime($prog_ingreso)) / 60;
                $minutos_tardanza = $diff > 0 ? (int) round($diff) : 0;
                $observacion = $minutos_tardanza > 0 ? 'RETARDADO' : 'PUNTUAL';
            } else {
                $observacion = 'PUNTUAL';
            }
        }
        if ($entrada_real && $salida_real) {
            $horas = (strtotime($salida_real) - strtotime($entrada_real)) / 3600;
            $horas_trabajadas = $horas > 0 ? round($horas, 2) : 0;
        }
    }

    $entrada_sql  = $entrada_real  ? "'" . $conn->real_escape_string($entrada_real)  . "'" : 'NULL';
    $salida_sql   = $salida_real   ? "'" . $conn->real_escape_string($salida_real)   . "'" : 'NULL';
    $prog_ing_sql = $prog_ingreso  ? "'" . $conn->real_escape_string($prog_ingreso)  . "'" : 'NULL';
    $prog_sal_sql = $prog_salida   ? "'" . $conn->real_escape_string($prog_salida)   . "'" : 'NULL';
    $horas_sql    = $horas_trabajadas !== null ? $horas_trabajadas : 'NULL';
    $obs_sql      = $observacion   ? "'" . $conn->real_escape_string($observacion)   . "'" : 'NULL';

    $q = "INSERT INTO asistencias
            (id_empleado, fecha, hora_entrada_prog, hora_entrada_real, hora_salida_prog, hora_salida_real, minutos_tardanza, horas_trabajadas, observacion, usuario)
          VALUES
            ($id_empleado, '$fecha', $prog_ing_sql, $entrada_sql, $prog_sal_sql, $salida_sql, $minutos_tardanza, $horas_sql, $obs_sql, $usuario_id)
          ON DUPLICATE KEY UPDATE
            hora_entrada_prog = VALUES(hora_entrada_prog),
            hora_entrada_real = VALUES(hora_entrada_real),
            hora_salida_prog  = VALUES(hora_salida_prog),
            hora_salida_real  = VALUES(hora_salida_real),
            minutos_tardanza  = VALUES(minutos_tardanza),
            horas_trabajadas  = VALUES(horas_trabajadas),
            observacion       = VALUES(observacion),
            usuario           = VALUES(usuario)";

    if ($conn->query($q)) {
        $guardados++;
    }
}

$conn->close();
echo json_encode(['ok' => true, 'guardados' => $guardados]);
