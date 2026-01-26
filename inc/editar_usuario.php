<?php
session_start();

include('sdba/sdba.php'); // include main file

$respuestaOk = false;
$mensajeError = 'hasta aca bien';
//$usuario = $_SESSION['id_usr'];

//if (isset($_POST) && !empty($_POST)) {
	$id = $_POST['id'];
	$nombre = $_POST['nombre'];
	$usuario = $_POST['usuario'];
	$password = $_POST['password'];
	$rol = $_POST['rol'];
	$tienda = $_POST['tienda'];

	$pepper = 'c1isvFdxMDdmjOlvxpecFw';
	$pwd = $password;
	$pwd_peppered = hash_hmac("sha256", $pwd, $pepper);

	
	$respuestaOk = true;
	if (empty($password)) {
		$data = array('nombres'=> $nombre,'usuario'=>$usuario,'tienda'=>$tienda,'rol'=>$rol);
	}
	else{
		
		$data = array('nombres'=> $nombre,'usuario'=>$usuario,'clave'=>$pwd_peppered,'tienda'=>$tienda,'rol'=>$rol);
	}
	//guardamos en tabla ventas
		
		$ventas = Sdba::table('usuarios');
		$ventas->where('id_usuario',$id);
		$ventas->update($data);

		

//}		

		$salidaJson = array('respuesta' => $respuestaOk,
							'mensaje' => $mensajeError);

		echo json_encode($salidaJson);


?>