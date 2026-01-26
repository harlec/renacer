<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();

include('sdba/sdba.php'); // include main file

$respuestaOk = false;
$mensajeError = 'hast aca bien';

if (isset($_POST) && !empty($_POST)) {

		$usuario = $_POST['usuario'];
		$pwd = $_POST['pass'];
		$pepper = 'c1isvFdxMDdmjOlvxpecFw';
		$pass = hash_hmac("sha256", $pwd, $pepper);
		// $usuario = 'harlec';
		// $pass = 'ikm169uhn';

		$users = Sdba::table('usuarios');
		$users->where('usuario', $usuario)->and_where('clave =',$pass);
		$user_list = $users->get_one();
		//print_r($user_list);
		if ($user_list) {
			$respuestaOk = true;
			$mensajeError = 'Bienvenido '.$user_list['nombres'];
			$_SESSION['usuario']  = $usuario;
			$_SESSION['ingress']  = true;
			$_SESSION['nombres']  = $user_list['nombres'];
			$_SESSION['id_usr']  = $user_list['id_usuario'];
			$_SESSION['type']    = $user_list['rol'];
			$_SESSION['tienda']  = $user_list['tienda'];

			// if ($user_list['type_usr']=='mozo') {
			// 	$_SESSION['type'] = 'mozo';
			// }
			// elseif ($user_list['type_usr']=='cajero') {
			// 	$_SESSION['type'] = 'cajero';
			// }
		}
		else{
			$respuestaOk = false;
			$mensajeError = 'No puede ingresar';
		}

}	

		$salidaJson = array('respuesta' => $respuestaOk,
							'mensaje' => $mensajeError);

		echo json_encode($salidaJson);


?>