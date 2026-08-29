<?php
include('inc/control.php');
if ($_SESSION['type']=='operador') {
	header("Location: dashboard.php");
}

$id = $_GET['id'];
include('inc/sdba/sdba.php'); // include main file
include('inc/config_facturacion.php');

$ventas = Sdba::table('empleados'); // creating table object
$ventas->where('id_empleado',$id);
$l = $ventas->get_one();

function rango_horario_general($ingreso, $salida) {
	$ingreso = $ingreso ? substr($ingreso, 0, 5) : '';
	$salida  = $salida  ? substr($salida, 0, 5)  : '';
	return ($ingreso && $salida) ? ($ingreso . ' - ' . $salida) : 'sin configurar';
}

$general_lv  = rango_horario_general(get_config('planilla_horario_lv_ingreso'), get_config('planilla_horario_lv_salida'));
$general_sab = rango_horario_general(get_config('planilla_horario_sab_ingreso'), get_config('planilla_horario_sab_salida'));
$general_dom = rango_horario_general(get_config('planilla_horario_dom_ingreso'), get_config('planilla_horario_dom_salida'));

$cargo_actual = $l['cargo'];
$cargo_actual_en_catalogo = false;
$cargos_opciones = '';
$cargos = Sdba::table('cargos');
$cargos->where('estado', '1');
$cargos->order_by('nombre', 'asc');
foreach ($cargos->get() as $c) {
	if ($c['nombre'] === $cargo_actual) $cargo_actual_en_catalogo = true;
	$sel = ($c['nombre'] === $cargo_actual) ? 'selected' : '';
	$cargos_opciones .= '<option value="' . htmlspecialchars($c['nombre']) . '" ' . $sel . '>' . htmlspecialchars($c['nombre']) . '</option>';
}
// El cargo actual del empleado puede ya no estar en el catálogo (se desactivó, o es texto
// libre de antes de este catálogo) — se agrega igual para no perderlo/blanquearlo al guardar.
if ($cargo_actual !== '' && !$cargo_actual_en_catalogo) {
	$cargos_opciones .= '<option value="' . htmlspecialchars($cargo_actual) . '" selected>' . htmlspecialchars($cargo_actual) . '</option>';
}

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
    <link rel="stylesheet" href="/assets/css/jquery-ui.min.css">
    <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.7.2/css/all.css" integrity="sha384-fnmOCqbTlWIlj8LyTjo7mOUStjsKC4pOpQbqyi7RrhN7udi9RwhKkMHpvLbHG9Sr" crossorigin="anonymous">
    <link rel="stylesheet" type="text/css" href="https://cdnjs.cloudflare.com/ajax/libs/limonte-sweetalert2/7.0.5/sweetalert2.min.css">
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
	      		<li >
	      			<a class="" href="ver_usuarios.php">Listar usuarios</a>
	      		</li>
	      		<li class="active">
	      			<a class="" href="agregar_empleado.php">Agregar colaborador</a>
	      		</li>
	      		<li >
	      			<a class="" href="ver_empleados.php">Listar colaboradores</a>
	      		</li>
	      		<li >
	      			<a class="" href="asistencia.php">Asistencia</a>
	      		</li>
	      		<li>
	      			<a class="" href="descansos.php">Descansos</a>
	      		</li>
	      			      		<li >
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
					<h3>Registrar Colaborador</h3>
				</div>
				<div class="container-fluid">
					<div class="row">
						<div class="col-md-12">
							<div class="kdashboard">
								<div class="row">
									<div class="col-md-6">
										<div class="panel panel-default pa">
											<div class="panel-body">
											    <form id="venta">
											    	<input type="hidden" name="id" value="<?php echo $id; ?>">
											    	<div class="row">
											    		<div class="col-md-12">
											    			<div class="form-group">
															    <label for="exampleInputPassword1">Dni</label>
															    <input type="text" class="form-control" name="dni" id="dni" placeholder="" value="<?php echo $l['dni']; ?>">
															</div>
											    			<div class="form-group">
															    <label for="exampleInputPassword1">Nombres</label>
															    <input type="text" class="form-control" name="nombres" id="nombres" placeholder="" value="<?php echo $l['nombres']; ?>">
															</div>
															<div class="form-group">
															    <label for="exampleInputPassword1">Apellidos</label>
															    <input type="text" class="form-control" name="apellidos" id="apellidos" placeholder="" value="<?php echo $l['apellidos']; ?>">
															</div>
															<div class="form-group">
															    <label for="exampleInputPassword1">Email</label>
															    <input type="text" class="form-control" name="email" id="email" placeholder="" value="<?php echo $l['email']; ?>">
															</div>
															<div class="form-group">
															    <label for="exampleInputPassword1">Celular</label>
															    <input type="text" class="form-control" name="celular" id="celular" placeholder="" value="<?php echo $l['celular']; ?>">
															</div>
															<div class="form-group">
															    <label for="exampleInputPassword1">Dirección</label>
															    <input type="text" class="form-control" name="direccion" id="direccion" value="<?php echo $l['direccion']; ?>">
															</div>
															<input type="hidden" name="ubicacion" value="<?php echo htmlspecialchars($l['ubicacion']); ?>">
															<div class="form-group">
															    <label for="cargo">Cargo / Ocupación</label>
															    <select class="form-control" name="cargo" id="cargo">
															    	<option value="">-- elegir --</option>
															    	<?php echo $cargos_opciones; ?>
															    </select>
															    <p class="help-block" style="margin-bottom:0">¿No está el cargo que buscas? Agrégalo en <a href="configuracion_planillas.php">Config. planillas</a>.</p>
															</div>
															<div class="form-group">
															    <label for="sueldo_mensual">Sueldo mensual (S/)</label>
															    <input type="number" step="0.01" min="0" class="form-control" name="sueldo_mensual" id="sueldo_mensual" placeholder="0.00" value="<?php echo $l['sueldo_mensual']; ?>">
															</div>
															<div class="form-group">
															    <label>AFP</label><br>
															    <label class="radio-inline"><input type="radio" name="afp" value="1" <?php echo $l['afp'] == '1' ? 'checked' : ''; ?>> Sí</label>
															    <label class="radio-inline"><input type="radio" name="afp" value="0" <?php echo $l['afp'] == '1' ? '' : 'checked'; ?>> No</label>
															</div>
															<div class="form-group" id="grupo-afp-monto" style="<?php echo $l['afp'] == '1' ? '' : 'display:none'; ?>">
															    <label for="afp_monto_mensual">Monto de descuento mensual por AFP (S/)</label>
															    <input type="number" step="0.01" min="0" class="form-control" name="afp_monto_mensual" id="afp_monto_mensual" placeholder="0.00" value="<?php echo $l['afp_monto_mensual']; ?>">
															    <p class="help-block" style="margin-bottom:0">Se descuenta la mitad de este monto en cada quincena.</p>
															</div>
															<p class="help-block" style="margin-bottom:4px">Horarios (opcionales): si se dejan vacíos, se usa el horario general de la empresa configurado en Config. Planillas.</p>
															<p class="help-block" style="margin-bottom:2px"><strong>Lunes a viernes</strong> <span class="text-muted">(general: <?php echo $general_lv; ?>)</span></p>
															<div class="row">
															    <div class="col-md-6">
																    <div class="form-group">
																	    <label for="hora_ingreso">Hora de ingreso</label>
																	    <input type="time" class="form-control" name="hora_ingreso" id="hora_ingreso" value="<?php echo ($l['hora_ingreso'] && $l['hora_ingreso'] != '00:00:00') ? substr($l['hora_ingreso'],0,5) : ''; ?>">
																	</div>
															    </div>
															    <div class="col-md-6">
																    <div class="form-group">
																	    <label for="hora_salida">Hora de salida</label>
																	    <input type="time" class="form-control" name="hora_salida" id="hora_salida" value="<?php echo ($l['hora_salida'] && $l['hora_salida'] != '00:00:00') ? substr($l['hora_salida'],0,5) : ''; ?>">
																	</div>
															    </div>
															</div>
															<p class="help-block" style="margin-bottom:2px"><strong>Sábado</strong> <span class="text-muted">(general: <?php echo $general_sab; ?>)</span></p>
															<div class="row">
															    <div class="col-md-6">
																    <div class="form-group">
																	    <label for="hora_ingreso_sab">Hora de ingreso</label>
																	    <input type="time" class="form-control" name="hora_ingreso_sab" id="hora_ingreso_sab" value="<?php echo (!empty($l['hora_ingreso_sab']) && $l['hora_ingreso_sab'] != '00:00:00') ? substr($l['hora_ingreso_sab'],0,5) : ''; ?>">
																	</div>
															    </div>
															    <div class="col-md-6">
																    <div class="form-group">
																	    <label for="hora_salida_sab">Hora de salida</label>
																	    <input type="time" class="form-control" name="hora_salida_sab" id="hora_salida_sab" value="<?php echo (!empty($l['hora_salida_sab']) && $l['hora_salida_sab'] != '00:00:00') ? substr($l['hora_salida_sab'],0,5) : ''; ?>">
																	</div>
															    </div>
															</div>
															<p class="help-block" style="margin-bottom:2px"><strong>Domingo</strong> <span class="text-muted">(general: <?php echo $general_dom; ?>)</span></p>
															<div class="row">
															    <div class="col-md-6">
																    <div class="form-group">
																	    <label for="hora_ingreso_dom">Hora de ingreso</label>
																	    <input type="time" class="form-control" name="hora_ingreso_dom" id="hora_ingreso_dom" value="<?php echo (!empty($l['hora_ingreso_dom']) && $l['hora_ingreso_dom'] != '00:00:00') ? substr($l['hora_ingreso_dom'],0,5) : ''; ?>">
																	</div>
															    </div>
															    <div class="col-md-6">
																    <div class="form-group">
																	    <label for="hora_salida_dom">Hora de salida</label>
																	    <input type="time" class="form-control" name="hora_salida_dom" id="hora_salida_dom" value="<?php echo (!empty($l['hora_salida_dom']) && $l['hora_salida_dom'] != '00:00:00') ? substr($l['hora_salida_dom'],0,5) : ''; ?>">
																	</div>
															    </div>
															</div>
															<p class="help-block">Forma de pago: <strong>Quincenal</strong> (cada 15 días)</p>
											    		</div>
											    	</div>

												  <button type="button" id="guardar_venta" class="btn btn-success btn-block btn-lg">Editar</button>
												</form>
			
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
	<script src="/assets/js/jquery-ui.min.js"></script> 
	<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js"></script>
	<script src="https://cdn.jsdelivr.net/npm/jquery-validation@1.17.0/dist/jquery.validate.min.js"></script>
	<script src="https://cdnjs.cloudflare.com/ajax/libs/limonte-sweetalert2/7.0.5/sweetalert2.min.js"></script>
	<script >
	// A $( document ).ready() block.
	$(document ).ready(function() {

		$('input[name="afp"]').on('change', function() {
			$('#grupo-afp-monto').toggle($('input[name="afp"]:checked').val() == '1');
		});

		$('body').on('click',"#guardar_venta", function(e){
          e.preventDefault();

				var str2 = $('#venta').serialize();
				//alert(str2);
				
				$.ajax({
					cache: false,
					type: "POST",
					dataType: "json",
					url: "/inc/editar_empleado.php",
					data: str2,
					success: function(response){

						if(response.respuesta == false){
							swal('Advertencia',response.mensaje,'warning');
							


						}else{

							swal('Perfecto', response.idventa,'success');
							//var id_venta = response.id_venta;
							console.log(response.mesa);
							//$('#mostrarmesa').load('inc/mobile/ver_mesa.php?mesa='+ response.mesa);
							document.location.href = "ver_empleados.php";
						
						}
					
					},
					error: function(){
						swal('Advertencia','Error General del Sistema','warning');
					}
				});
				
				//$(this ).hide();
				//return false;

			
		});

		
	    console.log( "ready!" );
	});
		
	</script>
</body>
</html>