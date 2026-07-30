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

echo json_encode(array('ok' => true));
