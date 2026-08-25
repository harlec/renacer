<?php
session_start();

include('sdba/sdba.php'); // include main file

$respuestaOk = false;
$mensajeError = 'hasta aca bien';

$id = intval($_GET['id'] ?? 0);

if ($id > 0) {
	$chk = Sdba::table('planilla_descuentos');
	$chk->where('id_descuento', $id);
	$chk->left_join('id_detalle', 'planilla_detalle', 'id_detalle');
	$chk->left_join('id_periodo', 'planilla_periodos', 'id_periodo', 'planilla_detalle');
	$fila = $chk->get_one();

	if (!$fila) {
		$mensajeError = 'El descuento no existe';
	} elseif ($fila['estado'] === 'cerrado') {
		$mensajeError = 'Esta planilla ya está cerrada';
	} else {
		$descuentos = Sdba::table('planilla_descuentos');
		$descuentos->where('id_descuento', $id);
		$descuentos->delete();

		$respuestaOk = true;
		$mensajeError = 'entro';
	}
}

$salidaJson = array('respuesta' => $respuestaOk,
					'mensaje' => $mensajeError);

echo json_encode($salidaJson);

?>
