<?php
include('inc/control.php');
if ($_SESSION['type']=='operador') {
	header("Location: dashboard.php");
}

$id_detalle = intval($_GET['id_detalle'] ?? 0);

$conn = new mysqli('localhost', 'admin_renacer', 'ikm169uhn', 'admin_renacer');
$conn->set_charset('utf8');

$r = $conn->query("
	SELECT pd.id_detalle, pd.id_periodo, pd.sueldo_periodo, pd.calculo_diario,
	       e.nombres, e.apellidos, e.cargo,
	       pp.fecha_inicio, pp.fecha_fin, pp.dias, pp.estado
	FROM planilla_detalle pd
	INNER JOIN empleados e ON e.id_empleado = pd.id_empleado
	INNER JOIN planilla_periodos pp ON pp.id_periodo = pd.id_periodo
	WHERE pd.id_detalle = $id_detalle
");
$det = $r ? $r->fetch_assoc() : null;

if (!$det) {
	echo 'Registro no encontrado. <a href="planillas.php">Volver</a>';
	exit;
}

$tipo_label = [
	'tardanza'  => 'Tardanza',
	'abarrotes' => 'Abarrotes',
	'adelanto'  => 'Adelanto',
	'falta'     => 'Día faltado',
	'prestamo'  => 'Préstamo',
	'afp'       => 'AFP',
];

$rd = $conn->query("SELECT id_descuento, tipo, fecha, importe, descripcion FROM planilla_descuentos WHERE id_detalle = $id_detalle ORDER BY fecha, id_descuento");

$filas = '';
$total_descuentos = 0;
if ($rd) {
	while ($d = $rd->fetch_assoc()) {
		$total_descuentos += (float) $d['importe'];
		$filas .= '<tr>
			<td>' . date('d/m/Y', strtotime($d['fecha'])) . '</td>
			<td>' . ($tipo_label[$d['tipo']] ?? $d['tipo']) . '</td>
			<td>S/ ' . number_format((float)$d['importe'],2) . '</td>
			<td>' . htmlspecialchars($d['descripcion']) . '</td>
			<td><button class="btn-custom btn-borrar-descuento" data-id="' . $d['id_descuento'] . '"><img src="assets/img/trash.png" /></button></td>
		</tr>';
	}
}
$sueldo_periodo = round((float)$det['sueldo_periodo'], 2);
$total_descuentos = round($total_descuentos, 2);
$total_pagar = round($sueldo_periodo - $total_descuentos, 2);
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
	      		<li>
	      			<a class="" href="asistencia.php">Asistencia</a>
	      		</li>
	      		<li>
	      			<a class="" href="descansos.php">Descansos</a>
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
			<div class="cuerpo-full">
				<div class="titulo">
					<h3><?php echo htmlspecialchars($det['nombres'] . ' ' . $det['apellidos']); ?>
					<small><?php echo htmlspecialchars($det['cargo']); ?> — <?php echo date('d/m/Y', strtotime($det['fecha_inicio'])) . ' al ' . date('d/m/Y', strtotime($det['fecha_fin'])); ?></small>
					<a href="ver_planilla.php?id=<?php echo $det['id_periodo']; ?>" class="btn btn-default btn-sm pull-right">Volver</a>
					</h3>
				</div>
				<div class="container-fluid">
					<div class="row">
						<div class="col-md-12">
							<div class="kdashboard">
								<div class="row">
									<div class="col-md-4">
										<div class="panel panel-default pa">
											<div class="panel-body">
												<p>Sueldo del periodo (<?php echo $det['dias']; ?> días)</p>
												<h4>S/ <?php echo number_format($sueldo_periodo,2); ?></h4>
											</div>
										</div>
									</div>
									<div class="col-md-4">
										<div class="panel panel-default pa">
											<div class="panel-body">
												<p>Total descuentos</p>
												<h4>S/ <?php echo number_format($total_descuentos,2); ?></h4>
											</div>
										</div>
									</div>
									<div class="col-md-4">
										<div class="panel panel-default pa">
											<div class="panel-body">
												<p>Total a pagar</p>
												<h4><strong>S/ <?php echo number_format($total_pagar,2); ?></strong></h4>
											</div>
										</div>
									</div>
								</div>
								<div class="row">
									<div class="col-md-12">
										<div class="panel panel-default pa">
											<div class="panel-body table-responsive">
												<button type="button" id="agregar_descuento" class="btn btn-success btn-sm">Agregar descuento</button>
												<br><br>
											    <table id="datos" class="table table-hover">
											    	<thead>
											    		<tr>
											    			<th>Fecha</th>
											    			<th>Tipo</th>
											    			<th>Importe</th>
											    			<th>Descripción</th>
											    			<th>Opciones</th>
											    		</tr>
											    	</thead>
											    	<tbody>
											    		<?php echo $filas; ?>
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
	<script src="https://cdnjs.cloudflare.com/ajax/libs/limonte-sweetalert2/10.5.0/sweetalert2.min.js" integrity="sha512-V9JHp52ZkrbVVjJqNz/XXYMUOyUfzaGKEGrcD2Ual7n39+UR1yJK0numAHZqkhhGTAH/Klj0KUe4btAZXccw9w==" crossorigin="anonymous"></script>
	<script>
	$(document).ready(function() {

		$('#agregar_descuento').on('click', function() {
			Swal.fire({
				title: 'Agregar descuento',
				html:
					'<div style="text-align:left">' +
					'<label style="font-size:12px">Tipo</label>' +
					'<select id="swal-tipo" class="swal2-input">' +
					'<option value="abarrotes">Abarrotes</option>' +
					'<option value="adelanto">Adelanto</option>' +
					'<option value="falta">Día faltado</option>' +
					'<option value="tardanza">Tardanza</option>' +
					'<option value="prestamo">Préstamo</option>' +
					'<option value="afp">AFP</option>' +
					'</select>' +
					'<label style="font-size:12px">Fecha</label>' +
					'<input id="swal-fecha" type="date" class="swal2-input" value="' + new Date().toISOString().slice(0,10) + '">' +
					'<label style="font-size:12px">Importe (S/)</label>' +
					'<input id="swal-importe" type="number" step="0.01" min="0.01" class="swal2-input">' +
					'<label style="font-size:12px">Descripción (opcional)</label>' +
					'<input id="swal-descripcion" type="text" class="swal2-input">' +
					'</div>',
				showCancelButton: true,
				confirmButtonText: 'Agregar',
				cancelButtonText: 'Cancelar',
				preConfirm: function() {
					var importe = parseFloat(document.getElementById('swal-importe').value);
					var fecha = document.getElementById('swal-fecha').value;
					if (!fecha || !importe || importe <= 0) {
						Swal.showValidationMessage('Ingresa una fecha y un importe válido');
						return false;
					}
					return {
						tipo: document.getElementById('swal-tipo').value,
						fecha: fecha,
						importe: importe,
						descripcion: document.getElementById('swal-descripcion').value
					};
				}
			}).then(function(result) {
				if (!result.isConfirmed) return;
				$.ajax({
					type: 'POST',
					dataType: 'json',
					url: 'inc/registrar_planilla_descuento.php',
					data: {
						id_detalle: <?php echo $id_detalle; ?>,
						tipo: result.value.tipo,
						fecha: result.value.fecha,
						importe: result.value.importe,
						descripcion: result.value.descripcion
					},
					success: function(data) {
						if (data.ok) {
							document.location.reload();
						} else {
							Swal.fire('Advertencia', data.mensaje || 'No se pudo agregar el descuento', 'warning');
						}
					},
					error: function() {
						Swal.fire('Advertencia', 'Error general del sistema', 'warning');
					}
				});
			});
		});

		$('body').on('click', '.btn-borrar-descuento', function() {
			var id = $(this).data('id');
			Swal.fire({
				title: 'Seguro de borrar?',
				text: 'No podrás revertir esto',
				icon: 'warning',
				showCancelButton: true,
				confirmButtonColor: '#3085d6',
				cancelButtonColor: '#d33',
				confirmButtonText: 'Si, borrar!'
			}).then(function(result) {
				if (result.isConfirmed) {
					$.ajax({
						type: 'GET',
						dataType: 'json',
						url: 'inc/borrar_planilla_descuento.php',
						data: { id: id },
						success: function(data) {
							if (data.respuesta) {
								document.location.reload();
							} else {
								Swal.fire('Advertencia', data.mensaje || 'No se pudo borrar el descuento', 'warning');
							}
						}
					});
				}
			});
		});

	});
	</script>
</body>
</html>
