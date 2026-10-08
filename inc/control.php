<?php

session_start();

if ($_SESSION['ingress']== true) {

	$usuario = $_SESSION['usuario'];
	
}
else{
	header("Location: index.html");
}

function menu($i){
	$uno = ''; $dos = ''; $tres = ''; $cuatro = ''; $cinco = ''; $seis = ''; $siete = ''; $ocho = ''; $nueve = ''; $diez = ''; $once = '';
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
		case '8':
			$ocho = 'active';
			break;
		case '9':
			$nueve = 'active';
			break;
		case '10':
			$diez = 'active';
			break;
		case '11':
			$once = 'active';
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
	            <li class="'.$nueve.' text-center" ><a title="Caja" href="caja_pagos.php"><img class="isvg" src="assets/img/caja.png"><br><span>Caja</span></a></li>
	            <li class="'.$seis.' text-center" ><a title="Compras" href="compra.php"><img class="isvg" src="assets/img/compras.png"><br><span>Compras</span></a></li>
	            <li class="'.$diez.' text-center" ><a title="Preventas" href="preventas.php"><img class="isvg" src="assets/img/clientes.png"><br><span>Preventas</span></a></li>
	            <li class="'.$once.' text-center" ><a title="Gastos y Balance" href="gastos.php"><img class="isvg" src="assets/img/caja.png"><br><span>Gastos</span></a></li>
	            <li class="'.$cinco.' text-center" ><a title="reportes" href="reportes.php"><img class="isvg" src="assets/img/reports.png"><br><span>Reportes</span></a></li>
	            <li class="'.$ocho.' dropdown text-center">
	              <a href="#" class="dropdown-toggle" data-toggle="dropdown" title="Configuración"><img class="isvg" src="/assets/img/config_tablet.svg"><br><span>Config.</span></a>
	              <ul class="dropdown-menu">
	                <li><a href="/tablet_config_admin.php"><img src="/assets/img/config_tablet.svg" style="width:16px;margin-right:6px;vertical-align:middle;"> Configuración Tablet</a></li>
	                <li><a href="/configuracion_facturacion.php"><i class="fas fa-file-invoice" style="width:16px;margin-right:6px"></i> Facturación Electrónica</a></li>
	                <li><a href="/alias_productos.php"><i class="fas fa-book" style="width:16px;margin-right:6px"></i> Diccionario</a></li>
	              </ul>
	            </li>
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
	            <li class="'.$nueve.' text-center" ><a title="Caja" href="caja_pagos.php"><img class="isvg" src="assets/img/caja.png"><br><span>Caja</span></a></li>
	          </ul>
	          <ul id="right-top">
	          	<li>Hola <strong style="text-transform: uppercase;">'.$_SESSION['usuario'].'</strong><a href="salir.php"><img class="isvg" src="assets/img/salir.png"><br></a></li>
	          </ul>
	        </div><!--/.nav-collapse -->';
	}
	
}
// Navbar rediseñado (Escritorio v2). Mismo conjunto de opciones que menu(), con íconos Phosphor.
// Requiere assets/css/escritorio.css y Phosphor Icons cargados en la página.
function menu_v2($i){
	$items = [
		['1',  'Escritorio', 'dashboard.php',    'ph-house',           37],
		['2',  'Usuarios',   'ver_usuarios.php', 'ph-user-plus',       145],
		['7',  'Clientes',   'ver_clientes.php', 'ph-users-three',     60],
		['3',  'Productos',  'ver_productos.php','ph-package',         75],
		['4',  'Ventas',     'venta.php',        'ph-receipt',         230],
		['9',  'Caja',       'caja_pagos.php',   'ph-cash-register',   150],
		['6',  'Compras',    'compra.php',       'ph-shopping-cart',   170],
		['10', 'Preventas',  'preventas.php',    'ph-clipboard-text',  30],
		['11', 'Gastos',     'gastos.php',       'ph-wallet',          120],
		['5',  'Reportes',   'reportes.php',     'ph-chart-line-up',   250],
	];
	$es_admin = ($_SESSION['type'] ?? '') == 'admin';
	if (!$es_admin) {
		$items = array_values(array_filter($items, function($it){ return in_array($it[0], ['1', '4', '9']); }));
	}
	echo '<header class="esc-header">
	<a class="esc-brand" href="dashboard.php"><span>GRUPO</span><b>AVASA</b></a>
	<nav class="esc-nav">';
	foreach ($items as $it) {
		$activo = ($it[0] == (string)$i);
		$ic = 'oklch(0.62 0.17 '.$it[4].')';
		echo '<a class="esc-nav-item'.($activo ? ' active' : '').'" href="'.$it[2].'" title="'.$it[1].'">
			<i class="'.($activo ? 'ph-fill' : 'ph-duotone').' '.$it[3].'"'.($activo ? '' : ' style="color:'.$ic.'"').'></i><span>'.$it[1].'</span></a>';
	}
	echo '</nav>';
	if ($es_admin) {
		echo '<div class="dropdown esc-config">
		<a href="#" class="esc-nav-item dropdown-toggle" data-toggle="dropdown" title="Configuración"><i class="ph-duotone ph-gear" style="color:oklch(0.62 0.17 210)"></i><span>Config.</span></a>
		<ul class="dropdown-menu dropdown-menu-right">
			<li><a href="/tablet_config_admin.php"><i class="ph ph-device-tablet"></i> Configuración Tablet</a></li>
			<li><a href="/configuracion_facturacion.php"><i class="ph ph-file-text"></i> Facturación Electrónica</a></li>
			<li><a href="/alias_productos.php"><i class="ph ph-book-open"></i> Diccionario</a></li>
		</ul></div>';
	}
	echo '<div class="esc-user">Hola <strong>'.htmlspecialchars(strtoupper($_SESSION['usuario']), ENT_QUOTES, 'UTF-8').'</strong>
		<a class="esc-logout" href="salir.php" title="Salir"><i class="ph-bold ph-sign-out"></i></a></div>
</header>';
}
?>