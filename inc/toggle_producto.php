<?php
// Alterna el estado de un producto entre activo ('1') e inactivo ('0'). Reemplaza al
// borrado suave de un solo sentido de borrar_producto.php: el producto nunca se elimina
// físicamente (no rompe el historial de ventas/compras), y ahora es reversible.
session_start();

include('sdba/sdba.php'); // include main file

$respuestaOk = false;
$mensajeError = 'hasta aca bien';
$estado_nuevo = null;

$id = intval($_GET['id'] ?? 0);

if ($id > 0) {
	$productos = Sdba::table('productos');
	$productos->where('id_producto', $id);
	$actual = $productos->get_one();

	if ($actual) {
		$estado_nuevo = ($actual['estado'] === '1') ? '0' : '1';

		$productos = Sdba::table('productos');
		$productos->where('id_producto', $id);
		$productos->update(array('estado' => $estado_nuevo));

		$respuestaOk = true;
		$mensajeError = $estado_nuevo === '1' ? 'producto activado' : 'producto desactivado';
	} else {
		$mensajeError = 'Producto no encontrado';
	}
} else {
	$mensajeError = 'Falta indicar el producto';
}

$salidaJson = array('respuesta' => $respuestaOk,
					'mensaje' => $mensajeError,
					'estado' => $estado_nuevo);

echo json_encode($salidaJson);

?>
