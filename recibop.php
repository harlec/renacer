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
	$venta = Sdba::table('proforma'); // creating table object
	$venta->where('id_venta', $id);
	$venta_l = $venta->get_one();

	//obtenemos el nombre del cliente

	$cl = $venta_l['cliente'];
	$cliente = Sdba::table('clientes');
	$cliente->where('id_cliente', $cl);
	$cls = $cliente->get_one();
	$clsn = $cls['cliente'];



	$fechita = date("d-m-Y", strtotime($venta_l['fecha'])); 
	
	$ventas = Sdba::table('detalle_proforma'); // creating table object
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

		$tot = $tot + $key['total'];
		$mostrar_de_venta .= '<tr>
								<td>['.$key["cantidad"].']'.$key['nom_prod'].'</td>
								<td style="text-align: right;" >'.number_format($key['precio_venta'],2,'.',',').'</td>
								<td style="text-align: right;"  > '.number_format($key["total"],2,'.',',').'</td>
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
		font-size: 10px;
		font-weight: bold;
	}
	tbody td{
		font-size: 10px;
	}
	@page {
		margin-left: 0.4cm;
		margin-right: 0.4cm;
		margin-top: 0.4cm;
	}
</style>	<h5 style="text-align:center;"><b>PROFORMA</b>
			</h5>
			<h5 style="text-align:center;">Ferreteros y Constructores<br> "EL TORITO DE ORO"</h5>
			<h6 style="text-align:center;"><b>ENVIROMENTAL SENSE CONSULTING S.R.L. - ENSCO S.R.L.</b><br>
				Mz-A sublote-01 Urb San José - Espaldas del Grifo Repsol - Barranca<br>
				986362380 - 992770595 - 986165174 <br>
				RUC 20600064879
			</h6>
			<h6>FECHA: <?php echo $fechita; ?><br><?php echo $tipo; ?><br><?php echo $clsn; ?>	
			</h6>
			<hr>
		    <table>
		    	<thead>
		    		<tr>
		    			<th>[CANT.] DESCRIPCIÓN</th>
		    			<th style="text-align: right;">P/U</th>
		    			<th style="text-align: right;">TOTAL</th>
		    		</tr>
		    	</thead>
		    	<tbody>
						<?php echo $mostrar_de_venta; ?>

						<tr>
							<td style="text-align: right;" colspan="2" class="text-right" ><h4>TOTAL: S/</h4></td>
							<td style="padding-left:8px;text-align: right;" class=""><h4><?php echo number_format($tot,2,'.',','); ?></h4>
							</td>
						</tr>
						<tr>
							<td colspan="3"><B>IMPORTE EN LETRAS: </B><?php echo $letras;?></td>
						</tr>
		    	</tbody>
		    </table>
		    <h6 style="text-align:center;">GRACIAS X SU PREFERENCIA</h6>

<?php
use Dompdf\Dompdf;
$dompdf = new DOMPDF();
$dompdf->load_html(ob_get_clean());
$dompdf->set_paper(array(0,0,200,1000));
$dompdf->render();
$pdf = $dompdf->output();
$filename = "recibo.pdf";
file_put_contents($filename, $pdf);
$dompdf->stream($filename);
?>												    