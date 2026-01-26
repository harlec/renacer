<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
$id_usuario = $_SESSION['id_usr']; 

include('sdba/sdba.php'); // include main file

$respuestaOk = false;
$mensajeError = 'hasta aca bien';
//$usuario = $_SESSION['id_usr'];

if (isset($_POST) && !empty($_POST)) {

	$fecha = $_POST['fecha'];
	$fecha_ope = date("Y-m-d H:i:s");
	$id_p = $_POST['id_pro'];
	//$plato= $_POST['plato'];
	$precio = $_POST['precio'];
	$cantidad = $_POST['cantidad'];
	//$monto = $_POST['monto'];
	$total = $_POST['total'];
	$total_pre = $_POST['total_pre'];
	$empleado = $_POST['vendedor'];
	$respuestaOk = true;
	//guardamos en tabla ventas

	if (!empty($fecha) && !empty($id_p) && !empty($total_pre)) {
			
			$ventas = Sdba::table('ventas');
			$data = array('id_venta'=>'','fecha'=> $fecha,'fecha_ope'=>$fecha_ope,'total'=>$total,'cliente'=>'1','usuario'=>$id_usuario,'estado'=>'0');
			$ventas->insert($data);
			$venta_id = $ventas->insert_id();
			if ($venta_id) {
				$respuestaOk = true;
				$mensajeError = 'entro';
			}

			$comision = 0;
			$acumulado = 0;
			//guardamos en tabla detalle de venta
			for ($i=0; $i < count($id_p) ; $i++) { 

				//calculamos la comision
				$productos = Sdba::table('productos');
				$productos->where('id_producto', $id_p[$i]); //agregado para corregir error de categorias
				$pl = $productos->get_one();

				if ($pl['marca']==1 && $total_pre[$i]>=50) {
					$comision = $comision + ($total_pre[$i]*0.03);
					$acumulado = $acumulado + $total_pre[$i];
				}
				elseif ($total_pre[$i]>=50) {
					$comision = $comision + ($total_pre[$i]*0.01);
					$acumulado = $acumulado + $total_pre[$i];
				}

				//guardamos el detalle de las ventas
				$dventas = Sdba::table('detalle_ventas');
				$ddata = array('id_detalle'=>'','venta'=>$venta_id,'producto'=>$id_p[$i],'cantidad'=>$cantidad[$i],'precio'=>$precio[$i],'total'=>$total_pre[$i],'estado'=>'0');
				$dventas->insert($ddata);
			}

			//calculamos la comision parte 2
			$sincomi = $total-$acumulado;
			if ($sincomi>=50) {
				$comision = $comision + ($sincomi*0.01);
			}

			//guardamos la comision
			$comica = Sdba::table('Puntos');
			$datac = array('id_punto'=>'','empleado'=>$empleado,'venta'=>$venta_id,'puntos'=>$comision,'fecha'=>$fecha,'estado'=>'0');
			$comica->insert($datac);

	}


		

}		

		$salidaJson = array('respuesta' => $respuestaOk,
							'mensaje' => $mensajeError,
							'venta_id' => $venta_id);

		echo json_encode($salidaJson);


?>