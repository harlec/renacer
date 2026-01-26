<?php
session_start();

include('sdba/sdba.php'); // include main file

$respuestaOk = false;
$mensajeError = 'hasta aca bien';
//$usuario = $_SESSION['id_usr'];

//if (isset($_POST) && !empty($_POST)) {
	$cliente = $_POST['cliente'];
	$fecha = $_POST['fecha'];
	$cantidad = $_POST['cantidad'];
	$motivo = 'A-'.$_POST['motivo'];
	$respuestaOk = true;

	$abono = Sdba::table('abonos');
	$data = array('id_abono'=>'','cliente'=>$cliente,'monto'=>$cantidad,'motivo'=>$motivo,'fecha'=>$fecha,'estado'=>'0');
	
	$abono->insert($data);
			

//}		

		$salidaJson = array('respuesta' => $respuestaOk,
							'mensaje' => $mensajeError,
							'id' =>$cliente);

		echo json_encode($salidaJson);


?>