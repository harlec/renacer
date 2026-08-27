<?php
include('inc/control.php');
if ($_SESSION['type']=='operador') {
	header("Location: dashboard.php");
}

$fecha = isset($_GET['fecha']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET['fecha']) ? $_GET['fecha'] : date('Y-m-d');

include('inc/sdba/sdba.php');
include('inc/config_facturacion.php');
include('inc/horario_helpers.php');

$conn = new mysqli('localhost', 'admin_renacer', 'ikm169uhn', 'admin_renacer');
$conn->set_charset('utf8');

$fecha_esc = $conn->real_escape_string($fecha);
$r = $conn->query("
	SELECT e.id_empleado, e.nombres, e.apellidos, e.cargo,
	       a.hora_entrada_real, a.hora_salida_real, a.minutos_tardanza, a.horas_trabajadas, a.observacion,
	       d.id_descanso
	FROM empleados e
	LEFT JOIN asistencias a ON a.id_empleado = e.id_empleado AND a.fecha = '$fecha_esc'
	LEFT JOIN empleado_descansos d ON d.id_empleado = e.id_empleado AND d.fecha = '$fecha_esc'
	WHERE e.estado = '1'
	ORDER BY e.nombres, e.apellidos
");

$obs_badge = [
	'PUNTUAL'   => '<span class="label label-success">Puntual</span>',
	'RETARDADO' => '<span class="label label-warning">Retardado</span>',
	'FALTO'     => '<span class="label label-danger">Faltó</span>',
];

$datos = '';
if ($r) {
	while ($value = $r->fetch_assoc()) {
		$es_descanso = !empty($value['id_descanso']);

		list($hora_ingreso_dia, $hora_salida_dia) = obtener_horario_programado($conn, $value['id_empleado'], $fecha);
		$horario_prog = ($hora_ingreso_dia && $hora_salida_dia)
			? substr($hora_ingreso_dia,0,5) . ' - ' . substr($hora_salida_dia,0,5)
			: '-';
		$entrada_val = $value['hora_entrada_real'] ? substr($value['hora_entrada_real'],0,5) : '';
		$salida_val  = $value['hora_salida_real'] ? substr($value['hora_salida_real'],0,5) : '';
		$falto_chk   = $value['observacion'] == 'FALTO' ? 'checked' : '';
		$tardanza    = $value['minutos_tardanza'] ? $value['minutos_tardanza'] . ' min' : '-';
		$horas       = $value['horas_trabajadas'] !== null ? number_format((float)$value['horas_trabajadas'],2) . ' h' : '-';
		$badge       = $value['observacion'] && isset($obs_badge[$value['observacion']]) ? $obs_badge[$value['observacion']] : '-';

		$dis = $es_descanso ? 'disabled' : '';
		if ($es_descanso) {
			$badge = '<span class="label label-info">Descanso</span>';
		}

		$fila_class = $es_descanso ? ' class="active"' : '';
		$descanso_chk = $es_descanso ? 'checked' : '';

		$datos .= '<tr' . $fila_class . ' data-descanso="' . ($es_descanso ? '1' : '0') . '">
			<td>' . htmlspecialchars($value['nombres'] . ' ' . $value['apellidos']) . '<br><small class="text-muted">' . htmlspecialchars($value['cargo']) . '</small></td>
			<td>' . $horario_prog . '</td>
			<td><input type="time" class="form-control input-sm entrada" data-id="' . $value['id_empleado'] . '" value="' . $entrada_val . '" ' . $dis . '></td>
			<td><input type="time" class="form-control input-sm salida" data-id="' . $value['id_empleado'] . '" value="' . $salida_val . '" ' . $dis . '></td>
			<td class="text-center"><input type="checkbox" class="descanso" data-id="' . $value['id_empleado'] . '" ' . $descanso_chk . ' disabled></td>
			<td class="text-center"><input type="checkbox" class="falto" data-id="' . $value['id_empleado'] . '" ' . $falto_chk . ' ' . $dis . '></td>
			<td>' . $tardanza . '</td>
			<td>' . $horas . '</td>
			<td>' . $badge . '</td>
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
	      		<li class="active">
	      			<a class="" href="asistencia.php">Asistencia</a>
	      		</li>
	      		<li>
	      			<a class="" href="descansos.php">Descansos</a>
	      		</li>
	      			      		<li>
	      			<a class="" href="planillas.php">Planillas</a>
	      		</li>
	      		<li>
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
					<h3>Asistencia diaria <a href="ver_asistencias.php" class="btn btn-default btn-sm pull-right">Ver historial</a></h3>
				</div>
				<div class="container-fluid">
					<div class="row">
						<div class="col-md-12">
							<div class="kdashboard">
								<div class="row">
									<div class="col-md-4">
										<div class="form-group">
											<label>Fecha</label>
											<input type="date" id="fecha" class="form-control" value="<?php echo $fecha; ?>">
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
											    			<th>Colaborador</th>
											    			<th>Horario</th>
											    			<th>Entrada</th>
											    			<th>Salida</th>
											    			<th>Descanso</th>
											    			<th>Faltó</th>
											    			<th>Tardanza</th>
											    			<th>Horas trab.</th>
											    			<th>Estado</th>
											    		</tr>
											    	</thead>
											    	<tbody>
											    		<?php echo $datos; ?>
											    	</tbody>
											    </table>
											    <button type="button" id="guardar_asistencia" class="btn btn-success btn-lg">Guardar asistencia</button>
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
	<script src="https://cdnjs.cloudflare.com/ajax/libs/limonte-sweetalert2/10.5.0/sweetalert2.min.js" integrity="sha512-V9JHp52ZkrbVVjJqNz/XXYMUOyUfzaGKEGrcD2Ual7n39+UR1yJK0numAHZqkhhGTAH/Klj0KUe4btAZXccw9w==" crossorigin="anonymous"></script>
	<script>
	$(document).ready(function() {

		$('#fecha').on('change', function() {
			document.location.href = 'asistencia.php?fecha=' + $(this).val();
		});

		$('#guardar_asistencia').on('click', function() {
			var id_empleado = [], entrada = [], salida = [], falto = [];

			$('#datos tbody tr').each(function() {
				var $row = $(this);
				if ($row.data('descanso') == 1) return; // día bloqueado, no se envía

				var id = $row.find('.entrada').data('id');
				id_empleado.push(id);
				entrada.push($row.find('.entrada').val());
				salida.push($row.find('.salida').val());
				falto.push($row.find('.falto').is(':checked') ? '1' : '0');
			});

			$.ajax({
				type: 'POST',
				dataType: 'json',
				url: 'inc/registrar_asistencia.php',
				data: { fecha: '<?php echo $fecha; ?>', id_empleado: id_empleado, entrada: entrada, salida: salida, falto: falto },
				success: function(data) {
					if (data.ok) {
						Swal.fire('Listo', 'Asistencia guardada', 'success').then(function() {
							document.location.reload();
						});
					} else {
						Swal.fire('Advertencia', data.mensaje || 'No se pudo guardar', 'warning');
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
