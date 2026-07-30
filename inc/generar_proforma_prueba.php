<?php
/**
 * Herramienta de QA: genera una proforma con datos aleatorios (cliente, productos,
 * cantidades) respetando las mismas reglas que registrar_proforma.php:
 * monto individual < S/700 y tope acumulado diario configurable.
 * Uso exclusivo de pruebas -- no reemplaza la captura real en ventap.php.
 */

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
if (!isset($_SESSION['type']) || $_SESSION['type'] !== 'admin') {
	echo json_encode(array('respuesta' => false, 'mensaje' => 'No autorizado', 'venta_id' => null));
	exit;
}
$id_usuario = $_SESSION['id_usr'];

include('sdba/sdba.php');
include('config_facturacion.php');

define('MONTO_MAXIMO_PROFORMA', 700);

$venta_id = null;

//elegimos un cliente al azar
$clientes = Sdba::table('clientes')->get();
if (empty($clientes)) {
	echo json_encode(array('respuesta' => false, 'mensaje' => 'No hay clientes registrados para generar la proforma de prueba.', 'venta_id' => null));
	exit;
}
$cliente = $clientes[array_rand($clientes)]['id_cliente'];

//elegimos productos con precio de venta valido
$productos_todos = Sdba::table('productos')->get();
$productos_validos = array();
foreach ($productos_todos as $p) {
	if ((float)$p['precio_venta'] > 0) {
		$productos_validos[] = $p;
	}
}
if (empty($productos_validos)) {
	echo json_encode(array('respuesta' => false, 'mensaje' => 'No hay productos con precio de venta para generar la proforma de prueba.', 'venta_id' => null));
	exit;
}
shuffle($productos_validos);

//armamos entre 1 y 3 items al azar sin superar el monto individual maximo
$detalle = array();
$total = 0;
foreach ($productos_validos as $p) {
	if (count($detalle) >= 3) break;

	$precio = (float) $p['precio_venta'];
	$margen = MONTO_MAXIMO_PROFORMA - 1 - $total;
	if ($margen < $precio) continue;

	$max_cant = min(3, (int) floor($margen / $precio));
	if ($max_cant < 1) continue;

	$cant = rand(1, $max_cant);
	$sub = round($precio * $cant, 2);

	$variantes = Sdba::table('variantes');
	$variantes->where('producto', $p['id_producto']);
	$vs = $variantes->get();
	$fv = !empty($vs) ? $vs[array_rand($vs)]['variante'] : '0000-00-00';

	$detalle[] = array(
		'producto' => $p['id_producto'],
		'cantidad' => $cant,
		'precio' => $precio,
		'total' => $sub,
		'fv' => $fv,
	);
	$total = round($total + $sub, 2);
}

if (empty($detalle)) {
	echo json_encode(array('respuesta' => false, 'mensaje' => 'No se pudo generar una proforma de prueba dentro del límite de S/ ' . number_format(MONTO_MAXIMO_PROFORMA, 2) . ' con los productos disponibles.', 'venta_id' => null));
	exit;
}

//validamos el acumulado diario contra el tope configurado (misma regla que registrar_proforma.php)
$tope_diario = (float) get_config('monto_maximo_proforma_diario', '0');
if ($tope_diario > 0) {
	$hoy = date('Y-m-d');
	$acumulado = Sdba::table('proforma');
	$acumulado->where('fecha_ope', $hoy . ' 00:00:00', false, false, 'AND', '>=');
	$acumulado->where('fecha_ope', $hoy . ' 23:59:59', false, false, 'AND', '<=');
	$acumulado->where('estado', '1', false, false, 'AND', '!=');
	$total_hoy = (float) $acumulado->sum('total');

	if ($total_hoy + $total > $tope_diario) {
		echo json_encode(array(
			'respuesta' => false,
			'mensaje' => 'Se alcanzó el monto máximo acumulado de proformas del día (S/ ' . number_format($tope_diario, 2) . '). Acumulado actual: S/ ' . number_format($total_hoy, 2),
			'venta_id' => null,
		));
		exit;
	}
}

//insertamos la proforma (mismo patron que registrar_proforma.php, sin afectar stock)
$fecha = date('Y-m-d');
$fecha_ope = date('Y-m-d H:i:s');

$ventas = Sdba::table('proforma');
$data = array('id_venta'=>'','fecha'=>$fecha,'fecha_ope'=>$fecha_ope,'total'=>$total,'cliente'=>$cliente,'usuario'=>$id_usuario,'tipo'=>'contado','estado'=>'0');
$ventas->insert($data);
$venta_id = $ventas->insert_id();

$respuestaOk = false;
$mensajeError = 'No se pudo registrar la proforma de prueba.';

if ($venta_id) {
	foreach ($detalle as $d) {
		$dventas = Sdba::table('detalle_proforma');
		$ddata = array('id_detalle'=>'','venta'=>$venta_id,'producto'=>$d['producto'],'cantidad'=>$d['cantidad'],'precio'=>$d['precio'],'total'=>$d['total'],'estado'=>'0');
		$dventas->insert($ddata);
	}
	$respuestaOk = true;
	$mensajeError = 'Proforma de prueba #' . $venta_id . ' generada por S/ ' . number_format($total, 2);
}

echo json_encode(array('respuesta' => $respuestaOk, 'mensaje' => $mensajeError, 'venta_id' => $venta_id, 'total' => $total));
