<?php

include('inc/control.php');
// if ($_SESSION['type']=='operador') {
// 	header("Location: dashboard.php");
// }

include('inc/sdba/sdba.php'); 

//recibimos las varibles
$fechaini = $_POST['fechaini'];
$fechafin = $_POST['fechafin'];
$producto = $_POST['producto'];

	//$fecha_hoy = new DateTime(date('2021-05-23'));
	$fecha_ini = new DateTime(date($fechaini));
	$fecha_fin = new DateTime(date($fechafin));
	//$fecha_hoy->modify('-1 day');


	$ventas1 = Sdba::table('ventas');
	$ventas1->where('fecha <=', $fecha_fin->format("Y-m-d"))->and_where('fecha >=', $fecha_ini->format("Y-m-d"));
	$max = $ventas1->max('id_venta');
	$min = $ventas1->min('id_venta');
	$total = $ventas1->sum('total');

	$totalc = 0;


	echo 'Max: '.$max.'<br>';
	echo 'Min: '.$min;
	
	$productos = Sdba::table('productos');
	//$productos->where('dia', $fecha_hoy);
	$productos_list = $productos->get();
	$product_t = array();
	

	foreach ($productos_list as $product ) {
		$id_product = $product['id_producto'];
		$name_product = $product['nom_prod'];

		$deta_list = Sdba::table('detalle_ventas');
		$deta_list->where('producto', $id_product)->and_where('venta >=',$min)->and_where('venta <=',$max)->and_where('estado !=','2');
		$deta_list_list = $deta_list->get();
		$cant_product = 0;

		foreach ($deta_list_list as $detalle) {
			$cant_product = $cant_product + $detalle['cantidad'];
			$prodct_t[$name_product] = $cant_product;
			$totalc += $detalle['cantidad']*$product['precio']*.65;
		}
	}

	arsort($prodct_t);
	$i=0;

	foreach ($prodct_t as $key => $value) {
		$resultado[$i] =$key;
		$valores[$i]=$value;
		$i++;
		if ($i == 5) break;
	}

?>

<!DOCTYPE html>
<html lang="es">
<head>
	<meta charset="UTF-8">
	<title>Sistema - Reporte ventas</title>
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" type="text/css" href="/assets/css/bootstrap.min.css">
    <link rel="stylesheet" type="text/css" href="/assets/css/custom.css">
    <link rel="stylesheet" href="/assets/css/jquery-ui.min.css">
    <script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>
    
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
	      		<li >
	      			<a href="reporte_compras.php">Reporte compras</a>
	      		</li>
	      		<li >
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
			<div class="row">
				<div class="col-md-12">
					<div class="kdashboard">
						<div class="row">
							<div class="col-md-12">
								<div class="panel panel-default pa">
									<div class="panel-body table-responsive">
										<div id="chart_div"></div>
										<div class="container-fluid">
										<div class="row">
											<div class="col-md-12">
												<br><br><br>
											</div>
										</div>
										<div class="row">
												<div class="col-md-10 col-md-offset-1">
													<canvas id="myChart"></canvas>
												</div>
											</div>
											

											<?php echo $total; ?>

											Total G = <?php echo $totalc; ?>
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
	<script src="/assets/js/jquery-ui.min.js"></script> 
	<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js"></script>
	<script src="https://cdn.jsdelivr.net/npm/jquery-validation@1.17.0/dist/jquery.validate.min.js"></script>
	<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.7.2/Chart.bundle.min.js"></script>
	<script >
	// A $( document ).ready() block.
	$(document ).ready(function() {

		var ctx = document.getElementById('myChart').getContext('2d');
						var chart = new Chart(ctx, {
						    // The type of chart we want to create
						    type: 'horizontalBar',

						    // The data for our dataset
						    data: {
						        labels: <?php echo json_encode($resultado); ?>,
						        datasets: [{
						            label: "ventas",
						            backgroundColor: 'rgb(66, 141, 241)',
						            borderColor: 'rgb(66, 141, 241)',
						            data: <?php echo json_encode($valores); ?>,
						        }]
						    },

						    // Configuration options go here
						    options: {
						        scales: {
						            yAxes: [{
						                ticks: {
						                    beginAtZero: true
						                }
						            }]
						        }
						    }
						});

	
	});
		
	</script>
</body>
</html>