<?php
include('inc/control.php');
if ($_SESSION['type']=='operador') {
	header("Location: dashboard.php");
}
include('inc/sdba/sdba.php'); 
$productos = Sdba::table('productos');
$productos->left_join('marca','marca','id_marca'); 
$productlist = $productos->get();
$lp = '';
foreach ($productlist as $value) {
	$lp .='<option value="'.$value['id_producto'].'">'.$value['nom_prod'].' '.$value['marca'].'</option>'; 
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
    <link rel="stylesheet" type="text/css" href="/assets/css/select2.min.css">
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
	        <?php menu('5'); ?>
	      </div>
	      <div class="submenu">
	      	<ul class="subtop-tabs">
	      		<li >
	      			<a href="reportes.php">Reporte Stock</a>
	      		</li>
	      		<li >
	      			<a href="reportes_vd.php">Ventas diarias</a>
	      		</li>
	      		<li >
	      			<a href="reporte_ventas.php">Reporte ventas</a>
	      		</li>
	      		<li>
	      			<a href="reporte_compras.php">Reporte compras</a>
	      		</li>
	      		<li>
	      			<a href="reporte_kardex.php">Reporte Kardex</a>
	      		</li>
	      		<li class="active">
	      			<a href="reporte_mv.php">Reporte Mas vendido</a>
	      		</li>
	      		<!-- <li >
	      			<a href="categorias.php">Categorías</a>
	      		</li>
	      		<li >
	      			<a href="marcas.php">Marcas</a>
	      		</li> -->
	      	</ul>
	      </div>
	    </nav>
		<div class="kbg">
			<div class="cuerpofull">
				<div class="titulo">
					<h3>Reporte Compras</h3>
				</div>
				<div class="container-fluid">
					<div class="row">
						<div class="col-md-12">
							<div class="kdashboard">
								<div class="row">
									<div class="col-md-12">
										<div class="panel panel-default pa">
											<div class="panel-body">
											    <h3 class="">Kardex</h3>
												<div class="row">
													<form action="reporte_mvk.php" method="post">
														<div class="col-md-2">
															<input type="date" class="form-control" name="fechaini" id="fechaini" value="" placeholder="Fecha Inicio">
														</div>
														<div class="col-md-2">
															<input type="date" class="form-control" name="fechafin" id="fechafin" value="" placeholder="Fecha Fin">
														</div>
														<div class="col-md-2">
															<button class="btn btn-success">Generar reporte</button>
														</div>
													</form>
													<br><br>
												</div>
												<br><br>											
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
	<script src="https://cdn.jsdelivr.net/npm/jquery-validation@1.17.0/dist/jquery.validate.min.js"></script>
	<script src="/assets/js/select2.full.min.js"></script>
	<script >
	// A $( document ).ready() block.
	$(document ).ready(function() {
		$('#producto').select2();
	    console.log( "ready!" );
	});
		
	</script>
</body>
</html>