<?php
session_start();

include('sdba/sdba.php'); // include main file

$respuestaOk = false;
$mensajeError = 'hasta aca bien';
//$usuario = $_SESSION['id_usr'];

//if (isset($_POST) && !empty($_POST)) {
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

	$variacion = Sdba::table('variantes');
	$variacion->where('producto',$producto)->and_where('variante',$fv);
	$vr = $variacion->get_one();
	$idvr = $vr['id_variante'];



	if ($tipo == '1') {
		$stockf = $stockfv + $cantidad;
		$stockft = $cantidad + $total;
		$datas = array('id_stock'=>'','producto'=>$producto,'ingreso'=>$cantidad,'stock'=>$stockf,'motivo'=>$motivo,'fv'=>$fv,'stockt'=>$stockf,'fecha'=>$fecha);
		$datava = array('id_variante'=>$idvr,'producto'=>$producto,'variante'=>$fv, 'stock'=>$stockf);
	}
	else{
		$stockf = $stockfv - $cantidad;
		$stockft = $total - $cantidad;
		$datas = array('id_stock'=>'','producto'=>$producto,'egreso'=>$cantidad,'stock'=>$stockf,'motivo'=>$motivo,'fv'=>$fv,'stockt'=>$stockf,'fecha'=>$fecha);
		$datava = array('id_variante'=>$idvr,'producto'=>$producto,'variante'=>$fv, 'stock'=>$stockf);
	}
	
				
	//guardamos en stock
	//$stock = Sdba::table('stock');
	$variacion->set($datava);
	$stock->insert($datas);
			
		

//}		

		$salidaJson = array('respuesta' => $respuestaOk,
							'mensaje' => $mensajeError,
							'id' =>$producto);

		echo json_encode($salidaJson);


?>