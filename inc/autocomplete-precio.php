<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();

include('sdba/sdba.php'); // include main file
	
	//$nombre = $_GET['term'];
	//$filtro = $_GET['filtro'];
	//$accion = $_GET['accion'];
	$productoli = explode('-', $_GET['producto']) ;
	$producto = $productoli[0];
	

	$productos = Sdba::table('productos');
	$productos->where('nom_prod', $producto);
	//$productos->like('name_product', $nombre);
	$productos_list = $productos->get();

	foreach ($productos_list as  $value) {
		//echo $key.'<br>';
		//$producto[] = $value['name_product'];
		$precio = $value['precio_venta'];
		$id_p = $value['id_producto'];
		//echo $value['name_product'].'<br>';

	}

	


	$salidaJson = array('precio' => $precio,
					    'id_p' => $id_p);

	echo json_encode($salidaJson);

?>