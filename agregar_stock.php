<?php
 // include main file
include('inc/control.php');
if ($_SESSION['type']=='operador') {
	header("Location: dashboard.php");
}

include('inc/sdba/sdba.php');

$id = $_GET['id'];
//obtnemos los datos del producto
$ventas = Sdba::table('productos'); // creating table object
$ventas->where('id_producto', $id);
$ventas_list = $ventas->get_one();

$nombre = $ventas_list['nom_prod'];
$serie = $ventas_list['serie'];
$precio = $ventas_list['precio_venta'];
$categoria = $ventas_list['categoria'];
$marca = $ventas_list['marca'];

//obtenemos las categorias
$cate = Sdba::table('categorias');
$catel = $cate->get();
$categorias_l='';
foreach ($catel as $value1) {
	$activo='';
	if ($value1['id_categoria']==$categoria) {
		$activo = 'selected';
	}
	$categorias_l .='<option '.$activo.' value="'.$value1['id_categoria'].'">'.$value1['nom_cat'].'</option>';
}

$marcas = Sdba::table('marca');
$ml = $marcas->get();
$mli = '';
foreach ($ml as $key) {
	$activo='';
	if ($key['id_marca']==$marca) {
		$activo = 'selected';
	}
	$mli .='<option '.$activo.' value="'.$key['id_marca'].'">'.$key['marca'].'</option>';
}

//obtenemos los stock de las tiendas
$stock = Sdba::table('stock');
$stock->where('producto',$id)->and_where('tienda =','1');
$stock->order_by('id_stock','desc');
$stock1 = $stock->get_one();

$stocks .='<tr><td>Tienda 1</td><td>'.$stock1['stock'].'</td></tr>';

$stock->reset();

$stock->where('producto',$id)->and_where('tienda =','2');
$stock->order_by('id_stock','desc');
$stock2 = $stock->get_one();
$stocks .='<tr><td>Tienda 2</td><td>'.$stock2['stock'].'</td></tr>';

$stock->reset();

$stock->where('producto',$id)->and_where('tienda =','3');
$stock->order_by('id_stock','desc');
$stock3 = $stock->get_one();
$stocks .='<tr><td>Tienda 3</td><td>'.$stock3['stock'].'</td></tr>';

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
	      	</ul>
	      </div>
	    </nav>
		<div class="kbg">
			<div class="cuerpo">
				<div class="titulo">
					<h3>Editar producto</h3>
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
											    	<input type="hidden" value="<?php echo $id; ?>" name="id">
											    	<div class="row">
											    		<div class="col-md-12">
											    			<div class="form-group">
															    <label for="exampleInputPassword1">Nombre</label>
															    <input type="text" class="form-control" name="nombre" id="nombre" placeholder="Nombre" value="<?php echo $nombre; ?>">
															</div>
															<div class="form-group">
															    <label for="exampleInputPassword1">Serie</label>
															    <input type="text" class="form-control" name="serie" id="serie" placeholder="Serie" value="<?php echo $serie; ?>">
															</div>
															<div class="form-group">
															    <label for="exampleInputPassword1">Categoría</label>
															    <select name="categoria" id="categorias" class="form-control">
															    	<?php echo $categorias_l; ?>
															    </select>
															</div>
															<div class="form-group">
															    <label for="exampleInputPassword1">Marca</label>
															    <select name="marca" id="marca" class="form-control">
															    	<?php echo $mli; ?>
															    </select>
															</div>
															<div class="form-group">
															    <label for="exampleInputPassword1">Precio</label>
															    <input type="text" class="form-control" name="precio" id="precio" placeholder="Precio" value="<?php echo $precio; ?>">
															</div>
											    		</div>
											    	</div>
											    	
												  <button type="button" id="guardar_venta" class="btn btn-success btn-block btn-lg">Editar </button>
												</form>


			
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
					<h3>Stock</h3>
				</div>
				<div class="container-fluid">
					<div class="row">
						<div class="col-md-12">
						    <table class="table">
						      <thead>
						      	<tr>
							      	<th>Tienda</th>
							      	<th>Stock</th>
							    </tr>
						      </thead>
						      <tbody>
						      	<?php echo $stocks; ?>
						      </tbody>
						    </table>

						    <h3>Agregar registro</h3>
						    <form>
						    	<div class="form-group">
						    		<label>Tipo</label>
						    		<select name ="tipo" class="form-control">
						    			<option value="1">Ingreso</option>
						    			<option value="2">Egreso</option>
						    		</select>
						    	</div>

						    	<div class="form-group">
						    		<label>Tienda</label>
						    		<select class="form-control">
						    			<option value="1">Tienda 1</option>
						    			<option value="2">Tienda 2</option>
						    			<option value="3">Tienda 3</option>
						    		</select>
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
						    	<button type="button" id="agregar_stock" class="btn btn-success btn-block btn-lg">Agregar Stock</button>
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
	<script >
	// A $( document ).ready() block.
	$(document ).ready(function() {

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

		
	    console.log( "ready!" );
	});
		
	</script>
</body>
</html>