<?php
session_start();
$usuario = $_SESSION['usuario'];
$tienda = $_SESSION['tienda'];

include('inc/control.php');
$fecha = date('d-m-Y');
$newDate = date("Y-m-d", strtotime($fecha));

include('inc/sdba/sdba.php'); // include main file


//variantes

$variantes_p = Sdba::table('variante_p');
$variantes_p->left_join('variante_vp','variantes','id_variante');
$variantes_p->left_join('producto_vp','productos','id_producto');
$variantes_p->left_join('producto_vp','marca','id_marca');
$variantes_p_l = $variantes_p->get();

$datos = '';
$i = 1;
foreach ($variantes_p_l as $value) {

	$ventas = Sdba::table('marca');
	$ventas->where('id_marca',$value['marca']);
	$ventas_l = $ventas->get_one();

	$stocktt = $value['stockp']/$value['cantidad_vp'];
	$marcan = $ventas_l['marca'];
	$precio_final = $value['precio_vp']/$value['cantidad_vp'];

	$datos .='<tr> 
    			<td style="text-transform:uppercase;" class="nom_prod">'.$value['codigo_producto'].' '.$value['nom_prod'].' '.$marcan.'</td>
    			<td style="text-transform:uppercase;" class="unidad"><input type="hidden" class="id_vp" value="'.$value['id_vp'].'">'.'<input type="hidden" class="cantidad_vp" value="'.$value['cantidad_vp'].'">'.$value['variante'].'('.$value['cantidad_vp'].')</td>
    			<td class="stock">'.$stocktt.'</td>
				<td>'.$value['precio_vp'].'</td>
    			<td style="display:none;" class="precio_venta">'.$precio_final.'</td>  
    			<td><button id="agregar" value="'.$value['id_producto'].'" class="btn btn-lg btn-success"> + </button></td>
    		  </tr>';
    $i++;
	
}






// $ventas = Sdba::table('productos');
// $ventas->left_join('unidad_prod','unidades','id_unidad');
// $ventas->left_join('marca','marca','id_marca');
// $ventas_list = $ventas->get(); 


// foreach ($ventas_list as $value) {

// 	$stocktt = $value['stockp'];

// 	$marcan = $value['marca'];

// 		$datos .='<tr> 
//     			<td style="text-transform:uppercase;" class="nom_prod">'.$value['codigo_producto'].' '.$value['nom_prod'].' '.$marcan.'</td>
//     			<td style="text-transform:uppercase;" class="unidad">'.$value['codigo'].'</td>
//     			<td class="stock">'.$stocktt.'</td>
//     			<td class="precio_venta">'.$value['precio_venta'].'</td>  
//     			<td><button id="agregar" value="'.$value['id_producto'].'" class="btn btn-xs btn-success"> + </button></td>
//     		  </tr>';
//     $i++;

    
// }

//Variantes
// $variantes  = Sdba::table('variantes');
// $variantes->left_join('producto','productos','id_producto');
// $variantes_lst = $variantes->get();
// foreach ($variantes_lst as $value1){
// 	$cantidad_vari = $value1['cantidad'];
// 	$ventas->where('id_producto',$value1['id_producto']);
// 	$ventas->left_join('marca','marca','id_marca');
// 	$ventas_l = $ventas->get_one();
// 	$marcaf = $ventas_l['marca'];
// 	$variantes_lst .= '<option value="'.$value1['id_variante'].'-'.$value1['cantidad'].'">'.$value1['nom_prod'].' '.$marcaf.' '.$value1['variante'].'</option>';
// }

//obtnemos colaboradores
$clientes = Sdba::table('clientes');
$el = $clientes->get();
$emplel = array();
foreach ($el as $value) {
	$emplel[]= $value['cliente'];
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
    <!-- <link rel="stylesheet" href="/assets/css/jquery-ui.min.css"> -->
    <link rel="stylesheet" type="text/css" href="https://cdnjs.cloudflare.com/ajax/libs/limonte-sweetalert2/7.0.5/sweetalert2.min.css">
    <link rel="stylesheet" type="text/css" href="//cdn.datatables.net/1.10.22/css/jquery.dataTables.min.css">
    <link rel="stylesheet" type="text/css" href="/assets/css/select2.min.css">
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
	      		<li class="active">
	      			<a href="venta.php">Registrar venta</a>
	      		</li>
	      		<li >
	      			<a href="ventas.php">Listar ventas</a>
	      		</li>
	      		<!-- <li>
	      			<a href="ventap.php">Proforma</a>
	      		</li>
	      		<li>
	      			<a href="venta_comprobantes.php">Comprobantes</a>
	      		</li> -->
	      	</ul>
	      </div>
	    </nav>
		<div class="kbg">
			<div class="cuerpo">
				<div class="titulo">
					<h3>Registrar Venta</h3>
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
															    <label for="exampleInputPassword1">Fecha</label>
															    <input type="date" class="form-control" name="fecha" id="fecha" value="<?php echo $newDate; ?>" placeholder="monto">
															 </div>
											    		</div>
											    		<div class="col-md-6">
											    			<label for="exampleInputPassword1">Cliente</label>
											    				<input class="form-control" type="text" id="cliente" name="cliente">
															    <!-- <select class="form-control" name="cliente">
															    	<?php //echo $emplel; ?>
															    </select> -->
											    		</div>
											    	</div>

											    	<div class="row">
											    		<div class="col-sm-2"></div>
											    		<div class="col-md-12">	
													    	<table id="items" class="table table-striped table-condensed">
																<thead>
																	<tr >
																		<th>Cantidad</th>
																		<th>Descripción</th>
																		<th>Unidad</th>
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
																<strong>Total: S/ </strong><input id="total" name="total" type="hidden" id="total"><input id="total1" name="total1" type="text">
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

						<table id="datos" class="table table-hover table-responsive"> 
						    	<thead> 
						    		<tr> 
						    			<th>Producto</th>
						    			<th>Unidad</th>
						    			<th>Stock</th>
						    			<th>Precio</th> 
						    			<th></th> 
						    		</tr> 
						    	</thead> 
						    	<tbody> 
						    		<?php echo $datos; ?>
						    	</tbody> 
						    </table>


						<!-- Nav tabs -->
						<!-- <ul class="nav nav-tabs" role="tablist" id="myTab">
						  <li class="active"><a href="#currentIssue" role="tab" data-toggle="tab">Productos</a></li>
						  <li ><a href="#otro" role="tab" data-toggle="tab">Agrupados</a></li>
						</ul>

						Tab panes
						<div class="tab-content">
						  <div class="tab-pane active" id="currentIssue">
						  	<table id="datos" class="table table-hover table-responsive"> 
						    	<thead> 
						    		<tr> 
						    			<th>Producto</th>
						    			<th>Unidad</th>
						    			<th>Stock</th>
						    			<th>Precio</th> 
						    			<th></th> 
						    		</tr> 
						    	</thead> 
						    	<tbody> 
						    		<?php //echo $datos; ?>
						    	</tbody> 
						    </table>
						  </div>
						  <div class="tab-pane " id="otro">
						  	<select id="select_p">
						  		<option value="">seleccione producto</option>
						  		<?= $variantes_lst; ?>
						  	</select>
						  	&nbsp;&nbsp<input class="" id="canti_variante" value="" type="text" name="canti_variante">
						  	<h4>Variantes de precios <input type="button" class="btn  btn-success" id="add"value="+" /></h4>
			    			<table id="variantes" class="table">
			    				<thead>
			    					<tr>
			    						<th>Producto</th>
			    						<th>Cantidad</th>
			    						<th></th>
			    					</tr>
			    				</thead>
			    				<tbody>
			    					
			    				</tbody>
			    			</table>
						  </div>
						  
						</div> -->


					    
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

		var availableTags = <?= json_encode($emplel); ?>;
	    $( "#cliente" ).autocomplete({
	      source: availableTags
	    });


		$('#add').click(function() {
				$('#variantes').find('tbody').append('<tr><td><input type="text" name="variante[]" ></td><td><input type="text" name="variante[]" ></td><td><input type="text" name="variante[]" ></td></tr>')
		    });


		$('#select_p').select2();

		$('body').on('change', '#select_p', function(){
			var demo = $(this).val();
			
			var porciones = demo.split('-');
			$("#canti_variante").val(porciones[1]);
			$('#variantes').find('tbody').append('<tr><td><input type="text" name="variante[]" ></td><td><input type="text" name="variante[]" ></td><td><input type="text" name="variante[]" ></td></tr>')

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

		//$('#datos').DataTable();
		var tabla = $('#datos').DataTable({
    "order": [[0, 'asc']]
});

// Búsqueda con ordenamiento por relevancia
$('div.dataTables_filter input').on('keyup', function() {
    var searchTerm = this.value.toLowerCase().trim();
    
    if (searchTerm.length > 0) {
        // Crear un array para ordenar por relevancia
        var rowsWithScore = [];
        
        tabla.rows({ search: 'applied' }).every(function(rowIdx) {
            var data = this.data();
            var producto = $(data[0]).text() || data[0]; // Obtener texto del producto
            producto = producto.toLowerCase();
            
            var score = 100; // Score por defecto (baja relevancia)
            
            // Si el producto contiene EXACTAMENTE el término buscado
            if (producto.indexOf(searchTerm) !== -1) {
                // Mientras más cerca del inicio, mejor score
                score = producto.indexOf(searchTerm);
            }
            
            // Bonus: si las palabras coinciden mejor (ej: "2 oz" vs "2" en otro contexto)
            var searchWords = searchTerm.split(' ');
            var matchCount = 0;
            searchWords.forEach(function(word) {
                if (word.length > 0 && producto.indexOf(word) !== -1) {
                    matchCount++;
                }
            });
            
            // Si coinciden todas las palabras buscadas, dar mejor score
            if (matchCount === searchWords.length) {
                score = score - 50;
            }
            
            rowsWithScore.push({
                row: this.node(),
                score: score,
                producto: producto
            });
        });
        
        // Ordenar por score (menor = más relevante)
        rowsWithScore.sort(function(a, b) {
            return a.score - b.score;
        });
        
        // Reordenar las filas en el DOM
        var tbody = $('#datos tbody');
        rowsWithScore.forEach(function(item) {
            tbody.append(item.row);
        });
    }
});


		var total = 0;

		$('#datos').on('click', '#agregar', function(){
			var cantidad_vp = $(this).closest('tr').find('.cantidad_vp').val();
			var id_vp = $(this).closest('tr').find('.id_vp').val();
		    var nombre = $(this).closest('tr').find('.nom_prod').text();
		    var precio = $(this).closest('tr').find('.precio_venta').text();
		    var fv = $(this).closest('tr').find('.fv').text();
		    var unidad = $(this).closest('tr').find('.unidad').text();
		    var stock = $(this).closest('tr').find('.stock').text();
		    var cantidad = 1;
		    var id_p = $(this).val();
		    var monto = precio*cantidad_vp;



		    //alert(cantidad_vp);
		    console.log(stock);
		    if (stock <=0) {
		    	swal('Advertencia','No puedes agregar, no tienes stock.','warning');
		    	
		    }
		    else{
		    	$('input[type=search]').val('');
		    	total = monto*1 + total*1;
		    	total1 = Number(total.toFixed(2));

		    	$('#items tr:last').after('<tr class="child"><input type="hidden" class="id_vp" value="'+id_vp+'" name="id_vp[]" ><input type="hidden" class="stocki" value="'+stock+'" name="stock[]" ><input type="hidden" value="'+id_p+'" name="id_pro[]" ><td><input class="cantidad" type="number" max="'+stock+'" value="'+cantidad_vp+'" name="cantidad[]"></td><td style="text-transform:uppercase;">'+nombre+'</td><td style="text-transform:uppercase;">'+unidad+'</td><td><input type="number" class="pre" value="'+precio+'" name="precio[]"></td><td ><input class="mon" type="text" value="'+monto+'" name="total_pre[]" ></td><td><button value="'+monto+'" class="borrar">x</button></td></tr>');
		    	$("#total").val(total);
		    	$("#total1").val(total1);

		    }


		});
	    //borrar item
	    $("#items").on('click', '.borrar', function () {
		    //$(this).closest('tr').remove();
		    
		    var resta = $(this).val();
		    console.log(resta)
		    $(this).parents("tr").remove();
		    total = (total - resta*1);
		    total1 = Number(total.toFixed(2));


		    $("#total").val(total);
		    $("#total1").val(total1);

		});
		//actualizar item
		var monto1 = 0;
		$('body').on('change paste keyup',".cantidad", function(){
			var stock = $(this).closest('tr').find('.stocki').val();
			var cantidad = $(this).closest('tr').find('.cantidad').val();
			console.log(stock);
			console.log(cantidad);
			//if (cantidad <= stock ) {
				//$('.cantidad').on('change paste keyup', function(){
				var anterior = $(this).closest('tr').find('.mon').val();
				var precio = $(this).closest('tr').find('.pre').val();
				
				var monto1 =  precio*cantidad;


				total = (total - anterior + monto1);
				total1 = total.toFixed(2);

				monto1 = monto1;
				$("#total").val(total);
				$("#total1").val(total1);
				
				//alert(monto1);
				$(this).closest('tr').find('.mon').val(monto1);
				$(this).closest('tr').find('.borrar').val(monto1);
			//}
			//else{
				//alert('No cuenta con esa cantidad');
				console.log('no cuenta');
			//}
		});

		$('body').on('change paste keyup',".pre", function(){
		//$('.cantidad').on('change paste keyup', function(){
			var anterior = $(this).closest('tr').find('.mon').val();
			var precio = $(this).closest('tr').find('.pre').val();
			var cantidad = $(this).closest('tr').find('.cantidad').val();
			var monto1 =  precio*cantidad;

			total = (total - anterior + monto1).toFixed(2);
			$("#total").val(total);
			
			//alert(monto1);
			monto1 = monto1.toFixed(2);
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
				alert(str2);
				
				$.ajax({
					cache: false,
					type: "POST",
					dataType: "json",
					url: "/inc/registrar_venta.php",
					data: str2,
					success: function(response){

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
				
				$(this ).hide();
				//return false;

			
		});

		
	    console.log( "ready!" );
	});
		
	</script>
</body>
</html>