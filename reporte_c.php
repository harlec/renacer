<?php

include('inc/control.php');
// if ($_SESSION['type']=='operador') {
// 	header("Location: dashboard.php");
// }

include('inc/sdba/sdba.php'); 

//recibimos las varibles
$fechaini = $_POST['fechaini'];
$fechafin = $_POST['fechafin'];


	$ventas1 = Sdba::table('compras'); // creating table object
	$ventas1->where('estado!=','2' )->and_where('fecha <=', $fechafin)->and_where('fecha >=',$fechaini);;
	$ventas1->left_join('proveedor','proveedores','id_proveedor');
	$ventas1->order_by('id_compra','desc');
	$ventas_list1 = $ventas1->get();

	foreach ($ventas_list1 as $value) {

		if ($value['exonerada']=='no') {
			$subtotal = ($value['total'])/(1.18);
			$igv = $value['total']-$subtotal;
		}
		else{
			$subtotal = $value['total'];
			$igv = 0;
		}

		

		$moneda = $value['moneda'];
		if ($moneda==0) {
			$mone = 'Soles ';
		}
		else{
			$mone = 'Dolares ';
		}

		$datos .='<tr> 
    			<th scope="row">'.$value['id_compra'].'</th> 
    			<td>'.$value['fecha'].'</td> 
    			<td>'.$value['fecha_despacho'].'</td> 
    			<td>01</td>  
    			<td>'.$value['guia'].'</td>
    			<td>'.$value['serie_f'].'</td>
    			<td>'.$value['numero_f'].'</td>
    			<td>'.$value['doc_identidad'].'</td>
    			<td>'.$value['proveedor'].'</td>
    			<td>'.$mone.'</td>
    			<td>'.round($subtotal,2).'</td>
    			<td>'.round($igv,2).'</td>
    			<td>'.round($value['total'],2).'</td> 
    			<td><a title="Ver venta" class="" alt="ver" href="ver_compra.php?id='.$value['id_compra'].'"><img src="assets/img/eye.png" /></a></td> 
    		  </tr>';
    
	}

?>

<!DOCTYPE html>
<html lang="es">
<head>
	<meta charset="UTF-8">
	<title>Sistema - Reporte ventas</title>
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" type="text/css" href="/assets/css/bootstrap.min.css">
    <link rel="stylesheet" type="text/css" href="/assets/css/custom.css">
    <link rel="stylesheet" href="/assets/css/jquery-ui.min.css">
    
    <link rel="stylesheet" type="text/css" href="//cdn.datatables.net/1.10.22/css/jquery.dataTables.min.css">
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/buttons/1.6.4/css/buttons.dataTables.min.css">
    
</head>

<body class="mobile dashboard">
	<div class="container-fluid">
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
	      		<li >
	      			<a href="reportes.php">Reporte Stock</a>
	      		</li>
	      		<li >
	      			<a href="reportes_vd.php">Ventas diarias</a>
	      		</li>
	      		<li >
	      			<a href="reporte_ventas.php">Reporte ventas</a>
	      		</li>
	      		<li class="active">
	      			<a href="reporte_compras.php">Reporte compras</a>
	      		</li>
	      		<li>
	      			<a href="reporte_kardex.php">Reporte Kardex</a>
	      		</li>
	      		<li >
	      			<a href="reporte_mv.php">Reporte Mas vendido</a>
	      		</li>
	      		<!-- <li >
	      			<a href="categorias.php">Categorías</a>
	      		</li>
	      		<li >
	      			<a href="marcas.php">Marcas</a>
	      		</li> -->
	      	</ul>
	      </div>
	    </nav>
		<div class="kbg">
			<div class="row">
				<div class="col-md-12">
					<div class="kdashboard">
						<div class="row">
							<div class="col-md-12">
								<div class="panel panel-default pa">
									<div class="panel-body table-responsive">
										<h3 class="">Reporte Compras</h3>
										<table id="datos">
											<thead>
												<tr> 
									    			<th>#</th> 
									    			<th>Fecha Ingreso</th>
									    			<th>Fecha despacho</th>
									    			<th>Tipo</th> 
									    			<th>Guia</th> 
									    			<th>Serie</th>
									    			<th>Numero</th>
									    			<th>Documento</th>
									    			<th>Denominacion</th>
									    			<th>Moneda</th>
									    			<th>Gravada</th>
									    			<th>Igv</th>
									    			<th>Total</th>  
									    			<th>Opciones</th>
									    		</tr> 
											</thead>
											<tbody>
												<?php echo $datos; ?>
											</tbody>
										</table>
										<br><br>

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
	<script src="/assets/js/jquery-ui.min.js"></script> 
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
	            'excel',
	            'pdf'
	        ]
		});	
		
	    console.log( "ready!" );
	});
		
	</script>
</body>
</html>