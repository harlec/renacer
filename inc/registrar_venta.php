<?php

session_start();
$id_usuario = $_SESSION['id_usr']; 
//$tienda = $_SESSION['tienda'];

include('sdba/sdba.php'); // include main file

$respuestaOk = false;
$mensajeError = 'hasta aca bien';
//$usuario = $_SESSION['id_usr'];

if (isset($_POST) && !empty($_POST)) {

	$fecha = $_POST['fecha'];
	$cliente = $_POST['cliente'];
	$fecha_ope = date("Y-m-d H:i:s");
	$id_p = $_POST['id_pro'];
	$cantidad = $_POST['cantidad'];
	$precio = $_POST['precio'];
	$total_pre = $_POST['total_pre'];
	$total = $_POST['total1'];
	$id_vp = $_POST['id_vp'];
	
	$respuestaOk = true;
	//guardamos en tabla ventas

	$clientes = Sdba::table('clientes');
	$clientes->where('cliente',$cliente);
	$clientes_l = $clientes->get_one();
	if($clientes_l['id_cliente']){
		$id_cliente = $clientes_l['id_cliente'];
	}
	else{
		$data_cl = array('id_cliente'=>'','cliente'=>$cliente,'estado'=>'1');
		$clientes->insert($data_cl);
		$id_cliente = $clientes->insert_id();
	}



	if (!empty($fecha) && !empty($id_p) && !empty($total_pre)) {
			
			$ventas = Sdba::table('ventas');
			$data = array('id_venta'=>'','fecha'=> $fecha,'fecha_ope'=>$fecha_ope,'total'=>$total,'cliente'=>$id_cliente,'usuario'=>$id_usuario,'estado'=>'0');
			$ventas->insert($data);
			$venta_id = $ventas->insert_id();
			if ($venta_id) {
				$respuestaOk = true;
				$mensajeError = 'entro';
			
				//guardamos en tabla detalle de venta
				for ($i=0; $i < count($id_p) ; $i++) { 
					//guardamos el detalle de las ventas
					$dventas = Sdba::table('detalle_ventas');
					$ddata = array('id_detalle'=>'','venta'=>$venta_id,'producto'=>$id_p[$i],'id_vp'=>$id_vp[$i],'cantidad'=>$cantidad[$i],'precio'=>$precio[$i],'total'=>$total_pre[$i],'estado'=>'0');
					$dventas->insert($ddata);

					//actualizamos el stock total
					$stock = Sdba::table('stock');
					$stock->where('producto',$id_p[$i]);
					$stock->order_by('id_stock','desc');
					$stockl = $stock->get_one();
					$cstock = $stockl['stockt'];
					$stocktot = $cstock - $cantidad[$i];


					$motivo = 'v-'.$venta_id;
					$datas = array('id_stock'=>'','producto'=>$id_p[$i],'egreso'=>$cantidad[$i],'motivo'=>$motivo,'stock'=>$stocktot,'fv'=>'','stockt'=>$stocktot,'fecha'=>$fecha, 'estado'=>'0');
					$stock->insert($datas);

					//actualizamos la variacion
					
					$productos = Sdba::table('productos');
					$productos->where('id_producto',$id_p[$i]);
					$datava = array( 'stockp'=>$stocktot);
					$productos->update($datava);
				}
			}


	}


		

}		

		$salidaJson = array('respuesta' => $respuestaOk,
							'mensaje' => $mensajeError,
							'venta_id' => $venta_id);

		echo json_encode($salidaJson);


?>