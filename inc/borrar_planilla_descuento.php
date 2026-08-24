<?php
session_start();

include('sdba/sdba.php'); // include main file

$respuestaOk = false;
$mensajeError = 'hasta aca bien';

$id = intval($_GET['id'] ?? 0);

if ($id > 0) {
	$descuentos = Sdba::table('planilla_descuentos');
	$descuentos->where('id_descuento', $id);
	$descuentos->delete();

	$respuestaOk = true;
	$mensajeError = 'entro';
}

$salidaJson = array('respuesta' => $respuestaOk,
					'mensaje' => $mensajeError);

echo json_encode($salidaJson);

?>
