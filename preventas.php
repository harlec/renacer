<?php
include('inc/control.php');

$conn = new mysqli('localhost', 'admin_renacer', 'ikm169uhn', 'admin_renacer');
$conn->set_charset('utf8');

$datos = '';
$r = $conn->query("
    SELECT p.id_preventa, p.fecha, c.cliente AS nombre_cliente,
           (SELECT COUNT(*) FROM detalle_preventa d WHERE d.preventa = p.id_preventa) AS n_items
    FROM preventa p
    LEFT JOIN clientes c ON c.id_cliente = p.cliente
    WHERE p.estado = '0'
    ORDER BY p.id_preventa DESC
");
while ($v = $r->fetch_assoc()) {
    $datos .= '<tr>
            <td>' . $v['id_preventa'] . '</td>
            <td>' . htmlspecialchars($v['fecha']) . '</td>
            <td>' . htmlspecialchars($v['nombre_cliente'] ?? '—') . '</td>
            <td>' . $v['n_items'] . '</td>
            <td><a class="btn btn-primary btn-xs" href="procesar_preventa.php?id=' . $v['id_preventa'] . '">Procesar</a></td>
          </tr>';
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
	<meta charset="UTF-8">
	<title>Sistema - Preventas</title>
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
	        <?php menu('10'); ?>
	      </div>
	    </nav>
		<div class="kbg">
			<div class="cuerpofull">
				<div class="titulo">
					<h3>Preventas pendientes</h3>
				</div>
				<div class="container-fluid">
					<div class="row">
						<div class="col-md-12">
							<div class="kdashboard">
								<div class="row">
									<div class="col-md-12">
										<div class="panel panel-default pa">
											<div class="panel-body table-responsive">
												<table id="datos" class="table table-hover">
													<thead>
														<tr>
															<th>Id</th>
															<th>Fecha</th>
															<th>Cliente</th>
															<th>Items</th>
															<th></th>
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
			</div>
		</div>
	</div>

	<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.3.1/jquery.min.js"></script>
	<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js"></script>
	<script src="//cdn.datatables.net/1.10.22/js/jquery.dataTables.min.js"></script>
	<script>
	$(document).ready(function() {
		$.extend(true, $.fn.dataTable.defaults, {
			"language": {
				"info": "Mostrando registros del _START_ al _END_ de un total de _TOTAL_ registros",
				"infoEmpty": "Mostrando registros del 0 al 0 de un total de 0 registros",
				"lengthMenu": "Mostrar _MENU_ registros",
				"paginate": { "first": "Primero", "last": "Último", "next": "Siguiente", "previous": "Anterior" },
				"search": "Buscar:",
				"searchPlaceholder": "Término de búsqueda",
				"zeroRecords": "No se encontraron resultados",
				"emptyTable": "No hay preventas pendientes"
			}
		});
		$('#datos').DataTable();
	});
	</script>
</body>
</html>
