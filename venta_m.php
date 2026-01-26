<?php
include('inc/control.php');
$fecha = date('d-m-Y');
$newDate = date("Y-m-d", strtotime($fecha));
//obtnemos colaboradores
include('inc/sdba/sdba.php'); // include main file
$clientes = Sdba::table('clientes');
$el = $clientes->get();
foreach ($el as $value) {
	$emplel.='<option value="'.$value['id_cliente'].'">'.$value['cliente'].'</option>';
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
	        <?php menu('4'); ?>
	      </div>
	      	<div class="submenu">
		      	<ul class="subtop-tabs">
		      		<li class="active">
		      			<a href="venta_m.php">Registrar venta</a>
		      		</li>
		      		<li >
		      			<a href="ventas_m.php">Listar ventas</a>
		      		</li>
		      	</ul>
		    </div>
	    </nav>
		<div class="kbg">
			<form id="venta">
				<div class="cuerpo">
					<div class="titulo">
						<h3>Venta</h3>
					</div>
					<div class="container-fluid">
						
						<div class="kdashboard" style="padding:0;">
							
							<div class="panel panel-default pa">
								<div class="panel-body">
								    	<div class="row">
								    		<div class="col-md-12">
								    			<div class="form-group">
												    <label for="exampleInputPassword1">Fecha</label>
												    <input type="date" class="form-control" name="fecha" id="fecha" value="<?php echo $newDate; ?>" placeholder="monto">
												 </div>
								    		</div>
								    		<div class="col-md-12">
								    			<label for="exampleInputPassword1">Cliente</label>
												    <select class="form-control" name="cliente">
												    	<?php echo $emplel; ?>
												    </select>
								    		</div>
								    		<div class="col-md-12">
								    			<label for="exampleInputPassword1">Tipo</label>
												    <select class="form-control" name="tipo">
												    	<option value="1">Contado</option>
												    	<option value="2">Credito</option>
												    </select>
								    		</div>
								    		<div class="col-md-12">
								    			<label for="exampleInputPassword1">Forma</label>
												    <select class="form-control" name="forma">
												    	<option value="1">Efectivo</option>
												    	<option value="2">Tar. Debito</option>
												    	<option value="3">Tar. Credito</option>
												    </select>
								    		</div>
								    		<div class="col-md-12">
								    			<h3 class="text-center">Items</h3>
								    			<div class="form-group text-center">
								    				<div class="row">
								    					<div class="col-xs-12 pa5r"><input class="form-control input-lg" name="producto" placeholder="producto" class="" id="basics" /></div>
								    					<div class="col-xs-6 pa5"><input class="form-control input-lg" type="number" name="precio" id="precio" placeholder="precio" ></div>
								    					<div class="col-xs-6 pa5"><input class="form-control input-lg" type="number" name="cantidad" id="cantidad" placeholder="cantidad" requiered ></div>
								    					<div class="col-xs-12"><br>
								    						<button id="add" type="button" class="btn btn-success btn-block"><i class="fa fa-plus" aria-hidden="true"></i> Agregar</button>
								    					</div>
								    				</div>
													
													
													
													<input type="number" name="id_p" id="id_p">
													
												</div>
								    		</div>
								    	</div class="row">
								    	<div class="row">
								    		<div class="col-sm-2"></div>
								    		<div class="col-md-12">	
								    			<div class="">
											    	<table id="items" class="table table-striped table-condensed">
											    		<thead>
											    			<tr>
											    				<th>Cantidad</th>
											    				<th>Producto</th>
											    				<th>Total</th>
											    				<th>Tipo</th>
											    				<th>Borrar</th>
											    			</tr>
											    		</thead>
														
														<tbody>
															<tr></tr>
															
														</tbody>
													</table>
												</div>
													<div class="text-right">
														<strong>Total: S/ </strong><input id="total" name="total" type="text" id="total">
													</div>
											</div>
											<div class="col-sm-2"></div>
								    	
									    </div>
									  <button type="button" id="guardar_venta" class="btn btn-success btn-block btn-lg">Registrar</button>
								</div>
							</div>
						</div>
							
					</div>
				</div>

			</form>
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
				var cantidad = $( "#cantidad" ).val();
				var tipo = $("#tipo").val();
				var id_p = $( "#id_p" ).val();
				var monto = (cantidad * precio).toFixed(2);
				var tipot = 'Normal'
				// if (tipo=='1') {
				total = monto*1 + total*1;
				// 	tipot = 'Normal';
				// }
				// else{
				// 	tipot = 'Gratis';
				// }
				//alert (total);
		       	$('#items tr:last').after('<tr class="child"><td width="15%">'+cantidad+'<input type="hidden" value="'+cantidad+'" name="cantidad[]"></td> <td width="50%"> '+plato+' <input type="hidden" value="'+plato+'" name="plato[]"></td><td>'+monto+' <input type="hidden" value="'+id_p+'" name="id_pro[]" ><input type="hidden" value="'+monto+'" name="total_pre[]" ></td><td>'+tipot+' <input type="hidden" value="'+tipo+'" name="tipo[]" ><input type="hidden" value="'+precio+'" name="precio[]"></td><td><button value="'+monto+'" class="borrar"><i class="fa fa-trash" aria-hidden="true"></i></button></td></tr>');
		       	$("#basics").val("");
		       	$("#precio").val("");
		       	$("#cantidad").val("");
		       	$("#total").val(total);
		    });
		    $('#btndescuento').click(function() {
		    	var descuento = $('#descuento').val();
		    	var total1 = $("#total").val();
		    	var ntotal = total1*descuento/100;
		    	var ttotal = total1 - ntotal;
		    	$("#total").val(ttotal);

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
		    $('#basics').on('keyup keypress blur change', function(){
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
					url: "/inc/registrar_venta.php",
					data: str2,
					success: function(response){
						console.log(response);
						alert(response);

						if(response.respuesta == false){
							swal('Advertencia',response.mensaje,'warning');
							


						}else{

							swal('Perfecto', response.venta_id,'success');
							//var id_venta = response.id_venta;
							console.log(response.mesa);
							//$('#mostrarmesa').load('inc/mobile/ver_mesa.php?mesa='+ response.mesa);
							document.location.href = "ver_venta.php?id="+response.venta_id;
						
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