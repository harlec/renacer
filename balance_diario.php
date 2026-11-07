<?php
include('inc/control.php');
include_once('inc/medios_pago.php');
include_once('inc/balance_calc.php');
if ($_SESSION['type'] != 'admin') {
	header("Location: dashboard.php");
	exit;
}

$conn = new mysqli('localhost', 'admin_renacer', 'ikm169uhn', 'admin_renacer');
$conn->set_charset('utf8');


$dia = $_GET['dia'] ?? date('Y-m-d');
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $dia)) $dia = date('Y-m-d');

$b = calcular_balance($conn, ['id_cperiodo' => 0, 'fecha_inicio' => $dia, 'fecha_fin' => $dia, 'caja_inicial' => 0]);
$conn->close();

$dia_ant = date('Y-m-d', strtotime($dia . ' -1 day'));
$dia_sig = date('Y-m-d', strtotime($dia . ' +1 day'));
$resultado = round($b['debe'] - $b['haber'], 2);
function s($n) { return 'S/ ' . number_format($n, 2); }
?>
<!DOCTYPE html>
<html lang="es">
<head>
	<meta charset="UTF-8">
	<title>Sistema - Balance diario</title>
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" type="text/css" href="/assets/css/bootstrap.min.css">
    <link rel="stylesheet" type="text/css" href="/assets/css/custom.css">
    <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.7.2/css/all.css" integrity="sha384-fnmOCqbTlWIlj8LyTjo7mOUStjsKC4pOpQbqyi7RrhN7udi9RwhKkMHpvLbHG9Sr" crossorigin="anonymous">
    <style>.kpi{background:#fff;border-radius:10px;padding:14px 18px;box-shadow:0 1px 3px rgba(0,0,0,.08);margin-bottom:15px}.kpi small{color:#888;text-transform:uppercase;font-weight:700}.kpi h3{margin:4px 0 0}.pos{color:#27ae60}.neg{color:#c0392b}</style>
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
	        <?php menu('11'); ?>
	      </div>
	      <div class="submenu">
	      	<ul class="subtop-tabs">
	      		<li><a href="gastos.php">Gastos</a></li>
	      		<li><a href="balance.php">Balance</a></li>
	      		<li class="active"><a href="balance_diario.php">Balance diario</a></li>
	      	</ul>
	      </div>
	    </nav>
		<div class="kbg">
			<div class="cuerpofull">
				<div class="titulo"><h3>Balance diario</h3></div>
				<div class="container-fluid">
					<form class="form-inline" method="get" style="margin-bottom:15px">
						<a class="btn btn-default btn-sm" href="?dia=<?php echo $dia_ant; ?>"><i class="fas fa-chevron-left"></i></a>
						<input type="date" name="dia" class="form-control" value="<?php echo $dia; ?>" onchange="this.form.submit()">
						<a class="btn btn-default btn-sm" href="?dia=<?php echo $dia_sig; ?>"><i class="fas fa-chevron-right"></i></a>
						<a class="btn btn-default btn-sm" href="?dia=<?php echo date('Y-m-d'); ?>">Hoy</a>
					</form>
					<div class="row">
						<div class="col-md-4"><div class="kpi"><small>Ingresos del día</small><h3 class="pos"><?php echo s($b['debe']); ?></h3></div></div>
						<div class="col-md-4"><div class="kpi"><small>Pagos y gastos del día</small><h3 class="neg"><?php echo s($b['haber']); ?></h3></div></div>
						<div class="col-md-4"><div class="kpi"><small>Resultado del día</small><h3 class="<?php echo $resultado >= 0 ? 'pos' : 'neg'; ?>"><?php echo s($resultado); ?></h3></div></div>
					</div>
					<div class="row">
						<div class="col-md-5">
							<div class="panel panel-default pa"><div class="panel-body table-responsive">
								<h4>Por medio de pago</h4>
								<table class="table table-condensed">
									<thead><tr><th>Medio</th><th class="text-right">Entra</th><th class="text-right">Sale</th><th class="text-right">Neto</th></tr></thead>
									<tbody>
									<?php foreach ($b['medios'] as $k => $md) { if (!$md['debe'] && !$md['haber']) continue; $n = $md['debe'] - $md['haber']; ?>
										<tr><td><?php echo $k === 'otro' ? 'Otros (sin clasificar)' : medio_label($k); ?></td><td class="text-right pos"><?php echo number_format($md['debe'], 2); ?></td><td class="text-right neg"><?php echo number_format($md['haber'], 2); ?></td><td class="text-right <?php echo $n >= 0 ? 'pos' : 'neg'; ?>"><strong><?php echo number_format($n, 2); ?></strong></td></tr>
									<?php } ?>
									</tbody>
								</table>
							</div></div>
							<div class="panel panel-default pa"><div class="panel-body">
								<h4>Por concepto</h4>
								<table class="table table-condensed">
									<?php foreach (['Ventas', 'Proveedores', 'Gastos', 'Planillas'] as $g) { $v = $b['resumen'][$g] ?? 0; echo '<tr><td>' . $g . '</td><td class="text-right ' . ($v >= 0 ? 'pos' : 'neg') . '">' . s($v) . '</td></tr>'; } ?>
								</table>
							</div></div>
						</div>
						<div class="col-md-7">
							<div class="panel panel-default pa"><div class="panel-body table-responsive">
								<h4>Movimientos del <?php echo date('d/m/Y', strtotime($dia)); ?></h4>
								<table class="table table-hover table-condensed">
									<thead><tr><th>Hora</th><th>Concepto</th><th>Medio</th><th>Detalle</th><th class="text-right">Ingreso</th><th class="text-right">Egreso</th></tr></thead>
									<tbody>
										<?php if (!$b['mov']) echo '<tr><td colspan="6" class="text-muted text-center">Sin movimientos este día</td></tr>'; ?>
										<?php foreach ($b['mov'] as $m) { ?>
										<tr>
											<td><?php echo in_array($m['grupo'], ['Ventas', 'Proveedores']) ? date('H:i', strtotime($m['fecha'])) : ''; ?></td>
											<td><?php echo $m['grupo']; ?></td>
											<td><?php echo $m['medio'] === 'otro' ? 'Otro' : medio_label($m['medio']); ?></td>
											<td><?php echo htmlspecialchars($m['detalle']); ?></td>
											<td class="text-right"><?php echo $m['debe'] ? number_format($m['debe'], 2) : ''; ?></td>
											<td class="text-right"><?php echo $m['haber'] ? number_format($m['haber'], 2) : ''; ?></td>
										</tr>
										<?php } ?>
									</tbody>
								</table>
							</div></div>
						</div>
					</div>
					<p class="text-muted" style="font-size:12px">Ingresos = cobros de ventas del día (no anuladas). Egresos = pagos a proveedores, gastos y planillas (la planilla se cuenta el día en que termina la quincena). Base de caja: lo realmente cobrado/pagado ese día.</p>
				</div>
			</div>
		</div>
	<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.3.1/jquery.min.js"></script>
	<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js"></script>
</body>
</html>
