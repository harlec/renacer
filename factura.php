<?php
include('inc/control.php');
include('inc/sdba/sdba.php'); // include main file


$facturan = 0;
	$factura = $ventas = Sdba::table('configuracion');
	$factura->where('parametro','factura');
	//$factura->order_by('id_comprobante','desc');
	$factura_list = $factura->get_one();
	$facturan = $factura_list['valor'] + 1;

	$id = $_GET['id'];

	//obtenemos fecha de la venta
	$venta = Sdba::table('ventas'); // creating table object
	$venta->where('id_venta', $id);
	$venta_l = $venta->get_one();

	$tipo = $venta_l['tipo'];
	if ($tipo == '1') {
		$tipop = 'Contado';
		$mst = 'display:none;';
	}
	else{
		$tipop = 'Crédito';
	}

	$forma_p = $venta_l['forma'];

	switch ($forma_p) {
		case '1':
			$forma_pl = 'Efectivo';
			break;
		case '2':
			$forma_pl = 'Tar. Debito';
			break;
		case '3':
			$forma_pl = 'Tar. Credito';
			break;
		case '4':
			$forma_pl = 'Credito';
			break;
	}

	$fechita = date("d-m-Y", strtotime($venta_l['fecha'])); 
	
	$ventas = Sdba::table('detalle_ventas'); // creating table object
	$ventas->where('venta', $id);
	$ventas->left_join('producto','productos','id_producto');
	$ventas_list = $ventas->get();

	$i=1;
	$tot = 0;
	foreach ($ventas_list as $key ) {

		$id_unidad = $key['unidad_prod'];
		$unidad = Sdba::table('unidades');
		$unidad->where('id_unidad', $id_unidad);
		$unidad_same = $unidad->get_one();

		$unidad_p = $unidad_same['codigo'];

		$tot = $tot + $key['total'];
		$mostrar_de_venta .= '<tr>
								<td><input type="hidden" name="exonerada[]" value="'.$key['exonerada'].'">'.$i.'</td>
								<input type="hidden" name="codigo[]" value="'.$key['id_producto'].'">
								<td><input type="text" name="plato[]" value="'.$key['nom_prod'].'"></td>
								<td><input type="text" name="unidad[]" value="'.$unidad_p.'"></td>
								<td><input type="text" name="precio[]" value="'.$key['precio'].'"></td>
								<td><input type="text" name="cantidad[]" value="'.$key["cantidad"].'"></td>
								<td> <input type="text" name="totalp[]" value="'.$key["total"].'"></td>
								<td><button id="rp" class="borrar" value="'.$key["total"].'"><i class="fa fa-trash" aria-hidden="true"></i></button></td>
							</tr>
							
							';
		$i++;
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
    <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.7.2/css/all.css" integrity="sha384-fnmOCqbTlWIlj8LyTjo7mOUStjsKC4pOpQbqyi7RrhN7udi9RwhKkMHpvLbHG9Sr" crossorigin="anonymous">
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
	      		<li >
	      			<a href="venta.php">Registrar venta</a>
	      		</li>
	      		<li >
	      			<a href="ventas.php">Listar ventas</a>
	      		</li>
	      	</ul>
	      </div>
	    </nav>
		<div class="kbg">
			<form id="frmfactura" method="post" action="inc/factura_e.php" >
				<div class="cuerpo">
					<div class="titulo">
						<h3>Factura</h3>
					</div>
					<div class="container-fluid">
						<div class="row">
							<div class="col-md-12">
								<div class="kdashboard">
									<div class="row">
										<div class="col-md-12">
											<div class="panel panel-default pa">  
												  <div class="panel-body">
												  	<div class="text-center">
												  		<h3>Venta <?php echo $id; ?><br><br></h3>
												  	</div>
												  	
												  		<input type="hidden" name="fechita" name="fechita" value="<?php echo $fechita; ?>">
												  		<input type="hidden" name="forma" name="forma" value="<?php echo $forma_pl; ?>">
												  		<input type="hidden" name="venta_id" name="venta_id" value="<?php echo $id; ?>">
												  		<input class="form-control" type="hidden" name="facturan" value="<?php echo $facturan; ?>">
												  			  	
													<br>
												  	<div class="table-responsive">
													    <table class="table">
													    	<thead>
													    		<tr>
													    			<th>#</th>
													    			<th>Prodcuto</th>
													    			<th>Unidad</th>
													    			<th>Precio</th>
													    			<th>Cantidad</th>
													    			<th>Total</th>
													    			<th>borrar</th>
													    		</tr>
													    	</thead>
													    	<tbody>
																	<?php echo $mostrar_de_venta; ?>
																	<tr>
																		<td colspan="5" class="text-right" ><h4>TOTAL:</h4></td>
																		<td class=""><h4><input value="<?php echo $tot; ?>" type="number" id="total" name="total"></h4>
																		</td>
																		<td></td>
																	</tr>
													    	</tbody>
													    </table>
													</div>
												    <div class="text-center">
												    	<!-- <button type="submit" class="btn btn-success btn-lg">Facturar</button> -->
												    </div>
												    
												    <div class="text-center">
												    	<button type="button" data-loading-text="Facturando..." id="facturar" class="btn btn-success btn-lg">Facturar</button>
												    	<div class="loader text-center" id="loading"></div>
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
				<div class="detalles">
					<div class="titulo">
						<h3>DATOS DE LA EMPRESA</h3>
					</div>
					<div class="container-fluid">
						<div class="row">
							<br>
					  		<div class="col-sm-12">
					  			<input class="form-control" type="text" name="ruc" id="ruc" placeholder="Ingrese Ruc"><br>
					  		</div>
						  	<div class="col-sm-12">
						  		<input class="form-control" type="text" name="r_social" id="r_social" placeholder="Razon social(automática)"><br>
						  	</div>
						  	<div class="col-sm-12">
						  		<textarea class="form-control" name="direccion" id="direccion">
						  			
						  		</textarea><br>
						  		
						  	</div>
					  	</div>	
					</div>
					<div class="titulo">
						<h3>CONDICIONES DE PAGO</h3>
					</div>
					<div class="container-fluid">
						<div class="row">
							<br>
							<div class="col-xs-12">
								<h3><?php echo $tipop; ?></h3>
							</div>

							<div class="col-xs-12 mos" style="display: none;">
								<input type="date" name="fechac" class="form-control"><br>
							</div>
							<div class="col-xs-12 mos" style="display: none;">
								<input readonly type="text" name="montoc" class="form-control" value="<?php echo $tot; ?>"> <br>
							</div>
						</div>
						
					</div>
				</div>
			</form>
		</div>
	 	<!-- Tab panes -->
		

	  
	<!-- jQuery (necessary for Bootstrap's JavaScript plugins) -->
	<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.3.1/jquery.min.js"></script>
	<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js"></script>
	<script src="/assets/js/jquery-ui.min.js"></script> 
	<script >
	// A $( document ).ready() block.
	$(document ).ready(function() {	
	    console.log( "ready!" );

	    console.log(<?php echo $facturan; ?>);
		$( "#nombre" ).autocomplete({
		      	source: function(request,response){
					var str = 'term='+request.term;
					$.ajax({
						type:'GET',
						dataType: 'json',
						url: '/inc/autocomplete-entidad.php',
						data: str,
						success: function(data){
							response(data);
							//$("#precio").val('12');
						}
					});
				}
				//minLength: 2
		    });

		$('#nombre').on('change paste keyup', function(){
		    	var str1 = 'producto='+$('#nombre').val();
		    	//alert (str1);
		    	$.ajax({	
			    	type:'GET',
					dataType: 'json',
				  	url: '/inc/autocomplete-ruc.php',
				  	data: str1,
				  	success: function(data1) {
				   	 	$('#ruc').val(data1);
				   	 	
				  	}
				});
			});

		$( "#ruc" ).on('change paste keyup', function() {
			if(this.value.length==11){
				var output = document.getElementById("output");
                  var data = new FormData();
                  var ruc = $(this).val();
                  data.append("ruc", ruc);
                  data.append("token", "c837b2e5-681b-43ca-ab39-9a115548a8c2-655574bd-c5b8-4270-a324-0f66b09195f1");

                  var xhr = new XMLHttpRequest();
                  
                  xhr.open("POST", "https://api.migo.pe/api/v1/ruc");
                  xhr.setRequestHeader("Accept", "application/json");
                  
                  xhr.send(data);
                  console.log(xhr);
                  console.log('hola');
                  console.log(xhr.response);

                  xhr.onload = function () {
				    if (xhr.readyState === xhr.DONE) {
				        if (xhr.status === 200) {

				        	var hugo = JSON.parse(xhr.response); 
				        	console.log(hugo.ruc);
				            console.log(xhr.response);
				            //$('#ruc').val(hugo.ruc);
				            $('#r_social').val(hugo.nombre_o_razon_social);
				            $('#direccion').val(hugo.direccion);
				            console.log(xhr.responseText);
				        }
				    }
				};
			}
  
		});

//borrar item
		    $("body").on('click', '.borrar', function () {
		    	var to = $(this).val();
		    	var tot = $('#total').val();
		    	var queda = tot-to;
		    	console.log(tot);
		    	console.log(queda);
			    $(this).closest('tr').remove();
			    $(this).parents('.pt-r').remove();
			    $('#total').val(queda);
			    console.log(to);

			});

			$("body").on('click', '#facturar', function () {
		    	var str2 = $('#frmfactura').serialize();
		    	console.log(str2);
		    	//alert(str2);
		    	$.ajax({
		    		beforeSend: function(){
					     $("#loading").show();
					      $("#facturar").css("visibility", "hidden");
					     
					},
					   complete: function(){
					     $("#loading").hide();
					},
					cache: false,
					type: "POST",
					dataType: "json",
					url: "inc/factura_e.php",
					data: str2,
					success: function(response){

						//alert('bien');
						console.log(response);

						var sunat = JSON.parse(response); 
				  		console.log(sunat.enlace);
						// console.log(response);
						//document.location.href = "venta.php";
						window.open(sunat.enlace_del_pdf,'_blank');
						return false;

						//if(response.respuesta == false){
							//swal('Advertencia',response,'warning');
							//console.log(response);

						//}else{

							//swal('Perfecto', response,'success')
							// console.log(response.mesa);
							// $('#mostrarmesa').load('inc/mobile/ver_mesa.php?mesa='+ response.mesa);
							// //document.location.href = "listar_ventas.php";
						
						//}
					
					},
					error: function(reponse1){
						alert(reponse1);
					}
				});
				
				//$(this ).hide();
				//return false;
		    	// var tot = $('#total').val();
		    	// var queda = tot-to;
		    	// console.log(tot);
		    	// console.log(queda);
			    // $(this).closest('tr').remove();
			    // $(this).parents('.pt-r').remove();
			    // $('#total').val(queda);
			    // console.log(to);

			});

			
				var forma = <?php echo $tipo; ?>;
				if (forma == '2') {
					$('.mos').show();
				}
				else{
					$('.mos').hide();
				}
			
				
                  //$('#ruc').val(xhr.response["ruc"]);

	});
		
	</script>
</body>
</html>