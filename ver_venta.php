<?php
include('inc/control.php');
include('inc/sdba/sdba.php'); // include main file

$id = $_GET['id'];
$ventas = Sdba::table('detalle_ventas'); // creating table object
$ventas->where('venta', $id);
$ventas->left_join('producto','productos','id_producto');
$ventas_list = $ventas->get(); 

//print_r($ventas_list);
$ocultar = '';
$ventas1 = Sdba::table('ventas');
$ventas1->where('id_venta', $id);
$ventas_list1 = $ventas1->get_one();
if ($ventas_list1['estado']=='1') {
	$ocultar = 'ocultar';
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
    			<td>'.$value['total'].'</td>
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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/limonte-sweetalert2/10.5.0/sweetalert2.min.css" integrity="sha512-YpZXdiMhuP3woCdvg0ou2UPj6l4KQUuf3gbMXTNMgtqTakMInX7h+64CTh+UIvYdA7ctBU2BAA/h4eEhoMEmsg==" crossorigin="anonymous" />
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
	        <?php menu('4'); ?>
	      </div>
	      <div class="submenu">
	      	<ul class="subtop-tabs">
	      		<li >
	      			<a href="venta.php">Registrar venta</a>
	      		</li>
	      		<li >
	      			<a href="ventas.php">Listar ventas</a>
	      		</li>
	      		<li>
	      			<a href="notas_venta.php">Facturar</a>
	      		</li>
	      		<li>
	      			<a href="comprobantes.php">Comprobantes</a>
	      		</li>
	      	</ul>
	      </div>
	    </nav>
		<div class="kbg">
			<div class="cuerpofull">
				<div class="titulo">
					<h3>Venta <?php echo $id; ?></h3>
				</div>
				<div class="container-fluid">
					<div class="row">
						<div class="col-md-12">
							<div class="kdashboard">
								<div class="row">
									<div class="col-md-6">
										<div class="panel panel-default pa">
											<div class="panel-body">
												<p><strong>Venta id: <?php echo $id; ?></strong></p>
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
											    <p class="text-right"><strong>Total: S/ <?php echo $tot; ?></strong></p>
											    <center>
											    	
												    <a target="_blank" class="btn btn-primary btn-lg <?php echo $ocultar;?>" href="recibo.php?id=<?php echo $id; ?>">Recibo</a>
												    <button type="button" class="btn btn-success btn-lg <?php echo $ocultar;?>" id="btn-ver-factura" data-href="factura.php?ids=<?php echo $id; ?>"><i class="fas fa-file-invoice-dollar"></i> Factura</button>
												    <button type="button" class="btn btn-danger btn-lg <?php echo $ocultar;?>" id="btn-ver-boleta" data-href="boleta.php?ids=<?php echo $id; ?>"><i class="fab fa-bitcoin"></i> Boleta</button>
												</center>
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
	<script src="assets/js/sweetalert2.all.min.js"></script>
	<script src="https://cdnjs.cloudflare.com/ajax/libs/limonte-sweetalert2/10.5.0/sweetalert2.min.js" integrity="sha512-V9JHp52ZkrbVVjJqNz/XXYMUOyUfzaGKEGrcD2Ual7n39+UR1yJK0numAHZqkhhGTAH/Klj0KUe4btAZXccw9w==" crossorigin="anonymous"></script>
	<script >
	// A $( document ).ready() block.
	$(document ).ready(function() {
	    console.log( "ready!" );

	    $('#btn-ver-factura, #btn-ver-boleta').on('click', function () {
	    	var url = $(this).data('href');
	    	Swal.fire({
	    	  title: 'Emitir comprobante',
	    	  text: 'Está a punto de emitir un comprobante de pago, esto tiene implicaciones legales.',
	    	  icon: 'warning',
	    	  showCancelButton: true,
	    	  confirmButtonColor: '#3085d6',
	    	  cancelButtonColor: '#d33',
	    	  confirmButtonText: 'Sí, continuar',
	    	  cancelButtonText: 'Cancelar'
	    	}).then((result) => {
	    	  if (result.isConfirmed) {
	    	    window.location.href = url;
	    	  }
	    	});
	    });

	});
		
	</script>
</body>
</html>