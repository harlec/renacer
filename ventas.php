<?php
include('inc/control.php');
$conn_tmp = new mysqli('localhost', 'admin_renacer', 'ikm169uhn', 'admin_renacer');
$where_u = ($_SESSION['type'] == 'admin') ? "" : "AND usuario = " . intval($_SESSION['id_usr']);
$r_tmp = $conn_tmp->query("SELECT COUNT(*) as c FROM ventas WHERE estado != '2' $where_u");
$total_count = $r_tmp ? intval($r_tmp->fetch_assoc()['c']) : 0;
$conn_tmp->close();
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
	      		<li class="active">
	      			<a href="ventas.php">Listar ventas</a>
	      		</li>
	      		<li>
	      			<a href="notas_venta.php">Facturar</a>
	      		</li>
	      		<!-- <li>
	      			<a href="ventap.php">Proforma</a>
	      		</li>
	      		<li>
	      			<a href="venta_comprobantes.php">Comprobantes</a>
	      		</li> -->
	      	</ul>
	      </div>
	    </nav>
		<div class="kbg">
			<div class="cuerpofull">
				<div class="titulo">
					<h3>Ventas</h3>
				</div>
				<div class="container-fluid">
					<div class="row">
						<div class="col-md-12">
							<div class="kdashboard">
								<div class="row">
									<div class="col-md-12">
										<div class="panel panel-default pa">
											<div class="panel-body">
											    <table id="datos" class="table table-hover"> 
											    	<thead> 
											    		<tr>
											    			<th>#</th>
											    			<th>Venta</th>
											    			<th>Usuario</th>
											    			<th>Cliente</th>
											    			<th>Forma</th>
											    			<th>Fecha</th>
											    			<th>Monto</th>
											    			<th>Comprobante</th>
											    			<th>Opciones</th>
											    		</tr> 
											    	</thead> 
											    	<tbody></tbody> 
											    </table>
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
	<script src="https://cdn.jsdelivr.net/npm/jquery-validation@1.17.0/dist/jquery.validate.min.js"></script>
	<script src="assets/js/sweetalert2.all.min.js"></script>
	<script src="//cdn.datatables.net/1.10.22/js/jquery.dataTables.min.js"></script>
	<script src="https://cdnjs.cloudflare.com/ajax/libs/limonte-sweetalert2/10.5.0/sweetalert2.min.js" integrity="sha512-V9JHp52ZkrbVVjJqNz/XXYMUOyUfzaGKEGrcD2Ual7n39+UR1yJK0numAHZqkhhGTAH/Klj0KUe4btAZXccw9w==" crossorigin="anonymous"></script>
		<script>
	$(document).ready(function() {

		var pageLength = 25;
		var lastPageStart = Math.max(0, Math.floor((<?= $total_count ?> - 1) / pageLength) * pageLength);
		$('#datos').DataTable({
			serverSide: true,
			processing: true,
			ajax: '/inc/get_ventas.php',
			displayStart: lastPageStart,
			order: [[1, 'asc']],
			pageLength: pageLength,
			columns: [
				{ data: null, orderable: false, render: function(data, type, row, meta) {
					return meta.row + meta.settings._iDisplayStart + 1;
				}},
				{ data: 0 },
				{ data: 1 },
				{ data: 2 },
				{ data: 3 },
				{ data: 4 },
				{ data: 5 },
				{ data: 6, orderable: false },
				{ data: 7, orderable: false }
			],
		    language: {
		        info: "Mostrando registros del _START_ al _END_ de un total de _TOTAL_ registros",
		        infoEmpty: "Mostrando 0 registros",
		        infoFiltered: "(filtrado de _MAX_ registros)",
		        loadingRecords: "Cargando...",
		        lengthMenu: "Mostrar _MENU_ registros",
		        processing: "Procesando...",
		        search: "Buscar:",
		        searchPlaceholder: "Término de búsqueda",
		        zeroRecords: "No se encontraron resultados",
		        emptyTable: "Ningún dato disponible",
		        paginate: { first: "Primero", last: "Último", next: "Siguiente", previous: "Anterior" }
		    }
		});

		$('body').on('click', ".btn-borrar", function() {
			var id = $(this).val();
			Swal.fire({
			  title: 'Seguro de borrar?',
			  text: "Tu no puedes revertir esto!",
			  icon: 'warning',
			  showCancelButton: true,
			  confirmButtonColor: '#3085d6',
			  cancelButtonColor: '#d33',
			  confirmButtonText: 'Si, borrar!'
			}).then((result) => {
			  if (result.isConfirmed) {
				$.ajax({
					type: 'GET',
					dataType: 'json',
					url: '/inc/borrar_venta.php',
					data: 'id=' + id,
					success: function(data1) {
						$('#datos').DataTable().ajax.reload();
					}
				});
			    Swal.fire('Borrado!', 'El registro fue borrado correctamente.', 'success');
			  }
			});
		});

	});
	</script>
</body>
</html>