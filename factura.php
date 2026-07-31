<?php
include('inc/control.php');
include('inc/sdba/sdba.php'); // include main file


$facturan = 0;
	$factura = $ventas = Sdba::table('configuracion');
	$factura->where('parametro','factura');
	//$factura->order_by('id_comprobante','desc');
	$factura_list = $factura->get_one();
	$facturan = $factura_list['valor'] + 1;

	// Acepta ?ids=1,2,3 (unir varias notas de venta) o ?id=1 (compatibilidad)
	$ids_raw   = $_GET['ids'] ?? $_GET['id'] ?? '';
	$venta_ids = array_values(array_unique(array_filter(array_map('intval', explode(',', $ids_raw)))));

	if (empty($venta_ids)) {
		die('Falta indicar la(s) venta(s) a facturar.');
	}

	// Validar que ninguna venta seleccionada esté ya facturada (estado=1) ni anulada (estado=2)
	$chk = Sdba::table('ventas');
	$chk->where_in('id_venta', $venta_ids);
	$chk->where('estado', '0');
	$chk_list = $chk->get();
	if (count($chk_list) !== count($venta_ids)) {
		die('Una o más de las ventas seleccionadas ya no está pendiente (puede que ya se haya facturado o anulado). Vuelve a la lista de notas de venta e inténtalo de nuevo.');
	}

	//obtenemos datos de la primera venta del grupo (tipo/forma/fecha se toman de ahí)
	$venta = Sdba::table('ventas'); // creating table object
	$venta->where('id_venta', $venta_ids[0]);
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

	$fechita = count($venta_ids) > 1 ? date('d-m-Y') : date("d-m-Y", strtotime($venta_l['fecha']));

	$i=1;
	$tot = 0;
	$mostrar_de_venta = '';
	foreach ($venta_ids as $vid) {
		$ventas = Sdba::table('detalle_ventas'); // creating table object
		$ventas->where('venta', $vid);
		$ventas->left_join('producto','productos','id_producto');
		$ventas_list = $ventas->get();

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
    <link rel="stylesheet" type="text/css" href="/assets/css/jquery-ui.min.css">
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
	      		<li class="active">
	      			<a href="notas_venta.php">Facturar</a>
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
												  		<h3>Ventas: <?php echo implode(', ', array_map(function($v){ return 'v-'.$v; }, $venta_ids)); ?><br><br></h3>
												  	</div>

												  		<input type="hidden" name="fechita" name="fechita" value="<?php echo $fechita; ?>">
												  		<input type="hidden" name="forma" name="forma" value="<?php echo $forma_pl; ?>">
												  		<input type="hidden" name="venta_ids" name="venta_ids" value="<?php echo implode(',', $venta_ids); ?>">
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
																	<tr id="fila-total">
																		<td colspan="5" class="text-right" ><h4>TOTAL:</h4></td>
																		<td class=""><h4><input value="<?php echo $tot; ?>" type="number" id="total" name="total"></h4>
																		</td>
																		<td></td>
																	</tr>
													    	</tbody>
													    </table>
													<div class="text-center" style="margin-bottom:10px">
														<button type="button" class="btn btn-default btn-sm" id="btn-agregar-item"><i class="fa fa-plus" aria-hidden="true"></i> Agregar producto</button>
													</div>
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
				var ruc = $(this).val();
				$.post('/inc/consulta_documento.php', {tipo:'ruc', numero: ruc}, function(response){
					var hugo = JSON.parse(response);
					$('#r_social').val(hugo.nombre_o_razon_social);
					$('#direccion').val(hugo.direccion);
				}, 'text');
			}
		});

//borrar item
		    $("body").on('click', '.borrar', function () {
		    	// Toma el monto actual de la fila (por si el usuario lo editó a mano),
		    	// no el valor original con el que se pintó el botón.
		    	var to = parseFloat($(this).closest('tr').find('input[name="totalp[]"]').val()) || 0;
		    	var tot = parseFloat($('#total').val()) || 0;
		    	var queda = Math.round((tot - to) * 100) / 100;
			    $(this).closest('tr').remove();
			    $('#total').val(queda);
			});

			var contadorItems = <?php echo $i; ?>;
			$("body").on('click', '#btn-agregar-item', function () {
				var fila = '<tr>' +
					'<td><input type="hidden" name="exonerada[]" value="no">' + (contadorItems++) + '</td>' +
					'<input type="hidden" name="codigo[]" value="0">' +
					'<td><input type="text" name="plato[]" class="plato-nuevo" placeholder="Buscar producto..."></td>' +
					'<td><input type="text" name="unidad[]" value="NIU"></td>' +
					'<td><input type="text" name="precio[]" value="0.00"></td>' +
					'<td><input type="text" name="cantidad[]" value="1"></td>' +
					'<td><input type="text" name="totalp[]" value="0.00"></td>' +
					'<td><button type="button" class="borrar"><i class="fa fa-trash" aria-hidden="true"></i></button></td>' +
					'</tr>';
				var $fila = $(fila);
				$('#fila-total').before($fila);

				// Mismo autocomplete de producto que usa venta.php
				$fila.find('.plato-nuevo').autocomplete({
					source: function (request, response) {
						$.ajax({
							type: 'GET',
							dataType: 'json',
							url: '/inc/autocomplete-producto.php',
							data: { term: request.term },
							success: function (data) { response(data); }
						});
					}
				});
			});

			// Al elegir (o terminar de escribir) el producto de una fila agregada a mano,
			// trae su precio real y su id, igual que hace venta.php con #basics.
			$("body").on('change paste keyup', '.plato-nuevo', function () {
				var $fila = $(this).closest('tr');
				$.ajax({
					type: 'GET',
					dataType: 'json',
					url: '/inc/autocomplete-precio.php',
					data: { producto: $(this).val() },
					success: function (response) {
						if (!response || !response.precio) return;
						$fila.find('input[name="precio[]"]').val(response.precio);
						$fila.find('input[name="codigo[]"]').val(response.id_p);
						var cant = parseFloat($fila.find('input[name="cantidad[]"]').val()) || 1;
						$fila.find('input[name="totalp[]"]').val((parseFloat(response.precio) * cant).toFixed(2));
					}
				});
			});

			$("body").on('click', '#facturar', function () {
		    	if ($(this).prop('disabled')) return;
		    	var $btn = $(this);
		    	var str2 = $('#frmfactura').serialize();
		    	$.ajax({
		    		beforeSend: function(){
					     $btn.prop('disabled', true);
					     $("#loading").show();
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
						if (response && response.ok) {
							window.open(response.enlace_del_pdf, '_blank');
							$btn.text('✓ Factura generada');
						} else {
							alert(response && response.mensaje ? response.mensaje : 'No se pudo generar la factura.');
							$btn.prop('disabled', false);
						}
					},
					error: function(){
						alert('Error de conexión al generar la factura. Intenta de nuevo.');
						$btn.prop('disabled', false);
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

			
				var forma = "<?php echo $tipo; ?>";
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