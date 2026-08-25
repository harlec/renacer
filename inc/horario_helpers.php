<?php
// Resuelve qué horario programado aplica a un empleado en una fecha dada: primero el
// override propio del empleado para ese tipo de día (lunes-viernes/sábado/domingo), y si
// no está definido (centinela '00:00:00', igual que hora_ingreso/hora_salida existentes),
// cae al horario general configurado en configuracion_planillas.php.
// Requiere que el llamador ya haya hecho include('sdba/sdba.php') e include('config_facturacion.php').

function tipo_dia_semana($fecha) {
    $n = (int) date('N', strtotime($fecha)); // 1=Lunes ... 7=Domingo
    if ($n >= 1 && $n <= 5) return 'lv';
    if ($n == 6) return 'sab';
    return 'dom';
}

function obtener_horario_programado($conn, $id_empleado, $fecha) {
    $tipo = tipo_dia_semana($fecha);
    $id_empleado = (int) $id_empleado;

    $r = $conn->query("SELECT hora_ingreso, hora_salida, hora_ingreso_sab, hora_salida_sab, hora_ingreso_dom, hora_salida_dom FROM empleados WHERE id_empleado = $id_empleado");
    $emp = $r ? $r->fetch_assoc() : null;
    if (!$emp) return [null, null];

    $col_ingreso = $tipo === 'lv' ? 'hora_ingreso' : ($tipo === 'sab' ? 'hora_ingreso_sab' : 'hora_ingreso_dom');
    $col_salida  = $tipo === 'lv' ? 'hora_salida'  : ($tipo === 'sab' ? 'hora_salida_sab'  : 'hora_salida_dom');

    $ingreso = ($emp[$col_ingreso] && $emp[$col_ingreso] !== '00:00:00') ? $emp[$col_ingreso] : null;
    $salida  = ($emp[$col_salida]  && $emp[$col_salida]  !== '00:00:00') ? $emp[$col_salida]  : null;

    if (!$ingreso) {
        $global_ingreso = get_config("planilla_horario_{$tipo}_ingreso");
        $ingreso = $global_ingreso !== '' ? $global_ingreso : null;
    }
    if (!$salida) {
        $global_salida = get_config("planilla_horario_{$tipo}_salida");
        $salida = $global_salida !== '' ? $global_salida : null;
    }

    return [$ingreso, $salida];
}
