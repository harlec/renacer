<?php
include('inc/control.php');
if ($_SESSION['type']=='operador') {
	header("Location: dashboard.php");
}

include('inc/sdba/sdba.php'); // include main file
$ventas = Sdba::table('productos');
$ventas->left_join('categoria','categorias','id_categoria'); // creating table object
$ventas_list = $ventas->get(); 

$datos = '';
$i = 1;
foreach ($ventas_list as $value) {

	$stockt = 0;
	$stock = Sdba::table('stock');
	$stock->where('producto',$value['id_producto']);
	$stock->order_by('id_stock','desc');
	$stock1 = $stock->get_one();
	$stocks .='<tr><td>Tienda 1</td><td>'.$stock1['stock'].'</td></tr>';
	$stockt = $stockt + $stock1['stock'];

	//rellenamos 
	if ($stock1['stock']=='') {
		$stock1f = 0;
	}
	else{
		$stock1f = $stock1['stock'];
	}

	if ($stock2['stock']=='') {
		$stock2f = 0;
	}
	else{
		$stock2f = $stock2['stock'];
	}

	if ($stock3['stock']=='') {
		$stock3f = 0;
	}
	else{
		$stock3f = $stock3['stock'];
	}

	$datos .='<tr><td>'.$value['id_producto'].'</td> 
    			<td style="text-transform:uppercase;">'.$value['nom_prod'].'</td>
    			<td>'.$value['nom_cat'].'</td>
    			<td>'.$value['serie'].'</td>
    			<td>'.$stockt.'</td> 
    		  </tr>';
    $i++;
}


?>


<!DOCTYPE html>
<html lang="es">
<head>
	<meta charset="UTF-8">
	<title>Sistema - Menu Principal</title>
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewpo