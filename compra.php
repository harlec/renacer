<?php
include('inc/control.php');
$fecha = date('d-m-Y');
$newDate = date("Y-m-d", strtotime($fecha));

include('inc/sdba/sdba.php'); // include main file
$ventas = Sdba::table('productos');
$ventas->left_join('unidad_prod','unidades','id_unidad'); // creating table object
$ventas_list = $ventas->get(); 

$datos = '';
$i = 1;
foreach ($ventas_list as $value) {

	$marca = Sdba::table('marca');
	$marca->where('id_marca',$value['marca']);
	//$marca->order_by('id_stock','desc');
	$marca1 = $marca->get_one();
	$marcan = $marca1['marca'];

	$datos .='<tr>
    			<td style="text-transform:uppercase;" class="nom_prod">'.$value['nom_prod'].' '.$marcan.'<input type="hidden" class="precio_venta" value="'.$value['precio_venta'].'"></td>
    			<td style="text-transform:uppercase;" class="unidad">'.$value['nombre'].'</td>
    			<td><button id="agregar" value="'.$value['id_producto'].'" class="btn btn-xs btn-success"> + </button></td>
    		  </tr>';
    $i++;
}
$proveedores = Sdba::table('proveedores');
$proveedoresl = $proveedores->get();
$proveedoreslt = '';
foreach ($proveedoresl as $key) {
	$proveedoreslt .='<option value="'.$key['id_proveedor'].'">'.$key['proveedor'].'</option>'; 
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
    <link rel="stylesheet" type="text/css" href="https://cdnjs.cloudflare.com/ajax/libs/limonte-sweetalert2/7.0.5/sweetalert2.min.css">
    <link rel="stylesheet" type="text/css" href="//cdn.datatables.net/1.10.22/css/jquery.dataTables.min.css">
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
	        <?php menu('6'); ?>
	      </div>
	      <div class="submenu">
	      	<ul class="subtop-tabs">
	      		<li class="active">
	      			<a href="compra.php">Registrar Compra</a>
	      		</li>
	      		<li >
	      			<a href="compras.php">Listar Compras</a>
	      		</li>
	      		<li >
	      			<a href="proveedores.php">Proveedores</a>
	      		</li>
	      		<li>
	      			<a href="cuentas_x_pagar.php">Cuentas x pagar</a>
	      		</li>
	      	</ul>
	      </div>
	    </nav>
		<div class="kbg">
			<div class="cuerpo">
				<div class="titulo">
					<h3>Registrar Compra</h3>
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
											    		<div class="col-md-3">
											    			<div class="form-group">
															    <label for="exampleInputPassword1">Fecha Ingreso</label>
															    <input type="date" class="form-control" name="fecha_in" id="fecha_in" value="<?php echo $newDate; ?>" placeholder="monto">
															 </div>
											    		</div>
											    		<div class="col-md-3">
											    			<div class="form-group">
															    <label for="exampleInputPassword1">Fecha Despacho</label>
															    <input type="date" class="form-control" name="fecha_des" id="fecha_des" value="<?php echo $newDate; ?>" placeholder="monto">
															 </div>
											    		</div>
											    		<div class="col-md-4">
											    			<div class="form-group">
															    <label for="exampleInputPassword1">Proveedor</label>
															    <select class="form-control" id="proveedor" name="proveedor">
															    	<?php echo $proveedoreslt; ?>
															    </select>
															</div>
											    		</div>
											    		<div class="col-md-2">
											    			<div class="form-group">
															    <label for="exampleInputPassword1">Exonerada Igv</label>
															    <select class="form-control" name="exonerada">
															    	<option value="no">No</option>
															    	<option value="si">Si</option>
															    </select>
															 </div>
											    		</div>
											    	</div>
											    	<div class="row">
											    		<div class="col-md-3">
											    			<div class="form-group">
															    <label for="exampleInputPassword1">No. Guía</label>
															    <input type="text" class="form-control" name="guia" id="guia" value="" placeholder="">
															 </div>
											    		</div>
											    		<div class="col-md-3">
											    			<div class="form-group">
															    <label for="exampleInputPassword1">Serie</label>
															    <input type="text" class="form-control" name="serie" id="serie" value="" placeholder="">
															 </div>
											    		</div>
											    		<div class="col-md-3">
											    			<div class="form-group">
															    <label for="exampleInputPassword1">Número</label>
															    <input type="text" class="form-control" name="numero" id="numero" value="" placeholder="">
															 </div>
											    		</div>
											    		<div class="col-md-3">
											    			<div class="form-group">
															    <label for="exampleInputPassword1">Moneda</label>
															    <select id="moneda" name="moneda" class="form-control">
															    	<option value="0">Soles</option>
															    	<option value="1">Dolares</option>
															    </select>
															 </div>
											    		</div>
											    	</div>
											    	<div class="row">
											    		<div class="col-md-12">
											    			<div class="form-group">
															    <label for="exampleInputPassword1">Observaciones</label>
															    <input type="text" class="form-control" name="observaciones" id="observaciones" value="" placeholder="">
															 </div>
											    		</div>
											    	</div>
											    	<div class="row">
											    		<div class="col-md-3">
											    			<div class="form-group">
													    <label>Forma de pago</label>
													    <select class="form-control" name="forma_pago" id="forma_pago">
													    	<option value="contado">Contado (pagada al registrar)</option>
													    	<option value="credito">Crédito (queda pendiente)</option>
													    </select>
													 </div>
											    		</div>
											    		<div class="col-md-3" id="col_metodo_contado">
											    			<div class="form-group">
													    <label>Método de pago</label>
													    <select class="form-control" name="metodo_pago" id="metodo_pago">
													    	<option value="efectivo">Efectivo</option>
													    	<option value="transferencia">Transferencia</option>
													    	<option value="deposito">Depósito</option>
													    	<option value="cheque">Cheque</option>
													    	<option value="otro">Otro</option>
													    </select>
													 </div>
											    		</div>
											    		<div class="col-md-3" id="col_fecha_compromiso" style="display:none">
											    			<div class="form-group">
													    <label>Fecha compromiso de pago</label>
													    <input type="date" class="form-control" name="fecha_compromiso_pago" id="fecha_compromiso_pago" value="">
													 </div>
											    		</div>
											    	</div>
											    		<div class="col-md-12">
											    			<h3 class="text-center">Items</h3>
											    		</div>

											    	<div class="row">
											    		<div class="col-sm-2"></div>
											    		<div class="col-md-12">	
													    	<table id="items" class="table table-striped table-condensed">
																<thead>
																	<tr >
																		<th>Cantidad</th>
																		<th>Unidad</th>
																		<th>Descripción</th>
																		<th>Precio</th>
																		<th>Monto</th>
																		<th></th>
																	</tr>
																</thead>
																<tbody>
																	<tr></tr>
																	
																</tbody>
															</table>
															<div class="text-right">
																<strong>Total:  </strong><input id="total" name="total" type="text" id="total">
															</div>
														</div>
														<div class="col-sm-2"></div>
											    	
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
			<div class="detalles">	
				<div class="titulo">
					<h3>Agregar productos</h3>
				</div>
				<div class="panel panel-default pa">
					<div class="panel-body">
					    <table id="datos" class="table table-hover"> 
					    	<thead> 
					    		<tr> 
					    			<th>Producto</th>
					    			<th>Unidad</th>
					    			<th></th> 
					    		</tr> 
					    	</thead> 
					    	<tbody> 
					    		<?php echo $datos; ?>
					    	</tbody> 
					    </table>
					</div>
				</div>
			</div>
		</div>
	 	<!-- Tab panes -->
		

	  
	<!-- jQuery (necessary for Bootstrap's JavaScript plugins) -->
	<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.3.1/jquery.min.js"></script>
	<script src="/assets/js/jquery-ui.min.js"></script> 
	<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js"></script>
	<script src="https://cdnjs.cloudflare.com/ajax/libs/limonte-sweetalert2/7.0.5/sweetalert2.min.js"></script>
	<script src="//cdn.datatables.net/1.10.22/js/jquery.dataTables.min.js"></script>
	<script src="/assets/js/select2.full.min.js"></script>
	<script >
	// A $( document ).ready() block.
	$(document ).ready(function() {
		$('#proveedor').select2();

		$('#forma_pago').on('change', function () {
			var esCredito = $(this).val() === 'credito';
			$('#col_fecha_compromiso').toggle(esCredito);
			$('#col_metodo_contado').toggle(!esCredito);
		});

		$.extend( true, $.fn.dataTable.defaults, {
		    "language": {
		        "decimal": ",",
		        "thousands": ".",
		        "info": "Mostrando registros del _START_ al _END_ de un total de _TOTAL_ registros",
		        "infoEmpty": "Mostrando registros del 0 al 0 de un total de 0 registros",
		        "infoPostFix": "",
		        "infoFiltered": "(filtrado de un total de _MAX_ registros)",
		        "loadingRecords": "Cargando...",
		        "lengthMenu": "Mostrar _MENU_ registros",
		        "paginate": {
		            "first": "Primero",
		            "last": "Último",
		            "next": "Siguiente",
		            "previous": "Anterior"
		        },
		        "processing": "Procesando...",
		        "search": "Buscar:",
		        "searchPlaceholder": "Término de búsqueda",
		        "zeroRecords": "No se encontraron resultados",
		        "emptyTable": "Ningún dato disponible en esta tabla",
		        "aria": {
		            "sortAscending":  ": Activar para ordenar la columna de manera ascendente",
		            "sortDescending": ": Activar para ordenar la columna de manera descendente"
		        },
		        //only works for built-in buttons, not for custom buttons
		        "buttons": {
		            "create": "Nuevo",
		            "edit": "Cambiar",
		            "remove": "Borrar",
		            "copy": "Copiar",
		            "csv": "fichero CSV",
		            "excel": "tabla Excel",
		            "pdf": "documento PDF",
		            "print": "Imprimir",
		            "colvis": "Visibilidad columnas",
		            "collection": "Colección",
		            "upload": "Seleccione fichero...."
		        },
		        "select": {
		            "rows": {
		                _: '%d filas seleccionadas',
		                0: 'clic fila para seleccionar',
		                1: 'una fila seleccionada'
		            }
		        }
		    }           
		} );     

		$('#datos').DataTable();


		var total = 0;

		$('#datos').on('click', '#agregar', function(){
		    var nombre = $(this).closest('tr').find('.nom_prod').text();
		    var precioDefecto = $(this).closest('tr').find('.precio_venta').val();
		    var unidad = $(this).closest('tr').find('.unidad').text();
		    var cantidad = 1;
		    var id_p = $(this).val();

		    $.ajax({
		        type: 'GET',
		        dataType: 'json',
		        url: '/inc/get_variantes_producto.php',
		        data: 'producto=' + id_p,
		        success: function(variantes) {
		            var precio, monto, row;
		            if (variantes.length > 0) {
		                var selectHtml = '<select class="variante_sel form-control input-sm">';
		                $.each(variantes, function(i, v) {
		                    selectHtml += '<option value="' + v.id_vp + '" data-precio="' + v.precioc_vp + '" data-cantidad="' + v.cantidad_vp + '">' + v.variante + '</option>';
		                });
		                selectHtml += '</select>';
		                precio = variantes[0].precioc_vp;
		                monto = precio * cantidad;
		                total = monto*1 + total*1;
		                row = '<tr class="child">' +
		                    '<input type="hidden" value="' + id_p + '" name="id_pro[]">' +
		                    '<input type="hidden" value="' + unidad + '" name="unidad[]">' +
		                    '<input type="hidden" class="id_vp_input" value="' + variantes[0].id_vp + '" name="id_vp[]">' +
		                    '<td><input class="cantidad" type="number" value="' + cantidad + '" name="cantidad[]"></td>' +
		                    '<td>' + selectHtml + '</td>' +
		                    '<td>' + nombre + '</td>' +
		                    '<td><input type="number" class="pre" value="' + precio + '" name="precio[]"></td>' +
		                    '<td><input class="mon" type="text" value="' + monto + '" name="total_pre[]"></td>' +
		                    '<td><button value="' + monto + '" class="borrar">x</button></td>' +
		                    '</tr>';
		            } else {
		                precio = precioDefecto;
		                monto = precio * cantidad;
		                total = monto*1 + total*1;
		                row = '<tr class="child">' +
		                    '<input type="hidden" value="' + id_p + '" name="id_pro[]">' +
		                    '<input type="hidden" value="' + unidad + '" name="unidad[]">' +
		                    '<input type="hidden" name="id_vp[]" value="">' +
		                    '<td><input class="cantidad" type="number" value="' + cantidad + '" name="cantidad[]"></td>' +
		                    '<td style="text-transform:uppercase;">' + unidad + '</td>' +
		                    '<td>' + nombre + '</td>' +
		                    '<td><input type="number" class="pre" value="' + precio + '" name="precio[]"></td>' +
		                    '<td><input class="mon" type="text" value="' + monto + '" name="total_pre[]"></td>' +
		                    '<td><button value="' + monto + '" class="borrar">x</button></td>' +
		                    '</tr>';
		            }
		            $('#items tr:last').after(row);
		            $("#total").val(total);
		        }
		    });
		});
	    // cambio de variante: actualiza precio e id_vp oculto
	    $('body').on('change', '.variante_sel', function() {
	        var $opt = $(this).find('option:selected');
	        var nuevoPrecio = parseFloat($opt.data('precio')) || 0;
	        var $row = $(this).closest('tr');
	        $row.find('.id_vp_input').val($opt.val());
	        var anterior = parseFloat($row.find('.mon').val()) || 0;
	        var cant = parseFloat($row.find('.cantidad').val()) || 1;
	        var monto = nuevoPrecio * cant;
	        $row.find('.pre').val(nuevoPrecio);
	        $row.find('.mon').val(monto);
	        $row.find('.borrar').val(monto);
	        total = total - anterior + monto;
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
		//actualizar item
		var monto1 = 0;
		$('body').on('change paste keyup',".cantidad", function(){
		//$('.cantidad').on('change paste keyup', function(){
			var anterior = $(this).closest('tr').find('.mon').val();
			var precio = $(this).closest('tr').find('.pre').val();
			var cantidad = $(this).closest('tr').find('.cantidad').val();
			var monto1 =  precio*cantidad;

			total = total - anterior + monto1;
			$("#total").val(total);
			
			//alert(monto1);
			$(this).closest('tr').find('.mon').val(monto1);
			$(this).closest('tr').find('.borrar').val(monto1);
		});

		$('body').on('change paste keyup',".pre", function(){
		//$('.cantidad').on('change paste keyup', function(){
			var anterior = $(this).closest('tr').find('.mon').val();
			var precio = $(this).closest('tr').find('.pre').val();
			var cantidad = $(this).closest('tr').find('.cantidad').val();
			var monto1 =  precio*cantidad;

			total = total - anterior + monto1;
			$("#total").val(total);
			
			//alert(monto1);
			$(this).closest('tr').find('.mon').val(monto1);
			$(this).closest('tr').find('.borrar').val(monto1);
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

				$.ajax({
					cache: false,
					type: "POST",
					dataType: "json",
					url: "/inc/registrar_compra.php",
					data: str2,
					success: function(response){

						if(response.respuesta == false){
							swal('Advertencia',response.mensaje,'warning');
							


						}else{

							swal('Perfecto', response.venta_id,'success');
							//var id_venta = response.id_venta;
							console.log(response.mesa);
							//$('#mostrarmesa').load('inc/mobile/ver_mesa.php?mesa='+ response.mesa);
							//document.location.href = "ver_venta.php?id="+response.venta_id;
						
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