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
			
			$ventas = Sdba::table('empleados');
			$data = array('id_empleado'=>'','dni'=>$dni,'nombres'=> $nombres,'apellidos'=>$apellidos,'email'=>$email,'celular'=>$celular,'direccion'=>$direccion,'ubicacion'=>$ubicacion,'estado'=>'1');
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