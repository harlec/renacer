<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();

echo "2";

include('sdba/sdba.php'); // include main file

echo "1";
for ($i=1000; $i < 1132 ; $i++) { 
	$variantes = Sdba::table('variantes');
	$variantes->where('producto',$i);
	$v = $variantes->get_one();
	if($v['id_variante']){
		echo "3";
	}
	else{
		$stock = Sdba::table('stock');
		$stock->where('producto',$i);
		$stock->order_by('id_stock','desc');
		$stockl = $stock->get_one();
		$cstock = $stockl['stockt'];
		$sfv = $stockl['fv'];

		$variacion = Sdba::table('variantes');
		$datava = array('id_variante'=>'','producto'=>$i,'variante'=>$sfv, 'stock'=>$cstock);
		$variacion->insert($datava);
	}
}

echo "muy bien";
?>