<?php
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

session_start();

include('sdba/sdba.php'); // include main file

$respuestaOk = false;
$mensajeError = 'hasta aca bien';
//$usuario = $_SESSION['id_usr'];

if (isset($_POST) && !empty($_POST)) {

	$variante= $_POST['variante'];
	$cantidad = $_POST['cantidad'];
	$accion = $_POST['accion'];

	$respuestaOk = true;
	//guardamos en tabla ventas

	if ($accion == 'nuevo') {
		$ventas = Sdba::table('variantes');
			$data = array('id_variante'=>'','variante'=>$variante,'cantidad'=>$cantidad,'estado'=>'1');
			$ventas->insert($data);
			$venta_id = $ventas->insert_id();	

	}
	else{

		$id = $_POST['id'];
		$vcatego = Sdba::table('variantes');
		$vcatego->where('id_variante',$id);
		$data1 = array('variante' => $variante, 'cantidad'=>$cantidad);
		$vcatego->update($data1);
		$venta_id = $id;
	}


		

}		

		$salidaJson = array('respuesta' => $respuestaOk,
							'mensaje' => $mensajeError,
							'id' => $venta_id,
							'variante' => $variante,
							'cantidad' => $cantidad,
							'accion' =>$accion);

		echo json_encode($salidaJson);


?>