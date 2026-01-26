<?php
include('inc/control.php');
if ($_SESSION['type']=='operador') {
	header("Location: dashboard.php");
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
															<div class="form-group">
															    <label for="exampleInputPassword1">Tienda</label>
															    <select name="ubicacion" class="form-control">
															    	<option value="1">Chimbote 1</option>
															    	<option value="2">Chimbote 2</option>
															    	<option value="3">Trujillo</option>
															    </select>
															</div>
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