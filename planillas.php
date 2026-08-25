<?php
include('inc/control.php');
if ($_SESSION['type']=='operador') {
	header("Location: dashboard.php");
}

$conn = new mysqli('localhost', 'admin_renacer', 'ikm169uhn', 'admin_renacer');
$conn->set_charset('utf8');

$r = $conn->query("
	SELECT pp.id_periodo, pp.fecha_inicio, pp.fecha_fin, pp.dias, pp.estado,
	       COUNT(pd.id_detalle) AS n_empleados,
	       COALESCE(SUM(pd.sueldo_periodo), 0) AS sueldo_total,
	       COALESCE(desc_t.total_desc, 0) AS descuentos_total
	FROM planilla_periodos pp
	LEFT JOIN planilla_detalle pd ON pd.id_periodo = pp.id_periodo
	LEFT JOIN (
		SELECT d.id_periodo, SUM(pdesc.importe) AS total_desc
		FROM planilla_detalle d
		INNER JOIN planilla_descuentos pdesc ON pdesc.id_detalle = d.id_detalle
		GROUP BY d.id_periodo
	) desc_t ON desc_t.id_periodo = pp.id_periodo
	GROUP BY pp.id_periodo
	ORDER BY pp.fecha_inicio DESC
");

$ultima = $conn->query("SELECT fecha_fin FROM planilla_periodos ORDER BY fecha_fin DESC LIMIT 1");
$ultima_fecha_fin = $ultima && $ultima->num_rows ? $ultima->fetch_assoc()['fecha_fin'] : null;

$estado_badge = [
	'abierto' => '<span class="label label-success">Abierto</span>',
	'cerrado' => '<span class="label label-default">Cerrado</span>',
];

$datos = '';
if ($r) {
	while ($value = $r->fetch_assoc()) {
		$sueldo_total = round((float)$value['sueldo_total'], 2);
		$descuentos   = round((float)$value['descuentos_total'], 2);
		$total_pagar  = round($sueldo_total - $descuentos, 2);

		$datos .= '<tr>
			<td>' . date('d/m/Y', strtotime($value['fecha_inicio'])) . ' - ' . date('d/m/Y', strtotime($value['fecha_fin'])) . '</td>
			<td>' . $value['dias'] . '</td>
			<td>' . $value['n_empleados'] . '</td>
			<td>S/ ' . number_format($sueldo_total,2) . '</td>
			<td>S/ ' . number_format($descuentos,2) . '</td>
			<td><strong>S/ ' . number_format($total_pagar,2) . '</strong></td>
			<td>' . ($estado_badge[$value['estado']] ?? $value['estado']) . '</td>
			<td>
				<a class="btn btn-custom btn-sm" href="ver_planilla.php?id=' . $value['id_periodo'] . '"><i class="fas fa-eye"></i> Ver</a>
				' . ($value['estado'] !== 'cerrado' ? '<button type="button" class="btn btn-danger btn-sm btn-borrar-planilla" data-id="' . $value['id_periodo'] . '"><i class="fas fa-trash"></i></button>' : '') . '
			</td>
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
	      		<li class="active">
	      			<a class="" href="planillas.php">Planillas</a>
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
					<h3>Planillas
						<button type="button" id="nueva_planilla" class="btn btn-success btn-sm pull-right">Nueva planilla</button>
					</h3>
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
											    			<th>Periodo</th>
											    			<th>Días</th>
											    			<th>Colaboradores</th>
											    			<th>Total sueldos</th>
											    			<th>Descuentos</th>
											    			<th>Total a pagar</th>
											    			<th>Estado</th>
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
			</div>
		</div>
	 	<!-- Tab panes -->

	<!-- jQuery (necessary for Bootstrap's JavaScript plugins) -->
	<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.3.1/jquery.min.js"></script>
	<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js"></script>
	<script src="//cdn.datatables.net/1.10.22/js/jquery.dataTables.min.js"></script>
	<script src="https://cdnjs.cloudflare.com/ajax/libs/limonte-sweetalert2/10.5.0/sweetalert2.min.js" integrity="sha512-V9JHp52ZkrbVVjJqNz/XXYMUOyUfzaGKEGrcD2Ual7n39+UR1yJK0numAHZqkhhGTAH/Klj0KUe4btAZXccw9w==" crossorigin="anonymous"></script>
	<script>
	var ULTIMA_FECHA_FIN = <?php echo $ultima_fecha_fin ? "'" . $ultima_fecha_fin . "'" : 'null'; ?>;

	function pad2(n) { return n < 10 ? '0' + n : '' + n; }
	function formatoFecha(d) { return d.getFullYear() + '-' + pad2(d.getMonth() + 1) + '-' + pad2(d.getDate()); }
	function sumarDias(fechaStr, dias) {
		var partes = fechaStr.split('-');
		var d = new Date(parseInt(partes[0], 10), parseInt(partes[1], 10) - 1, parseInt(partes[2], 10));
		d.setDate(d.getDate() + dias);
		return formatoFecha(d);
	}

	$(document).ready(function() {
		$('#datos').DataTable({ order: [] });

		$('#nueva_planilla').on('click', function() {
			// Quincena rotativa de 15 días exactos: el siguiente periodo empieza justo
			// donde terminó el anterior, sin importar el día del mes (no está atado al
			// calendario). Si no hay periodo previo, se propone desde hoy.
			var inicioSugerido = ULTIMA_FECHA_FIN ? sumarDias(ULTIMA_FECHA_FIN, 1) : formatoFecha(new Date());
			var finSugerido = sumarDias(inicioSugerido, 14);

			Swal.fire({
				title: 'Nueva planilla',
				html:
					'<div style="text-align:left">' +
					'<label style="font-size:12px">Fecha inicio</label>' +
					'<input id="swal-inicio" type="date" class="swal2-input" value="' + inicioSugerido + '">' +
					'<label style="font-size:12px">Fecha fin</label>' +
					'<input id="swal-fin" type="date" class="swal2-input" value="' + finSugerido + '">' +
					'</div>',
				showCancelButton: true,
				confirmButtonText: 'Generar',
				cancelButtonText: 'Cancelar',
				preConfirm: function() {
					var inicio = document.getElementById('swal-inicio').value;
					var fin = document.getElementById('swal-fin').value;
					if (!inicio || !fin) {
						Swal.showValidationMessage('Ingresa ambas fechas');
						return false;
					}
					if (fin < inicio) {
						Swal.showValidationMessage('La fecha fin no puede ser anterior a la fecha inicio');
						return false;
					}
					return { inicio: inicio, fin: fin };
				}
			}).then(function(result) {
				if (!result.isConfirmed) return;
				$.ajax({
					type: 'POST',
					dataType: 'json',
					url: 'inc/registrar_planilla_periodo.php',
					data: { fecha_inicio: result.value.inicio, fecha_fin: result.value.fin },
					success: function(data) {
						if (data.ok) {
							document.location.href = 'ver_planilla.php?id=' + data.id_periodo;
						} else {
							Swal.fire('Advertencia', data.mensaje || 'No se pudo generar la planilla', 'warning');
						}
					},
					error: function() {
						Swal.fire('Advertencia', 'Error general del sistema', 'warning');
					}
				});
			});
		});

		$('body').on('click', '.btn-borrar-planilla', function() {
			var id = $(this).data('id');
			Swal.fire({
				title: 'Seguro de borrar esta planilla?',
				text: 'Se borrarán también los sueldos y descuentos calculados de todos los colaboradores en este periodo. No podrás revertir esto.',
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
					url: 'inc/borrar_planilla_periodo.php',
					data: { id: id },
					success: function(data) {
						if (data.respuesta) {
							document.location.reload();
						} else {
							Swal.fire('Advertencia', data.mensaje || 'No se pudo borrar la planilla', 'warning');
						}
					},
					error: function() {
						Swal.fire('Advertencia', 'Error general del sistema', 'warning');
					}
				});
			});
		});
	});
	</script>
</body>
</html>
