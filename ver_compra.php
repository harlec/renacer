<?php
include('inc/control.php');
include('inc/sdba/sdba.php'); // include main file

$id = $_GET['id'];
$ventas = Sdba::table('detalle_compras'); // creating table object
$ventas->where('compra', $id);
$ventas->left_join('producto','productos','id_producto');
$ventas_list = $ventas->get(); 

//print_r($ventas_list);
$ocultar = '';
$ventas1 = Sdba::table('compras');
$ventas1->where('id_compra', $id);
$ventas_list1 = $ventas1->get_one();
if ($ventas_list1['estado']=='1') {
	$ocultar = 'ocultar';
} 
$moneda = $ventas_list1['moneda'];
if ($moneda==0) {
	$mone = 'S/ ';
}
else{
	$mone = '$ ';
}


$datos = '';
$i = 1;
$tot = 0;
foreach ($ventas_list as $value) {

	$tot = $tot + $value['total'];

	$datos .='<tr> 
    			<th scope="row">'.$i.'</th> 
    			<td>'.$value['nom_prod'].'</td> 
    			<td>'.$value['cantidad'].'</td>
    			<td>'.$value['precio'].'</td> 
    			<td>'.round($value['total'],2).'</td>
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
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" type="text/css" href="/assets/css/bootstrap.min.css">
    <link rel="stylesheet" type="text/css" href="/assets/css/custom.css">
    <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.7.2/css/all.css" integrity="sha384-fnmOCqbTlWIlj8LyTjo7mOUStjsKC4pOpQbqyi7RrhN7udi9RwhKkMHpvLbHG9Sr" crossorigin="anonymous">
    </head>

<body class="mobile dashboard">
	<div class="">
		<nav class="navbar navbar-inverse navbar-fixed-top">
	      <div class="">
	        <div class="navbar-header">
	          <button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#navbar" aria-expanded="false" aria-controls="navbar">
	            <span class="sr-only">Toggle navigation</span>
	            <span class="icon-bar"></span>
	            <span class="icon-bar"></span>
	            <span class="icon-bar"></span>
	          </button>
	          <a class="navbar-brand" href="#"><img class="img-responsive logo" src="/assets/img/harlec-sistema.png"></a>
	        </div>
	        <?php menu('7'); ?>
	      </div>
	      <div class="submenu">
	      	<ul class="subtop-tabs">
	      		<li>
	      			<a href="compra.php">Registrar Compra</a>
	      		</li>
	      		<li >
	      			<a href="compras.php">Listar Compras</a>
	      		</li>
	      		<li >
	      			<a href="proveedores.php">Proveedores</a>
	      		</li>
	      	</ul>
	      </div>
	    </nav>
		<div class="kbg">
			<div class="cuerpofull">
				<div class="titulo">
					<h3>Registrar Compra</h3>
				</div>
				<div class="container-fluid">
					<div class="row">
						<div class="col-md-12">
							<div class="kdashboard">
								<div class="row">
									<div class="col-md-6">
										<div class="panel panel-default pa">
											<div class="panel-body">
												<p><strong>Compra id: <?php echo $id; ?></strong></p>
											    <table id="datos" class="table table-hover"> 
											    	<thead> 
											    		<tr> 
											    			<th>#</th> 
											    			<th>Nombre</th> 
											    			<th>Cantidad</th> 
											    			<th>Precio</th>
											    			<th>Total</th>  
											    		</tr> 
											    	</thead> 
											    	<tbody> 
											    		<?php echo $datos; ?>

											    	</tbody> 
											    </table>
											    <p class="text-right"><strong>Total: <?php echo $mone; ?> <?php echo $tot; ?></strong></p>
											    
											</div>
										</div>
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	 	<!-- Tab panes -->
		

	  
	<!-- jQuery (necessary for Bootstrap's JavaScript plugins) -->
	<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.3.1/jquery.min.js"></script>
	<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js"></script>
	<script >
	// A $( document ).ready() block.
	$(document ).ready(function() {	
	    console.log( "ready!" );
	});
		
	</script>
</body>
</html>