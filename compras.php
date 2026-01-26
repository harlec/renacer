<?php
include('inc/control.php');
include('inc/sdba/sdba.php'); // include main file

	$ventas1 = Sdba::table('compras'); // creating table object
	$ventas1->where('estado!=','2' );
	$ventas1->left_join('proveedor','proveedores','id_proveedor');
	$ventas1->order_by('id_compra','desc');
	$ventas_list1 = $ventas1->get();
	
	// if ($value['estado']=='1') {
	// 	$ocultar = 'ocultar';
	// 	$comprobante = '<a title="Ver comprobante" target="_BLANK" href="'.$ventas_list1['url'].'">'.$ventas_list1['tipo'].''.$ventas_list1['numero'].'</a>';
	// }
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
    			<td><a title="Ver venta" class="" alt="ver" href="ver_compra.php?id='.$value['id_compra'].'"><i class="fas fa-eye"></i></a><button class="btn-custom" id="borrar" value="'.$value['id_compra'].'" alt="borrar"><img src="assets/img/trash.png" /></button></td> 
    		  </tr>';
    
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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/limonte-sweetalert2/10.5.0/sweetalert2.min.css" integrity="sha512-YpZXdiMhuP3woCdvg0ou2UPj6l4KQUuf3gbMXTNMgtqTakMInX7h+64CTh+UIvYdA7ctBU2BAA/h4eEhoMEmsg==" crossorigin="anonymous" />
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
	      		<li  >
	      			<a href="compra.php">Registrar Compra</a>
	      		</li>
	      		<li class="active">
	      			<a href="compras.php">Listar Compras</a>
	      		</li>
	      		<li >
	      			<a href="proveedores.php">Proveedores</a>
	      		</li>
	      	</ul>
	      </div>
	    </nav>
		<div class="kbg">
			<div class="cuerpofull">
				<div class="titulo">
					<h3>Listar compras</h3>
				</div>
				<div class="container-fluid">
					<div class="row">
						<div class="col-md-12">
							<div class="kdashboard">
								<div class="row">
									<div class="col-md-12">
										<div class="panel panel-default pa">
											<div class="panel-body table-responsive">
											    <table id="datos" class="table table-hover"> 
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
	<script src="https://cdnjs.cloudflare.com/ajax/libs/limonte-sweetalert2/10.5.0/sweetalert2.min.js" integrity="sha512-V9JHp52ZkrbVVjJqNz/XXYMUOyUfzaGKEGrcD2Ual7n39+UR1yJK0numAHZqkhhGTAH/Klj0KUe4btAZXccw9w==" crossorigin="anonymous"></script>
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

		$('body').on('click',"#borrar", function() {
	    	Swal.fire({
			  title: 'Seguro de borrar?',
			  text: "Tu no puedes revertir esto!",
			  icon: 'warning',
			  showCancelButton: true,
			  confirmButtonColor: '#3085d6',
			  cancelButtonColor: '#d33',
			  confirmButtonText: 'Si, borrar!'
			}).then((result) => {
			  if (result.isConfirmed) {
			  	var id = $(this).val();
				var str1 = 'id=' + id;
			  	$.ajax({	
			    	type:'GET',
					dataType: 'json',
				  	url: '/inc/borrar_compra.php',
				  	data: str1,
				  	success: function(data1) {
				   	 	console.log('borrado');
				   	 	document.location.href = "compras.php"; 	
				  	}
				});
			    Swal.fire(
			      'Borrado!',
			      'El registro fue borrado correctamente.',
			      'success'
			    )
			  }
			})
 
		});



		

		
	    console.log( "ready!" );
	});
		
	</script>
</body>
</html>