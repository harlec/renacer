<?php
include('inc/sdba/sdba.php'); // include main file
$ventas = Sdba::table('productos'); // creating table object
$ventas_list = $ventas->get(); 

$datos = '';
$i = 1;
foreach ($ventas_list as $value) {

	$datos .='<tr><td>'.$value['id_producto'].'</td> 
    			<td>'.$value['nom_prod'].'</td>
    			<td>'.$value['precio_venta'].'</td>  
    			<td><a class="" alt="ver" href="editar_producto.php?id='.$value['id_producto'].'"><img src="assets/img/edit.png"/></a><a class="" href="boleta.php?id='.$value['id_venta'].'" alt="boleta"><img src="assets/img/trash.png" /></a></td> 
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
    <link rel="stylesheet" type="text/css" href="//cdn.datatables.net/1.10.22/css/jquery.dataTables.min.css">
    </head>

<body class="mobile dashboard">
	<div class="container-fluid">
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
	        <div id="navbar" class="navbar-collapse collapse">
	          <ul class="nav navbar-nav">
	            <li ><a title="ventas" href="dashboard.php"><img class="isvg" src="assets/img/dashboard.svg"></a></li>
	            <li class=""><a title="Listar ventas" href="venta-diaria.php"><img class="isvg" src="assets/img/users.png"></a></li>
	            <li class="active"><a title="venta diaria" href="ver_pedidos_cocina.php"><img class="isvg" src="assets/img/products.png"></a></li>
	            <li ><a title="venta diaria" href="venta.php"><img class="isvg" src="assets/img/ventas.png"></a></li>
	            <li class=""><a title="reportes" href="reportes.php"><img class="isvg" src="assets/img/reports.png"></a></li>
	            <!-- <li class="dropdown">
	              <a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false">Dropdown <span class="caret"></span></a>
	              <ul class="dropdown-menu">
	                <li><a href="#">Action</a></li>
	                <li><a href="#">Another action</a></li>
	                <li><a href="#">Something else here</a></li>
	                <li role="separator" class="divider"></li>
	                <li class="dropdown-header">Nav header</li>
	                <li><a href="#">Separated link</a></li>
	                <li><a href="#">One more separated link</a></li>
	              </ul>
	            </li> -->
	          </ul>
	          <ul id="right-top">
	          	<li><a href="salir.php"><img class="isvg" src="assets/img/salir.png"></a></a></li>
	          </ul>
	        </div><!--/.nav-collapse -->
	      </div>
	    </nav>
		<div class="kbg">
			<div class="row">
				<div class="col-md-12">
					<div class="kdashboard">
						<div class="denlaces">
							<a class="enlace " href="venta.php">Registrar venta</a><a class="enlace active" href="ventas.php">Listar ventas</a>
						</div>
						<div class="row">
							<div class="col-md-6">
								<div class="panel panel-default pa">
									<div class="panel-body">
									    <table id="datos" class="table table-hover"> 
									    	<thead> 
									    		<tr>  
									    			<th>Id</th> 
									    			<th>Producto</th>
									    			<th>Precio</th> 
									    			<th>Opciones</th> 
									    		</tr> 
									    	</thead> 
									    	<tbody> 
									    		<?php echo $datos; ?>
									    	</tbody> 
									    </table>
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
	<script src="//cdn.datatables.net/1.10.22/js/jquery.dataTables.min.js"></script>
	<script >
	// A $( document ).ready() block.
	$(document ).ready(function() {
		$('#datos').DataTable();	
	    console.log( "ready!" );
	});
		
	</script>
</body>
</html>