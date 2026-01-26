<?php
include('inc/control.php');
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
	        <?php menu('4'); ?>
	      </div>
	      <div class="submenu">
	      	<ul class="subtop-tabs">
	      		<li >
	      			<a href="venta.php">Registrar venta</a>
	      		</li>
	      		<li >
	      			<a href="ventas.php">Listar ventas</a>
	      		</li>
	      	</ul>
	      </div>
	    </nav>
		<div class="kbg">
			<form id="frmfactura" >
				<div class="cuerpo">
					<div class="titulo">
						<h3>Factura</h3>
					</div>
					<div class="container-fluid">
						<div class="row">
							<div class="col-md-12">
								<div class="kdashboard">
									<div class="row">
										<div class="col-md-12">
											<div class="panel panel-default pa">  
												  <div class="panel-body">
												  	
												    <div style="display:none;" id="msn_bien" class="text-center">
												    	<h3>El zip ha sido generado</h3>
												    </div>
												    <div class="loader text-center" id="loading"></div>
												    
												    <div class="text-center">
												    	<button type="button" data-loading-text="Facturando..." id="facturar" class="btn btn-success btn-lg">Facturar</button>
												    	<div class="loader text-center" id="loading"></div>
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
				<div class="detalles">
					<div class="titulo">
						<h3>DATOS DE LA EMPRESA</h3>
					</div>
					<div class="container-fluid">
						<div class="row">
							<br>
					  		<div class="col-sm-12">
					  			<input class="form-control" type="text" name="ruc" id="ruc" placeholder="Ingrese Ruc"><br>
					  		</div>
						  	<div class="col-sm-12">
						  		<input class="form-control" type="text" name="r_social" id="r_social" placeholder="Razon social(automática)"><br>
						  	</div>
						  	<div class="col-sm-12">
						  		<textarea class="form-control" name="direccion" id="direccion">
						  			
						  		</textarea><br>
						  		
						  	</div>
					  	</div>	
					</div>
					<div class="titulo">
						<h3>CONDICIONES DE PAGO</h3>
					</div>
					<div class="container-fluid">
						<div class="row">
							<br>
							<div class="col-xs-12">
								<h3><?php echo $tipop; ?></h3>
							</div>

							<div class="col-xs-12 mos" style="display: none;">
								<input type="date" name="fechac" class="form-control"><br>
							</div>
							<div class="col-xs-12 mos" style="display: none;">
								<input readonly type="text" name="montoc" class="form-control" value="<?php echo $tot; ?>"> <br>
							</div>
						</div>
						
					</div>
				</div>
			</form>
		</div>
	 	<!-- Tab panes -->
		

	  
	<!-- jQuery (necessary for Bootstrap's JavaScript plugins) -->
	<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.3.1/jquery.min.js"></script>
	<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js"></script>
	<script src="/assets/js/jquery-ui.min.js"></script> 
	<script >
	// A $( document ).ready() block.
	$(document ).ready(function() {	
	    console.log( "ready!" );    
			$("body").on('click', '#facturar', function () {
				console.log('hola');
		    	var str2 = $('#frmfactura').serialize();
		    	console.log(str2);
		    	//alert(str2);
		    	$.ajax({
		    		beforeSend: function(){
					     $("#loading").show();
					      $("#facturar").css("visibility", "hidden");
					     
					},
					   complete: function(){
					     $("#loading").hide();
					     $("#msn_bien").show();
					},
					cache: false,
					type: "POST",
					dataType: "json",
					url: "ju.php",
					data: "envio:hugo",
					success: function(response){

						alert('bien');
						console.log(response);

						var sunat = JSON.parse(response); 
				  		//console.log(sunat.enlace);
						// console.log(response);
						//document.location.href = "venta.php";
						//window.open(sunat.enlace_del_pdf,'_blank');
						return false;

						//if(response.respuesta == false){
							//swal('Advertencia',response,'warning');
							//console.log(response);

						//}else{

							//swal('Perfecto', response,'success')
							// console.log(response.mesa);
							// $('#mostrarmesa').load('inc/mobile/ver_mesa.php?mesa='+ response.mesa);
							// //document.location.href = "listar_ventas.php";
						
						//}
					
					},
					error: function(reponse1){
						alert(reponse1);
					}
				});
				
				//$(this ).hide();
				//return false;
		    	// var tot = $('#total').val();
		    	// var queda = tot-to;
		    	// console.log(tot);
		    	// console.log(queda);
			    // $(this).closest('tr').remove();
			    // $(this).parents('.pt-r').remove();
			    // $('#total').val(queda);
			    // console.log(to);

			});

			
				
				
                  //$('#ruc').val(xhr.response["ruc"]);

	});
		
	</script>
</body>
</html>