<?php
session_start();

include('sdba/sdba.php'); // include main file

$respuestaOk = false;
$mensajeError = 'hasta aca bien';
//$usuario = $_SESSION['id_usr'];

//if (isset($_POST) && !empty($_POST)) {
	$id = $_POST['id'];
	$cliente = $_POST['cliente'];
	$documento = $_POST['documento'];
	$telefono = $_POST['telefono'];
	$email = $_POST['email'];

	
	$respuestaOk = true;

	$data = array('cliente'=> $cliente,'doc_identidad'=>$documento,'telefono'=>$telefono,'email'=>$email);
	
	//guardamos en tabla ventas
		
		$ventas = Sdba::table('clientes');
		$ventas->where('id_cliente',$id);
		$ventas->update($data);

		

//}		

		$salidaJson = array('respuesta' => $respuestaOk,
							'mensaje' => $mensajeError);

		echo json_encode($salidaJson);


?>