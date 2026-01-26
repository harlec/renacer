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
	$id = $_POST['id'];
	$nombre = $_POST['nombre'];
	$precio_v = $_POST['precio_v'];
	//$precio_c = $_POST['precio_c'];
	$unidad = $_POST['unidad'];
	$categoria = $_POST['categoria'];
	//$marca = $_POST['marca'];
	//$color = $_POST['color'];
	//$serie = $_POST['serie'];
	// $exonerada = $_POST['exonerada'];
	// $codigo = $_POST['codigo'];
	$stockn = $_POST['stock'];
	$id_vp = $_POST['id_vp'];
	$variante = $_POST['variante'];
	$cantidadv = $_POST['cantidadv'];
	$preciov = $_POST['preciov'];
	$fecha = date("Y-m-d");

	$respuestaOk = true;
	//guardamos en tabla ventas
			
			$ventas = Sdba::table('productos');
			$ventas->where('id_producto', $id);
			$data = array('nom_prod'=>$nombre,'codigo_producto'=>'','precio_venta'=>$precio_v,'precio_compra'=>'','unidad_prod'=>$unidad,'categoria'=>$categoria,'stockp'=>$stockn);
			$ventas->update($data);

			//insertamos las variantes
			$variantes = Sdba::table('variante_p');
				for ($i=0; $i < count($variante ) ; $i++) { 
					$data2 = array('id_vp'=>$id_vp[$i],'producto_vp'=>$id,'variante_vp'=>$variante[$i],'cantidad_vp'=>$cantidadv[$i],'precio_vp'=>$preciov[$i],'state_vp'=>'1');
					$variantes->set($data2);
				}
				$respuestaOk = true;
				$mensajeError = 'entro';
				//guardamos en stock
				$stockQuery = Sdba::table('stock');
				$stockQuery->where('producto', $id);
				$stockQuery->order_by('id_stock', 'desc');
				$st = $stockQuery->get_one();
				$stockfv = $st['stockt'];

				// Verificamos el stock
				if($stockfv > $stockn){
					$diferencia = $stockfv - $stockn;
					$datas = array(
						'id_stock' => '',
						'producto' => $id,
						'egreso' => $diferencia,
						'stock' => $stockn,
						'motivo' => 'aj',
						'fv' => $fv,
						'stockt' => $stockn,
						'fecha' => $fecha
					);	
				}
				elseif($stockfv < $stockn){  // ✅ Corregido: $stockn en lugar de $sctockn
					$diferencia = $stockn - $stockfv;
					$datas = array(
						'id_stock' => '',
						'producto' => $id,
						'ingreso' => $diferencia,
						'stock' => $stockn,
						'motivo' => 'aj',
						'fv' => $fv,
						'stockt' => $stockn,
						'fecha' => $fecha
					);
				}

				// Insertar nuevo stock
				$stockInsert = Sdba::table('stock');
				$stockInsert->insert($datas);	


		

//}		

		$salidaJson = array('respuesta' => $respuestaOk,
							'mensaje' => $mensajeError);

		echo json_encode($salidaJson);


?>