<?php
include('inc/control.php');
if ($_SESSION['type']=='operador') {
	header("Location: dashboard.php");
}

include('inc/sdba/sdba.php'); // include main file
$ventas = Sdba::table('stock');
$ventas->left_join('producto','productos','id_producto'); 
$ventas->like('motivo','A-%');// creating table object
$ventas_list = $ventas->get(); 

$datos = '';
$i = 1;
foreach ($ventas_list as $value) {


	$datos .='<tr><td>'.$value['id_stock'].'</td> 
				<td>'.$value['fecha'].'</td> 
    			<td style="text-transform:uppercase;">'.$value['nom_prod'].'</td>
    			<td>'.$value['ingreso'].'</td>
    			<td>'.$value['egreso'].'</td>
    			<td>'.$value['stock'].'</td> 
    			<td>'.$value['motivo'].'</td> 
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
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/buttons/1.6.4/css/buttons.dataTables.min.css">
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
	      		<li class="active">
	      			<a href="ver_ajustes.php">Historial movimientos</a>
	      		</li>
	      	</ul>
	      </div>
	    </nav>
		<div class="kbg">
			<div class="cuerpofull">
				<div class="titulo">
					<h3>Historial movimientos</h3>
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
											    			<th>Fecha</th> 
											    			<th>Producto</th>
											    			<th>Ingreso</th>
											    			<th>Egreso</th>
											    			<th>Stock</th>
											    			<th>Motivo</th> 	
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
	 	<!-- Tab panes -->
		

	  
	<!-- jQuery (necessary for Bootstrap's JavaScript plugins) -->
	<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.3.1/jquery.min.js"></script>
	<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js"></script>
	<script src="https://cdn.jsdelivr.net/npm/jquery-validation@1.17.0/dist/jquery.validate.min.js"></script>
	<script src="//cdn.datatables.net/1.10.22/js/jquery.dataTables.min.js"></script>
	<script src="https://cdn.datatables.net/buttons/1.6.1/js/dataTables.buttons.min.js"></script>
  <script src="https://cdn.datatables.net/buttons/1.6.1/js/buttons.flash.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.1.3/jszip.min.js"></script>
  <script src="https://cdn.datatables.net/buttons/1.6.1/js/buttons.html5.min.js"></script>
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
		            "excel": "Excel",
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

		$('#datos').DataTable({
			dom: 'Bfrtip',
			buttons: [
	            {
	                extend: 'copyHtml5',
	                exportOptions: {
	                    columns: [ 0, ':visible' ]
	                }
	            },
	            {
	                extend: 'excelHtml5',
	                title:'Productos',
	                exportOptions: {
	                    columns: [ 0, 1, 2, 3, 4, 5 ]
	                },
	                footer: true,

	            },
	            {
	                extend: 'pdfHtml5',
	                exportOptions: {
	                    columns: [ 0, 1, 2, 3, 4, 5]
	                },
	                footer: true,
	                
	            }
	         
	        ]
			//"order": [[ 2, 'asc' ],[ 1, 'asc' ]]
		});	
	    console.log( "ready!" );

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