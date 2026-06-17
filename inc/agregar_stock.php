<?php
session_start();

include('sdba/sdba.php'); // include main file

$respuestaOk = false;
$mensajeError = 'hasta aca bien';

	$total = $_POST['total'];
	$producto = $_POST['producto'];
	$tipo = $_POST['tipo'];
	$fv = $_POST['fv'];
	if(empty($fv)){
		$fv = '0000-00-00';
	}
	$cantidad = $_POST['cantidad'];
	$motivo = 'A-'.$_POST['motivo'];
	$respuestaOk = true;
	$fecha = date("Y-m-d");

	$stock = Sdba::table('stock');
	$stock->where('producto',$producto);
	$stock->order_by('id_stock','desc');
	$st = $stock->get_one();
	$stockfv = $st['stockt'];

	if ($tipo == '1') {
		$stockf = $stockfv + $cantidad;
		$datas = array('id_stock'=>'','producto'=>$producto,'ingreso'=>$cantidad,'stock'=>$stockf,'motivo'=>$motivo,'fv'=>$fv,'stockt'=>$stockf,'fecha'=>$fecha);
	} else {
		$stockf = $stockfv - $cantidad;
		$datas = array('id_stock'=>'','producto'=>$producto,'egreso'=>$cantidad,'stock'=>$stockf,'motivo'=>$motivo,'fv'=>$fv,'stockt'=>$stockf,'fecha'=>$fecha);
	}

	$stockInsert = Sdba::table('stock');
	$stockInsert->insert($datas);

	$prod = Sdba::table('productos');
	$prod->where('id_producto', $producto);
	$prod->update(array('stockp' => $stockf));

		$salidaJson = array('respuesta' => $respuestaOk,
							'mensaje' => $mensajeError,
							'id' => $producto);

		echo json_encode($salidaJson);

?>
