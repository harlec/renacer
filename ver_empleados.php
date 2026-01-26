<?php
include('inc/control.php');
if ($_SESSION['type']=='operador') {
	header("Location: dashboard.php");
}

include('inc/sdba/sdba.php'); // include main file
$ventas = Sdba::table('empleados'); // creating table object
$ventas_list = $ventas->get(); 

$datos = '';
$i = 1;
$ubicacion = '';
foreach ($ventas_list as $value) {
	if ($value['ubicacion']==1) {
		$ubicacion = 'Chimbote1';
	}
	elseif ($value['ubicacion']==2) {
		$ubicacion = 'Chimbote2';
	}
	elseif ($value['ubicacion']==3) {
		$ubicacion = 'Trujillo';
	}

	$datos .='<tr><td>'.$value['id_empleado'].'</td> 
				<td>'.$value['dni'].'</td>
    			<td>'.$value['nombres'].'</td>
    			<td>'.$value['apellidos'].'</td>
    			<td>'.$value['email'].'</td> 
    			<td>'.$value['celular'].'</td> 
    			<td>'.$value['direccion'].'</td> 
    			<td>'.$ubicacion.'</td> 
    			<td>'.$value['puntos'].'</td> 
    			<td><a title="Ver venta" class="" alt="ver" href="editar_empleado.php?id='.$value['id_empleado'].'"><img src="assets/img/edit.png" /> </a><button class="btn-custom" id="borrar" value="'.$value['id_empleado'].'" alt="borrar"><img src="assets/img/trash.png" /></button></td> 
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
	        <?php menu('2'); ?>
	      </div>
	      <div class="submenu">
	      	<ul class="subtop-tabs">
	      		<li >
	      			<a class="" href="agregar_usuario.php">Registrar usuario</a>
	      		</li>
	      		<li >
	      			<a class="" href="ver_usuarios.php">Listar usuarios</a>
	      		</li>
	      		<li >
	      			<a class="" href="agregar_empleado.php">Agregar colaborador</a>
	      		</li>
	      		<li class="active">
	      			<a class="" href="ver_empleados.php">Listar colaboradores</a>
	      		</li>
	      	</ul>
	      </div>
	    </nav>
		<div class="kbg">
			<div class="cuerpofull">
				<div class="titulo">
					<h3>Listar Usuarios</h3>
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
											    			<th>Dni</th>
											    			<th>Nombre</th>
											    			<th>Apellidos</th> 
											    			<th>Email</th>
											    			<th>Celular</th>
											    			<th>Dirección</th>
											    			<th>Ubicacion</th>
											    			<th>Puntos</th>
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
	    console.log( "ready!" );

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
				  	url: '/inc/borrar_empleado.php',
				  	data: str1,
				  	success: function(data1) {
				   	 	console.log('borrado');
				   	 	document.location.href = "ver_empleados.php";
				   	 	
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
	});
		
	</script>
</body>
</html>