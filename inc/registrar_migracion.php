<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
$id_usuario = $_SESSION['id_usr']; 
$tienda = $_SESSION['tienda'];

include('sdba/sdba.php'); // include main file

$respuestaOk = false;
$mensajeError = 'hasta aca bien';
//$usuario = $_SESSION['id_usr'];

if (isset($_POST) && !empty($_POST)) {

	$fecha = $_POST['fecha'];
	$destino = $_POST['destino'];

	$id_p = $_POST['id_pro'];
	$cantidad = $_POST['cantidad'];
	$origen = $_POST['origen'];
	$respuestaOk = true;
	//guardamos en tabla ventas

	if (!empty($fecha) && !empty($id_p) && !empty($destino)) {
			
			$ventas = Sdba::table('migracion');
			$data = array('id_migracion'=>'','fecha'=> $fecha,'destino'=>$destino,'estado'=>'0');
			$ventas->insert($data);
			$venta_id = $ventas->insert_id();
			if ($venta_id) {
				$respuestaOk = true;
				$mensajeError = 'entro';
			}
			//guardamos en tabla detalle de venta
			for ($i=0; $i < count($id_p) ; $i++) { 

				//calculamos la comision
				$productos = Sdba::table('productos');
				$productos->where('id_producto', $id_p[$i]); //agregado para corregir error de categorias
				$pl = $productos->get_one();


				//guardamos el detalle de las migraciones
				$dventas = Sdba::table('detalle_migracion');
				$ddata = array('id_detallem'=>'','migracion'=>$venta_id,'producto'=>$id_p[$i],'cantidad'=>$cantidad[$i],'origen'=>$origen[$i],'estado'=>'0');
				$dventas->insert($ddata);

				//stock
				$stock = Sdba::table('stock');
				$stock->where('producto',$id_p[$i])->and_where('tienda =',$origen[$i]);
				$stock->order_by('id_stock','desc');
				$stockl = $stock->get_one();
				$cstock = $stockl['stock'];
				$nstock = $cstock - $cantidad[$i];
				$motivo = 'mo-'.$venta_id;
				$datas = array('id_stock'=>'','producto'=>$id_p[$i],'egreso'=>$cantidad[$i],'motivo'=>$motivo,'stock'=>$nstock,'tienda'=>$origen[$i],'fecha'=>$fecha, 'estado'=>'0');
				$stock->insert($datas);

				//movemos
				$nstock = 0;
				$stock->reset();
				$stock->where('producto',$id_p[$i])->and_where('tienda =',$destino);
				$stock->order_by('id_stock','desc');
				$stockl = $stock->get_one();
				$cstock = $stockl['stock'];
				$nstock = $cstock + $cantidad[$i];
				$motivo = 'md-'.$venta_id;

				$datam = array('id_stock'=>'','producto'=>$id_p[$i],'ingreso'=>$cantidad[$i],'motivo'=>$motivo,'stock'=>$nstock,'tienda'=>$destino,'fecha'=>$fecha, 'estado'=>'0');

				$stock->insert($datam);

			}	

	}


		

}		

		$salidaJson = array('respuesta' => $respuestaOk,
							'mensaje' => $mensajeError,
							'venta_id' => $venta_id);

		echo json_encode($salidaJson);


?>