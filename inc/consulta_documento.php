<?php
session_start();
include('sdba/sdba.php');
include('config_facturacion.php');

header('Content-Type: application/json');

if ($_SESSION['ingress'] !== true) {
    echo json_encode(array('error' => 'No autorizado'));
    exit;
}

$tipo = $_REQUEST['tipo'] === 'ruc' ? 'ruc' : 'dni';
$numero = trim($_REQUEST['numero']);
$token = get_config('migo_token');

if ($numero === '' || $token === '') {
    echo json_encode(array('error' => 'Falta número de documento o token de Migo no configurado'));
    exit;
}

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'https://api.migo.pe/api/v1/' . $tipo);
curl_setopt($ch, CURLOPT_HTTPHEADER, array('Accept: application/json'));
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, array($tipo => $numero, 'token' => $token));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_TIMEOUT, 15);
$respuesta = curl_exec($ch);
curl_close($ch);

echo $respuesta;
