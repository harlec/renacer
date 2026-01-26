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

	$ruc = $_POST['ruc'];
	$denominacion = $_POST['denominacion'];
	$direccion = $_POST['direccion'];
	$accion = $_POST['accion'];
	$telefono = $_POST['telefono'];
	$email = $_POST['email'];
	$respuestaOk = true;
	//guardamos en tabla ventas

	if ($accion == 'nuevo') {
		$ventas = Sdba::table('proveedores');
		$data = array('id_proveedor'=>'','proveedor'=>$denominacion,'doc_identidad'=>$ruc,'direccion'=>$direccion,'telefono'=>$telefono,'email'=>$email,'estado'=>'1');
		$ventas->insert($data);
		$venta_id = $ventas->insert_id();	

	}
	else{

		$id = $_POST['id'];
		$vcatego = Sdba::table('proveedores');
		$vcatego->where('id_proveedor',$id);
		$data1 = array('proveedor'=>$denominacion,'doc_identidad'=>$ruc,'direccion'=>$direccion,'telefono'=>$telefono,'email'=>$email);
		$vcatego->update($data1);
		$venta_id = $id;
	}	

}		

		$salidaJson = array('respuesta' => $respuestaOk,
							'mensaje' => $mensajeError,
							'id' => $venta_id,
							'ruc' => $ruc,
							'denominacion' => $denominacion,
							'direccion' => $direccion,
							'accion' =>$accion);

		echo json_encode($salidaJson);


?>