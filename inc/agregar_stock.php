<?php
session_start();

include('sdba/sdba.php');

$respuestaOk = false;
$mensajeError = 'Error al procesar';

try {
	$producto = isset($_POST['producto']) ? intval($_POST['producto']) : 0;
	$tipo     = isset($_POST['tipo'])     ? $_POST['tipo']             : '1';
	$cantidad = isset($_POST['cantidad']) ? floatval($_POST['cantidad']): 0;
	$motivo   = 'A-' . (isset($_POST['motivo']) && $_POST['motivo'] !== '' ? $_POST['motivo'] : 'ajuste');
	$fv       = (isset($_POST['fv']) && !empty($_POST['fv'])) ? $_POST['fv'] : '0000-00-00';
	$fecha    = date("Y-m-d");

	if (!$producto || $cantidad <= 0) {
		$mensajeError = 'Datos incompletos';
		echo json_encode(array('respuesta' => false, 'mensaje' => $mensajeError, 'id' => $producto));
		exit;
	}

	// Stock actual desde la tabla
	$stockQ = Sdba::table('stock');
	$stockQ->where('producto', $producto);
	$stockQ->order_by('id_stock', 'desc');
	$st = $stockQ->get_one();
	$stockfv = ($st && isset($st['stockt'])) ? floatval($st['stockt']) : 0;

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

	$respuestaOk = true;
	$mensajeError = 'ok';

} catch (Exception $e) {
	$mensajeError = $e->getMessage();
} catch (Error $e) {
	$mensajeError = $e->getMessage();
}

echo json_encode(array('respuesta' => $respuestaOk, 'mensaje' => $mensajeError, 'id' => $producto));
?>
