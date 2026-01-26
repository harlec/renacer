<?php
 // include main file
include('inc/control.php');
if ($_SESSION['type']=='operador') {
	header("Location: dashboard.php");
}

include('inc/sdba/sdba.php');

$id = $_GET['id'];
//obtnemos los datos del producto
$ventas = Sdba::table('ventas'); // creating table object
$ventas->where('cliente', $id);
$ventas_list = $ventas->get();
$t = '';
$tipo = '';
foreach ($ventas_list as $value) {
	if ($value['tipo']=='1') {
		$tipo = 'Contado';
	}
	elseif ($value['tipo']=='2') {
		$tipo = 'Credito';
	}
	$t .='<tr>
			<td>'.$value['id_venta'].'</td>
			<td>'.$value['fecha'].'</td>
			<td>'.$tipo.'</td>
			<td>'.$value['total'].'</td>
		  </tr>';
}

//obtenemos los stock de las tiendas
$variantes = Sdba::table('abonos');
$variantes->where('cliente',$id);
$v = $variantes->get();
//$total = $variantes->sum('stock');
$stocks = '';
foreach ($v as $value) {
	
		$abonos .='<tr><td>'.$value['fecha'].'</td><td>'.$value['monto'].'</td></tr>';
	
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
    <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.7.2/css/all.css" integrity="sha384-fnmOCqbTlWIlj8LyTjo7mOUStjsKC4pOpQbqyi7RrhN7udi9RwhKkMHpvLbHG9Sr" crossorigin="anonymous">
    <link rel="stylesheet" type="text/css" href="https://cdnjs.cloudflare.com/ajax/libs/limonte-sweetalert2/7.0.5/sweetalert2.min.css">
    <link rel="stylesheet" type="text/css" href="/assets/css/select2.min.css">
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
	      			<a class="" href="agregar_cliente.php">Registrar Cliente</a>
	      		</li>
	      		<li >
	      			<a class="" href="ver_clientes.php">Listar Clientes</a>
	      		</li>
	      	</ul>
	      </div>
	    </nav>
		<div class="kbg">
			<div class="cuerpo">
				<div class="titulo">
					<h3>Ver Cliente </h3>
				</div>
				<div class="container-fluid">
					<div class="row">
						<div class="col-md-12">
							<div class="kdashboard">
								<div class="row">
									<div class="col-md-12">
										<div class="panel panel-default pa">
											<div class="panel-body">
										    	<div class="row">
										    		<div class="col-md-12 table-reponsive">
										    			<table class="table" id="datos">
										    				<thead>
										    					<tr>
										    						<th>Id</th>
										    						<th>Fecha</th>
										    						<th>Tipo</th>
										    						<th>Monto</th>
										    					</tr>
										    				</thead>
										    				<tbody>
										    					<?php echo $t; ?>
										    				</tbody>
										    			</table>
										    		</div>
										    	</div>
											</div>
										</div>
									</div>
									.
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
			<div class="detalles">
				<div class="titulo">
					<h3>Abono</h3>
				</div>
				<div class="container-fluid">
					<div class="row">
						<div class="col-md-12">
						    <table class="table">
						      <thead>
						      	<tr>
							      	<th>Fecha</th>
							      	<th>Abono</th>
							    </tr>
						      </thead>
						      <tbody>
						      	<?php echo $abonos; ?>
						      </tbody>
						    </table>

						    <h3>Agregar registro</h3>
						    <form id="stockfrm">
						    	<input type="hidden" name="cliente" value="<?php echo $id; ?>">
						    	<div class="form-group">
						    		<label>Fecha</label>
						    		<input class="form-control" type="date" name="fecha">
						    	</div>
						    	<div class="form-group">
						    		<label>Cantidad</label>
						    		<input class="form-control" type="text" name="cantidad">
						    	</div>
						    	<div class="form-group">
						    		<label>Motivo</label>
						    		<input class="form-control" type="text" name="motivo" placeholder="Ingrese motivo">
						    	</div>
						    	<br>
						    	<button type="button" id="agregar_stock" class="btn btn-success btn-block btn-lg">Agregar Abono</button>
						    </form>
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
	<script src="https://cdnjs.cloudflare.com/ajax/libs/limonte-sweetalert2/7.0.5/sweetalert2.min.js"></script>
	<script src="/assets/js/select2.full.min.js"></script>
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
		$('#categorias').select2();
		$('#marca').select2();

		$('body').on('click',"#guardar_venta", function(e){
            e.preventDefault();
			var str2 = $('#venta').serialize();
			$.ajax({
				cache: false,
				type: "POST",
				dataType: "json",
				url: "/inc/editar_producto.php",
				data: str2,
				success: function(response){

					if(response.respuesta == false){
						swal('Advertencia',response.mensaje,'warning');

					}else{

						swal('Perfecto', response.idventa,'success');
						//var id_venta = response.id_venta;
						console.log(response.mesa);
						//$('#mostrarmesa').load('inc/mobile/ver_mesa.php?mesa='+ response.mesa);
						document.location.href = "ver_productos.php";
					
					}
				
				},
				error: function(){
					swal('Advertencia','Error General del Sistema','warning');
				}
			});
			
			$(this ).hide();
			//return false;
		});

		$('body').on('click',"#agregar_stock", function(e){
            e.preventDefault();
			var str2 = $('#stockfrm').serialize();
			$.ajax({
				cache: false,
				type: "POST",
				dataType: "json",
				url: "/inc/agregar_abono.php",
				data: str2,
				success: function(response){

					if(response.respuesta == false){
						swal('Advertencia',response.mensaje,'warning');

					}else{

						swal('Perfecto', response.idventa,'success');
						//var id_venta = response.id_venta;
						console.log(response.mesa);
						//$('#mostrarmesa').load('inc/mobile/ver_mesa.php?mesa='+ response.mesa);
						document.location.href = "ver_cliente.php?id="+ response.id;
					
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