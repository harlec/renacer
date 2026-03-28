<?php
include('inc/control.php');
include('inc/sdba/sdba.php');

// Control de acceso - Solo usuarios específicos
$usuarios_permitidos = ['hars'];
if (!in_array($_SESSION['usuario'], $usuarios_permitidos)) {
    header("Location: ventas.php");
    exit;
}

// Inicializar todas las variables
$total_ventas_hoy = 0;
$total_ventas_mes = 0;
$productos_criticos_count = 0;
$ventas_pendientes_count = 0;
$mejores_clientes_list = array();
$productos_mas_vendidos_list = array();

try {
    // Configurar timeout para evitar cuelgues
    set_time_limit(30);
    
    $fecha_hoy = date('Y-m-d');
    $fecha_mes = date('Y-m-01');
    
    // Métrica 1: Ventas del día - Método básico
    $ventas_hoy = Sdba::table('ventas');
    $ventas_hoy->where('fecha', $fecha_hoy);
    $ventas_hoy_data = $ventas_hoy->get();
    foreach($ventas_hoy_data as $venta) {
        if($venta['estado'] != '2') {
            $total_ventas_hoy += floatval($venta['total']);
        }
    }
    
    // Métrica 2: Ventas del mes - Método básico  
    $ventas_mes = Sdba::table('ventas');
    $ventas_mes->where('fecha >=', $fecha_mes);
    $ventas_mes_data = $ventas_mes->get();
    foreach($ventas_mes_data as $venta) {
        if($venta['estado'] != '2') {
            $total_ventas_mes += floatval($venta['total']);
        }
    }
    
    // Métrica 3: Total de productos vendidos
    $detalle_ventas_total = Sdba::table('detalle_ventas');
    $productos_criticos_count = $detalle_ventas_total->total();
    
    // Métrica 4: Ventas pendientes
    $ventas_pendientes = Sdba::table('ventas');
    $ventas_pendientes->where('estado', '0');
    $ventas_pendientes_count = $ventas_pendientes->total();
    
    // Mejores clientes - Por cantidad de ventas y monto total
    $ventas_todas = Sdba::table('ventas');
    $ventas_todas->where('estado !=', '2');
    $ventas_todas->left_join('cliente','clientes','id_cliente');
    $ventas_todas_data = $ventas_todas->get();
    
    $clientes_info = array();
    if (is_array($ventas_todas_data)) {
        foreach($ventas_todas_data as $venta) {
            if (isset($venta['cliente']) && !empty($venta['cliente'])) {
                $cliente = trim($venta['cliente']);  // Nombre del cliente desde el JOIN
                if(!isset($clientes_info[$cliente])) {
                    $clientes_info[$cliente] = array(
                        'nombre' => $cliente,
                        'cantidad_ventas' => 0,
                        'monto_total' => 0
                    );
                }
                $clientes_info[$cliente]['cantidad_ventas']++;
                $clientes_info[$cliente]['monto_total'] += floatval($venta['total']);
            }
        }
    }
    
    // Ordenar clientes por monto total - Compatible PHP 7.4
    if (!empty($clientes_info)) {
        usort($clientes_info, function($a, $b) {
            if ($a['monto_total'] == $b['monto_total']) {
                return 0;
            }
            return ($a['monto_total'] > $b['monto_total']) ? -1 : 1;
        });
        
        // Tomar los primeros 10
        $mejores_clientes_list = array_slice($clientes_info, 0, 10);
    }
    
    // Productos más vendidos - Top 10
    $detalle_ventas_todas = Sdba::table('detalle_ventas');
    $detalle_ventas_todas->left_join('producto', 'productos', 'id_producto');
    $detalle_ventas_todas_data = $detalle_ventas_todas->get();
    
    $productos_vendidos = array();
    if (is_array($detalle_ventas_todas_data)) {
        foreach($detalle_ventas_todas_data as $detalle) {
            if (isset($detalle['nom_prod']) && isset($detalle['cantidad']) && isset($detalle['total'])) {
                $nombre_producto = trim($detalle['nom_prod']);
                
                if(!empty($nombre_producto)) {
                    if(!isset($productos_vendidos[$nombre_producto])) {
                        $productos_vendidos[$nombre_producto] = array(
                            'nombre' => $nombre_producto,
                            'cantidad_total' => 0,
                            'monto_total' => 0
                        );
                    }
                    $productos_vendidos[$nombre_producto]['cantidad_total'] += intval($detalle['cantidad']);
                    $productos_vendidos[$nombre_producto]['monto_total'] += floatval($detalle['total']);
                }
            }
        }
    }
    
    // Ordenar por cantidad vendida - Compatible PHP 7.4
    if (!empty($productos_vendidos)) {
        usort($productos_vendidos, function($a, $b) {
            if ($a['cantidad_total'] == $b['cantidad_total']) {
                return 0;
            }
            return ($a['cantidad_total'] > $b['cantidad_total']) ? -1 : 1;
        });
        
        $productos_mas_vendidos_list = array_slice($productos_vendidos, 0, 10);
    }
    
} catch (Exception $e) {
    // Valores por defecto en caso de error
    $total_ventas_hoy = 0;
    $total_ventas_mes = 0;
    $productos_criticos_count = 0;
    $ventas_pendientes_count = 0;
    $mejores_clientes_list = array();
    $productos_mas_vendidos_list = array();
}

// Asegurar valores numéricos - Validación robusta
$total_ventas_hoy = is_numeric($total_ventas_hoy) ? floatval($total_ventas_hoy) : 0;
$total_ventas_mes = is_numeric($total_ventas_mes) ? floatval($total_ventas_mes) : 0;
$productos_criticos_count = is_numeric($productos_criticos_count) ? intval($productos_criticos_count) : 0;
$ventas_pendientes_count = is_numeric($ventas_pendientes_count) ? intval($ventas_pendientes_count) : 0;

// Validar arrays
if (!is_array($mejores_clientes_list)) $mejores_clientes_list = array();
if (!is_array($productos_mas_vendidos_list)) $productos_mas_vendidos_list = array();
?>

<!DOCTYPE html>
<html lang="es">
<head>
	<meta charset="UTF-8">
	<title>Dashboard Ejecutivo - Sistema Renacer</title>
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" type="text/css" href="/assets/css/bootstrap.min.css">
    <link rel="stylesheet" type="text/css" href="/assets/css/custom.css">
    <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.7.2/css/all.css">
    <style>
    .metric-card {
        background: white;
        border-radius: 8px;
        padding: 20px;
        margin-bottom: 20px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        border-left: 4px solid #007bff;
    }
    .metric-card.success { border-left-color: #28a745; }
    .metric-card.warning { border-left-color: #ffc107; }
    .metric-card.danger { border-left-color: #dc3545; }
    .metric-card.info { border-left-color: #17a2b8; }
    
    .metric-number {
        font-size: 2.5em;
        font-weight: bold;
        margin-bottom: 5px;
    }
    .metric-label {
        color: #666;
        font-size: 0.9em;
        text-transform: uppercase;
    }
    .metric-icon {
        float: right;
        font-size: 3em;
        opacity: 0.3;
        margin-top: -10px;
    }
    .table-small { font-size: 0.85em; }
    </style>
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
                        <h2><i class="fas fa-chart-line"></i> Dashboard Ejecutivo</h2>
                        <p class="text-muted">Panel de control para usuarios autorizados</p>
                    </div>
                </div>

                <!-- Métricas Principales -->
                <div class="row">
                    <div class="col-md-3">
                        <div class="metric-card success">
                            <div class="metric-icon"><i class="fas fa-cash-register"></i></div>
                            <div class="metric-number">S/ <?php echo number_format($total_ventas_hoy, 2); ?></div>
                            <div class="metric-label">Ventas del Día</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="metric-card info">
                            <div class="metric-icon"><i class="fas fa-chart-line"></i></div>
                            <div class="metric-number">S/ <?php echo number_format($total_ventas_mes, 2); ?></div>
                            <div class="metric-label">Ventas del Mes</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="metric-card warning">
                            <div class="metric-icon"><i class="fas fa-exclamation-triangle"></i></div>
                            <div class="metric-number"><?php echo $productos_criticos_count; ?></div>
                            <div class="metric-label">Productos Vendidos</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="metric-card danger">
                            <div class="metric-icon"><i class="fas fa-clock"></i></div>
                            <div class="metric-number"><?php echo $ventas_pendientes_count; ?></div>
                            <div class="metric-label">Ventas Pendientes</div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <!-- Mejores Clientes -->
                    <div class="col-md-6">
                        <div class="panel panel-default">
                            <div class="panel-header" style="padding: 15px; border-bottom: 1px solid #ddd; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;">
                                <h4><i class="fas fa-crown"></i> 👑 Mejores Clientes por Ventas y Monto</h4>
                            </div>
                            <div class="panel-body">
                                <div class="table-responsive">
                                    <table class="table table-striped table-small">
                                        <thead>
                                            <tr>
                                                <th>Pos.</th>
                                                <th>Cliente</th>
                                                <th>Cant. Ventas</th>
                                                <th>Monto Total (S/)</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php 
                                            $pos = 1;
                                            if (is_array($mejores_clientes_list)) {
                                            foreach($mejores_clientes_list as $cliente): 
                                                if (isset($cliente['nombre']) && isset($cliente['cantidad_ventas']) && isset($cliente['monto_total'])): ?>
                                            <tr>
                                                <td>
                                                    <?php if($pos == 1): ?>
                                                        <span class="badge" style="background: gold; color: black;">🥇 <?php echo $pos; ?></span>
                                                    <?php elseif($pos == 2): ?>
                                                        <span class="badge" style="background: silver; color: black;">🥈 <?php echo $pos; ?></span>
                                                    <?php elseif($pos == 3): ?>
                                                        <span class="badge" style="background: #cd7f32; color: white;">🥉 <?php echo $pos; ?></span>
                                                    <?php else: ?>
                                                        <span class="badge badge-primary"><?php echo $pos; ?></span>
                                                    <?php endif; ?>
                                                </td>
                                                <td style="font-weight: bold;"><?php echo htmlspecialchars($cliente['nombre']); ?></td>
                                                <td>
                                                    <span class="badge badge-info"><?php echo intval($cliente['cantidad_ventas']); ?> ventas</span>
                                                </td>
                                                <td>
                                                    <span class="badge badge-success" style="font-size: 0.9em;">
                                                        S/ <?php echo number_format(floatval($cliente['monto_total']), 2); ?>
                                                    </span>
                                                </td>
                                            </tr>
                                            <?php $pos++; endforeach; ?>
                                            <?php if (empty($mejores_clientes_list)): ?>
                                            <tr>
                                                <td colspan="4" class="text-center text-muted">No hay datos disponibles</td>
                                            </tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Top 10 Productos Más Vendidos -->
                    <div class="col-md-6">
                        <div class="panel panel-default">
                            <div class="panel-header" style="padding: 15px; border-bottom: 1px solid #ddd; background: linear-gradient(135deg, #28a745 0%, #20c997 100%); color: white;">
                                <h4><i class="fas fa-fire"></i> 🔥 Top 10 Productos Más Vendidos</h4>
                            </div>
                            <div class="panel-body">
                                <div class="table-responsive">
                                    <table class="table table-striped table-small">
                                        <thead>
                                            <tr>
                                                <th>Pos.</th>
                                                <th>Producto</th>
                                                <th>Cantidad</th>
                                                <th>Monto Total (S/)</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php 
                                            $pos = 1;
                                            if (is_array($productos_mas_vendidos_list)) {
                                            foreach($productos_mas_vendidos_list as $producto): 
                                                if (isset($producto['nombre']) && isset($producto['cantidad_total']) && isset($producto['monto_total'])): ?>
                                            <tr>
                                                <td>
                                                    <?php if($pos == 1): ?>
                                                        <span class="badge" style="background: gold; color: black;">🥇 <?php echo $pos; ?></span>
                                                    <?php elseif($pos == 2): ?>
                                                        <span class="badge" style="background: silver; color: black;">🥈 <?php echo $pos; ?></span>
                                                    <?php elseif($pos == 3): ?>
                                                        <span class="badge" style="background: #cd7f32; color: white;">🥉 <?php echo $pos; ?></span>
                                                    <?php else: ?>
                                                        <span class="badge badge-success"><?php echo $pos; ?></span>
                                                    <?php endif; ?>
                                                </td>
                                                <td style="font-weight: bold;"><?php echo htmlspecialchars($producto['nombre']); ?></td>
                                                <td>
                                                    <span class="badge badge-primary" style="font-size: 0.9em;">
                                                        <?php echo number_format(intval($producto['cantidad_total'])); ?> unidades
                                                    </span>
                                                </td>
                                                <td>
                                                    <span class="badge badge-success" style="font-size: 0.9em;">
                                                        S/ <?php echo number_format(floatval($producto['monto_total']), 2); ?>
                                                    </span>
                                                </td>
                                            </tr>
                                            <?php $pos++; endforeach; ?>
                                            <?php if (empty($productos_mas_vendidos_list)): ?>
                                            <tr>
                                                <td colspan="4" class="text-center text-muted">
                                                    <i class="fas fa-info-circle"></i> No hay datos de ventas disponibles
                                                </td>
                                            </tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
			</div>
		</div>

        <!-- Auto-refresh cada 5 minutos -->
        <script>
            setTimeout(function(){
                location.reload();
            }, 300000);
        </script>

	<!-- jQuery y Bootstrap -->
	<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.3.1/jquery.min.js"></script>
	<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js"></script>
</body>
</html>