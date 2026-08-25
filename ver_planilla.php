<?php
include('inc/control.php');
if ($_SESSION['type']=='operador') {
	header("Location: dashboard.php");
}

$id_periodo = intval($_GET['id'] ?? 0);

$conn = new mysqli('localhost', 'admin_renacer', 'ikm169uhn', 'admin_renacer');
$conn->set_charset('utf8');

$rp = $conn->query("SELECT id_periodo, fecha_inicio, fecha_fin, dias, estado FROM planilla_periodos WHERE id_periodo = $id_periodo");
$periodo = $rp ? $rp->fetch_assoc() : null;

if (!$periodo) {
	echo 'Planilla no encontrada. <a href="planillas.php">Volver</a>';
	exit;
}

$r = $conn->query("
	SELECT pd.id_detalle, e.nombres, e.apellidos, e.cargo, pd.sueldo_periodo,
	       COALESCE(SUM(CASE WHEN pdesc.tipo='tardanza'  THEN pdesc.importe ELSE 0 END),0) AS tardanza,
	       COALESCE(SUM(CASE WHEN pdesc.tipo='abarrotes' THEN pdesc.importe ELSE 0 END),0) AS abarrotes,
	       COALESCE(SUM(CASE WHEN pdesc.tipo='adelanto'  THEN pdesc.importe ELSE 0 END),0) AS adelanto,
	       COALESCE(SUM(CASE WHEN pdesc.tipo='falta'     THEN pdesc.importe ELSE 0 END),0) AS falta,
	       COALESCE(SUM(CASE WHEN pdesc.tipo='prestamo'  THEN pdesc.importe ELSE 0 END),0) AS prestamo
	FROM planilla_detalle pd
	INNER JOIN empleados e ON e.id_empleado = pd.id_empleado
	LEFT JOIN planilla_descuentos pdesc ON pdesc.id_detalle = pd.id_detalle
	WHERE pd.id_periodo = $id_periodo
	GROUP BY pd.id_detalle
	ORDER BY e.nombres, e.apellidos
");

$datos = '';
$gran_total = 0;
if ($r) {
	while ($value = $r->fetch_assoc()) {
		$sueldo = round((float)$value['sueldo_periodo'], 2);
		$descuentos = round((float)$value['tardanza'] + (float)$value['abarrotes'] + (float)$value['adelanto'] + (float)$value['falta'] + (float)$value['prestamo'], 2);
		$total = round($sueldo - $descuentos, 2);
		$gran_total += $total;

		$datos .= '<tr>
			<td>' . htmlspecialchars($value['nombres'] . ' ' . $value['apellidos']) . '</td>
			<td>' . htmlspecialchars($value['cargo']) . '</td>
			<td>S/ ' . number_format($sueldo,2) . '</td>
			<td>S/ ' . number_format((float)$value['tardanza'],2) . '</td>
			<td>S/ ' . number_format((float)$value['abarrotes'],2) . '</td>
			<td>S/ ' . number_format((float)$value['adelanto'],2) . '</td>
			<td>S/ ' . number_format((float)$value['falta'],2) . '</td>
			<td>S/ ' . number_format((float)$value['prestamo'],2) . '</td>
			<td><strong>S/ ' . number_format($total,2) . '</strong></td>
			<td><a class="btn btn-custom btn-sm" href="ver_planilla_detalle.php?id_detalle=' . $value['id_detalle'] . '"><i class="fas fa-list"></i> Detalle</a></td>
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
					<h3>Planilla: <?php echo date('d/m/Y', strtotime($periodo['fecha_inicio'])) . ' - ' . date('d/m/Y', strtotime($periodo['fecha_fin'])); ?>
					<small>(<?php echo $periodo['dias']; ?> días, <?php echo ucfirst($periodo['estado']); ?>)</small>
					<a href="planillas.php" class="btn btn-default btn-sm pull-right">Volver</a>
					<a href="ver_asistencias.php?fechaini=<?php echo $periodo['fecha_inicio']; ?>&fechafin=<?php echo $periodo['fecha_fin']; ?>" class="btn btn-default btn-sm pull-right" style="margin-right:8px"><i class="fas fa-clipboard-list"></i> Ver asistencias del periodo</a>
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
											    			<th>Colaborador</th>
											    			<th>Cargo</th>
											    			<th>Sueldo periodo</th>
											    			<th>Tardanzas</th>
											    			<th>Abarrotes</th>
											    			<th>Adelantos</th>
											    			<th>Faltas</th>
											    			<th>Préstamos</th>
											    			<th>Total a pagar</th>
											    			<th>Opciones</th>
											    		</tr>
											    	</thead>
											    	<tbody>
											    		<?php echo $datos; ?>
											    	</tbody>
											    	<tfoot>
											    		<tr>
											    			<th colspan="8" class="text-right">TOTAL A PAGAR</th>
											    			<th colspan="2">S/ <?php echo number_format($gran_total,2); ?></th>
											    		</tr>
											    	</tfoot>
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
		$('#datos').DataTable({ order: [], paging: false, info: false });
	});
	</script>
</body>
</html>
