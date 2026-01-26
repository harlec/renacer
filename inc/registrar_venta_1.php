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
	$tipo = $_POST['tipo'];
	$forma = $_POST['forma'];
	$fecha_ope = date("Y-m-d H:i:s");
	$id_p = $_POST['id_pro'];
	$fv= $_POST['fv'];
	$precio = $_POST['precio'];
	$cantidad = $_POST['cantidad'];
	//$monto = $_POST['monto'];
	$total = $_POST['total'];
	$total_pre = $_POST['total_pre'];
	$respuestaOk = true;
	//guardamos en tabla ventas

	if (!empty($fecha) && !empty($id_p) && !empty($total_pre)) {
			
			$ventas = Sdba::table('ventas');
			$data = array('id_venta'=>'','fecha'=> $fecha,'fecha_ope'=>$fecha_ope,'total'=>$total,'cliente'=>$cliente,'usuario'=>$id_usuario,'tipo'=>$tipo,'forma'=>$forma,'estado'=>'0');
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


				//guardamos el detalle de las ventas
				$dventas = Sdba::table('detalle_ventas');
				$ddata = array('id_detalle'=>'','venta'=>$venta_id,'producto'=>$id_p[$i],'cantidad'=>$cantidad[$i],'precio'=>$precio[$i],'total'=>$total_pre[$i],'estado'=>'0');
				$dventas->insert($ddata);

				//actualizamos el stock total
				$stock = Sdba::table('stock');
				$stock->where('producto',$id_p[$i]);
				$stock->order_by('id_stock','desc');
				$stockl = $stock->get_one();
				$cstock = $stockl['stockt'];
				$stocktot = $cstock - $cantidad[$i];

				//actualizamos el stock por lote
				$stock->reset();
				$stock->where('producto',$id_p[$i])->and_where('fv =',$fv[$i]);
				$stock->order_by('id_stock','desc');
				$stockl = $stock->get_one();
				$cstock = $stockl['stock'];
				$nstock = $cstock - $cantidad[$i];

				$motivo = 'v-'.$venta_id;
				$datas = array('id_stock'=>'','producto'=>$id_p[$i],'egreso'=>$cantidad[$i],'motivo'=>$motivo,'stock'=>$nstock,'fv'=>$fv[$i],'stockt'=>$stocktot,'fecha'=>$fecha, 'estado'=>'0');
				$stock->insert($datas);

				//actualizamos la variacion
				$variacion = Sdba::table('variantes');
				$variacion->where('producto',$id_p[$i])->and_where('variante',$fv[$i]);
				$vr = $variacion->get_one();
				$idvr = $vr['id_variante'];
				$datava = array('id_variante'=>$idvr,'producto'=>$id_p[$i],'variante'=>$fv[$i], 'stock'=>$nstock);
				$variacion->set($datava);
			}	

	}


		

}		

		$salidaJson = array('respuesta' => $respuestaOk,
							'mensaje' => $mensajeError,
							'venta_id' => $venta_id);

		echo json_encode($salidaJson);


?>