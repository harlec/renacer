<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();

include('sdba/sdba.php'); // include main file

$respuestaOk = false;
$mensajeError = 'hasta aca bien';
//$usuario = $_SESSION['id_usr'];

//if (isset($_POST) && !empty($_POST)) {
	$id = $_GET['id'];
	$respuestaOk = true;
	$fecha = date("Y-m-d");

	$deventa = Sdba::table('stock');
	$deventa->where('motivo','c-'.$id);
	$vdl = $deventa->get();

	foreach ($vdl as $value) {
		$producto = $value['producto'];

		$stock = Sdba::table('stock');
		$stock->where('producto',$producto);
		$stock->order_by('id_stock','desc');
		$stockl = $stock->get_one();
		$cstock = isset($stockl['stockt']) ? (int)$stockl['stockt'] : 0;
		$nstockt = $cstock - $value['ingreso']; //stock total x producto
		$motivo = 'EC-'.$id;
		$datas = array('id_stock'=>'','producto'=>$producto,'egreso'=>$value['ingreso'],'motivo'=>$motivo,'stock'=>$nstockt,'fv'=>'','stockt'=>$nstockt,'fecha'=>$fecha, 'estado'=>'0');
		$stock->insert($datas);

		$productos = Sdba::table('productos');
		$productos->where('id_producto',$producto);
		$productos->update(array('stockp'=>$nstockt));
		
	}

	//regresamos el stock de la venta
	

			
			$compras = Sdba::table('compras');
			$compras->where('id_compra',$id);
			$data = array('estado'=>'2');
			$compras->update($data);
			
				$respuestaOk = true;
				$mensajeError = 'entro';


		

//}		

		$salidaJson = array('respuesta' => $respuestaOk,
							'mensaje' => $mensajeError);

		echo json_encode($salidaJson);


?>