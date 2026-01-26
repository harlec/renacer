

<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
 
 include('inc/sdba/sdba.php');
$fecha_hoy = new DateTime(date('Y-m-d'));
$fecha_hoy->modify('-1 month');

$anio = $fecha_hoy->format("Y");
$mes = $fecha_hoy->format("m");

$fecha_ini = $anio.'-'.$mes.'-31';
$fecha_fin = $anio.'-'.$mes.'-01';
$comprobantes = Sdba::table('comprobantes');
$comprobantes->where('fecha <=',$fecha_ini)->and_where('fecha >=',$fecha_fin);
$compol = $comprobantes->get();
//print_r($compol);

//creamos una instancia de ZipArchive
$zip = new ZipArchive();
 
//ruta donde guardar los archivos zip, la creamos sino existe
$rutaFinal = "archivos";
 
if(!file_exists($rutaFinal)){
  mkdir($rutaFinal);
}
 
//Asignamos el nombre del archivo zip
$archivoZip = 'cmp_ferre.zip';
 
//Creamos y abrimos el archivo zip
if ($zip->open($archivoZip, ZIPARCHIVE::CREATE) === true) {
 
  //Agregamos los archivos uno a uno
  //$zip->addFile("demo.pdf", "https://www.pse.pe/cpe/c86d3438-ae5c-448a-90e3-91096da8ae5f.pdf");
  //$zip->addFile("factura.pdf", "factura.pdf");
  //$zip->addFile("factura.pdf", "factura.pdf");
  //$zip->addFile("factura1.pdf", "factura1.pdf");
  foreach ($compol as $value) {
    $ruta = 'archivos/'.$value['serie'].$value['numero'].'.pdf';
    $nom = $value['serie'].$value['numero'].'.pdf';
    $zip->addFile($ruta, $nom);
  }
  
  //$zip->addFile("blog.kiuvox.com.txt", "blog.kiuvox.com.txt");
 
  //Cerramos el archivo zip
  $zip->close();
 
  //Muevo el archivo a una ruta
  //donde no se mezcle los zip con los demas archivos
  rename($archivoZip, "$rutaFinal/$archivoZip");
 
  //imrimimos un enlace para descargar el archivo zip
  echo "Descargar: <a href='$rutaFinal/$archivoZip'>$archivoZip</a>";
} else {
  echo 'Error creando ' . $archivoZip;
}
?>