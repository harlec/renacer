<?php

session_start();

if ($_SESSION['ingress']== true) {

	$usuario = $_SESSION['usuario'];
	
}
else{
	header("Location: index.html");
}

function menu($i){
	$uno = ''; $dos = ''; $tres = ''; $cuatro = ''; $cinco = '';
	switch ($i) {
		case '1':
			$uno = 'active';
			break;
		case '2':
			$dos = 'active';
			break;
		case '3':
			$tres = 'active';
			break;
		case '4':
			$cuatro = 'active';
			break;
		case '5':
			$cinco = 'active';
			break;
		case '6':
			$seis = 'active';
			break;
		case '7':
			$siete = 'active';
			break;
	}
	if ($_SESSION['type']=='admin') {
		echo '<div id="navbar" class="navbar-collapse collapse">
	          <ul class="nav navbar-nav menu">
	            <li class="'.$uno.' text-center"><a title="Escritorio" href="dashboard.php"><img class="isvg" src="assets/img/dashboard.svg"><br><span>Escritorio</span></a></li>
	            <li class="'.$dos.' text-center" ><a title="Usuarios" href="ver_usuarios.php"><img class="isvg" src="assets/img/users.png"><br><span>Usuarios</span></a></li>
	            <li class="'.$siete.' text-center" ><a title="clientes" href="ver_clientes.php"><img class="isvg" src="assets/img/clientes.png"><br><span>Clientes</span></a></li>
	            <li class="'.$tres.' text-center" ><a title="Productos" href="ver_productos.php"><img class="isvg" src="assets/img/products.png"><br><span>Productos</span></a></li>
	            <li class="'.$cuatro.' text-center" ><a title="Ventas" href="venta.php"><img class="isvg" src="assets/img/ventas.png"><br><span>Ventas</span></a></li>
	            <li class="'.$seis.' text-center" ><a title="Compras" href="compra.php"><img class="isvg" src="assets/img/compras.png"><br><span>Compras</span></a></li>
	            <li class="'.$cinco.' text-center" ><a title="reportes" href="reportes.php"><img class="isvg" src="assets/img/reports.png"><br><span>Reportes</span></a></li>
	          </ul>
	          <ul id="right-top">
	          	<li>Hola <strong style="text-transform: uppercase;">'.$_SESSION['usuario'].'</strong><a href="salir.php"><img class="isvg" src="assets/img/salir.png"><br></a></li>
	          </ul>
	        </div><!--/.nav-collapse -->';
	}
	elseif ($_SESSION['type']=='operador') {
		echo '<div id="navbar" class="navbar-collapse collapse">
	          <ul class="nav navbar-nav menu">
	            <li class="'.$uno.' text-center"><a title="Escritorio" href="dashboard.php"><img class="isvg" src="assets/img/dashboard.svg"><br><span>Escritorio</span></a></li>
	            <li class="'.$cuatro.' text-center" ><a title="Ventas" href="venta.php"><img class="isvg" src="assets/img/ventas.png"><br><span>Ventas</span></a></li>
	          </ul>
	          <ul id="right-top">
	          	<li>Hola <strong style="text-transform: uppercase;">'.$_SESSION['usuario'].'</strong><a href="salir.php"><img class="isvg" src="assets/img/salir.png"><br></a></li>
	          </ul>
	        </div><!--/.nav-collapse -->';
	}
	
}
?>