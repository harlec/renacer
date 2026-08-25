<?php
session_start();
include('sdba/sdba.php');
include('config_facturacion.php');

if ($_SESSION['type'] !== 'admin') {
    echo json_encode(array('ok' => false, 'mensaje' => 'No autorizado'));
    exit;
}

function normalizar_hora($valor) {
    $valor = trim((string) $valor);
    return preg_match('/^\d{2}:\d{2}$/', $valor) ? $valor . ':00' : '';
}

set_config('planilla_horario_lv_ingreso', normalizar_hora($_POST['horario_lv_ingreso'] ?? ''));
set_config('planilla_horario_lv_salida', normalizar_hora($_POST['horario_lv_salida'] ?? ''));
set_config('planilla_horario_sab_ingreso', normalizar_hora($_POST['horario_sab_ingreso'] ?? ''));
set_config('planilla_horario_sab_salida', normalizar_hora($_POST['horario_sab_salida'] ?? ''));
set_config('planilla_horario_dom_ingreso', normalizar_hora($_POST['horario_dom_ingreso'] ?? ''));
set_config('planilla_horario_dom_salida', normalizar_hora($_POST['horario_dom_salida'] ?? ''));

$factor = (float) ($_POST['factor_tardanza'] ?? 2);
set_config('planilla_factor_tardanza', $factor > 0 ? $factor : 2);

$dias = (int) ($_POST['dias_mes_referencia'] ?? 30);
set_config('planilla_dias_mes_referencia', $dias > 0 ? $dias : 30);

echo json_encode(array('ok' => true));
