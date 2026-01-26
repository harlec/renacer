<?php
include('inc/control.php');
include('inc/sdba/sdba.php'); // include main file


$facturan = 0;
	$factura = $ventas = Sdba::table('configuracion');
	$factura->where('parametro','boleta');
	$factura_list = $factura->get_one();
	$facturan = $factura_list['valor'] + 1;

	$id = $_GET['id'];

	$comprobante = Sdba::table('comprobantes');
	$comprobante->where('id_comprobante',$id);
	$comprobantes = $comprobante->get_one();
	$ventan = $comprobantes['venta'];
	$rucn = $comprobantes['doc'];
	$razonn = $comprobantes['nombre'];
	$num_compro = $comprobantes['numero'];
	$tipo_doc = $comprobantes['tipo_doc'];
	$num_compro = $comprobantes['numero'];

	switch ($tipo_doc) {
		case '-':
			$tipo_docn= 'OTRO';
			break;
		case '1':
			$tipo_docn= 'DNI';
			break;
		case '4':
			$tipo_docn= 'CARNET DE EXTRANJERÍA';
			break;
		case '7':
			$tipo_docn= 'PASAPORTE';
			break;
		case '0':
			$tipo_docn= 'NO DOMICILIADO';
			break;
	}

	//obtenemos fecha de la venta
	$venta = Sdba::table('ventas'); // creating table object
	$venta->where('id_venta', $ventan);
	$venta_l = $venta->get_one();

	$fechita = date("d-m-Y", strtotime($venta_l['fecha'])); 

	
	$ventas = Sdba::table('detalle_ventas'); // creating table object
	$ventas->where('venta', $ventan);
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
						<h3>Boleta</h3>
					</div>
					<div class="container-fluid">
						<div class="row">
							<div class="col-md-12">
								<div class="panel panel-default pa">  
									  <div class="panel-body">
									  	<div class="text-center">
									  		<h3>VENTA <?php echo $id; ?><br></h3>
									  	</div>
									  		<input type="hidden" name="fechita" name="fechita" value="<?php echo $fechita; ?>">
									  		<input type="hidden" name="venta_id" name="venta_id" value="<?php echo $id; ?>">
									  		<input class="form-control" type="hidden" name="facturan" value="<?php echo $num_compro; ?>">
									  	<div class="table-responsive">
									  		<table class="table">
										    	<thead>
										    		<tr>
										    			<th>#</th>
										    			<th>Producto</th>
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
														<td class=""><h4>
															<input value="<?php echo $tot; ?>" type="number" id="total" name="total">
															
															</h4>
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
									    	<button type="button" data-loading-text="Facturando..." id="facturar" class="btn btn-success btn-lg">Generar Nota de crédito</button>
									    	<div class="loader text-center" id="loading"></div>
									    </div>
									  </div>
								</div>
							</div>
						</div>
					</div>
				</div>
				<div class="detalles">
					<div class="titulo">
						<h3>Datos</h3>
					</div>
					<br>
					<div class="container-fluid">
						<div class="row">
				  			<div class="col-sm-12">
				  				<input type="hidden" name="numero_compro" value="<?php echo $num_compro; ?>">
				  				<input type="hidden" name="tipo_doc" value="<?php echo $tipo_doc; ?>">
						  		<input type="text" name="tipo_docn" class="form-control" value="<?php echo $tipo_docn; ?>"><br>
				  			</div>
				  			<div class="col-sm-12">
				  				<input class="form-control" value="<?php echo $rucn; ?>" type="text" name="ruc" id="ruc" value="-" placeholder=""><br>
				  			</div>
				  			<div class="col-sm-12">
				  				<input class="form-control" value="<?php echo $razonn; ?>" type="text" name="r_social" value="VARIOS" id="r_social"><br>
				  			</div>
				  			<div class="col-sm-12">
				  				<input class="form-control" type="text" name="direccion" id="direccion" placeholder="Dirección opcional"><br>
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

	    console.log(<?php echo $num_compro; ?>);
	    $('body').on('change',"select[name='tipo_doc']", function(){
		    var linkText = $(this).val()
		    //alert(linkText);
		    switch (linkText) { 
				case '-': 
					$('#ruc').val('-');
					$('#r_social').val('VARIOS');
					break;
				default:
					$('#ruc').val('');
					$('#ruc').attr("placeholder", "Ingrese numero");
					$('#r_social').val('');
					$('#r_social').attr("placeholder", "Nombres");
			}

		});

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

		$( "#ruc" ).change(function() {
			var output = document.getElementById("output");
                  var data = new FormData();
                  var ruc = $(this).val();
                  data.append("dni", ruc);
                  data.append("token", "c837b2e5-681b-43ca-ab39-9a115548a8c2-655574bd-c5b8-4270-a324-0f66b09195f1");

                  var xhr = new XMLHttpRequest();
                  
                  xhr.open("POST", "https://api.migo.pe/api/v1/dni");
                  xhr.setRequestHeader("Accept", "application/json");
                  
                  xhr.send(data);
                  console.log(xhr);
                  console.log('hola');
                  console.log(xhr.response);


                  xhr.onload = function () {
				    if (xhr.readyState === xhr.DONE) {
				        if (xhr.status === 200) {

				        	var hugo = JSON.parse(xhr.response); 
				        	//console.log(hugo.ruc);
				            console.log(xhr.response);
				            //var nombres = hugo.nombres + ' ' + hugo.apellido_paterno + ' ' + hugo.apellido_materno; 
				            var nombres = hugo.nombre;
				            //$('#ruc').val(hugo.ruc);
				            $('#r_social').val(nombres);
				            //$('#direccion').val(hugo.direccion);
				            //console.log(xhr.responseText);
				        }
				    }
				};
  
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
					     
					},
					   complete: function(){
					     $("#loading").hide();
					},
					cache: false,
					type: "POST",
					dataType: "json",
					url: "inc/notadcb_e.php",
					data: str2,
					success: function(response){

						//alert('bien');
						console.log(response);

						var sunat = JSON.parse(response); 
				  		console.log(sunat.enlace);
						// console.log(response);



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
				
                  //$('#ruc').val(xhr.response["ruc"]);

	});
		
	</script>
</body>
</html>