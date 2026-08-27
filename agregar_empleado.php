<?php
include('inc/control.php');
if ($_SESSION['type']=='operador') {
	header("Location: dashboard.php");
}

include('inc/sdba/sdba.php');
include('inc/config_facturacion.php');

$cargos_opciones = '';
$cargos = Sdba::table('cargos');
$cargos->where('estado', '1');
$cargos->order_by('nombre', 'asc');
foreach ($cargos->get() as $c) {
	$cargos_opciones .= '<option value="' . htmlspecialchars($c['nombre']) . '">' . htmlspecialchars($c['nombre']) . '</option>';
}

function rango_horario_general($ingreso, $salida) {
	$ingreso = $ingreso ? substr($ingreso, 0, 5) : '';
	$salida  = $salida  ? substr($salida, 0, 5)  : '';
	return ($ingreso && $salida) ? ($ingreso . ' - ' . $salida) : 'sin configurar';
}

$general_lv  = rango_horario_general(get_config('planilla_horario_lv_ingreso'), get_config('planilla_horario_lv_salida'));
$general_sab = rango_horario_general(get_config('planilla_horario_sab_ingreso'), get_config('planilla_horario_sab_salida'));
$general_dom = rango_horario_general(get_config('planilla_horario_dom_ingreso'), get_config('planilla_horario_dom_salida'));
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
											    	<div class="row">
											    		<div class="col-md-12">
											    			<div class="form-group">
															    <label for="exampleInputPassword1">Dni</label>
															    <input type="text" class="form-control" name="dni" id="dni" placeholder="">
															</div>
											    			<div class="form-group">
															    <label for="exampleInputPassword1">Nombres</label>
															    <input type="text" class="form-control" name="nombres" id="nombres" placeholder="">
															</div>
															<div class="form-group">
															    <label for="exampleInputPassword1">Apellidos</label>
															    <input type="text" class="form-control" name="apellidos" id="apellidos" placeholder="">
															</div>
															<div class="form-group">
															    <label for="exampleInputPassword1">Email</label>
															    <input type="text" class="form-control" name="email" id="email" placeholder="">
															</div>
															<div class="form-group">
															    <label for="exampleInputPassword1">Celular</label>
															    <input type="text" class="form-control" name="celular" id="celular" placeholder="">
															</div>
															<div class="form-group">
															    <label for="exampleInputPassword1">Dirección</label>
															    <input type="text" class="form-control" name="direccion" id="direccion" placeholder="">
															</div>
															<input type="hidden" name="ubicacion" value="1">
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
															    <input type="number" step="0.01" min="0" class="form-control" name="sueldo_mensual" id="sueldo_mensual" placeholder="0.00">
															</div>
															<p class="help-block" style="margin-bottom:4px">Horarios (opcionales): si se dejan vacíos, se usa el horario general de la empresa configurado en Config. Planillas.</p>
															<p class="help-block" style="margin-bottom:2px"><strong>Lunes a viernes</strong> <span class="text-muted">(general: <?php echo $general_lv; ?>)</span></p>
															<div class="row">
															    <div class="col-md-6">
																    <div class="form-group">
																	    <label for="hora_ingreso">Hora de ingreso</label>
																	    <input type="time" class="form-control" name="hora_ingreso" id="hora_ingreso">
																	</div>
															    </div>
															    <div class="col-md-6">
																    <div class="form-group">
																	    <label for="hora_salida">Hora de salida</label>
																	    <input type="time" class="form-control" name="hora_salida" id="hora_salida">
																	</div>
															    </div>
															</div>
															<p class="help-block" style="margin-bottom:2px"><strong>Sábado</strong> <span class="text-muted">(general: <?php echo $general_sab; ?>)</span></p>
															<div class="row">
															    <div class="col-md-6">
																    <div class="form-group">
																	    <label for="hora_ingreso_sab">Hora de ingreso</label>
																	    <input type="time" class="form-control" name="hora_ingreso_sab" id="hora_ingreso_sab">
																	</div>
															    </div>
															    <div class="col-md-6">
																    <div class="form-group">
																	    <label for="hora_salida_sab">Hora de salida</label>
																	    <input type="time" class="form-control" name="hora_salida_sab" id="hora_salida_sab">
																	</div>
															    </div>
															</div>
															<p class="help-block" style="margin-bottom:2px"><strong>Domingo</strong> <span class="text-muted">(general: <?php echo $general_dom; ?>)</span></p>
															<div class="row">
															    <div class="col-md-6">
																    <div class="form-group">
																	    <label for="hora_ingreso_dom">Hora de ingreso</label>
																	    <input type="time" class="form-control" name="hora_ingreso_dom" id="hora_ingreso_dom">
																	</div>
															    </div>
															    <div class="col-md-6">
																    <div class="form-group">
																	    <label for="hora_salida_dom">Hora de salida</label>
																	    <input type="time" class="form-control" name="hora_salida_dom" id="hora_salida_dom">
																	</div>
															    </div>
															</div>
															<p class="help-block">Forma de pago: <strong>Quincenal</strong> (cada 15 días)</p>
											    		</div>
											    	</div>

												  <button type="button" id="guardar_venta" class="btn btn-success btn-block btn-lg">Registrar</button>
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

		$('body').on('click',"#guardar_venta", function(e){
          e.preventDefault();

				var str2 = $('#venta').serialize();
				//alert(str2);
				
				$.ajax({
					cache: false,
					type: "POST",
					dataType: "json",
					url: "/inc/registrar_empleado.php",
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