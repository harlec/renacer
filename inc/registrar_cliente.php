<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();

include('sdba/sdba.php'); // include main file

$respuestaOk = false;
$mensajeError = 'hasta aca bien';
//$usuario = $_SESSION['id_usr'];

//if (isset($_POST) && !empty($_POST)) {

	$cliente = $_POST['cliente'];
	$documento = $_POST['documento'];
	$telefono = $_POST['telefono'];
	$email = $_POST['email'];

	//verificar si usuario existe
	$usuarios = Sdba::table('clientes');
	$usuarios->where('doc_identidad', $documento);
	$usuario1 = $usuarios->get_one();
	if ($usuario1) {
		$respuestaOk = false;
		$mensajeError = 'Usuario tomado, Escoge otro usuario';
	}
	else{

		$respuestaOk = true;
		//guardamos en tabla ventas
			
			$ventas = Sdba::table('clientes');
			$data = array('id_cliente'=>'','cliente'=> $cliente,'doc_identidad'=>$documento,'telefono'=>$telefono,'email'=>$email,'estado'=>'1');
			$ventas->insert($data);
			$venta_id = $ventas->insert_id();
			if ($venta_id) {
				$respuestaOk = true;
				$mensajeError = 'entro';
			}
	}


		

//}		

		$salidaJson = array('respuesta' => $respuestaOk,
							'mensaje' => $mensajeError);

		echo json_encode($salidaJson);


?>