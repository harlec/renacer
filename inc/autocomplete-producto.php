<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();

include('sdba/sdba.php'); // include main file
	
	$nombre = $_GET['term'];
	//$filtro = $_GET['filtro'];
	//$accion = $_GET['accion'];
	//$producto = $_GET['producto'];

	$clientes = Sdba::table('productos');
	//$productos->where('cliente', $filtro);
	//$clientes->distinct();
	$clientes->like('nom_prod', $nombre);
	//$clientes->distinct();
	$clientes_list = $clientes->get();

	foreach ($clientes_list as  $value) {

		$marca = Sdba::table('marca');
		$marca->where('id_marca',$value['marca']);
		//$marca->order_by('id_stock','desc');
		$marca1 = $marca->get_one();
		$marcan = $marca1['marca'];


		$variantes = Sdba::table('variantes');
		$variantes->where('producto', $value['id_producto']);
		$v = $variantes->get();
		foreach ($v as $key) {
			if ($key['variante']=='0000-00-00') {
				$lote = '-';
			}
			else{
				$lote = $key['variante'];
			}
			
			$cliente[] = $value['nom_prod'].'-'.$marcan.'-'.$lote;
		}

		//echo $key.'<br>';
		
		//$precio = $value['precio'];
		//echo $value['name_product'].'<br>';

	}

	


	
	echo json_encode($cliente);

?>