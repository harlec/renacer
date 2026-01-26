<?php
include('inc/control.php');
if ($_SESSION['type']=='operador') {
	header("Location: dashboard.php");
}
include('inc/sdba/sdba.php'); 
$unidades = Sdba::table('unidades');
$unidades->where('estado','1');
$unidades_list = $unidades->get();
$unidades_l='';
foreach ($unidades_list as $v) {
	$select = '';
	if($v['id_unidad']=='3'){$select="selected";}
	$unidades_l .='<option '.$select.' value="'.$v['id_unidad'].'">'.$v['nombre'].'</option>';
}

$categorias = Sdba::table('categorias');
//$categorias->where('estado','1');
$categorias_list = $categorias->get();
$categorias_l='';
foreach ($categorias_list as $value1) {
	$categorias_l .='<option value="'.$value1['id_categoria'].'">'.$value1['nom_cat'].'</option>';
}

//variantes
$variantes = Sdba::table('variantes');
$variantes_list = $variantes->get();
$variantes_l = '';
foreach ($variantes_list as $vl) {
	$variantes_l .='<option value="'.$vl['id_variante'].'">'.$vl['variante'].'</option>';
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
    <link rel="stylesheet" type="text/css" href="/assets/css/select2.min.css">
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
	        <?php menu('3'); ?>
	      </div>
	      <div class="submenu">
	      	<ul class="subtop-tabs">
	      		<li class="active">
	      			<a href="agregar_producto.php">Registrar producto</a>
	      		</li>
	      		<li >
	      			<a href="ver_productos.php">Listar productos</a>
	      		</li>
	      		<li >
	      			<a href="categorias.php">Categorías</a>
	      		</li>
	      		<li >
	      			<a href="variantes.php">Variantes</a>
	      		</li>
	      	</ul>
	      </div>
	    </nav>
		<div class="kbg">
			<div class="cuerpofull">
				<div class="titulo">
					<h3>Registrar producto</h3>
				</div>
				<div class="container-fluid">
					<div class="row">
						<div class="col-md-12">
							<div class="kdashboard">
								<div class="row">
									<div class="col-md-12">
										<div class="panel panel-default pa">
											<div class="panel-body">
											    <form id="venta">
											    	<div class="row">
											    		<div class="col-md-6">
											    			<div class="form-group">
															    <label for="exampleInputPassword1">Nombre</label>
															    <input type="text" class="form-control" name="nombre" id="nombre" placeholder="Nombre">
															</div>
															<!-- <div class="form-group">
															    <label for="exampleInputPassword1">Código</label>
															    <input type="text" class="form-control" name="codigo" id="codigo" placeholder="Codigo interno">
															</div> -->
															<div style="" class="form-group">
															    <label for="exampleInputPassword1">Unidad</label>
															    <select name="unidad" id="unidad" class="form-control">
															    	<?php echo $unidades_l; ?>
															    </select>
															</div>
															<div class="form-group">
															    <label for="exampleInputPassword1">Categoría</label>
															    <select name="categoria" id="categoria" class="form-control">
															    	<?php echo $categorias_l; ?>
															    </select>
															</div>
															<div class="form-group">
															    <label for="exampleInputPassword1">Precio venta</label>
															    <input type="text" class="form-control" name="precio_v" id="precio_v" placeholder="Precio venta">
															</div>
															<!-- <div class="form-group">
															    <label for="exampleInputPassword1">Exonerado Igv</label>
															    <select name="exonerada" id="exonerada" class="form-control">
															    	<option value="no">No</option>
															    	<option value="si">Si</option>
															    </select>
															</div> -->
															<div class="form-group">
															    <label for="exampleInputPassword1">Stock</label>
															    <input type="text" class="form-control" name="stock" id="stock" placeholder="Stock">
															</div>
											    		</div>
											    		<div class="col-md-6">
											    			<h3>Opciones adicionales</h3>
											    			<h4>Variantes de precios</h4> 
											    			<table class="table">
											    			<tr>
									    						<td width="40%"><select style="width: 50%" id="vari"><?= $variantes_l; ?><</select></td>
									    						<td><input name="conatidad" id="cantidad" placeholder="cantidad" type="text"></td>
									    						<td><input name´="precio" id="precio" placeholder="precio" type="text"></td>
									    						<td><input type="button" class="btn  btn-success" id="add"value="+" /></td>
									    					</tr>
											    			</table>
											    			<table id="variantes" class="table">
											    				<thead>
											    					<tr>
											    						<th>Variante</th>
											    						<th>Cantidad</th>
											    						<th>Precio</th>
											    					</tr>
											    				</thead>
											    				<tbody>
											    					
											    				</tbody>
											    			</table>
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
	 	<!-- Tab panes -->
		

	  
	<!-- jQuery (necessary for Bootstrap's JavaScript plugins) -->
	<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.3.1/jquery.min.js"></script>
	<script src="/assets/js/jquery-ui.min.js"></script> 
	<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js"></script>
	<script src="https://cdn.jsdelivr.net/npm/jquery-validation@1.17.0/dist/jquery.validate.min.js"></script>
	<script src="https://cdnjs.cloudflare.com/ajax/libs/limonte-sweetalert2/7.0.5/sweetalert2.min.js"></script>
	<script src="/assets/js/select2.full.min.js"></script>
	<script >
	// A $( document ).ready() block.
	$(document ).ready(function() {

		$('#categoria').select2();
		$('#unidad').select2();
		$('#vari').select2();

		var total = 0;
			$('#add').click(function() {
				var variante = $('#vari').val();
				var variante_text =  $("#vari option:selected").text();
				var cantidad = $('#cantidad').val();
				var precio = $('#precio').val();
				//alert(variante_text);
				$('#variantes').find('tbody').append('<tr><td><input type="hidden" name="variante[]" value="'+variante+'"><input type="text" value="'+variante_text+'"></td><td><input type="text" name="cantidadv[]" value="'+cantidad+'"></td><td><input type="text" name="preciov[]" value="'+precio+'"></td><td><button value="" class="borrar">x</button></td></tr>');
				$('#cantidad').val('');
				$('#precio').val('');
		    });

		    //borrar item
		    $("#variantes").on('click', '.borrar', function () {
			    //$(this).closest('tr').remove();
			    
			    // var resta = $(this).val();
			    // console.log(resta)
			    $(this).parents("tr").remove();
			   /* total = total - resta*1;
			    $("#total").val(total);*/
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
				//alert(str2);
				
				$.ajax({
					cache: false,
					type: "POST",
					dataType: "json",
					url: "/inc/registrar_producto.php",
					data: str2,
					success: function(response){

						if(response.respuesta == false){
							swal('Advertencia',response.mensaje,'warning');
							


						}else{

							swal('Perfecto', response.idventa,'success');
							//var id_venta = response.id_venta;
							console.log(response.mesa);
							//$('#mostrarmesa').load('inc/mobile/ver_mesa.php?mesa='+ response.mesa);
							document.location.href = "ver_productos.php";
						
						}
					
					},
					error: function(){
						swal('Advertencia','Error General del Sistema','warning');
					}
				});
				
				$(this ).hide();
				//return false;

			
		});

		
	    console.log( "ready!" );
	});
		
	</script>
</body>
</html>