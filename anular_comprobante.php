<?php
include('inc/control.php');
include('inc/sdba/sdba.php'); // include main file

	$id=$_GET['id'];
	$ventas1 = Sdba::table('comprobantes'); // creating table object
	$ventas1->where('id_comprobante',$id);
	$ventas_l1 = $ventas1->get_one();
	switch ($ventas_l1['tipo']) {
		case 'B':
			$comprobante = 'Boleta Electrónica '.$ventas_l1['serie'].'-'.$ventas_l1['numero'];
			$tipo = '2';
			break;
		case 'F':
			$comprobante = 'Factura Electrónica '.$ventas_l1['serie'].'-'.$ventas_l1['numero'];
			$tipo = '1';
			break;
	}
	$ocultar = '';
	$comunicado = '';
	if ($ventas_l1['state']=='1') {
		$ocultar = 'style="display:none;"';
		$comunicado = '<div class="alert alert-danger alert-dismissible" role="alert">
						  <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
						  <strong>Comprobante comunicado de baja</strong> 
						</div>';
	}
	if ($ventas_l1['state']=='2') {
		$ocultar = 'style="display:none;"';
		$comunicado = '<div class="alert alert-success alert-dismissible" role="alert">
						  <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
						  <strong>La comunicacion de baja ha sido aceptada</strong> 
						</div>';
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
    <link rel="stylesheet" type="text/css" href="//cdn.datatables.net/1.10.22/css/jquery.dataTables.min.css">
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
	      			<a class="" href="venta.php">Factura Electrónica</a>
	      		</li>
	      		<li >
	      			<a class="" href="ventab.php">Boleta Electrónica</a>
	      		</li>
	      		<li >
	      			<a class="" href="ventas.php">Comprobantes</a>
	      		</li>
	      	</ul>
	      </div>
	    </nav>

			<div class="cuerpo" style="padding-top:111px;">
				<div class="titulo">
					<h3>Comunicación de baja <?php echo $comprobante ?></h3>
				</div>
				<div class="container-fluid">	
					<div class="row">
						<div class="col-md-12">
							<div class="kdashboard">
								<div class="row">
									<div class="col-md-11">
										<div class="panel panel-default pa">
											<div class="panel-body text-center">
												<h2>Comunicación de baja</h2>
											    <h2><?php echo $comprobante; ?></h2>
											    <h3><?php echo $ventas_l1['nombre']; ?></h3>
											    <h3>TOTAL: S/ <?php echo $ventas_l1['total']; ?></h3>
											    <BR>
											    <div class="loader text-center" id="loading"></div>
											    <?php echo $comunicado; ?>
											    <form <?php echo $ocultar; ?> id="anulaa">
											    	<input type="hidden" name="id" value="<?php echo $id; ?>">
											    	<input type="hidden" name="serie" value="<?php echo $ventas_l1['serie']; ?>">
											    	<input type="hidden" name="numero" value="<?php echo $ventas_l1['numero']; ?>">
											    	<input type="hidden" name="tipo" value="<?php echo $tipo; ?>">
											    	<input class="form-control" name="motivo" type="text" placeholder="Ingrese motivo de eliminación" required><br>
											    	<button type="button" id="anular" class="btn btn-danger btn-block btn-lg">Anular o comunicar baja</button>
											    </form>
											</div>
										</div>
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
			<div style="padding-top:111px;" class="detalles">
				<div class="titulo">
					<h3>OTRAS OPCIONES</h3>
				</div>
					<BR>
						<div style="padding:20px;">
							<a class="btn btn-success btn-block btn-lg" href="venta.php">GENERAR OTRA FACTURA</a><br>
							<a class="btn btn-success btn-block btn-lg" href="ventab.php">GENERAR OTRA BOLETA</a><br>
							<a class="btn btn-success btn-block btn-lg" href="ventas.php">VER TODOS LOS COMPROBANTES</a><br>
					
						</div>
					
				</div>
			</div>
	 	<!-- Tab panes -->
		

	  
	<!-- jQuery (necessary for Bootstrap's JavaScript plugins) -->
	<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.3.1/jquery.min.js"></script>
	<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js"></script>
	<script src="assets/js/sweetalert2.all.min.js"></script>

	<script >
	// A $( document ).ready() block.
	$(document ).ready(function() {

		// Manejado directo por click (no por submit del <form>) para no depender del
		// plugin jquery.validate — si ese plugin no se engancha por cualquier motivo,
		// el botón haría un submit normal del navegador y te sacaría de la página
		// mostrando el JSON crudo de inc/anular.php en vez de quedarte aquí con SweetAlert.
		$('#anular').on('click', function () {
			var $btn = $(this);
			var motivo = $.trim($('input[name="motivo"]').val());
			if (!motivo) {
				Swal.fire('Falta el motivo', 'Ingresa el motivo de la anulación.', 'warning');
				return;
			}

			Swal.fire({
				title: 'Confirmar anulación',
				text: 'Está a punto de anular o comunicar la baja de este comprobante, esto tiene implicaciones legales.',
				icon: 'warning',
				showCancelButton: true,
				confirmButtonColor: '#d33',
				cancelButtonColor: '#3085d6',
				confirmButtonText: 'Sí, anular',
				cancelButtonText: 'Cancelar'
			}).then((result) => {
				if (!result.isConfirmed) return;

				$btn.prop('disabled', true);
				$('#loading').show();
				$.ajax({
					type: 'POST',
					url: 'inc/anular.php',
					dataType: 'json',
					data: $('#anulaa').serialize(),
					success: function (response) {
						$('#loading').hide();
						if (response && response.ok) {
							$('#anulaa').fadeOut('slow');
							Swal.fire('Listo', response.mensaje, 'success');
						} else {
							$btn.prop('disabled', false);
							Swal.fire('No se pudo anular', response && response.mensaje ? response.mensaje : 'Intenta de nuevo.', 'error');
						}
					},
					error: function () {
						$('#loading').hide();
						$btn.prop('disabled', false);
						Swal.fire('Error de conexión', 'No se pudo comunicar la baja. Intenta de nuevo.', 'error');
					}
				});
			});
		});
	    console.log( "ready!" );
	});
		
	</script>
</body>
</html>