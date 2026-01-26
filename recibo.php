<?php
ob_start();
require_once 'inc/dompdf/autoload.inc.php';
require 'inc/vendor/autoload.php';
use Luecano\NumeroALetras\NumeroALetras;

include('inc/control.php');
include('inc/sdba/sdba.php'); // include main file


$facturan = 0;
	$factura = $ventas = Sdba::table('configuracion');
	$factura->where('parametro','factura');
	//$factura->order_by('id_comprobante','desc');
	$factura_list = $factura->get_one();
	$facturan = $factura_list['valor'] + 1;

	$id = $_GET['id'];

	//obtenemos fecha de la venta
	$venta = Sdba::table('ventas'); // creating table object
	$venta->where('id_venta', $id);
	$venta->left_join('usuario','usuarios','id_usuario');
	$venta_l = $venta->get_one();

	//obtenemos el nombre del cliente

	$cl = $venta_l['cliente'];
	$vendedor = $venta_l['usuario'];
	$cliente = Sdba::table('clientes');
	$cliente->where('id_cliente', $cl);
	$cls = $cliente->get_one();
	$clsn = $cls['cliente'];



	$fechita = date("d-m-Y H:i:s", strtotime($venta_l['fecha_ope'])); 
	
	$ventas = Sdba::table('detalle_ventas'); // creating table object
	$ventas->where('venta', $id);
	$ventas->left_join('producto','productos','id_producto');
	$ventas_list = $ventas->get();
	if ($venta_l['tipo']=='1') {
		$tipo = 'Contado';
	}
	else{
		$tipo = 'Credito';
	}

	$i=1;
	$tot = 0;
	foreach ($ventas_list as $key ) {


		$id_unidad = $key['unidad_prod'];
		$unidad = Sdba::table('unidades');
		$unidad->where('id_unidad', $id_unidad);
		$unidad_same = $unidad->get_one();

		$unidad_p = $unidad_same['codigo'];
		$mostrar = $key["cantidad"];
		$mostrar_f = '['.$mostrar.']['.$unidad_same["nombre"].']';

		//
		$variantes = Sdba::table('variante_p');
		$variantes->where('id_vp',$key['id_vp']);
		$variantes->left_join('variante_vp','variantes','id_variante');
		$variantes_s = $variantes->get_one();
		$nombre_vp = $variantes_s['variante'];
		$cantidad_vp = $variantes_s['cantidad_vp'];

		if($mostrar == $cantidad_vp){
			$mostrar_f = '[1]['.$nombre_vp.']';
		}

		// if ($id_unidad=='5') {
		// 	function decimalAFraction($decimal) {
		// 	    // Separa la parte entera y decimal
		// 	    $partes = explode('.', $decimal);
		// 	    $entero = $partes[0]; // No se usa directamente para simplificar, pero puede ser útil
		// 	    $decimales = isset($partes[1]) ? $partes[1] : '';

		// 	    // Calcula el numerador
		// 	    $numerador = (int)$decimales;

		// 	    // Calcula el denominador
		// 	    $longitudDecimales = strlen($decimales);
		// 	    $denominador = pow(10, $longitudDecimales);

		// 	    // Si hay parte entera (no es un número entre 0 y 1), crea una fracción mixta
		// 	    if ($entero > 0) {
		// 	        $numerador += $entero * $denominador;
		// 	    }

		// 	    // Simplifica la fracción
		// 	    $mcd = mcd($numerador, $denominador);
		// 	    $numeradorFinal = $numerador / $mcd;
		// 	    $denominadorFinal = $denominador / $mcd;

		// 	    return $numeradorFinal . '/' . $denominadorFinal;
		// 	}

		// 	// Función para calcular el Máximo Común Divisor (MCD)
		// 	function mcd($a, $b) {
		// 	    if ($b == 0) {
		// 	        return $a;
		// 	    }
		// 	    return mcd($b, $a % $b);
		// 	}

		// 	// Ejemplo de uso
		// 	$numeroDecimal = $key["cantidad"];
		// 	$mostrar = decimalAFraction($numeroDecimal);
		// }

		$tot = $tot + $key['total'];
		$mostrar_de_venta .= '<tr>
								<td style="font-weight:bold;"> '.$mostrar_f.$key['nom_prod'].'</td>
								<td style="text-align: right; font-weight:bold;"  >'.number_format($key["total"],2,'.',',').'</td>
							</tr>';
		$i++;
	}

	$formatter = new NumeroALetras();
	$letras =  $formatter->toInvoice($tot, 2).' SOLES';

?>

<style>
	body{
		font-family: Helvetica, Sans-Serif;
	}
	thead th{
		font-size: 9px;
		font-weight: bold;
	}
	tbody td{
		font-size: 9px;
	}
	@page {
		margin-left: 0.4cm;
		margin-right: 0.4cm;
		margin-top: 0.4cm;
	}
</style>	
<img style="width:200px; text-align:center;" src="assets/img/logo_avasa.png">
<h5>“Y aunque tu principio haya sido pequeño,
Tu postrer estado será muy grande”<br>Job 8: 7</h5>
<h5 style="text-align:center;"><b>NOTA VENTA Nº <?= $id ?></b></h5>
			<h5 style="text-align:center;">Grupo "Avasa"<br>Distribuidora RENACER<br>Jr. Parchitea Mz. A lt. 09 - Santa</h5>
			<h6>FECHA: <?php echo $fechita; ?><br>CLIENTE: <?php echo $clsn; ?>	
			</h6>
			<hr>
		    <table>
		    	<thead>
		    		<tr>
		    			<th>[CANT.][UNID] DESCRIPCIÓN</th>
		    			<!-- <th style="text-align: right;">P/U</th> -->
		    			<th style="text-align: right;">TOTAL</th>
		    		</tr>
		    	</thead>
		    	<tbody>
						<?php echo $mostrar_de_venta; ?>

						<tr>
							<td style="text-align: right;" class="text-right" ><h4>TOTAL: S/</h4></td>
							<td style="padding-left:8px;text-align: right;" class=""><h4><?php echo number_format($tot,2,'.',','); ?></h4>
							</td>
						</tr>
						<tr>
							<td colspan="2"><B>IMPORTE EN LETRAS: </B><?php echo $letras;?></td>

						</tr>
						<tr>
							<td colspan="2"><B>VENDEDOR: </B><?php echo $vendedor;?></td>
							
						</tr>
						<tr>
							<td colspan="2"><B>PERSONAL ENTREGA: __________________________</td>
							
						</tr>
		    	</tbody>
		    </table>
		    <h6 style="text-align:center;">DIOS TE BENDIGA<br>
					GRACIAS POR TU PREFERENCIA<br>
					Todo reclamo deberá realizarse dentro de los
					13 días posteriores a la emisión de la boleta.</h6>

<?php
use Dompdf\Dompdf;
$dompdf = new DOMPDF();
$dompdf->load_html(ob_get_clean());
$dompdf->set_paper(array(0,0,200,1000));
$dompdf->render();
$pdf = $dompdf->output();
$filename = "recibo.pdf";
file_put_contents($filename, $pdf);
//$dompdf->stream($filename);
$dompdf->stream($filename, array("Attachment" => 0));

?>	

<script>
	window.print();
</script>	
