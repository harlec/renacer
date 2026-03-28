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
$crecimiento = 0;
$productos_criticos_count = 0;
$ventas_pendientes_count = 0;
$ventas_contado_count = 0;
$ventas_credito_count = 0;
$mejores_clientes_list = array();
$productos_stock_critico_list = array();
$movimientos_stock_list = array();

try {
    $fecha_hoy = date('Y-m-d');
    $fecha_mes = date('Y-m-01');
    
    // Métrica 1: Ventas del día
    $ventas_hoy = Sdba::table('ventas');
    $ventas_hoy->where('fecha', $fecha_hoy)->and_where('estado !=', '2');
    $total_ventas_hoy = $ventas_hoy->sum('total');
    
    // Métrica 2: Ventas del mes  
    $ventas_mes = Sdba::table('ventas');
    $ventas_mes->where('fecha >=', $fecha_mes)->and_where('estado !=', '2');
    $total_ventas_mes = $ventas_mes->sum('total');
    
    // Métrica 3: Productos con stock crítico
    $productos_criticos = Sdba::table('productos');
    $productos_criticos->where('stockp <', '10');
    $productos_criticos_count = $productos_criticos->total();
    
    // Métrica 4: Ventas pendientes (sin comprobante)
    $ventas_pendientes = Sdba::table('ventas');
    $ventas_pendientes->where('estado', '0');
    $ventas_pendientes_count = $ventas_pendientes->total();
    
    // Mejores clientes por total de compra - Usando consulta directa
    $query_clientes = "SELECT cliente, SUM(total) as total_compras 
                      FROM ventas 
                      WHERE estado != '2' 
                      GROUP BY cliente 
                      ORDER BY total_compras DESC 
                      LIMIT 10";
    
    $mejores_clientes_db = Sdba::table('ventas');
    $result = $mejores_clientes_db->db->query($query_clientes);
    $mejores_clientes_list = array();
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $mejores_clientes_list[] = $row;
        }
        $result->free();
    }
    
    // Productos con stock crítico (detallado)
    $productos_stock_critico = Sdba::table('productos');
    $productos_stock_critico->where('stockp <', '10');
    $productos_stock_critico->order_by('stockp', 'asc');
    $productos_stock_critico_list = $productos_stock_critico->get(10);
    
    // Últimos movimientos de stock
    $movimientos_stock = Sdba::table('stock');
    $movimientos_stock->left_join('producto', 'productos', 'id_producto');
    $movimientos_stock->order_by('id_stock', 'desc');
    $movimientos_stock_list = $movimientos_stock->get(15);
    
    // Ventas por tipo (contado vs crédito)
    $ventas_contado = Sdba::table('ventas');
    $ventas_contado->where('tipo', '1')->and_where('estado !=', '2');
    $ventas_contado_count = $ventas_contado->total();
    
    $ventas_credito = Sdba::table('ventas');
    $ventas_credito->where('tipo', '2')->and_where('estado !=', '2');
    $ventas_credito_count = $ventas_credito->total();
    
} catch (Exception $e) {
    // En caso de error, usar valores por defecto
    $total_ventas_hoy = 0;
    $total_ventas_mes = 0;
    $productos_criticos_count = 0;
    $ventas_pendientes_count = 0;
    $ventas_contado_count = 0;
    $ventas_credito_count = 0;
    $mejores_clientes_list = array();
    $productos_stock_critico_list = array();
    $movimientos_stock_list = array();
}

// Asegurar que las variables sean números válidos
$total_ventas_hoy = $total_ventas_hoy ? $total_ventas_hoy : 0;
$total_ventas_mes = $total_ventas_mes ? $total_ventas_mes : 0;
$productos_criticos_count = $productos_criticos_count ? $productos_criticos_count : 0;
$ventas_pendientes_count = $ventas_pendientes_count ? $ventas_pendientes_count : 0;
$ventas_contado_count = $ventas_contado_count ? $ventas_contado_count : 0;
$ventas_credito_count = $ventas_credito_count ? $ventas_credito_count : 0;
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
                <div class="row" style="margin-top: 70px;">
                    <div class="col-md-12">
                        <h2><i class="fas fa-chart-line"></i> Dashboard Ejecutivo</h2>
                        <p class="text-muted">Panel exclusivo para usuarios autorizados</p>
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
                            <div class="metric-label">Stock Crítico</div>
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
                                <h4><i class="fas fa-crown"></i> 👑 Mejores Clientes por Total de Compra</h4>
                            </div>
                            <div class="panel-body">
                                <div class="table-responsive">
                                    <table class="table table-striped table-small">
                                        <thead>
                                            <tr>
                                                <th>Pos.</th>
                                                <th>Cliente</th>
                                                <th>Total Comprado</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php 
                                            $pos = 1;
                                            foreach($mejores_clientes_list as $cliente): ?>
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
                                                <td style="font-weight: bold;"><?php echo $cliente['cliente']; ?></td>
                                                <td>
                                                    <span class="badge badge-success" style="font-size: 0.9em;">
                                                        S/ <?php echo number_format($cliente['total_compras'], 2); ?>
                                                    </span>
                                                </td>
                                            </tr>
                                            <?php $pos++; endforeach; ?>
                                            <?php if (empty($mejores_clientes_list)): ?>
                                            <tr>
                                                <td colspan="3" class="text-center text-muted">No hay datos disponibles</td>
                                            </tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Stock Crítico -->
                    <div class="col-md-6">
                        <div class="panel panel-default">
                            <div class="panel-header" style="padding: 15px; border-bottom: 1px solid #ddd;">
                                <h4><i class="fas fa-exclamation-triangle text-warning"></i> Stock Crítico (< 10)</h4>
                            </div>
                            <div class="panel-body">
                                <div class="table-responsive">
                                    <table class="table table-striped table-small">
                                        <thead>
                                            <tr>
                                                <th>Producto</th>
                                                <th>Stock Actual</th>
                                                <th>Estado</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach($productos_stock_critico_list as $prod): ?>
                                            <tr>
                                                <td><?php echo $prod['nom_prod']; ?></td>
                                                <td><?php echo $prod['stockp']; ?></td>
                                                <td>
                                                    <?php if($prod['stockp'] <= 0): ?>
                                                        <span class="badge badge-danger">AGOTADO</span>
                                                    <?php elseif($prod['stockp'] <= 5): ?>
                                                        <span class="badge badge-danger">CRÍTICO</span>
                                                    <?php else: ?>
                                                        <span class="badge badge-warning">BAJO</span>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                            <?php if (empty($productos_stock_critico_list)): ?>
                                            <tr>
                                                <td colspan="3" class="text-center text-success">
                                                    <i class="fas fa-check-circle"></i> Todos los productos tienen stock suficiente
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

                <!-- Gráfico Simple -->
                <div class="row">
                    <div class="col-md-12">
                        <div class="panel panel-default">
                            <div class="panel-header" style="padding: 15px; border-bottom: 1px solid #ddd;">
                                <h4><i class="fas fa-chart-pie"></i> Distribución de Ventas</h4>
                            </div>
                            <div class="panel-body text-center">
                                <div class="row">
                                    <div class="col-md-6">
                                        <h3><i class="fas fa-money-bill text-success"></i> Contado</h3>
                                        <h2 class="text-success"><?php echo $ventas_contado_count; ?> ventas</h2>
                                    </div>
                                    <div class="col-md-6">
                                        <h3><i class="fas fa-credit-card text-warning"></i> Crédito</h3>
                                        <h2 class="text-warning"><?php echo $ventas_credito_count; ?> ventas</h2>
                                    </div>
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

<!DOCTYPE html>
<html lang="es">
<head>
	<meta charset="UTF-8">
	<title>Dashboard Ejecutivo - Sistema Renacer</title>
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" type="text/css" href="/assets/css/bootstrap.min.css">
    <link rel="stylesheet" type="text/css" href="/assets/css/custom.css">
    <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.7.2/css/all.css" integrity="sha384-fnmOCqbTlWIlj8LyTjo7mOUStjsKC4pOpQbqyi7RrhN7udi9RwhKkMHpvLbHG9Sr" crossorigin="anonymous">
    <link rel="stylesheet" type="text/css" href="/assets/css/sweetalert2.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
    .growth-positive { color: #28a745; }
    .growth-negative { color: #dc3545; }
    .table-small { font-size: 0.85em; }
    .chart-container { 
        position: relative; 
        height: 300px; 
        margin-bottom: 20px;
    }
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
                <div class="row" style="margin-top: 70px;">
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
                            <small class="<?php echo $crecimiento >= 0 ? 'growth-positive' : 'growth-negative'; ?>">
                                <?php echo $crecimiento >= 0 ? '+' : ''; ?><?php echo number_format($crecimiento, 1); ?>% vs mes anterior
                            </small>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="metric-card warning">
                            <div class="metric-icon"><i class="fas fa-exclamation-triangle"></i></div>
                            <div class="metric-number"><?php echo $productos_criticos_count; ?></div>
                            <div class="metric-label">Stock Crítico</div>
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
                    <!-- Panel Izquierdo -->
                    <div class="col-md-6">
                        <!-- Gráfico de Ventas Contado vs Crédito -->
                        <div class="panel panel-default">
                            <div class="panel-header" style="padding: 15px; border-bottom: 1px solid #ddd;">
                                <h4><i class="fas fa-pie-chart"></i> Ventas por Tipo de Pago</h4>
                            </div>
                            <div class="panel-body">
                                <div class="chart-container">
                                    <canvas id="pieChart"></canvas>
                                </div>
                            </div>
                        </div>

                        <!-- Top Productos Más Vendidos -->
                        <div class="panel panel-default">
                            <div class="panel-header" style="padding: 15px; border-bottom: 1px solid #ddd;">
                                <h4><i class="fas fa-trophy"></i> Top 10 Productos Más Vendidos</h4>
                            </div>
                            <div class="panel-body">
                                <div class="table-responsive">
                                    <table class="table table-striped table-small">
                                        <thead>
                                            <tr>
                                                <th>#</th>
                                                <th>Producto</th>
                                                <th>Cantidad Vendida</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr>
                                                <td colspan="3" class="text-center text-muted">
                                                    <i class="fas fa-info-circle"></i> Datos de productos en desarrollo
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Panel Derecho -->
                    <div class="col-md-6">
                        <!-- MEJORES CLIENTES POR TOTAL DE COMPRA -->
                        <div class="panel panel-default">
                            <div class="panel-header" style="padding: 15px; border-bottom: 1px solid #ddd; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;">
                                <h4><i class="fas fa-crown"></i> 👑 Mejores Clientes por Total de Compra</h4>
                            </div>
                            <div class="panel-body">
                                <div class="table-responsive">
                                    <table class="table table-striped table-small">
                                        <thead>
                                            <tr>
                                                <th>Pos.</th>
                                                <th>Cliente</th>
                                                <th>Total Comprado</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php 
                                            $pos = 1;
                                            foreach($mejores_clientes_list as $cliente_nombre => $total_compras): ?>
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
                                                <td style="font-weight: bold;"><?php echo $cliente_nombre; ?></td>
                                                <td>
                                                    <span class="badge badge-success" style="font-size: 0.9em;">
                                                        S/ <?php echo number_format($total_compras, 2); ?>
                                                    </span>
                                                </td>
                                            </tr>
                                            <?php $pos++; endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <!-- Productos con Stock Crítico -->
                        <div class="panel panel-default">
                            <div class="panel-header" style="padding: 15px; border-bottom: 1px solid #ddd;">
                                <h4><i class="fas fa-exclamation-triangle text-warning"></i> Stock Crítico (< 10)</h4>
                            </div>
                            <div class="panel-body">
                                <div class="table-responsive">
                                    <table class="table table-striped table-small">
                                        <thead>
                                            <tr>
                                                <th>Producto</th>
                                                <th>Stock Actual</th>
                                                <th>Estado</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach($productos_stock_critico_list as $prod): ?>
                                            <tr>
                                                <td><?php echo $prod['nom_prod']; ?></td>
                                                <td><?php echo $prod['stockp']; ?></td>
                                                <td>
                                                    <?php if($prod['stockp'] <= 0): ?>
                                                        <span class="badge badge-danger">AGOTADO</span>
                                                    <?php elseif($prod['stockp'] <= 5): ?>
                                                        <span class="badge badge-danger">CRÍTICO</span>
                                                    <?php else: ?>
                                                        <span class="badge badge-warning">BAJO</span>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Últimos Movimientos de Stock -->
                <div class="row">
                    <div class="col-md-12">
                        <div class="panel panel-default">
                            <div class="panel-header" style="padding: 15px; border-bottom: 1px solid #ddd;">
                                <h4><i class="fas fa-exchange-alt"></i> Últimos Movimientos de Stock</h4>
                            </div>
                            <div class="panel-body">
                                <div class="table-responsive">
                                    <table class="table table-striped table-small">
                                        <thead>
                                            <tr>
                                                <th>Fecha</th>
                                                <th>Producto</th>
                                                <th>Ingreso</th>
                                                <th>Egreso</th>
                                                <th>Stock Final</th>
                                                <th>Motivo</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach($movimientos_stock_list as $mov): ?>
                                            <tr>
                                                <td><?php echo date('d/m/Y', strtotime($mov['fecha'])); ?></td>
                                                <td>Producto #<?php echo $mov['producto']; ?></td>
                                                <td>
                                                    <?php if($mov['ingreso'] > 0): ?>
                                                        <span class="text-success">+<?php echo $mov['ingreso']; ?></span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php if($mov['egreso'] > 0): ?>
                                                        <span class="text-danger">-<?php echo $mov['egreso']; ?></span>
                                                    <?php endif; ?>
                                                </td>
                                                <td><strong><?php echo $mov['stockt']; ?></strong></td>
                                                <td><small class="text-muted"><?php echo $mov['motivo']; ?></small></td>
                                            </tr>
                                            <?php endforeach; ?>
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
            }, 300000); // 5 minutos
        </script>

	<!-- jQuery (necessary for Bootstrap's JavaScript plugins) -->
	<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.3.1/jquery.min.js"></script>
	<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js"></script>
	<script src="https://cdn.jsdelivr.net/npm/jquery-validation@1.17.0/dist/jquery.validate.min.js"></script>
	<script src="assets/js/sweetalert2.all.min.js"></script>

    <!-- Gráfico de Pie para Contado vs Crédito -->
    <script>
        const ctx = document.getElementById('pieChart').getContext('2d');
        const pieChart = new Chart(ctx, {
            type: 'pie',
            data: {
                labels: ['Contado', 'Crédito'],
                datasets: [{
                    data: [<?php echo $ventas_contado_count; ?>, <?php echo $ventas_credito_count; ?>],
                    backgroundColor: [
                        '#28a745',
                        '#ffc107'
                    ],
                    borderWidth: 2,
                    borderColor: '#fff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const label = context.label || '';
                                const value = context.parsed;
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const percentage = ((value / total) * 100).toFixed(1);
                                return label + ': ' + value + ' ventas (' + percentage + '%)';
                            }
                        }
                    }
                }
            }
        });
    </script>
</body>
</html>
		

	  
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