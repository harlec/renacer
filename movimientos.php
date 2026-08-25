<?php
include('inc/control.php');
if ($_SESSION['type']=='operador') {
	header("Location: dashboard.php");
}

$conn = new mysqli('localhost', 'admin_renacer', 'ikm169uhn', 'admin_renacer');
$conn->set_charset('utf8');

$empleados_rows = $conn->query("SELECT id_empleado, nombres, apellidos FROM empleados WHERE estado = '1' ORDER BY nombres, apellidos");
$empleados_opciones = '';
if ($empleados_rows) {
	while ($e = $empleados_rows->fetch_assoc()) {
		$empleados_opciones .= '<option value="' . $e['id_empleado'] . '">' . htmlspecialchars($e['nombres'] . ' ' . $e['apellidos']) . '</option>';
	}
}

$r = $conn->query("
	SELECT m.id_movimiento, m.tipo, m.fecha, m.importe, m.descripcion, m.id_detalle_aplicado, m.id_venta,
	       e.nombres, e.apellidos, pp.fecha_inicio, pp.fecha_fin
	FROM movimientos_empleado m
	INNER JOIN empleados e ON e.id_empleado = m.id_empleado
	LEFT JOIN planilla_detalle pd ON pd.id_detalle = m.id_detalle_aplicado
	LEFT JOIN planilla_periodos pp ON pp.id_periodo = pd.id_periodo
	ORDER BY m.fecha DESC, m.id_movimiento DESC
");

$tipo_badge = [
	'adelanto'   => '<span class="label label-warning">Adelanto</span>',
	'abarrotes'  => '<span class="label label-info">Abarrotes</span>',
];

$datos = '';
if ($r) {
	while ($value = $r->fetch_assoc()) {
		$estado = $value['id_detalle_aplicado']
			? '<span class="label label-default">Aplicado (' . date('d/m/Y', strtotime($value['fecha_inicio'])) . ' - ' . date('d/m/Y', strtotime($value['fecha_fin'])) . ')</span>'
			: '<span class="label label-success">Pendiente</span>';

		$acciones = '';
		if ($value['id_venta']) {
			// Viene de una venta real (abarrotes desde venta.php): se anula desde la venta, no desde aquí.
			$acciones .= '<a class="btn btn-default btn-xs" href="ver_venta.php?id=' . $value['id_venta'] . '"><i class="fas fa-eye"></i> Ver venta</a>';
		} elseif (!$value['id_detalle_aplicado']) {
			$acciones .= '<button type="button" class="btn btn-danger btn-xs btn-borrar-movimiento" data-id="' . $value['id_movimiento'] . '"><i class="fa fa-trash"></i></button>';
		}

		$datos .= '<tr>
			<td>' . date('d/m/Y', strtotime($value['fecha'])) . '</td>
			<td>' . htmlspecialchars($value['nombres'] . ' ' . $value['apellidos']) . '</td>
			<td>' . ($tipo_badge[$value['tipo']] ?? $value['tipo']) . '</td>
			<td>S/ ' . number_format((float)$value['importe'], 2) . '</td>
			<td>' . htmlspecialchars($value['descripcion']) . '</td>
			<td>' . $estado . '</td>
			<td>' . $acciones . '</td>
		</tr>';
	}
}
$conn->close();
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
	        <?php menu('2'); ?>
	      </div>
	      <div class="submenu">
	      	<ul class="subtop-tabs">
	      		<li>
	      			<a class="" href="agregar_usuario.php">Registrar usuario</a>
	      		</li>
	      		<li>
	      			<a class="" href="ver_usuarios.php">Listar usuarios</a>
	      		</li>
	      		<li>
	      			<a class="" href="agregar_empleado.php">Agregar colaborador</a>
	      		</li>
	      		<li>
	      			<a class="" href="ver_empleados.php">Listar colaboradores</a>
	      		</li>
	      		<li>
	      			<a class="" href="asistencia.php">Asistencia</a>
	      		</li>
	      		<li>
	      			<a class="" href="planillas.php">Planillas</a>
	      		</li>
	      		<li class="active">
	      			<a class="" href="movimientos.php">Adelantos y abarrotes</a>
	      		</li>
	      		<li>
	      			<a class="" href="configuracion_planillas.php">Config. planillas</a>
	      		</li>
	      	</ul>
	      </div>
	    </nav>
		<div class="kbg">
			<div class="cuerpofull">
				<div class="titulo">
					<h3>Adelantos y abarrotes</h3>
				</div>
				<div class="container-fluid">
					<div class="row">
						<div class="col-md-12">
							<div class="kdashboard">
								<div class="row">
									<div class="col-md-12">
										<div class="panel panel-default pa">
											<div class="panel-body">
												<p class="help-block" style="margin-top:0">
													Registra un adelanto en efectivo con la fecha en que realmente se entregó. El consumo de <strong>abarrotes</strong> ya no se registra aquí: se hace desde <a href="venta.php">Registrar venta</a> marcando "Es un empleado", para que también descuente el stock.
													El adelanto se descuenta automáticamente del sueldo del colaborador cuando generes la planilla que cubra esa fecha
													(no hace falta que la planilla ya exista).
												</p>
												<div class="row">
													<div class="col-sm-3">
														<div class="form-group">
															<label>Colaborador</label>
															<select class="form-control" id="mov-empleado">
																<option value="">-- elegir --</option>
																<?php echo $empleados_opciones; ?>
															</select>
														</div>
													</div>
													<input type="hidden" id="mov-tipo" value="adelanto">
													<div class="col-sm-2">
														<div class="form-group">
															<label>Fecha</label>
															<input type="date" class="form-control" id="mov-fecha" value="<?php echo date('Y-m-d'); ?>">
														</div>
													</div>
													<div class="col-sm-2">
														<div class="form-group">
															<label>Importe (S/)</label>
															<input type="number" step="0.01" min="0.01" class="form-control" id="mov-importe" placeholder="0.00">
														</div>
													</div>
													<div class="col-sm-3">
														<div class="form-group">
															<label>Descripción (opcional)</label>
															<input type="text" class="form-control" id="mov-descripcion" placeholder="Ej. motivo del adelanto">
														</div>
													</div>
												</div>
												<button type="button" class="btn btn-success" id="btn-registrar-movimiento">Registrar</button>
											</div>
										</div>
									</div>
								</div>
								<div class="row">
									<div class="col-md-12">
										<div class="panel panel-default pa">
											<div class="panel-body table-responsive">
											    <table id="datos" class="table table-hover">
											    	<thead>
											    		<tr>
											    			<th>Fecha</th>
											    			<th>Colaborador</th>
											    			<th>Tipo</th>
											    			<th>Importe</th>
											    			<th>Descripción</th>
											    			<th>Estado</th>
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
	 	<!-- Tab panes -->



	<!-- jQuery (necessary for Bootstrap's JavaScript plugins) -->
	<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.3.1/jquery.min.js"></script>
	<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js"></script>
	<script src="//cdn.datatables.net/1.10.22/js/jquery.dataTables.min.js"></script>
	<script src="https://cdnjs.cloudflare.com/ajax/libs/limonte-sweetalert2/10.5.0/sweetalert2.min.js" integrity="sha512-V9JHp52ZkrbVVjJqNz/XXYMUOyUfzaGKEGrcD2Ual7n39+UR1yJK0numAHZqkhhGTAH/Klj0KUe4btAZXccw9w==" crossorigin="anonymous"></script>
	<script>
	$(document).ready(function() {
		$('#datos').DataTable({ order: [] });

		$('#btn-registrar-movimiento').on('click', function() {
			var id_empleado = $('#mov-empleado').val();
			var tipo = $('#mov-tipo').val();
			var fecha = $('#mov-fecha').val();
			var importe = $('#mov-importe').val();
			var descripcion = $('#mov-descripcion').val();

			if (!id_empleado || !fecha || !importe || parseFloat(importe) <= 0) {
				Swal.fire('Advertencia', 'Completa colaborador, fecha e importe', 'warning');
				return;
			}

			$.ajax({
				type: 'POST',
				dataType: 'json',
				url: 'inc/registrar_movimiento.php',
				data: { id_empleado: id_empleado, tipo: tipo, fecha: fecha, importe: importe, descripcion: descripcion },
				success: function(data) {
					if (data.ok) {
						document.location.reload();
					} else {
						Swal.fire('Advertencia', data.mensaje || 'No se pudo registrar', 'warning');
					}
				},
				error: function() {
					Swal.fire('Advertencia', 'Error general del sistema', 'warning');
				}
			});
		});

		$('#datos').on('click', '.btn-borrar-movimiento', function() {
			var id = $(this).data('id');
			Swal.fire({
				title: 'Seguro de borrar?',
				text: 'No podrás revertir esto',
				icon: 'warning',
				showCancelButton: true,
				confirmButtonColor: '#3085d6',
				cancelButtonColor: '#d33',
				confirmButtonText: 'Sí, borrar!'
			}).then(function(result) {
				if (!result.isConfirmed) return;
				$.ajax({
					type: 'GET',
					dataType: 'json',
					url: 'inc/borrar_movimiento.php',
					data: { id: id },
					success: function(data) {
						if (data.ok) {
							document.location.reload();
						} else {
							Swal.fire('Advertencia', data.mensaje || 'No se pudo borrar', 'warning');
						}
					}
				});
			});
		});
	});
	</script>
</body>
</html>
