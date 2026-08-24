<?php
include('inc/control.php');
if ($_SESSION['type']=='operador') {
	header("Location: dashboard.php");
}

$hoy = date('Y-m-d');
$fechaini = isset($_GET['fechaini']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET['fechaini']) ? $_GET['fechaini'] : date('Y-m-d', strtotime('-6 days'));
$fechafin = isset($_GET['fechafin']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET['fechafin']) ? $_GET['fechafin'] : $hoy;

$conn = new mysqli('localhost', 'admin_renacer', 'ikm169uhn', 'admin_renacer');
$conn->set_charset('utf8');

$ini_esc = $conn->real_escape_string($fechaini);
$fin_esc = $conn->real_escape_string($fechafin);

$r = $conn->query("
	SELECT a.fecha, e.nombres, e.apellidos, a.hora_entrada_prog, a.hora_entrada_real, a.hora_salida_prog, a.hora_salida_real,
	       a.minutos_tardanza, a.horas_trabajadas, a.observacion
	FROM asistencias a
	INNER JOIN empleados e ON e.id_empleado = a.id_empleado
	WHERE a.fecha BETWEEN '$ini_esc' AND '$fin_esc'
	ORDER BY a.fecha DESC, e.nombres
");

$obs_badge = [
	'PUNTUAL'   => '<span class="label label-success">Puntual</span>',
	'RETARDADO' => '<span class="label label-warning">Retardado</span>',
	'FALTO'     => '<span class="label label-danger">Faltó</span>',
];

$datos = '';
if ($r) {
	while ($value = $r->fetch_assoc()) {
		$badge = $value['observacion'] && isset($obs_badge[$value['observacion']]) ? $obs_badge[$value['observacion']] : '-';
		$datos .= '<tr>
			<td>' . date('d/m/Y', strtotime($value['fecha'])) . '</td>
			<td>' . htmlspecialchars($value['nombres'] . ' ' . $value['apellidos']) . '</td>
			<td>' . ($value['hora_entrada_prog'] ? substr($value['hora_entrada_prog'],0,5) : '-') . '</td>
			<td>' . ($value['hora_entrada_real'] ? substr($value['hora_entrada_real'],0,5) : '-') . '</td>
			<td>' . ($value['hora_salida_prog'] ? substr($value['hora_salida_prog'],0,5) : '-') . '</td>
			<td>' . ($value['hora_salida_real'] ? substr($value['hora_salida_real'],0,5) : '-') . '</td>
			<td>' . ($value['minutos_tardanza'] ? $value['minutos_tardanza'] . ' min' : '-') . '</td>
			<td>' . ($value['horas_trabajadas'] !== null ? number_format((float)$value['horas_trabajadas'],2).' h' : '-') . '</td>
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
	      			<a class="" href="planillas.php">Planillas</a>
	      		</li>
	      	</ul>
	      </div>
	    </nav>
		<div class="kbg">
			<div class="cuerpofull">
				<div class="titulo">
					<h3>Historial de asistencia</h3>
				</div>
				<div class="container-fluid">
					<div class="row">
						<div class="col-md-12">
							<div class="kdashboard">
								<div class="row">
									<div class="col-md-3">
										<div class="form-group">
											<label>Desde</label>
											<input type="date" id="fechaini" class="form-control" value="<?php echo $fechaini; ?>">
										</div>
									</div>
									<div class="col-md-3">
										<div class="form-group">
											<label>Hasta</label>
											<input type="date" id="fechafin" class="form-control" value="<?php echo $fechafin; ?>">
										</div>
									</div>
									<div class="col-md-2">
										<label>&nbsp;</label>
										<button type="button" id="filtrar" class="btn btn-primary btn-block">Filtrar</button>
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
											    			<th>Entrada prog.</th>
											    			<th>Entrada real</th>
											    			<th>Salida prog.</th>
											    			<th>Salida real</th>
											    			<th>Tardanza</th>
											    			<th>Horas trab.</th>
											    			<th>Estado</th>
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
	<script>
	$(document).ready(function() {
		$('#datos').DataTable({ order: [] });

		$('#filtrar').on('click', function() {
			document.location.href = 'ver_asistencias.php?fechaini=' + $('#fechaini').val() + '&fechafin=' + $('#fechafin').val();
		});
	});
	</script>
</body>
</html>
