<?php
session_start();
include('sdba/sdba.php');
include('config_facturacion.php');

if ($_SESSION['type'] !== 'admin') {
    echo json_encode(array('ok' => false, 'mensaje' => 'No autorizado'));
    exit;
}

set_config('nubefact_ruta', $_POST['nubefact_ruta']);
set_config('nubefact_token', $_POST['nubefact_token']);
set_config('nubefact_activo', $_POST['nubefact_activo'] == '1' ? '1' : '0');
set_config('migo_token', $_POST['migo_token']);
set_config('monto_maximo_proforma_diario', isset($_POST['monto_maximo_proforma_diario']) ? (float) $_POST['monto_maximo_proforma_diario'] : 0);

function normalizar_serie($valor, $default) {
    $valor = strtoupper(trim((string) $valor));
    $valor = substr($valor, 0, 4);
    return $valor !== '' ? $valor : $default;
}

set_config('serie_boleta', normalizar_serie($_POST['serie_boleta'] ?? '', 'BV03'));
set_config('serie_factura', normalizar_serie($_POST['serie_factura'] ?? '', 'F003'));
set_config('serie_nota_credito_boleta', normalizar_serie($_POST['serie_nota_credito_boleta'] ?? '', 'BC03'));
set_config('serie_nota_credito_factura', normalizar_serie($_POST['serie_nota_credito_factura'] ?? '', 'FC03'));

echo json_encode(array('ok' => true));
