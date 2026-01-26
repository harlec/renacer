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

	$categoria = $_POST['categoria'];
	$accion = $_POST['accion'];
	$respuestaOk = true;
	//guardamos en tabla ventas

	if ($accion == 'nuevo') {
		$ventas = Sdba::table('marca');
			$data = array('id_marca'=>'','marca'=>$categoria,'estado'=>'1');
			$ventas->insert($data);
			$venta_id = $ventas->insert_id();	

	}
	else{

		$id = $_POST['id'];
		$vcatego = Sdba::table('marca');
		$vcatego->where('id_marca',$id);
		$data1 = array('marca' => $categoria);
		$vcatego->update($data1);
		$venta_id = $id;
	}


		

}		

		$salidaJson = array('respuesta' => $respuestaOk,
							'mensaje' => $mensajeError,
							'id' => $venta_id,
							'categoria' => $categoria,
							'accion' =>$accion);

		echo json_encode($salidaJson);


?>