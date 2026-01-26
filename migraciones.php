<?php
include('inc/control.php');
include('inc/sdba/sdba.php');

$tienda = $_SESSION['tienda'];
 // include main file
$ventas = Sdba::table('migracion');
$ventas_list = $ventas->get(); 

$datos = '';
$i = 1;
foreach ($ventas_list as $value) {

	$datos .='<tr> 
    			<th scope="row">'.$i.'</th> 
    			<td>M-'.$value['id_migracion'].'</td>
    			<td>'.$value['destino'].'</td>
    			<td>'.$value['fecha'].'</td> 
    			<td><a title="Ver venta" class="btn btn-primary" alt="ver" href="ver_migracion.php?id='.$value['id_migracion'].'"><i class="fas fa-eye"></i></a></td>
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
    <link rel="stylesheet" type="text/css" href="//cdn.datatables.net/1.10.22/css/jquery.dataTables.min.css">
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
	        <?php menu('7'); ?>
	      </div>
	      <div class="submenu">
	      	<ul class="subtop-tabs">
	      		<li >
	      			<a href="migracion.php">Movimientos</a>
	      		</li>
	      		<li class="active">
	      			<a href="migraciones.php">Listar Movimientos</a>
	      		</li>
	      		<li >
	      			<a href="ver_ajustes.php"> Registro Log</a>
	      		</li>
	      	</ul>
	      </div>
	    </nav>
		<div class="kbg">
			<div class="cuerpofull">
				<div class="titulo">
					<h3>Listar Movimientos</h3>
				</div>
				<div class="container-fluid">
					<div class="row">
						<div class="col-md-12">
							<div class="kdashboard">
								<div class="row">
									<div class="col-md-12">
										<div class="panel panel-default pa">
											<div class="panel-body">
											    <table id="datos" class="table table-hover"> 
											    	<thead> 
											    		<tr> 
											    			<th>#</th>
											    			<th>Migracion</th> 
											    			<th>Destino</th>
											    			<th>Fecha</th>
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
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	 	<!-- Tab panes -->
		

	  
	<!-- jQuery (necessary for Bootstrap's JavaScript plugins) -->
	<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.3.1/jquery.min.js"></script>
	<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js"></script>
	<script src="https://cdn.jsdelivr.net/npm/jquery-validation@1.17.0/dist/jquery.validate.min.js"></script>
	<script src="assets/js/sweetalert2.all.min.js"></script>
	<script src="//cdn.datatables.net/1.10.22/js/jquery.dataTables.min.js"></script>
	<script >
	// A $( document ).ready() block.
	$(document ).ready(function() {
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

		$('#mesasa').load('inc/mobile/listar_mesas-all.php');
		var refreshId =  setInterval( function(){
		    $('#mesasa').load('inc/mobile/listar_mesas-all.php');//actualizas el div
		}, 1000 );

		var total = 0;

		$('body').on('change',"input[name='mesa']", function(){
		    var linkText = $('input:radio[name=mesa]:checked').val()
		    //$('#inimesa').show();
		    $('input:text[name=mesa]').val(linkText);
		    // $('#mimesa').html(linkText);
		    $('.i-mesa-i').html(linkText);
		    $( '.pt-r').remove();
		    $(this).addClass('mselect');
		    console.log( linkText );
		    $('#mostrarmesa').empty();
		    $('#mostrarmesa').load('inc/mobile/ver_mesa.php?mesa='+ linkText);

		});
		$('body').on('click',"#abrirmesa", function() {
			console.log('listo');
		  	$( "#inimesa" ).hide();
		  	//$( "#venta" ).show();
		  	var mesa =  $('input:hidden[name=mesa1]').val();
		  	var str1 = 'estado=1&mesa=' + mesa;
		  	console.log(mesa);
		  	$.ajax({	
		    	type:'GET',
				dataType: 'json',
			  	url: '/inc/mobile/cambiar_estado_mesa.php',
			  	data: str1,
			  	success: function(data1) {
			   	 	//alert(data1);
			   	 	
			  	}
			});
			$('#mostrarmesa').delay(1000).queue(function( nxt ) {
			    $(this).load('inc/mobile/ver_mesa.php?mesa='+ mesa);
			    nxt();
			});

		});

		$('body').on('click',".btn-ag", function() {
		  $( ".nav-c-p" ).hide();
		  $( ".tab-c-p" ).hide();
		  $( ".panel-ag" ).hide();
		  $( ".nav-ag" ).show();
		  $('.i-mesa').show();
		  $('.confirmar').show();
		});
		$('.p-item').click(function() {
			var plant = document.getElementById($(this).attr("id"));
			var precio = plant.getAttribute('id-price');
			var a = $(this).attr("value");
			var b = $(this).attr("id");
			$('.p-name').html(a);
			$('#id_p').val(b);
			$('.info-p').append( "" );
			$('input:hidden[name=name1_p]').val(a);
			$('input:hidden[name=price_p]').val(precio);
			//$('#list-p' ).append( "<p class='bg-success'>"+a+"id: "+b+"</p>" );
			//console.log(a);
			console.log(precio);
		});
		$('#confi').click(function() {
			
			var x = $('#q').val();
			var c = $('input:hidden[name=name1_p]').val();
			var d = $('input:hidden[name=idu_p]').val();
			var p = $('input:hidden[name=price_p]').val();
			var tot = parseInt($('input:text[name=total_v]').val());
			var t = x * p;
			var y = $('#id_p').attr("value");
			var z = y+'p';
			var monto = t;
			var total = monto + tot;
			console.log('total_anterior'+tot);

			$('#' + z).html(x);
			$('#list-p' ).append( "<div class='row bg-success pt-r'><div class='col-xs-1'><input type='text' value='"+ x +"' name='cantidad[]'></div><div class='col-xs-7'> <input type='text' value='"+ c +"' name='plato[]'><input type='text' value='"+coment+"' name='comentario[]'></div><div class='col-xs-1'><input type='text' value='"+ t +"' name='totalp[]'></div><div class='col-xs-1'><button id='rp' class='borrar' value='"+ t +"'><i class='fa fa-trash' aria-hidden='true'></i></button></div></div>" );
			$('#cf-p').show();
			console.log(x);
			console.log(c);
			console.log(d);
			console.log(t);
			$('input:text[name=total_v]').val(total);
			//$("#total").text('Total: ' + total);
			console.log(tot)
		});
		//borrar item
		    $("body").on('click', '.borrar', function () {
		    	var to = $(this).val();
		    	var tot = $('input:text[name=total_v]').val();
		    	var queda = parseInt(tot)-to;
			    $(this).closest('tr').remove();
			    $(this).parents('.pt-r').remove();
			    $('input:text[name=total_v]').val(queda);
			    console.log(to);

			});

		//para boton adicionar
		$('body').on('click', '.btn-number', function(e){
		    e.preventDefault();
		    
		    fieldName = $(this).attr('data-field');
		    type      = $(this).attr('data-type');
		    var input = $("input[name='"+fieldName+"']");
		    var currentVal = parseInt(input.val());
		    if (!isNaN(currentVal)) {
		        if(type == 'minus') {
		            
		            if(currentVal > input.attr('min')) {
		                input.val(currentVal - 1).change();
		            } 
		            if(parseInt(input.val()) == input.attr('min')) {
		                $(this).attr('disabled', true);
		            }

		        } else if(type == 'plus') {

		            if(currentVal < input.attr('max')) {
		                input.val(currentVal + 1).change();
		            }
		            if(parseInt(input.val()) == input.attr('max')) {
		                $(this).attr('disabled', true);
		            }

		        }
		    } else {
		        input.val(0);
		    }
		});
		$('body').on('focusin',".input-number", function(){
		   $(this).data('oldValue', $(this).val());
		});
		$('body').on('change',".input-number", function() {
		    
		    minValue =  parseInt($(this).attr('min'));
		    maxValue =  parseInt($(this).attr('max'));
		    valueCurrent = parseInt($(this).val());
		    
		    name = $(this).attr('name');
		    if(valueCurrent >= minValue) {
		        $(".btn-number[data-type='minus'][data-field='"+name+"']").removeAttr('disabled')
		    } else {
		        alert('Sorry, the minimum value was reached');
		        $(this).val($(this).data('oldValue'));
		    }
		    if(valueCurrent <= maxValue) {
		        $(".btn-number[data-type='plus'][data-field='"+name+"']").removeAttr('disabled')
		    } else {
		        alert('Sorry, the maximum value was reached');
		        $(this).val($(this).data('oldValue'));
		    }
		    
		    
		});
		$('body').on('keydown',".input-number", function (e) {
		        // Allow: backspace, delete, tab, escape, enter and .
		        if ($.inArray(e.keyCode, [46, 8, 9, 27, 13, 190]) !== -1 ||
		             // Allow: Ctrl+A
		            (e.keyCode == 65 && e.ctrlKey === true) || 
		             // Allow: home, end, left, right
		            (e.keyCode >= 35 && e.keyCode <= 39)) {
		                 // let it happen, don't do anything
		                 return;
		        }
		        // Ensure that it is a number and stop the keypress
		        if ((e.shiftKey || (e.keyCode < 48 || e.keyCode > 57)) && (e.keyCode < 96 || e.keyCode > 105)) {
		            e.preventDefault();
		        }
		    });
		$('body').on('click',".confirmar", function(){
			$( ".nav-c-p" ).show();
		  	$( ".tab-c-p" ).show();
		  	$( ".panel-ag" ).show();
		  	$( ".nav-ag" ).hide();
		  	$('.i-mesa').hide();
		  	$('.confirmar').hide();	
		});
		$('body').on('click',"#cf-p", function(e){
          e.preventDefault();

				
				//var tipoVenta = $('input:radio[name=pregunta]:checked').val();
				//DNI = $('#dni_ruc').val();

				var str2 = $('#venta').serialize();
				//alert(str2);
				
				$.ajax({
					cache: false,
					type: "POST",
					dataType: "json",
					url: "inc/registrar_venta_mobile.php",
					data: str2,
					success: function(response){

						if(response.respuesta == false){
							swal('Advertencia',response.mensaje,'warning');
							


						}else{

							swal('Perfecto', response.idventa,'success');
							//var id_venta = response.id_venta;
							console.log(response.mesa);
							$('#mostrarmesa').load('inc/mobile/ver_mesa.php?mesa='+ response.mesa);
							//document.location.href = "listar_ventas.php";
						
						}
					
					},
					error: function(){
						swal('Advertencia','Error General del Sistema','warning');
					}
				});
				
				$(this ).hide();
				//return false;

			
		});

		$('body').on('click',"#cerrar_mesa", function() {
			var id_v = $(this).val();
			var mesa = $('#mimesa').val();
			console.log(mesa);
			$('input:text[name=i_mesa]').val(mesa);
			var total = $('#total').val();
			$('input:text[name=total_venta1]').val(total);
			$('input:hidden[name=id_venta1]').val(id_v);
			console.log(id_v);
		  
		});

		$('body').on('click',"#s_c_mesa", function() {
			var id_venta1 = $('#id_venta1').val();
			var mesa = $('#mimesa').val();
			var tipo_pago = $('#tipo_pago').val();
			console.log(mesa);
			var str1 = 'mesa=' + mesa + '&id=' + id_venta1 + '&tipo_pago=' + tipo_pago;
			console.log(str1);
		  	$.ajax({	
		    	type:'GET',
				dataType: 'json',
			  	url: '/inc/mobile/cerrar_mesa.php',
			  	data: str1,
			  	success: function(data1) {
			   	 	console.log('bien');
			   	 	$('#modal-cerrar').modal('hide');
			   	 	$('#mostrarmesa').load('inc/mobile/ver_mesa.php?mesa='+mesa);
			   	 	
			  	}
			});
			
		  
		});

		$('body').on('click',"#btn-borrar", function() {
			var id_venta1 = $('input:hidden[name=id_venta]').val();
			console.log("borrado");
			console.log(id_venta1);
			var id_dv = $(this).val();
			console.log(id_dv);
			$('#id_pa').val(id_dv);	
		  
		});
		$('body').on('click',"#confirmar_acuse", function() {
			var id_venta1 = $('input:hidden[name=id_venta]').val();
			var id_dv1 = $('#id_pa').val();
			var acuseb = $('#acuseb').val();
			var str3 = 'id='+ id_dv1+'&acuseb='+acuseb+'&idventa='+id_venta1;

			alert(str3);

			$.ajax({
					cache: false,
					type: "POST",
					dataType: "json",
					url: "inc/mobile/acuseb.php",
					data: str3,
					success: function(response){

						if(response.respuesta == false){
							console.log('malo');
							


						}else{
							console.log('borro');
							console.log(response.mensaje);
							var id_dv3 = response.id_dv;
							$('#' + id_dv3).addClass( "borradoi" );
							$('#' + id_dv3 + ' > div > #vacuse').append( response.mensaje );
							$('#' + id_dv3 + ' > div > #btn-borrar').hide();
							$('#mborrar').modal('hide');

							

							//swal('Perfecto', response.idventa,'success');
							//var id_venta = response.id_venta;
							console.log(id_dv3);
							//$('#mostrarmesa').load('inc/mobile/ver_mesa.php?mesa='+ response.mesa);
							//document.location.href = "listar_ventas.php";
						
						}
					
					},
					error: function(demo){
						console.log(demo);
						
					}
				});

		  
		});
		



		

		
	    console.log( "ready!" );
	});
		
	</script>
</body>
</html>