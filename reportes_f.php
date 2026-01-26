<?php
include('inc/control.php');
if ($_SESSION['type']=='operador') {
	header("Location: dashboard.php");
}

include('inc/sdba/sdba.php'); // include main file
$ventas = Sdba::table('productos');
$ventas->left_join('categoria','categorias','id_categoria'); // creating table object
$ventas_list = $ventas->get(); 

$datos = '';
$i = 1;
foreach ($ventas_list as $value) {

	$marca = Sdba::table('marca');
	$marca->where('id_marca',$value['marca']);
	//$marca->order_by('id_stock','desc');
	$marca1 = $marca->get_one();
	$marcan = $marca1['marca'];
	$fecha = $_POST['fechaini'];

	$stockt = 0;
	$stock = Sdba::table('stock');
	$stock->where('producto',$value['id_producto'])->and_where('fecha <=',$fecha);
	$stock->order_by('id_stock','desc');
	$stock1 = $stock->get_one();
	$stocks .='<tr><td>Tienda 1</td><td>'.$stock1['stock'].'</td></tr>';
	$stockt = $stockt + $stock1['stockt'];

	//unidades
	$unidad = Sdba::table('unidades');
	$unidad->where('id_unidad',$value['unidad_prod']);
	//$unidad->order_by('id_stock','desc');
	$unidad1 = $unidad->get_one();
	$unidadn = $unidad1['nombre'];

	

	$datos .='<tr><td>'.$value['id_producto'].'</td> 
    			<td style="text-transform:uppercase;">'.$value['nom_prod'].'</td>
    			<td>'.$unidadn.'</td>
    			<td>'.$marcan.'</td>
    			<td>'.$value['nom_cat'].'</td>
    			<td>'.$value['exonerada'].'</td>
    			<td>'.$stockt.'</td> 
    			<td>'.$value['precio_venta'].'</td> 
    			<td><a class="" alt="ver" href="editar_producto.php?id='.$value['id_producto'].'"><img src="assets/img/edit.png"/></a><button class="btn-custom" id="borrar" value="'.$value['id_producto'].'" alt="boleta"><img src="assets/img/trash.png" /></button></td> 
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
	        <?php menu('5'); ?>
	      </div>
	      <div class="submenu">
	      	<ul class="subtop-tabs">
	      		<li class="active">
	      			<a href="reportes.php">Reporte Stock</a>
	      		</li>
	      		<li >
	      			<a href="reportes_vd.php">Ventas diarias</a>
	      		</li>
	      		<li >
	      			<a href="reporte_ventas.php">Reporte ventas</a>
	      		</li>
	      		<li >
	      			<a href="reporte_compras.php">Reporte compras</a>
	      		</li>
	      		<li>
	      			<a href="reporte_kardex.php">Reporte Kardex</a>
	      		</li>
	      		<li>
	      			<a href="reporte_mv.php">Reporte Mas vendido</a>
	      		</li>
	      	</ul>
	      </div>
	    </nav>
		<div class="kbg">
			<div class="cuerpofull">
				<div class="titulo">
					<h3>Reporte stock <?php echo $fecha; ?></h3>
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
											    			<th>Producto</th>
											    			<th>Unidad</th>
											    			<th>Marca</th>
											    			<th>Categoría</th>
											    			<th>Exonerada Igv</th>
											    			<th>Stock</th>
											    			<th>Precio</th> 
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
	 	<!-- Tab panes -->
		

	  
	<!-- jQuery (necessary for Bootstrap's JavaScript plugins) -->
	<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.3.1/jquery.min.js"></script>
	<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js"></script>
	<script src="https://cdn.jsdelivr.net/npm/jquery-validation@1.17.0/dist/jquery.validate.min.js"></script>
	<script src="//cdn.datatables.net/1.10.22/js/jquery.dataTables.min.js"></script>
	<script src="https://cdn.datatables.net/buttons/1.6.1/js/dataTables.buttons.min.js"></script>
  <script src="https://cdn.datatables.net/buttons/1.6.1/js/buttons.flash.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.1.3/jszip.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/pdfmake.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/vfs_fonts.js"></script>
  <script src="https://cdn.datatables.net/buttons/1.6.1/js/buttons.html5.min.js"></script>
  <script src="https://cdn.datatables.net/buttons/1.6.1/js/buttons.print.min.js"></script>
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
		            "pdf": "PDF",
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
	                extend: 'excel',
	                title: 'Reporte stock al <?php echo $fecha; ?>  ',
	                footer: true
	            },
	            {
	                extend: 'pdf',
	                title: 'Reporte stock al <?php echo $fecha; ?>  ',
	                footer: true
	            }
	        ]

		});		
	    console.log( "ready!" );
	});
		
	</script>
</body>
</html>