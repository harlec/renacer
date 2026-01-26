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
    <link rel="stylesheet" type="text/css" href="/assets/css/sweetalert2.min.css">
</head>

<body class="mobile dashboard escritorio">
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
	        <?php menu('1'); ?>

	      </div>
	    </nav>
		<div class="kbg">
			<div class="container-fluid">
				<div class="row">
					<div class="col-md-9">
						<div class="kdashboard">
							<!-- <div class="row">
								<div class="col-md-6">
									<div class="panel panel-default">
										<div class="panel-body">
										    <h5>Ultimas ventas</h5>
										    <table class="table table-hover"> 
										    	<thead> 
										    		<tr> 
										    			<th>#</th> 
										    			<th>First Name</th> 
										    			<th>Last Name</th> 
										    			<th>Username</th> 
										    		</tr> 
										    	</thead> 
										    	<tbody> 
										    		<tr> 
										    			<th scope="row">1</th> 
										    			<td>Mark</td> 
										    			<td>Otto</td> 
										    			<td>@mdo</td> 
										    		</tr> 
										    		<tr> 
										    			<th scope="row">2</th> 
										    			<td>Jacob</td> 
										    			<td>Thornton</td> 
										    			<td>@fat</td> 
										    		</tr> 
										    		<tr> 
										    			<th scope="row">3</th> 
										    			<td>Larry</td> 
										    			<td>the Bird</td> 
										    			<td>@twitter</td> 
										    		</tr> 
										    	</tbody> 
										    </table>
										</div>
									</div>
								</div>
							</div>
							<div class="panel panel-default">
							  <div class="panel-body">
							    <h5>Ultimas ventas</h5>

							  </div>
							</div> -->
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
	<script >
	// A $( document ).ready() block.
	$(document ).ready(function() {
		

		
	    console.log( "ready!" );
	});
		
	</script>
</body>
</html>