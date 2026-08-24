<?php
// ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);
// error_reporting(E_ALL);
session_start();

include('sdba/sdba.php'); // include main file

$respuestaOk = false;
$mensajeError = 'hasta aca bien';
//$usuario = $_SESSION['id_usr'];

if (isset($_POST) && !empty($_POST)) {

	$dni = $_POST['dni'];
	$nombres = $_POST['nombres'];
	$apellidos = $_POST['apellidos'];
	$email = $_POST['email'];
	$celular = $_POST['celular'];
	$ubicacion = $_POST['ubicacion'];
	$direccion =$_POST['direccion'];
	$cargo = $_POST['cargo'];
	// Sdba::insert() no soporta NULL (siempre castea a string entre comillas),
	// por eso usamos '00:00:00' como "sin horario definido" en vez de NULL.
	$hora_ingreso = $_POST['hora_ingreso'] ? $_POST['hora_ingreso'] : '00:00:00';
	$hora_salida = $_POST['hora_salida'] ? $_POST['hora_salida'] : '00:00:00';
	$sueldo_mensual = $_POST['sueldo_mensual'] ? $_POST['sueldo_mensual'] : 0;

			$ventas = Sdba::table('empleados');
			$data = array('id_empleado'=>'','dni'=>$dni,'nombres'=> $nombres,'apellidos'=>$apellidos,'email'=>$email,'celular'=>$celular,'direccion'=>$direccion,'ubicacion'=>$ubicacion,'cargo'=>$cargo,'hora_ingreso'=>$hora_ingreso,'hora_salida'=>$hora_salida,'sueldo_mensual'=>$sueldo_mensual,'estado'=>'1');
			$ventas->insert($data);
			$venta_id = $ventas->insert_id();
			if ($venta_id) {
				$respuestaOk = true;
				$mensajeError = 'entro';
			}


		

}		

		$salidaJson = array('respuesta' => $respuestaOk,
							'mensaje' => $mensajeError);

		echo json_encode($salidaJson);


?>