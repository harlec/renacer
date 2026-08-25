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

	$id=$_POST['id'];
	$dni = $_POST['dni'];
	$nombres = $_POST['nombres'];
	$apellidos = $_POST['apellidos'];
	$email = $_POST['email'];
	$celular = $_POST['celular'];
	$ubicacion = $_POST['ubicacion'];
	$direccion = $_POST['direccion'];
	$cargo = $_POST['cargo'];
	// Sdba::update() no soporta NULL, por eso '00:00:00' representa "sin horario definido".
	$hora_ingreso = $_POST['hora_ingreso'] ? $_POST['hora_ingreso'] : '00:00:00';
	$hora_salida = $_POST['hora_salida'] ? $_POST['hora_salida'] : '00:00:00';
	$hora_ingreso_sab = $_POST['hora_ingreso_sab'] ? $_POST['hora_ingreso_sab'] : '00:00:00';
	$hora_salida_sab = $_POST['hora_salida_sab'] ? $_POST['hora_salida_sab'] : '00:00:00';
	$hora_ingreso_dom = $_POST['hora_ingreso_dom'] ? $_POST['hora_ingreso_dom'] : '00:00:00';
	$hora_salida_dom = $_POST['hora_salida_dom'] ? $_POST['hora_salida_dom'] : '00:00:00';
	$sueldo_mensual = $_POST['sueldo_mensual'] ? $_POST['sueldo_mensual'] : 0;

			$ventas = Sdba::table('empleados');
			$ventas->where('id_empleado',$id);
			$data = array('dni'=>$dni,'nombres'=> $nombres,'apellidos'=>$apellidos,'email'=>$email,'celular'=>$celular,'direccion'=>$direccion,'ubicacion'=>$ubicacion,'cargo'=>$cargo,'hora_ingreso'=>$hora_ingreso,'hora_salida'=>$hora_salida,'hora_ingreso_sab'=>$hora_ingreso_sab,'hora_salida_sab'=>$hora_salida_sab,'hora_ingreso_dom'=>$hora_ingreso_dom,'hora_salida_dom'=>$hora_salida_dom,'sueldo_mensual'=>$sueldo_mensual,'estado'=>'1');
			$ventas->update($data);
			$venta_id = $id;
			if ($venta_id) {
				$respuestaOk = true;
				$mensajeError = 'entro';
			}
}		

		$salidaJson = array('respuesta' => $respuestaOk,
							'mensaje' => $mensajeError);

		echo json_encode($salidaJson);


?>