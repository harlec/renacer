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

$plantillas_rows = $conn->query("SELECT id_plantilla, nombre, dia_inicio, dia_fin_tipo, dia_fin FROM planilla_periodo_plantillas WHERE estado = '1' ORDER BY dia_inicio");
$plantillas = [];
$plantillas_filas = '';
if ($plantillas_rows) {
	while ($p = $plantillas_rows->fetch_assoc()) {
		$plantillas[] = $p;
		$rango = $p['dia_fin_tipo'] === 'fin_mes'
			? ('Día ' . $p['dia_inicio'] . ' a fin de mes')
			: ('Día ' . $p['dia_inicio'] . ' al ' . $p['dia_fin']);
		$plantillas_filas .= '<tr>
			<td>' . htmlspecialchars($p['nombre']) . '</td>
			<td>' . $rango . '</td>
			<td><button type="button" class="btn btn-danger btn-xs btn-borrar-plantilla" data-id="' . $p['id_plantilla'] . '"><i class="fa fa-trash"></i></button></td>
		</tr>';
	}
}
$plantillas_json = json_encode($plantillas);

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
			<td><a class="btn btn-custom btn-sm" href="ver_planilla.php?id=' . $value['id_periodo'] . '"><i class="fas fa-eye"></i> Ver</a></td>
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
						<button type="button" id="btn-gestionar-plantillas" class="btn btn-default btn-sm pull-right" style="margin-right:8px">Gestionar plantillas</button>
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

		<!-- Modal gestión de plantillas de periodo -->
		<div class="modal fade" id="modal-plantillas" tabindex="-1" role="dialog">
			<div class="modal-dialog" role="document">
				<div class="modal-content">
					<div class="modal-header">
						<button type="button" class="close" data-dismiss="modal">&times;</button>
						<h4 class="modal-title">Plantillas de periodo de planilla</h4>
					</div>
					<div class="modal-body">
						<table class="table table-bordered">
							<thead><tr><th>Nombre</th><th>Rango</th><th></th></tr></thead>
							<tbody id="plantillas-body"><?php echo $plantillas_filas; ?></tbody>
						</table>
						<hr>
						<p class="help-block" style="margin-top:0">Nueva plantilla</p>
						<div class="row">
							<div class="col-sm-4">
								<div class="form-group">
									<label>Nombre</label>
									<input type="text" class="form-control" id="np-nombre" placeholder="Ej. Quincena 1">
								</div>
							</div>
							<div class="col-sm-3">
								<div class="form-group">
									<label>Día inicio</label>
									<input type="number" min="1" max="31" class="form-control" id="np-dia-inicio">
								</div>
							</div>
							<div class="col-sm-3">
								<div class="form-group">
									<label>Día fin</label>
									<input type="number" min="1" max="31" class="form-control" id="np-dia-fin">
								</div>
							</div>
							<div class="col-sm-2">
								<div class="checkbox" style="margin-top:25px">
									<label><input type="checkbox" id="np-fin-mes"> Fin de mes</label>
								</div>
							</div>
						</div>
						<button type="button" class="btn btn-success" id="btn-agregar-plantilla">Agregar</button>
					</div>
				</div>
			</div>
		</div>

	<!-- jQuery (necessary for Bootstrap's JavaScript plugins) -->
	<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.3.1/jquery.min.js"></script>
	<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js"></script>
	<script src="//cdn.datatables.net/1.10.22/js/jquery.dataTables.min.js"></script>
	<script src="https://cdnjs.cloudflare.com/ajax/libs/limonte-sweetalert2/10.5.0/sweetalert2.min.js" integrity="sha512-V9JHp52ZkrbVVjJqNz/XXYMUOyUfzaGKEGrcD2Ual7n39+UR1yJK0numAHZqkhhGTAH/Klj0KUe4btAZXccw9w==" crossorigin="anonymous"></script>
	<script>
	var PLANTILLAS = <?php echo $plantillas_json; ?>;
	var NOMBRES_MES = ['Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];

	function pad2(n) { return n < 10 ? '0' + n : '' + n; }
	function ultimoDiaMes(year, month) { return new Date(year, month, 0).getDate(); }

	$(document).ready(function() {
		$('#datos').DataTable({ order: [] });

		$('#nueva_planilla').on('click', function() {
			if (!PLANTILLAS.length) {
				Swal.fire('Advertencia', 'Primero crea al menos una plantilla de periodo en "Gestionar plantillas"', 'warning');
				return;
			}
			var opciones = PLANTILLAS.map(function(p, i) {
				var rango = p.dia_fin_tipo === 'fin_mes' ? ('día ' + p.dia_inicio + ' a fin de mes') : ('día ' + p.dia_inicio + ' al ' + p.dia_fin);
				return '<option value="' + i + '">' + p.nombre + ' (' + rango + ')</option>';
			}).join('');
			var hoy = new Date();
			var anioActual = hoy.getFullYear();
			var opcionesMes = NOMBRES_MES.map(function(nombre, i) {
				return '<option value="' + (i + 1) + '"' + (i === hoy.getMonth() ? ' selected' : '') + '>' + nombre + '</option>';
			}).join('');
			var opcionesAnio = [anioActual - 1, anioActual, anioActual + 1].map(function(y) {
				return '<option value="' + y + '"' + (y === anioActual ? ' selected' : '') + '>' + y + '</option>';
			}).join('');

			Swal.fire({
				title: 'Nueva planilla',
				html:
					'<div style="text-align:left">' +
					'<label style="font-size:12px">Plantilla de periodo</label>' +
					'<select id="swal-plantilla" class="swal2-select" style="display:block;width:90%">' + opciones + '</select>' +
					'<label style="font-size:12px">Mes</label>' +
					'<div style="display:flex;gap:8px;justify-content:center">' +
					'<select id="swal-mes" class="swal2-select" style="display:block;margin:0;width:55%">' + opcionesMes + '</select>' +
					'<select id="swal-anio" class="swal2-select" style="display:block;margin:0;width:35%">' + opcionesAnio + '</select>' +
					'</div>' +
					'</div>',
				showCancelButton: true,
				confirmButtonText: 'Generar',
				cancelButtonText: 'Cancelar',
				preConfirm: function() {
					var plantilla = PLANTILLAS[parseInt(document.getElementById('swal-plantilla').value, 10)];
					var year = parseInt(document.getElementById('swal-anio').value, 10);
					var month = parseInt(document.getElementById('swal-mes').value, 10);
					var ultimoDia = ultimoDiaMes(year, month);
					var diaInicio = Math.min(parseInt(plantilla.dia_inicio, 10), ultimoDia);
					var diaFin = plantilla.dia_fin_tipo === 'fin_mes' ? ultimoDia : Math.min(parseInt(plantilla.dia_fin, 10), ultimoDia);
					var inicio = year + '-' + pad2(month) + '-' + pad2(diaInicio);
					var fin = year + '-' + pad2(month) + '-' + pad2(diaFin);
					if (fin < inicio) {
						Swal.showValidationMessage('El rango de la plantilla es inválido para ese mes');
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

		$('#btn-gestionar-plantillas').on('click', function() {
			$('#modal-plantillas').modal('show');
		});

		$('#btn-agregar-plantilla').on('click', function() {
			var nombre = $('#np-nombre').val().trim();
			var diaInicio = $('#np-dia-inicio').val();
			var finMes = $('#np-fin-mes').is(':checked');
			var diaFin = $('#np-dia-fin').val();
			if (!nombre || !diaInicio || (!finMes && !diaFin)) {
				Swal.fire('Advertencia', 'Completa nombre, día inicio y día fin (o marca "Fin de mes")', 'warning');
				return;
			}
			$.ajax({
				type: 'POST',
				dataType: 'json',
				url: 'inc/registrar_periodo_plantilla.php',
				data: { nombre: nombre, dia_inicio: diaInicio, dia_fin_tipo: finMes ? 'fin_mes' : 'fijo', dia_fin: diaFin },
				success: function(data) {
					if (data.ok) {
						document.location.reload();
					} else {
						Swal.fire('Advertencia', data.mensaje || 'No se pudo crear la plantilla', 'warning');
					}
				},
				error: function() {
					Swal.fire('Advertencia', 'Error general del sistema', 'warning');
				}
			});
		});

		$('#plantillas-body').on('click', '.btn-borrar-plantilla', function() {
			var id = $(this).data('id');
			$.ajax({
				type: 'GET',
				dataType: 'json',
				url: 'inc/borrar_periodo_plantilla.php',
				data: { id: id },
				success: function(data) {
					if (data.ok) {
						document.location.reload();
					} else {
						Swal.fire('Advertencia', data.mensaje || 'No se pudo borrar la plantilla', 'warning');
					}
				},
				error: function() {
					Swal.fire('Advertencia', 'Error general del sistema', 'warning');
				}
			});
		});
	});
	</script>
</body>
</html>
