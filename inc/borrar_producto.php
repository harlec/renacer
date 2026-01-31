<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();

include('sdba/sdba.php'); // include main file

$respuestaOk = false;
$mensajeError = 'hasta aca bien';
//$usuario = $_SESSION['id_usr'];

	$id = $_GET['id'];
	$respuestaOk = true;

	// Eliminar variantes asociadas en variante_p para evitar variantes huérfanas
	$varp = Sdba::table('variante_p');
	$varp->where('id_producto', $id);
	$varp->delete();

	// Ahora eliminar el producto
	$ventas = Sdba::table('productos');
	$ventas->where('id_producto', $id);
	$ventas->delete();

	$respuestaOk = true;
	$mensajeError = 'producto y variantes (si existían) eliminados';

		$salidaJson = array('respuesta' => $respuestaOk,
							'mensaje' => $mensajeError);

		echo json_encode($salidaJson);


?>