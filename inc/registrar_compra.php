<?php
session_start();
$id_usuario = $_SESSION['id_usr']; 

include('sdba/sdba.php'); // include main file

$respuestaOk = false;
$mensajeError = 'hasta aca bien';
//$usuario = $_SESSION['id_usr'];

if (isset($_POST) && !empty($_POST)) {

	//datos generales
	$fecha_ingreso = $_POST['fecha_in'];
	$fecha_despacho = $_POST['fecha_des'];
	$fecha = date("Y-m-d");
	$proveedor = $_POST['proveedor'];
	$guia = $_POST['guia'];
	$serie = $_POST['serie'];
	$numero = $_POST['numero'];
	$moneda = $_POST['moneda'];
	$observaciones = $_POST['observaciones'];
	$exonerada = $_POST['exonerada'];
	//item
	$id_p = $_POST['id_pro'];
	$unidad= $_POST['unidad'];
	$fv = $_POST['fv'];
	$precio = $_POST['precio'];
	$cantidad = $_POST['cantidad'];
	//$monto = $_POST['monto'];
	$total = $_POST['total'];
	$total_pre = $_POST['total_pre'];
	$respuestaOk = true;
	$venta_id = '';
	
	if (!empty($fecha) && !empty($id_p)) {
		
	
			
			$ventas = Sdba::table('compras');
			$data = array('id_compra'=>'','fecha'=> $fecha,'fecha_ingreso'=>$fecha_ingreso,'fecha_despacho'=>$fecha_despacho,'guia'=>$guia,'serie_f'=>$serie,'numero_f'=>$numero,'total'=>$total,'moneda'=>$moneda,'proveedor'=>$proveedor,'usuario'=>$id_usuario,'observacion'=>$observaciones,'exonerada'=>$exonerada,'estado'=>'0');
			$ventas->insert($data);
			$venta_id = $ventas->insert_id();
			if ($venta_id) {
				$respuestaOk = true;
				$mensajeError = 'entro';
			}
			//guardamos en tabla detalle de compra
			for ($i=0; $i < count($id_p) ; $i++) { 
				if ($unidad[$i]=='TNE') {
					$cantidad1 = $cantidad[$i]*50;
				}
				else{
					$cantidad1 = $cantidad[$i];
				}
				$dventas = Sdba::table('detalle_compras');
				$ddata = array('id_de_compra'=>'','compra'=>$venta_id,'producto'=>$id_p[$i],'cantidad'=>$cantidad[$i],'precio'=>$precio[$i],'total'=>$total_pre[$i],'estado'=>'0');
				$dventas->insert($ddata);

				$stock = Sdba::table('stock');
				$stock->where('producto',$id_p[$i]);
				$stock->order_by('id_stock','desc');
				$stockt = $stock->get_one();
				$stocktot = $stockt['stockt'] + $cantidad1;

				if(empty($fv[$i])){
					$fv[$i] = '0000-00-00';
				}

				$variacion = Sdba::table('variantes');
				$variacion->where('producto',$id_p[$i])->and_where('variante',$fv[$i]);
				$vr = $variacion->get_one();
				$idvr = $vr['id_variante'];

				$stock->reset();
				$stock->where('producto',$id_p[$i])->and_where('fv =',$fv[$i]);
				$stock->order_by('id_stock','desc');
				$stockl = $stock->get_one();
				$cstock = $stockl['stock'];
				$nstock = $cstock + $cantidad1;
				$motivo = 'c-'.$venta_id;
				$datas = array('id_stock'=>'','producto'=>$id_p[$i],'ingreso'=>$cantidad1,'motivo'=>$motivo,'stock'=>$nstock,'fv'=>$fv[$i],'stockt'=>$stocktot,'fecha'=>$fecha, 'estado'=>'0');
				$stock->insert($datas);
				//agregamos variantes
				$datava = array('id_variante'=>$idvr,'producto'=>$id_p[$i],'variante'=>$fv[$i], 'stock'=>$nstock);
				$variacion->set($datava);
			}

	}
	else{
		$venta_id = 'Error';
		$mensajeError = 'Debe completar los campos de la venta';
	}


		

}		

		$salidaJson = array('respuesta' => $respuestaOk,
							'mensaje' => $mensajeError,
							'venta_id' => $venta_id);

		echo json_encode($salidaJson);


?>