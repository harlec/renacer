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
	$forma_pago = in_array($_POST['forma_pago'] ?? '', ['contado', 'credito'], true) ? $_POST['forma_pago'] : 'contado';
	$fecha_compromiso_pago = ($forma_pago === 'credito' && !empty($_POST['fecha_compromiso_pago'])) ? $_POST['fecha_compromiso_pago'] : null;
	$metodos_pago_validos = ['efectivo', 'transferencia', 'deposito', 'cheque', 'otro'];
	$metodo_pago = in_array($_POST['metodo_pago'] ?? '', $metodos_pago_validos, true) ? $_POST['metodo_pago'] : 'efectivo';
	//item
	$id_p = $_POST['id_pro'];
	$unidad= $_POST['unidad'];
	$precio = $_POST['precio'];
	$cantidad = $_POST['cantidad'];
	$id_vp_arr = isset($_POST['id_vp']) ? $_POST['id_vp'] : array();
	//$monto = $_POST['monto'];
	$total = $_POST['total'];
	$total_pre = $_POST['total_pre'];
	$respuestaOk = true;
	$venta_id = '';
	
	if (!empty($fecha) && !empty($id_p)) {
		
	
			
			$ventas = Sdba::table('compras');
			$data = array('id_compra'=>'','fecha'=> $fecha,'fecha_ingreso'=>$fecha_ingreso,'fecha_despacho'=>$fecha_despacho,'guia'=>$guia,'serie_f'=>$serie,'numero_f'=>$numero,'total'=>$total,'moneda'=>$moneda,'proveedor'=>$proveedor,'usuario'=>$id_usuario,'observacion'=>$observaciones,'exonerada'=>$exonerada,'estado'=>'0','forma_pago'=>$forma_pago,'fecha_compromiso_pago'=>$fecha_compromiso_pago);
			$ventas->insert($data);
			$venta_id = $ventas->insert_id();
			if ($venta_id) {
				$respuestaOk = true;
				$mensajeError = 'entro';

				// Una compra "al contado" se asume pagada en el momento: se registra de una
				// vez el abono por el total, para que no quede pendiente en Cuentas x pagar.
				if ($forma_pago === 'contado' && floatval($total) > 0) {
					$pago = Sdba::table('compra_pagos');
					$pago->insert(array('id_pago'=>'','compra'=>$venta_id,'monto'=>$total,'metodo'=>$metodo_pago,'usuario'=>$id_usuario,'fecha'=>date('Y-m-d H:i:s')));
				}
			}
			//guardamos en tabla detalle de compra
			for ($i=0; $i < count($id_p) ; $i++) { 
				if ($unidad[$i]=='TNE') {
					$cantidad1 = $cantidad[$i]*50;
				} elseif (!empty($id_vp_arr[$i])) {
					$vp_q = Sdba::table('variante_p');
					$vp_q->where('id_vp', intval($id_vp_arr[$i]));
					$vp_q_data = $vp_q->get_one();
					$cvp = floatval($vp_q_data['cantidad_vp']);
					$cantidad1 = $cvp > 0 ? $cantidad[$i] * $cvp : $cantidad[$i];
				} else {
					$cantidad1 = $cantidad[$i];
				}
				$dventas = Sdba::table('detalle_compras');
				$ddata = array('id_de_compra'=>'','compra'=>$venta_id,'producto'=>$id_p[$i],'cantidad'=>$cantidad1,'precio'=>$precio[$i],'total'=>$total_pre[$i],'estado'=>'0');
				$dventas->insert($ddata);

				$stock = Sdba::table('stock');
				$stock->where('producto',$id_p[$i]);
				$stock->order_by('id_stock','desc');
				$stockl = $stock->get_one();
				$cstock = isset($stockl['stockt']) ? (int)$stockl['stockt'] : 0;
				$stocktot = $cstock + $cantidad1;

				$motivo = 'c-'.$venta_id;
				$datas = array('id_stock'=>'','producto'=>$id_p[$i],'ingreso'=>$cantidad1,'motivo'=>$motivo,'stock'=>$stocktot,'fv'=>'','stockt'=>$stocktot,'fecha'=>$fecha, 'estado'=>'0');
				$stock->insert($datas);

				$productos = Sdba::table('productos');
				$productos->where('id_producto',$id_p[$i]);
				$productos->update(array('stockp'=>$stocktot));
				if (!empty($precio[$i])) {
					$prod2 = Sdba::table('productos');
					$prod2->where('id_producto', $id_p[$i]);
					$prod2->update(array('precio_compra' => floatval($precio[$i])));
				}
			}

			// Actualizar precioc_vp de variantes según la variante seleccionada
			for ($i = 0; $i < count($id_p); $i++) {
				if (!empty($id_vp_arr[$i])) {
					$id_vp_sel = intval($id_vp_arr[$i]);
					$vp_sel = Sdba::table('variante_p');
					$vp_sel->where('id_vp', $id_vp_sel);
					$vp_data = $vp_sel->get_one();
					$cantidad_vp_sel = floatval($vp_data['cantidad_vp']);
					if ($cantidad_vp_sel > 0) {
						$precio_sel = floatval($precio[$i]);
						$precio_unit = $precio_sel / $cantidad_vp_sel;
						// Actualizar la variante seleccionada
						$vp_upd = Sdba::table('variante_p');
						$vp_upd->where('id_vp', $id_vp_sel);
						$vp_upd->update(array('precioc_vp' => $precio_sel));
						// Recalcular precioc_vp de las demás variantes del mismo producto
						$vp_otros = Sdba::table('variante_p');
						$vp_otros->where('producto_vp', $id_p[$i]);
						$vp_otros_list = $vp_otros->get();
						foreach ($vp_otros_list as $vo) {
							if ($vo['id_vp'] != $id_vp_sel) {
								$vp_upd2 = Sdba::table('variante_p');
								$vp_upd2->where('id_vp', $vo['id_vp']);
								$vp_upd2->update(array('precioc_vp' => round($precio_unit * floatval($vo['cantidad_vp']), 2)));
							}
						}
					}
				}
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