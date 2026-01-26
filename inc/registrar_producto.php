<?php
// ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);
// error_reporting(E_ALL);
session_start();

include('sdba/sdba.php'); // include main file

$respuestaOk = false;
$mensajeError = 'hasta aca bien';
//$usuario = $_SESSION['id_usr'];

//if (isset($_POST) && !empty($_POST)) {

	$nombre = $_POST['nombre'];
	$precio_v = $_POST['precio_v'];
	//$precio_c = $_POST['precio_c'];
	$unidad = $_POST['unidad'];
	$categoria = $_POST['categoria'];
	//$tienda = $_POST['tienda'];
	$stockn = $_POST['stock'];
	//$exonerada = $_POST['exonerada'];
	//$fv = $_POST['fv'];
	//$marca = $_POST['marca'];
	//$codigo = $_POST['codigo'];
	//$color = $_POST['color'];
	$respuestaOk = true;
	$fecha = date("Y-m-d");

	$variante = $_POST['variante'];
	$cantidadv = $_POST['cantidadv'];
	$preciov = $_POST['preciov'];
	//guardamos en tabla ventas
			
			$ventas = Sdba::table('productos');
			$data = array('id_producto'=>'','cod_sunat'=> '','serie'=>'','nom_prod'=>$nombre,'codigo_producto'=>'','unidad_prod'=>$unidad,'categoria'=>$categoria,'precio_venta'=>$precio_v,'precio_compra'=>'','stockp'=>$stockn,'exonerada'=>'no','proveedor'=>'1','estado'=>'1');
			$ventas->insert($data);
			$venta_id = $ventas->insert_id();
			$variantes = Sdba::table('variante_p');
			if ($venta_id) {

				for ($i=0; $i < count($variante ) ; $i++) { 
					$data2 = array('id_vp'=>'','producto_vp'=>$venta_id,'variante_vp'=>$variante[$i],'cantidad_vp'=>$cantidadv[$i],'precio_vp'=>$preciov[$i],'state_vp'=>'1');
					$variantes->insert($data2);
				}
				$respuestaOk = true;
				$mensajeError = 'entro';
				//guardamos en stock

				$stock = Sdba::table('stock');
				$datas = array('id_stock'=>'','producto'=>$venta_id,'ingreso'=>$stockn,'stock'=>$stockn,'motivo'=>'si','fv'=>$fv,'stockt'=>$stockn,'fecha'=>$fecha);
				$stock->insert($datas);

				//variantes
				// $variantes = Sdba::table('variantes');
				// $datav = array('id_variante'=>'','producto'=>$venta_id,'variante'=>$fv,'stock'=>$stockn);
				// $variantes->insert($datav);
			}


		

//}		

		$salidaJson = array('respuesta' => $respuestaOk,
							'mensaje' => $mensajeError);

		echo json_encode($salidaJson);


?>