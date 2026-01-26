<h1>Se esta generando el ZIP espere un momento...</h1>
<?php

 

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include('inc/sdba/sdba.php');
$fecha_hoy = new DateTime(date('Y-m-d'));
$fecha_hoy->modify('-1 month');

$anio = $fecha_hoy->format("Y");
$mes = $fecha_hoy->format("m");

echo $mes;

$fecha_ini = $anio.'-'.$mes.'-31';
$fecha_fin = $anio.'-'.$mes.'-01';
$comprobantes = Sdba::table('comprobantes');
$comprobantes->where('fecha <=',$fecha_ini)->and_where('fecha >=',$fecha_fin);
//$ventas1->where('fecha <=', $fechafin)->and_where('fecha >=',$fechaini);
$compol = $comprobantes->get();

foreach ($compol as  $value) {

	$nom = 'archivos/'.$value['serie'].$value['numero'].'.pdf';
	$url= $value['url'].'.pdf';
	$ch = curl_init($url);
	$fp = fopen($nom, "w");

	curl_setopt($ch, CURLOPT_FILE, $fp);
	curl_setopt($ch, CURLOPT_HEADER, 0);

	curl_exec($ch);
	if(curl_error($ch)) {
	    fwrite($fp, curl_error($ch));
	}
	curl_close($ch);
	fclose($fp);
	}



echo "<center> <a href='https://ferreteria2.harlec.com.pe/descargar.php'><b>Descargar comprobantes</b></a></center>";
 
?>