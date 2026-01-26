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
	      		<li class="active">
	      			<a class="" href="agregar_usuario.php">Registrar usuario</a>
	      		</li>
	      		<li >
	      			<a class="" href="ver_usuarios.php">Listar usuarios</a>
	      		</li>
	      		<li >
	      			<a class="" href="agregar_empleado.php">Agregar colaborador</a>
	      		</li>
	      		<li >
	      			<a class="" href="ver_empleados.php">Listar colaboradores</a>
	      		</li>
	      	</ul>
	      </div>
	    </nav>
		<div class="kbg">
			<div class="cuerpofull">
				<div class="titulo">
					<h3>Registrar usuario</h3>
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
															    <label for="exampleInputPassword1">Nombres</label>
															    <input type="text" class="form-control" name="nombre" id="nombre" placeholder="Nombre">
															</div>
															<div class="form-group">
															    <label for="exampleInputPassword1">Usuario</label>
															    <input type="text" class="form-control" name="usuario" id="usuario" placeholder="Usuario">
															</div>
															<div class="form-group">
															    <label for="exampleInputPassword1">Password</label>
															    <input type="password" class="form-control" name="password" id="password" placeholder="password">
															</div>
															<div class="form-group">
															    <label for="exampleInputPassword1">Rol</label>
															    <select class="form-control" name="rol" id="rol">
															    	<option value="admin">administrador</option>
															    	<option value="operador">vendedor</option>
															    </select>
															</div>
															<!-- <div class="form-group">
															    <label for="exampleInputPassword1">Tienda</label>
															    <select name="tienda" class="form-control">
															    	<option value="1">Tienda 1</option>
															    	<option value="2">Tienda 2</option>
															    	<option value="3">Tienda 3</option>
															    </select>
															</div> -->
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

		var total = 0;
			$('#add').click(function() {
				var plato = $( "#basics" ).val();
				var precio = $('#precio').val();
				var monto = $( "#cantidad" ).val();
				var id_p = $( "#id_p" ).val();
				var cantidad = (monto / precio).toFixed(2);
				total = monto*1 + total*1;
				//alert (total);
		       	$('#items tr:last').after('<tr class="child"><td><input value="'+plato+'" name="plato[]"></td><td><input value="'+precio+'" name="precio[]"></td><td><input value="'+cantidad+'" name="cantidad[]"></td><td>'+monto+' <input type="hidden" value="'+id_p+'" name="id_pro[]" ><input type="hidden" value="'+monto+'" name="total_pre[]" ></td><td><button value="'+monto+'" class="borrar"><i class="fa fa-trash" aria-hidden="true"></i></button></td></tr>');
		       	$("#basics").val("");
		       	$("#precio").val("");
		       	$("#cantidad").val("");
		       	$("#total").val(total);
		    });

		    //borrar item
		    $("#items").on('click', '.borrar', function () {
			    //$(this).closest('tr').remove();
			    
			    var resta = $(this).val();
			    console.log(resta)
			    $(this).parents("tr").remove();
			    total = total - resta*1;
			    $("#total").val(total);
			});

	   		//autocompletamos el producto
		    $('#basics').autocomplete({
		      	source: function(request,response){
					var str = 'term='+request.term;
					//alert('entro');
					$.ajax({
							type:'GET',
							dataType: 'json',
							url: '/inc/autocomplete-producto.php',
							data: str,
							success: function(data){
								response(data);
								//$("#precio").val('12');
							}
					});
				}
				//minLength: 2
		    });

		    //obtenemos el precio
		    $('#basics').on('change paste keyup', function(){
		    	var str1 = 'producto='+$('#basics').val();
		    	//alert (str1);
		    	$.ajax({	
			    	type:'GET',
					dataType: 'json',
				  	url: '/inc/autocomplete-precio.php',
				  	data: str1,
				  	success: function(response) {
				   	 	$('#precio').val(response.precio);
				   	 	$('#id_p').val(response.id_p);
				   	 	
				  	}
				});
			});
		$('body').on('click',"#guardar_venta", function(e){
          e.preventDefault();

				
				//var tipoVenta = $('input:radio[name=pregunta]:checked').val();
				//DNI = $('#dni_ruc').val();

				var str2 = $('#venta').serialize();
				alert(str2);
				
				$.ajax({
					cache: false,
					type: "POST",
					dataType: "json",
					url: "/inc/registrar_usuario.php",
					data: str2,
					success: function(response){

						if(response.respuesta == false){
							swal('Advertencia',response.mensaje,'warning');
							


						}else{

							swal('Perfecto', response.idventa,'success');
							//var id_venta = response.id_venta;
							console.log(response.mesa);
							//$('#mostrarmesa').load('inc/mobile/ver_mesa.php?mesa='+ response.mesa);
							document.location.href = "ver_usuarios.php";
						
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