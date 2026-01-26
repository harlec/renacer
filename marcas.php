<?php
include('inc/control.php');
if ($_SESSION['type']=='operador') {
	header("Location: dashboard.php");
}

include('inc/sdba/sdba.php'); // include main file
$ventas = Sdba::table('marca');
//$ventas->left_join('unidad_prod','unidades','id_unidad'); // creating table object
$ventas_list = $ventas->get(); 

$datos = '';
$i = 1;
foreach ($ventas_list as $value) {

	$datos .='<tr><td>'.$value['id_marca'].'</td> 
    			<td id ="'.$value['id_marca'].'" class="nom_cat">'.$value['marca'].'</td> 
    			<td><button class="editar_c btn-custom" alt="editar" value="'.$value['id_marca'].'"><img src="assets/img/edit.png"/></td> 
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
	        <?php menu('3'); ?>
	      </div>
	      <div class="submenu">
	      	<ul class="subtop-tabs">
	      		<li >
	      			<a href="agregar_producto.php">Registrar producto</a>
	      		</li>
	      		<li >
	      			<a href="ver_productos.php">Listar productos</a>
	      		</li>
	      		<li >
	      			<a href="categorias.php">Categorías</a>
	      		</li>
	      		<li class="active">
	      			<a href="marcas.php">Marcas</a>
	      		</li>
	      		<li >
	      			<a href="variantes.php">Variantes</a>
	      		</li>
	      	</ul>
	      </div>
	    </nav>
		<div class="kbg">
			<div class="cuerpo">
				<div class="titulo">
					<div class="container-fluid">
						<div class="row">
							<div class="col-xs-9">
								<h3>Marca</h3>
							</div>
							<div class="col-xs-3">
								<button id="addnew" style="margin-top:6px;" class="btn btn-success">+ Agregar marca</button>
							</div>
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
											    			<th>Marca</th>
											    			<th>Opciones</th> 
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
					<h3 id="tituc">Nueva Marca</h3>
				</div>
				<div class="container-fluid">
					<div class="row">
						<div class="col-xs-12">
							<form>
								<div class="form-group">
								    <label for="exampleInputEmail1">Marca</label>
								    <input class="form-control" type="text" name='categoria' id="categoria"><br>
								    <input type="hidden" id="id" name="id">
								</div>
								
								<div class="row">
									<div class="col-xs-6">
										<button id="cancel" class="btn btn-black btn-block" type="button">Cancelar</button>
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
	    	var categoria = $('#categoria').val();
	    	var opcion = $(this).val();
	    	var id_ca = $('#id').val();
	    	//alert(categoria);
	    	var str1 = 'categoria=' + categoria+'&accion=' + opcion+'&id=' + id_ca;
	    	//alert(str1);
		  	$.ajax({	
		    	type: "POST",
				dataType: 'json',
			  	url: '/inc/registrar_marca.php',
			  	data: str1,
			  	success: function(data1) {
			   	 	console.log('guardado');
			   	 	$('#categoria').val('');
			   	 	var row_id = data1.id;
			   	 	if (data1.accion =='nuevo') {
				   	 	table1.rows.add(
					       [[ data1.id, data1.categoria,"<button>x</button>" ]]
					    ).draw();
			   	 	}
			   	 	else{
			   	 		table1.cell($('#' + row_id)).data(data1.categoria).draw();
			   	 	}	
			  	},
			  	error: function(reponse1){
						alert(reponse1);
					}
			});

	    	 
	   	});

	   	$('body').on('click',".editar_c", function() {
	    	var id_categoria = $(this).val();
	    	//alert(id_categoria);
	    	var nom_categoria1 = $(this).closest('tr').find('.nom_cat').text();
	    	$('#tituc').text('Editar marca');
	    	$('#categoria').val(nom_categoria1);
	    	$('#guardar_cate').text('Editar');
	    	$('#guardar_cate').val('editar');
	    	$('#id').val(id_categoria);
	    	//var str1 = 'categoria=' + id_categoria+'&accion=editar';
	    	//alert(str1);
		 //  	$.ajax({	
		 //    	type: "POST",
			// 	dataType: 'json',
			//   	url: '/inc/registrar_categoria.php',
			//   	data: str1,
			//   	success: function(data1) {
			//    	 	console.log('guardado');
			//    	 	$('#categoria').val('');
			//    	 	table1.rows.add(
			// 	       [[ data1.id, data1.categoria,"<button>x</button>" ]]
			// 	    ).draw();
			   	 	
			//   	}
			// });

	    	 
	   	});
	   	$('body').on('click',"#addnew", function() {
	    	$('#tituc').text('Nueva marca');
	    	$('#guardar_cate').text('Guardar');
	    	$('#guardar_cate').val('nuevo');
	    	$('#categoria').val("");
	    	 
	   	});
	   	$('body').on('click',"#cancel", function() {
	    	$('#tituc').text('Nueva marca');
	    	$('#guardar_cate').text('Guardar');
	    	$('#guardar_cate').val('nuevo');
	    	$('#categoria').val("");
	    	 
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
	});
		
	</script>
</body>
</html>