
<?php
session_start();
include('inc/control.php');
include('inc/sdba/sdba.php');

// Control de acceso - Solo usuarios específicos
$usuarios_permitidos = ['hars'];
if (!in_array($_SESSION['usuario'], $usuarios_permitidos)) {
    header("Location: ventas.php");
    exit;
}

$hoy = date('Y-m-d');
$mes_actual = date('Y-m');
// VENTAS DEL DÍA
$query_ventas_dia = "SELECT COUNT(*) as total FROM ventas WHERE DATE(fecha) = '$hoy' AND estado != '2'";
if (!$es_admin) {
    $query_ventas_dia .= " AND usuario = '$usuario_id'";
}
$result_dia = Sdba::db()->query($query_ventas_dia)->row();
$total_ventas_dia = $result_dia['total'];

// Monto del día
$query_monto_dia = "SELECT SUM(total) as monto FROM ventas 
WHERE DATE(fecha) = '$hoy' AND estado != '2'";
if (!$es_admin) {
    $query_monto_dia .= " AND usuario = '$usuario_id'";
}
$result_monto_dia = Sdba::db()->query($query_monto_dia)->row();
$monto_dia = $result_monto_dia['monto'] ?: 0;

// VENTAS DEL MES
$query_ventas_mes = "SELECT COUNT(*) as total FROM ventas WHERE DATE_FORMAT(fecha, '%Y-%m') = '$mes_actual' AND estado != '2'";
if (!$es_admin) {
    $query_ventas_mes .= " AND usuario = '$usuario_id'";
}
$result_mes = Sdba::db()->query($query_ventas_mes)->row();
$total_ventas_mes = $result_mes['total'];

// Monto del mes
$query_monto_mes = "SELECT SUM(total) as monto FROM ventas 
WHERE DATE_FORMAT(fecha, '%Y-%m') = '$mes_actual' AND estado != '2'";
if (!$es_admin) {
    $query_monto_mes .= " AND usuario = '$usuario_id'";
}
$result_monto_mes = Sdba::db()->query($query_monto_mes)->row();
$monto_mes = $result_monto_mes['monto'] ?: 0;

// PRODUCTOS MÁS VENDIDOS (Top 5 del mes)
$query_productos = "SELECT productos.nom_prod, SUM(detalle_ventas.cantidad) as total_vendido, SUM(detalle_ventas.total) as monto_total 
FROM detalle_ventas 
LEFT JOIN ventas ON detalle_ventas.venta = ventas.id_venta 
LEFT JOIN productos ON detalle_ventas.producto = productos.id_producto 
WHERE DATE_FORMAT(ventas.fecha, '%Y-%m') = '$mes_actual' 
AND ventas.estado != '2' ";
if (!$es_admin) {
    $query_productos .= "AND ventas.usuario = '$usuario_id' ";
}
$query_productos .= "GROUP BY detalle_ventas.producto 
ORDER BY monto_total DESC 
LIMIT 5";

$productos_result = Sdba::db()->query($query_productos)->result();

// CLIENTES CON MAYORES COMPRAS (Top 5 del mes)
$query_clientes = "SELECT clientes.cliente as nombre_cliente, SUM(detalle_ventas.total) as total_compras, COUNT(DISTINCT ventas.id_venta) as num_compras
FROM ventas 
LEFT JOIN detalle_ventas ON ventas.id_venta = detalle_ventas.venta 
LEFT JOIN clientes ON ventas.cliente = clientes.id_cliente 
WHERE DATE_FORMAT(ventas.fecha, '%Y-%m') = '$mes_actual' 
AND ventas.estado != '2' 
AND ventas.cliente != '' ";
if (!$es_admin) {
    $query_clientes .= "AND ventas.usuario = '$usuario_id' ";
}
$query_clientes .= "GROUP BY ventas.cliente 
ORDER BY total_compras DESC 
LIMIT 5";

$clientes_result = Sdba::db()->query($query_clientes)->result();

// STOCK BAJO (Productos con stock menor a 10)
$stock_bajo = Sdba::table('productos');
$stock_bajo->where('stockp', '<', false, false, 'AND', '<');
$stock_bajo->and_where('stockp', '>', false, false);
$query_stock = "SELECT codigo_producto, nom_prod, stockp, precio_venta FROM productos WHERE stockp < 10 AND stockp > 0 ORDER BY stockp ASC";
$productos_stock_bajo = Sdba::db()->query($query_stock)->result();

?>


<!DOCTYPE html>
<html lang="es">
<head>
	<meta charset="UTF-8">
	<title>Sistema - Menu Principal</title>
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    
    <!-- Preconnect a CDNs externos para mejorar performance -->
    <link rel="preconnect" href="https://ajax.googleapis.com">
    <link rel="preconnect" href="https://maxcdn.bootstrapcdn.com">
    <link rel="preconnect" href="https://use.fontawesome.com">
    <link rel="preconnect" href="https://cdn.jsdelivr.net">
    
    <!-- CSS Críticos -->
    <link rel="stylesheet" type="text/css" href="/assets/css/bootstrap.min.css">
    <link rel="stylesheet" type="text/css" href="/assets/css/custom.css">
    
    <!-- CSS No críticos - carga diferida -->
    <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.7.2/css/all.css" integrity="sha384-fnmOCqbTlWIlj8LyTjo7mOUStjsKC4pOpQbqyi7RrhN7udi9RwhKkMHpvLbHG9Sr" crossorigin="anonymous" media="print" onload="this.media='all'">
    <link rel="stylesheet" type="text/css" href="/assets/css/sweetalert2.min.css" media="print" onload="this.media='all'">
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
					<div class="col-md-12">
						<div class="kdashboard">
							
							<!-- Tarjetas de Estadísticas -->
							<div class="row">
								<div class="col-md-3 col-sm-6">
									<div class="panel panel-primary">
										<div class="panel-body">
											<div class="text-center">
												<i class="fas fa-calendar-day fa-3x"></i>
												<h3 class="mt-2"><?php echo $total_ventas_dia; ?></h3>
												<p>Ventas de Hoy</p>
												<h4 class="text-success">S/ <?php echo number_format($monto_dia, 2); ?></h4>
											</div>
										</div>
									</div>
								</div>
								
								<div class="col-md-3 col-sm-6">
									<div class="panel panel-success">
										<div class="panel-body">
											<div class="text-center">
												<i class="fas fa-calendar-alt fa-3x"></i>
												<h3 class="mt-2"><?php echo $total_ventas_mes; ?></h3>
												<p>Ventas del Mes</p>
												<h4 class="text-success">S/ <?php echo number_format($monto_mes, 2); ?></h4>
											</div>
										</div>
									</div>
								</div>
								
								<div class="col-md-3 col-sm-6">
									<div class="panel panel-warning">
										<div class="panel-body">
											<div class="text-center">
												<i class="fas fa-exclamation-triangle fa-3x"></i>
												<h3 class="mt-2"><?php echo count($productos_stock_bajo); ?></h3>
												<p>Productos Stock Bajo</p>
												<small>Menos de 10 unidades</small>
											</div>
										</div>
									</div>
								</div>
								
								<div class="col-md-3 col-sm-6">
									<div class="panel panel-info">
										<div class="panel-body">
											<div class="text-center">
												<i class="fas fa-chart-line fa-3x"></i>
												<h3 class="mt-2">S/ <?php echo number_format($monto_mes / max($total_ventas_mes, 1), 2); ?></h3>
												<p>Ticket Promedio</p>
												<small>Este mes</small>
											</div>
										</div>
									</div>
								</div>
							</div>
							
							<!-- Segunda fila de paneles -->
							<div class="row">
								<!-- Productos más vendidos -->
								<div class="col-md-6">
									<div class="panel panel-default">
										<div class="panel-heading">
											<h4><i class="fas fa-trophy"></i> Top 5 Productos del Mes</h4>
										</div>
										<div class="panel-body">
											<table class="table table-hover table-striped">
												<thead>
													<tr>
														<th>Producto</th>
														<th class="text-right">Cantidad</th>
														<th class="text-right">Monto</th>
													</tr>
												</thead>
												<tbody>
													<?php if(count($productos_result) > 0): ?>
														<?php foreach($productos_result as $prod): ?>
															<tr>
																<td style="text-transform:uppercase;"><?php echo htmlspecialchars($prod['nom_prod'] ?: 'Sin nombre', ENT_QUOTES, 'UTF-8'); ?></td>
																<td class="text-right"><strong><?php echo (int)$prod['total_vendido']; ?></strong></td>
																<td class="text-right text-success">S/ <?php echo number_format($prod['monto_total'], 2); ?></td>
															</tr>
														<?php endforeach; ?>
													<?php else: ?>
														<tr><td colspan="3" class="text-center text-muted">No hay datos este mes</td></tr>
													<?php endif; ?>
												</tbody>
											</table>
										</div>
									</div>
								</div>
								
								<!-- Clientes top -->
								<div class="col-md-6">
									<div class="panel panel-default">
										<div class="panel-heading">
											<h4><i class="fas fa-users"></i> Top 5 Clientes del Mes</h4>
										</div>
										<div class="panel-body">
											<table class="table table-hover table-striped">
												<thead>
													<tr>
														<th>Cliente</th>
														<th class="text-right">Compras</th>
														<th class="text-right">Total</th>
													</tr>
												</thead>
												<tbody>
													<?php if(count($clientes_result) > 0): ?>
														<?php foreach($clientes_result as $cli): ?>
															<tr>
																<td style="text-transform:uppercase;"><?php echo htmlspecialchars($cli['nombre_cliente'] ?: 'SIN NOMBRE', ENT_QUOTES, 'UTF-8'); ?></td>
																<td class="text-right"><?php echo (int)$cli['num_compras']; ?></td>
																<td class="text-right text-success"><strong>S/ <?php echo number_format($cli['total_compras'], 2); ?></strong></td>
															</tr>
														<?php endforeach; ?>
													<?php else: ?>
														<tr><td colspan="3" class="text-center text-muted">No hay datos este mes</td></tr>
													<?php endif; ?>
												</tbody>
											</table>
										</div>
									</div>
								</div>
							</div>
							
							<!-- Productos con stock bajo -->
							<?php if(count($productos_stock_bajo) > 0): ?>
							<div class="row">
								<div class="col-md-12">
									<div class="panel panel-danger">
										<div class="panel-heading">
											<h4><i class="fas fa-box-open"></i> Productos con Stock Bajo (Menos de 10 unidades)</h4>
										</div>
										<div class="panel-body">
											<div class="table-responsive">
												<table class="table table-hover table-condensed">
													<thead>
														<tr>
															<th>Código</th>
															<th>Producto</th>
															<th class="text-center">Stock Actual</th>
															<th class="text-right">Precio Venta</th>
														</tr>
													</thead>
													<tbody>
														<?php foreach(array_slice($productos_stock_bajo, 0, 10) as $prod): ?>
															<tr class="<?php echo $prod['stockp'] <= 3 ? 'danger' : 'warning'; ?>">
																<td><?php echo htmlspecialchars($prod['codigo_producto'], ENT_QUOTES, 'UTF-8'); ?></td>
																<td style="text-transform:uppercase;"><?php echo htmlspecialchars($prod['nom_prod'], ENT_QUOTES, 'UTF-8'); ?></td>
																<td class="text-center">
																	<span class="label label-<?php echo $prod['stockp'] <= 3 ? 'danger' : 'warning'; ?>">
																		<?php echo $prod['stockp']; ?>
																	</span>
																</td>
																<td class="text-right">S/ <?php echo number_format($prod['precio_venta'], 2); ?></td>
															</tr>
														<?php endforeach; ?>
													</tbody>
												</table>
											</div>
										</div>
									</div>
								</div>
							</div>
							<?php endif; ?>
							
						</div>
					</div>
				</div>
			</div>
		</div>
	 	<!-- Tab panes -->
		

	  
	<!-- jQuery (necessary for Bootstrap's JavaScript plugins) -->
	<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.3.1/jquery.min.js" defer></script>
	<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js" defer></script>
	<script src="https://cdn.jsdelivr.net/npm/jquery-validation@1.17.0/dist/jquery.validate.min.js" defer></script>
	<script src="assets/js/sweetalert2.all.min.js" defer></script>
</body>
</html>