<?php
include('inc/control.php');
if ($_SESSION['type']=='operador') {
	header("Location: dashboard.php");
}

include('inc/sdba/sdba.php'); // include main file
$ventas = Sdba::table('proveedores');
//$ventas->left_join('unidad_prod','unidades','id_unidad'); // creating table object
$ventas_list = $ventas->get(); 

$datos = '';
$i = 1;
foreach ($ventas_list as $value) {

	$datos .='<tr><td>'.$value['id_proveedor'].'</td> 
				<td id ="'.$value['doc_identidad'].'" class="doc">'.$value['doc_identidad'].'</td> 
    			<td id ="'.$value['proveedor'].'" class="proveedor">'.$value['proveedor'].'</td> 
    			<td id ="'.$value['direccion'].'" class="direccion">'.$value['direccion'].'</td> 
    			<td id ="'.$value['telefono'].'" class="telefono">'.$value['telefono'].'</td> 
    			<td id ="'.$value['email'].'" class="email">'.$value['email'].'</td> 
    			<td><button class="editar_c btn-custom" alt="editar" value="'.$value['id_proveedor'].'"><img src="assets/img/edit.png"/></button></td> 
    		  </tr>';
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
	        <?php menu('6'); ?>
	      </div>
	      <div class="submenu">
	      	<ul class="subtop-tabs">
	      		<li>
	      			<a href="compra.php">Registrar Compra</a>
	      		</li>
	      		<li >
	      			<a href="compras.php">Listar Compras</a>
	      		</li>
	      		<li class="active">
	      			<a href="proveedores.php">Proveedores</a>
	      		</li>
	      	</ul>
	      </div>
	    </nav>
		<div class="kbg">
			<div class="cuerpo">
				<div class="titulo">
					<div class="row">
						<div class="col-xs-9">
							<h3>Proveedores</h3>
						</div>
						<div class="col-xs-3">
							<button id="addnew" style="margin-top:6px;" class="btn btn-success">+ Agregar proveedor</button>
						</div>
					</div>
					
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
											    			<th>Id</th> 
											    			<th>Documento</th>
											    			<th>Proveedor</th>
											    			<th>Dirección</th>
											    			<th>Telefono</th>
											    			<th>Email</th>
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
			<div class="detalles">
				<div class="titulo-n">
					<h3 id="tituc">Nuevo proveedor</h3>
				</div>
				<div class="container-fluid">
					<div class="row">
						<div class="col-xs-12">
							<form>
								<div class="form-group">
								    <label for="exampleInputEmail1">Ruc</label>
								    <input class="form-control" type="text" name='ruc' id="ruc"><br>
								    <input type="hidden" id="id" name="id">
								</div>
								<div class="form-group">
								    <label for="exampleInputEmail1">Denominación</label>
								    <input class="form-control" type="text" name='denominacion' id="denominacion"><br>
								</div>
								<div class="form-group">
								    <label for="exampleInputEmail1">Dirección</label>
								    <textarea class="form-control" name='direccion' id="direccion"></textarea>
								</div>
								<div class="form-group">
								    <label for="exampleInputEmail1">Celular</label>
								    <input type="text" class="form-control" name='celular' id="celular">
								</div>
								<div class="form-group">
								    <label for="exampleInputEmail1">Email</label>
								    <input type="text" class="form-control" name='email' id="email">
								</div>
								<div class="row">
									<div class="col-xs-6">
										<button class="btn btn-black btn-block" type="button">Cancelar</button>
									</div>
									<div class="col-xs-6">
										<button id="guardar_cate" class="btn btn-success btn-block" type="button" value="nuevo">Guardar</button>
									</div>
								</div>
							</form>
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

		var table1 = $('#datos').DataTable();	
	    console.log( "ready!" );

	    $('body').on('click',"#guardar_cate", function() {
	    	var ruc = $('#ruc').val();
	    	var denominacion = $('#denominacion').val();
	    	var direccion = $('#direccion').val();
	    	var telefono = $('#celular').val();
	    	var email = $('#email').val();
	    	var opcion = $(this).val();
	    	var id_ca = $('#id').val();
	    	//alert(categoria);
	    	var str1 = 'ruc=' + ruc +'&denominacion=' + denominacion + '&direccion=' + direccion +'&accion=' + opcion+'&id=' + id_ca+'&telefono=' + telefono+'&email=' + email;
	    	alert(str1);
		  	$.ajax({	
		    	type: "POST",
				dataType: 'json',
			  	url: '/inc/registrar_proveedor.php',
			  	data: str1,
			  	success: function(data1) {
			   	 	console.log('guardado');
			   	 	$('#categoria').val('');
			   	 	var row_id = data1.id;
			   	 	if (data1.accion =='nuevo') {
				   	 	table1.rows.add(
					       [[ data1.id, data1.ruc, data1.denominacion, data1.direccion,'<button class="editar_c btn-custom" alt="editar" value="'+data1.id+'"><img src="assets/img/edit.png"/></button>' ]]
					    ).draw();
			   	 	}
			   	 	else{
			   	 		table1.cell($('#' + row_id)).data(data1.categoria).draw();
			   	 	}	
			  	},
			  	error: function(reponse1){
						alert(reponse1);
						console.log(response1);
					}
			});

	    	 
	   	});

	   	$('body').on('click',".editar_c", function() {
	    	var id_proveedor = $(this).val();
	    	//alert(id_categoria);
	    	var doc = $(this).closest('tr').find('.doc').text();
	    	var proveedor = $(this).closest('tr').find('.proveedor').text();
	    	var direccion = $(this).closest('tr').find('.direccion').text();
	    	var telefono = $(this).closest('tr').find('.telefono').text();
	    	var email = $(this).closest('tr').find('.email').text();
	    	$('#tituc').text('Editar proveedor');
	    	$('#ruc').val(doc);
	    	$('#denominacion').val(proveedor);
	    	$('#direccion').val(direccion);
	    	$('#celular').val(telefono);
	    	$('#email').val(email);
	    	$('#guardar_cate').text('Editar');
	    	$('#guardar_cate').val('editar');
	    	$('#id').val(id_proveedor);

	    	 
	   	});
	   	$('body').on('click',"#addnew", function() {
	    	$('#tituc').text('Nuevo proveedor');
	    	$('#ruc').val("");
	    	$('#denominacion').val("");
	    	$('#direccion').val("");
	    	$('#guardar_cate').text('Guardar');
	    	$('#guardar_cate').val('nuevo');
	    	 
	   	});

	    $('body').on('click',"#borrar", function() {
			var id = $(this).val();
			var str1 = 'id=' + id;
		  	$.ajax({	
		    	type:'GET',
				dataType: 'json',
			  	url: '/inc/borrar_producto.php',
			  	data: str1,
			  	success: function(data1) {
			   	 	console.log('borrado');
			   	 	document.location.href = "ver_productos.php";
			   	 	
			  	}
			});
		});
		$( "#ruc" ).on('change paste keyup', function() {
			//console.log(this.value.length);
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
                  /*console.log(xhr);
                  console.log('hola');
                  console.log(xhr.response);*/

                  xhr.onload = function () {
				    if (xhr.readyState === xhr.DONE) {
				        if (xhr.status === 200) {

				        	var hugo = JSON.parse(xhr.response); 
				        	//console.log(hugo.ruc);
				            //console.log(xhr.response);
				            //$('#ruc').val(hugo.ruc);
				            $('#denominacion').val(hugo.nombre_o_razon_social);
				            $('#direccion').val(hugo.direccion);
				            //console.log(xhr.responseText);
				        }
				    }
				};
			}
  
		});
	});
		
	</script>
</body>
</html>