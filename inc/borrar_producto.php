<?php
// Borrado suave del producto (estado='0'), igual que proveedores/ventas/compras: nunca se
// elimina físicamente para no romper el historial de ventas/compras que ya lo referencian
// (y que antes fallaba en silencio por la restricción de llave foránea).
session_start();

include('sdba/sdba.php'); // include main file

$respuestaOk = false;
$mensajeError = 'hasta aca bien';

$id = intval($_GET['id'] ?? 0);

if ($id > 0) {
	$productos = Sdba::table('productos');
	$productos->where('id_producto', $id);
	$productos->update(array('estado' => '0'));

	$respuestaOk = true;
	$mensajeError = 'producto eliminado';
} else {
	$mensajeError = 'Falta indicar el producto';
}

$salidaJson = array('respuesta' => $respuestaOk,
					'mensaje' => $mensajeError);

echo json_encode($salidaJson);

?>
