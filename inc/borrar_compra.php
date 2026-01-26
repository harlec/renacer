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
		$fv = $value['fv'];

		$stock = Sdba::table('stock');
		$stock->where('producto',$producto);
		$stock->order_by('id_stock','desc');
		$stockl = $stock->get_one();
		$cstock = $stockl['stockt'];
		$nstockt = $cstock - $value['ingreso']; //stock total x producto
		$motivo = 'EC-'.$id;

		//obtenemos el stock del producto x lote
		$stock->reset();
		$stock->where('producto',$producto)->and_where('fv =',$fv);
		$stock->order_by('id_stock','desc');
		$stockl = $stock->get_one();
		$cstock = $stockl['stock'];
		$nstock = $cstock - $value['ingreso'];


		$datas = array('id_stock'=>'','producto'=>$producto,'egreso'=>$value['ingreso'],'motivo'=>$motivo,'stock'=>$nstock,'fv'=>$fv,'stockt'=>$nstockt,'fecha'=>$fecha, 'estado'=>'0');
		$stock->insert($datas);


		//actualizamos la variacion
		$variacion = Sdba::table('variantes');
		$variacion->where('producto',$producto)->and_where('variante',$fv);
		$vr = $variacion->get_one();
		$idvr = $vr['id_variante'];
		$datava = array('id_variante'=>$idvr,'producto'=>$producto,'variante'=>$fv, 'stock'=>$nstock);
		$variacion->set($datava);
		
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