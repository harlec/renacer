<?php
include('inc/control.php');
if ($_SESSION['type']=='operador') {
	header("Location: dashboard.php");
}

include('inc/sdba/sdba.php'); // include main file
$ventas = Sdba::table('ventas');
//$ventas->left_join('categoria','categorias','id_categoria'); // creating table object
$ventas->where('fecha',date('Y-m-d'));
$ventas_list = $ventas->get(); 

$datos = '';
$i = 1;
foreach ($ventas_list as $value) {

	$ocultar='';
	$comprobante = '';
	$id = $value['id_venta'];

	$ventas1 = Sdba::table('comprobantes'); // creating table object
	$ventas1->where('venta', $id);
	$ventas1->order_by('id_comprobante','desc');
	$ventas_list1 = $ventas1->get_one();
	
	if ($value['estado']=='1') {
		$ocultar = 'ocultar';
		$comprobante = '<a title="Ver comprobante" target="_BLANK" href="'.$ventas_list1['url'].'">'.$ventas_list1['tipo'].''.$ventas_list1['numero'].'</a>';
	}
	if ($value['tipo']=='1') {
		$tipo = 'Contado';
	}
	else{
		$tipo = 'Credito';
	}
	switch ($value['forma']) {
		case '1':
			$forma = 'Efectivo';
			break;
		case '2':
			$forma = 'Tar. Debito';
			break;
		case '3':
			$forma = 'Tar. Crédito';
			break;
	}


	$datos .='<tr> 
    			<th scope="row">'.$i.'</th> 
    			<td>v-'.$value['id_venta'].'</td>
    			<td>'.$tipo.'</td>
    			<td>'.$forma.'</td>
    			<td>'.$value['fecha'].'</td> 
    			<td>'.$value['total'].'</td> 
    			<td>'.$comprobante.'</td> 
    			<td><a title="Ver venta" class="" title="ver" href="ver_venta.php?id='.$value['id_venta'].'"><img src="assets/img/eye.png" /></a><a class="btn btn-success '.$ocultar.'" href="factura.php?id='.$value['id_venta'].'" title="factura electrónica"><i class="fas fa-file-invoice-dollar"></i></a><a class="btn btn-danger '.$ocultar.'" href="boleta.php?id='.$value['id_venta'].'" title="boleta electrónica"><i class="fab fa-bitcoin"></i></a><button class="btn-custom" id="borrar" value="'.$value['id_venta'].'" title="borrar"><img src="assets/img/trash.png" /></button></td> 
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
	      		<li >
	      			<a href="reportes.php">Reporte Stock</a>
	      		</li>
	      		<li class="active">
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
			<div class="cuerpofull">
				<div class="titulo">
					<h3>Reporte Venta diaria</h3>
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
											    			<th>Venta</th> 
											    			<th>Tipo</th>
											    			<th>Forma</th>
											    			<th>Fecha</th> 
											    			<th>Monto</th> 
											    			<th>Comprobante</th> 
											    			<th>Opciones</th>

											    		</tr> 
											    	</thead> 
											    	<tbody> 
											    		<?php echo $datos; ?>
											    	</tbody> 
											    	<tfoot>
								                         <tr>
								                             <th colspan="5" style="text-align:right"></th>
								                             <th ></th><th ></th><th ></th>
								                         </tr>
								                    </tfoot>
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
	            'excel',
	            'pdf'
	        ],
	        "footerCallback": function ( row, data, start, end, display ) {
                var api = this.api(), data;

                // Remove the formatting to get integer data for summation
                var intVal = function ( i ) {
                    return typeof i === 'string' ?
                        i.replace(/[\$,]/g, '')*1 :
                        typeof i === 'number' ?
                            i : 0;
                };

                // Total over all pages
                total = api
                    .column(5)
                    .data()
                    .reduce( function (a, b) {
                        return Math.ceil(intVal(a) + intVal(b));
                    },0 );

                // Total over this page
                // pageTotal = api
                //     .column( 4, { page: 'current'} )
                //     .data()
                //     .reduce( function (a, b) {
                //         return intVal(a) + intVal(b);
                //     }, 0 );

                // Update footer
                $( api.column( 5 ).footer() ).html(total);
                console.log(total);
            }
		});		
	    console.log( "ready!" );
	});
		
	</script>
</body>
</html>