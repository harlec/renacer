<?php
include('inc/control.php');
if ($_SESSION['type']=='operador') {
	header("Location: dashboard.php");
}

$mes = isset($_GET['mes']) && preg_match('/^\d{4}-\d{2}$/', $_GET['mes']) ? $_GET['mes'] : date('Y-m');
$id_empleado_sel = isset($_GET['id_empleado']) ? intval($_GET['id_empleado']) : 0;

$conn = new mysqli('localhost', 'admin_renacer', 'ikm169uhn', 'admin_renacer');
$conn->set_charset('utf8');

$empleados_rows = $conn->query("SELECT id_empleado, nombres, apellidos, cargo FROM empleados WHERE estado = '1' ORDER BY nombres, apellidos");
$empleados_opciones = '<option value="">-- elegir colaborador --</option>';
if ($empleados_rows) {
	while ($e = $empleados_rows->fetch_assoc()) {
		$sel = ($e['id_empleado'] == $id_empleado_sel) ? 'selected' : '';
		$label = $e['nombres'] . ' ' . $e['apellidos'] . ($e['cargo'] ? ' (' . $e['cargo'] . ')' : '');
		$empleados_opciones .= '<option value="' . $e['id_empleado'] . '" ' . $sel . '>' . htmlspecialchars($label) . '</option>';
	}
}

list($anio, $mesNum) = explode('-', $mes);
$dias_en_mes = (int) date('t', strtotime("$anio-$mesNum-01"));
$dia_semana_inicio = (int) date('N', strtotime("$anio-$mesNum-01")); // 1=Lunes ... 7=Domingo

$dias_marcados = [];
if ($id_empleado_sel > 0) {
	$fecha_ini = "$anio-$mesNum-01";
	$fecha_fin = "$anio-$mesNum-" . str_pad((string)$dias_en_mes, 2, '0', STR_PAD_LEFT);
	$rd = $conn->query("SELECT fecha FROM empleado_descansos WHERE id_empleado = $id_empleado_sel AND fecha BETWEEN '$fecha_ini' AND '$fecha_fin'");
	if ($rd) {
		while ($d = $rd->fetch_assoc()) {
			$dias_marcados[] = (int) date('j', strtotime($d['fecha']));
		}
	}
}

$celdas = '';
for ($i = 1; $i < $dia_semana_inicio; $i++) {
	$celdas .= '<div class="cal-celda cal-vacia"></div>';
}
for ($dia = 1; $dia <= $dias_en_mes; $dia++) {
	$checked = in_array($dia, $dias_marcados) ? 'checked' : '';
	$activa  = $checked ? 'cal-activa' : '';
	$celdas .= '<label class="cal-celda ' . $activa . '">
			<input type="checkbox" class="cal-check" value="' . $dia . '" ' . $checked . '>
			<span>' . $dia . '</span>
		</label>';
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
    <style>
        .cal-grid {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 6px;
        }
        .cal-header {
            text-align: center;
            font-weight: bold;
            font-size: 12px;
            color: #777;
            text-transform: uppercase;
        }
        .cal-celda {
            display: flex;
            align-items: center;
            justify-content: center;
            height: 56px;
            border: 1px solid #e3e3e3;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            position: relative;
            background: #fff;
            user-select: none;
        }
        .cal-celda.cal-vacia {
            border: none;
            cursor: default;
        }
        .cal-celda.cal-activa {
            background: #dff0d8;
            border-color: #3c763d;
            color: #3c763d;
            font-weight: bold;
        }
        .cal-celda input {
            position: absolute;
            opacity: 0;
        }
        #contador-descansos.cnt-ok { color: #3c763d; }
        #contador-descansos.cnt-warn { color: #8a6d3b; }
    </style>
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
					<h3>Días de descanso</h3>
				</div>
				<div class="container-fluid">
					<div class="row">
						<div class="col-md-12">
							<div class="kdashboard">
								<div class="panel panel-default pa">
									<div class="panel-body">
										<p class="help-block" style="margin-top:0">
											Marca en el calendario los días de descanso del colaborador (normalmente 2 o 3 al mes, alternando).
											Esos días se bloquean en <a href="asistencia.php">Asistencia</a> y nunca se cuentan como falta, para que no afecten su pago.
										</p>
										<div class="row">
											<div class="col-sm-5">
												<div class="form-group">
													<label>Colaborador</label>
													<select class="form-control" id="sel-empleado">
														<?php echo $empleados_opciones; ?>
													</select>
												</div>
											</div>
											<div class="col-sm-3">
												<div class="form-group">
													<label>Mes</label>
													<input type="month" class="form-control" id="sel-mes" value="<?php echo $mes; ?>">
												</div>
											</div>
										</div>
									</div>
								</div>

								<?php if ($id_empleado_sel > 0): ?>
								<div class="panel panel-default pa">
									<div class="panel-body">
										<p>
											<strong id="contador-descansos" class="cnt-warn">0 descansos seleccionados</strong>
											<span class="text-muted"> (recomendado: 2 a 3 por mes)</span>
										</p>
										<div class="cal-grid">
											<div class="cal-header">Lun</div>
											<div class="cal-header">Mar</div>
											<div class="cal-header">Mié</div>
											<div class="cal-header">Jue</div>
											<div class="cal-header">Vie</div>
											<div class="cal-header">Sáb</div>
											<div class="cal-header">Dom</div>
											<?php echo $celdas; ?>
										</div>
										<br>
										<button type="button" id="guardar_descansos" class="btn btn-success btn-lg">Guardar descansos</button>
									</div>
								</div>
								<?php else: ?>
								<div class="panel panel-default pa">
									<div class="panel-body text-center text-muted">
										Elige un colaborador para ver y marcar su calendario de descansos.
									</div>
								</div>
								<?php endif; ?>
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

		function irA() {
			var idEmpleado = $('#sel-empleado').val();
			var mes = $('#sel-mes').val();
			if (!idEmpleado) return;
			document.location.href = 'descansos.php?id_empleado=' + idEmpleado + '&mes=' + mes;
		}

		$('#sel-empleado').on('change', irA);
		$('#sel-mes').on('change', irA);

		function actualizarContador() {
			var n = $('.cal-check:checked').length;
			var $c = $('#contador-descansos');
			$c.text(n + (n == 1 ? ' descanso seleccionado' : ' descansos seleccionados'));
			$c.removeClass('cnt-ok cnt-warn');
			$c.addClass((n >= 2 && n <= 3) ? 'cnt-ok' : 'cnt-warn');
		}

		$('.cal-check').on('change', function() {
			$(this).closest('.cal-celda').toggleClass('cal-activa', $(this).prop('checked'));
			actualizarContador();
		});

		actualizarContador();

		$('#guardar_descansos').on('click', function() {
			var idEmpleado = $('#sel-empleado').val();
			var mes = $('#sel-mes').val();
			var dias = [];
			$('.cal-check:checked').each(function() { dias.push($(this).val()); });

			$.ajax({
				type: 'POST',
				dataType: 'json',
				url: 'inc/registrar_descanso.php',
				data: { id_empleado: idEmpleado, mes: mes, dias: dias },
				success: function(data) {
					if (data.ok) {
						Swal.fire('Listo', 'Descansos guardados', 'success').then(function() {
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
