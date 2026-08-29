<?php
// Crea un periodo de planilla y genera automáticamente una línea de planilla_detalle
// por cada empleado activo, con el sueldo del periodo (sueldo_mensual/dias_mes_referencia
// * dias del periodo) y, por cada día con tardanza registrada en Asistencia dentro del
// rango, un descuento automático usando la tarifa hora de ESE día (según su horario
// programado, que ya refleja si era lunes-viernes/sábado/domingo) y el factor de
// penalización configurable.
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

$dias_mes_referencia = (float) get_config('planilla_dias_mes_referencia', 30);
if ($dias_mes_referencia <= 0) $dias_mes_referencia = 30;
$factor_tardanza = (float) get_config('planilla_factor_tardanza', 2);
if ($factor_tardanza <= 0) $factor_tardanza = 2;

$empleados = $conn->query("SELECT id_empleado, sueldo_mensual, afp, afp_monto_mensual FROM empleados WHERE estado = '1'");
if ($empleados) {
    while ($emp = $empleados->fetch_assoc()) {
        $id_empleado    = (int) $emp['id_empleado'];
        $sueldo_mensual = round((float) $emp['sueldo_mensual'], 2);
        $calculo_diario = round($sueldo_mensual / $dias_mes_referencia, 2);
        $sueldo_periodo = round($calculo_diario * $dias, 2);

        $conn->query("INSERT INTO planilla_detalle (id_periodo, id_empleado, sueldo_mensual, calculo_diario, sueldo_periodo)
                       VALUES ($id_periodo, $id_empleado, $sueldo_mensual, $calculo_diario, $sueldo_periodo)");
        if ($conn->error) continue;
        $id_detalle = $conn->insert_id;

        $rt = $conn->query("SELECT fecha, minutos_tardanza, hora_entrada_prog, hora_salida_prog
                             FROM asistencias
                             WHERE id_empleado = $id_empleado AND fecha BETWEEN '$ini_esc' AND '$fin_esc' AND minutos_tardanza > 0");
        if ($rt) {
            while ($a = $rt->fetch_assoc()) {
                if (!$a['hora_entrada_prog'] || !$a['hora_salida_prog']) continue; // sin horario programado ese día, no se puede tarifar

                $horas_prog = (strtotime($a['hora_salida_prog']) - strtotime($a['hora_entrada_prog'])) / 3600;
                if ($horas_prog <= 0) continue;

                $tarifa_hora = $calculo_diario / $horas_prog;
                $minutos_tardanza = (int) $a['minutos_tardanza'];
                $minutos_efectivos = $minutos_tardanza * $factor_tardanza;
                $importe = round(($minutos_efectivos / 60) * $tarifa_hora, 2);
                if ($importe <= 0) continue;

                $desc = "Tardanza {$a['fecha']}: {$minutos_tardanza} min x{$factor_tardanza} = " . round($minutos_efectivos) . " min efectivos";
                $fecha_desc_esc = $conn->real_escape_string($a['fecha']);
                $conn->query("INSERT INTO planilla_descuentos (id_detalle, tipo, fecha, importe, descripcion, usuario)
                               VALUES ($id_detalle, 'tardanza', '$fecha_desc_esc', $importe, '" . $conn->real_escape_string($desc) . "', $usuario_id)");
            }
        }

        // AFP: si el colaborador está marcado con AFP y tiene un monto mensual configurado,
        // cada planilla que se genere aplica la mitad de ese monto (una mitad por quincena).
        if ($emp['afp'] == '1') {
            $afp_monto_mensual = round((float) $emp['afp_monto_mensual'], 2);
            if ($afp_monto_mensual > 0) {
                $importe_afp = round($afp_monto_mensual / 2, 2);
                $conn->query("INSERT INTO planilla_descuentos (id_detalle, tipo, fecha, importe, descripcion, usuario)
                               VALUES ($id_detalle, 'afp', '$fin_esc', $importe_afp, 'AFP (quincena)', $usuario_id)");
            }
        }

        // Adelantos y crédito de abarrotes: cada movimiento pendiente (con fecha hasta
        // el fin de este periodo) aporta como máximo UNA cuota por planilla generada,
        // en orden — así es como un movimiento en "3 partes" se reparte en las 3
        // próximas planillas que se generen, sin importar el rango exacto de fechas de
        // cada una (solo la primera cuota necesita que la fecha del movimiento caiga
        // dentro/antes del periodo).
        $rm = $conn->query("SELECT id_movimiento, tipo, descripcion, id_venta, partes
                             FROM movimientos_empleado
                             WHERE id_empleado = $id_empleado AND fecha <= '$fin_esc'
                             ORDER BY fecha ASC, id_movimiento ASC");
        if ($rm) {
            while ($m = $rm->fetch_assoc()) {
                $id_movimiento = (int) $m['id_movimiento'];
                $rc = $conn->query("SELECT id_cuota, numero_cuota, monto FROM movimiento_cuotas
                                     WHERE id_movimiento = $id_movimiento AND id_detalle_aplicado IS NULL
                                     ORDER BY numero_cuota ASC LIMIT 1");
                $cuota = $rc && $rc->num_rows ? $rc->fetch_assoc() : null;
                if (!$cuota) continue; // sin cuotas pendientes

                $monto_cuota = round((float) $cuota['monto'], 2);

                $tipo_esc = $conn->real_escape_string($m['tipo']);
                $desc_base = $m['descripcion'] ?: ucfirst($m['tipo']);
                $desc = (int) $m['partes'] > 1
                    ? $desc_base . " (cuota {$cuota['numero_cuota']}/{$m['partes']})"
                    : $desc_base;
                $desc_esc = $conn->real_escape_string($desc);

                if ($monto_cuota > 0) {
                    $conn->query("INSERT INTO planilla_descuentos (id_detalle, tipo, fecha, importe, descripcion, usuario)
                                   VALUES ($id_detalle, '$tipo_esc', '$fin_esc', $monto_cuota, '$desc_esc', $usuario_id)");

                    // Si el movimiento viene de una venta real (abarrotes), esta cuota
                    // se registra como un pago parcial de esa venta, para que deje de
                    // figurar (total o parcialmente) con saldo pendiente.
                    if (!empty($m['id_venta'])) {
                        $conn->query("INSERT INTO venta_pagos (venta, metodo, monto, usuario, fecha)
                                       VALUES (" . (int)$m['id_venta'] . ", 'planilla', $monto_cuota, $usuario_id, NOW())");
                    }
                }

                $conn->query("UPDATE movimiento_cuotas SET id_detalle_aplicado = $id_detalle WHERE id_cuota = " . (int)$cuota['id_cuota']);
            }
        }
    }
}

$conn->close();
echo json_encode(['ok' => true, 'id_periodo' => $id_periodo]);
