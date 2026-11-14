<?php
include('inc/control.php');
include_once('inc/medios_pago.php');

$conn = new mysqli('localhost', 'admin_renacer', 'ikm169uhn', 'admin_renacer');
$conn->set_charset('utf8');

$desde = $_GET['desde'] ?? date('Y-m-01');
$hasta = $_GET['hasta'] ?? date('Y-m-d');
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $desde)) $desde = date('Y-m-01');
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $hasta)) $hasta = date('Y-m-d');
$id_prov = intval($_GET['proveedor'] ?? 0);

$proveedores_lista = [];
$rp = $conn->query("SELECT id_proveedor, proveedor FROM proveedores ORDER BY proveedor");
while ($rp && $row = $rp->fetch_assoc()) $proveedores_lista[(int)$row['id_proveedor']] = $row['proveedor'];

$filtro_prov = $id_prov ? " AND c.proveedor = $id_prov" : '';
$r = $conn->query("
    SELECT cp.id_pago, cp.fecha, cp.monto, cp.metodo, cp.compra,
           c.serie_f, c.numero_f, c.deuda_anterior, c.forma_pago,
           pr.proveedor AS nombre_proveedor, u.nombres AS nombre_usuario
    FROM compra_pagos cp
    INNER JOIN compras c ON c.id_compra = cp.compra
    LEFT JOIN proveedores pr ON pr.id_proveedor = c.proveedor
    LEFT JOIN usuarios u ON u.id_usuario = cp.usuario
    WHERE c.estado != '2' AND DATE(cp.fecha) BETWEEN '" . $conn->real_escape_string($desde) . "' AND '" . $conn->real_escape_string($hasta) . "'$filtro_prov
    ORDER BY cp.fecha DESC, cp.id_pago DESC");

$forma_label = ['contado' => 'Contado', 'credito' => 'Crédito'];
$datos = '';
$total = 0;
$por_medio = [];
while ($r && $x = $r->fetch_assoc()) {
	$monto = round((float)$x['monto'], 2);
	$total += $monto;
	$por_medio[$x['metodo']] = ($por_medio[$x['metodo']] ?? 0) + $monto;
	$doc = $x['deuda_anterior'] === '1' ? 'Deuda anterior' : htmlspecialchars($x['serie_f'] . '-' . $x['numero_f']);
	$datos .= '<tr>
		<td data-order="' . strtotime($x['fecha']) . '">' . date('d/m/Y H:i', strtotime($x['fecha'])) . '</td>
		<td>' . htmlspecialchars($x['nombre_proveedor'] ?: 'Sin proveedor') . '</td>
		<td>' . $doc . '</td>
		<td>' . ($forma_label[$x['forma_pago']] ?? htmlspecialchars($x['forma_pago'])) . '</td>
		<td>' . htmlspecialchars(medio_label($x['metodo'])) . '</td>
		<td class="text-right" data-order="' . $monto . '">' . number_format($monto, 2) . '</td>
		<td>' . htmlspecialchars($x['nombre_usuario'] ?: '-') . '</td>
		<td><a title="Ver compra" href="ver_compra.php?id=' . (int)$x['compra'] . '"><i class="fas fa-eye"></i> Ver</a></td>
	</tr>';
}
$total = round($total, 2);
$conn->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
	<meta charset="UTF-8">
	<title>Sistema - Historial de pagos a proveedores</title>
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" type="text/css" href="/assets/css/bootstrap.min.css">
    <link rel="stylesheet" type="text/css" href="/assets/css/custom.css">
    <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.7.2/css/all.css" integrity="sha384-fnmOCqbTlWIlj8LyTjo7mOUStjsKC4pOpQbqyi7RrhN7udi9RwhKkMHpvLbHG9Sr" crossorigin="anonymous">
    <link rel="stylesheet" type="text/css" href="//cdn.datatables.net/1.10.22/css/jquery.dataTables.min.css">
    <style>.kpi{background:#fff;border-radius:10px;padding:14px 18px;box-shadow:0 1px 3px rgba(0,0,0,.08);margin-bottom:15px}.kpi small{color:#888;text-transform:uppercase;font-weight:700}.kpi h3{margin:4px 0 0}</style>
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
	        <?php menu('6'); ?>
	      </div>
	      <div class="submenu">
	      	<ul class="subtop-tabs">
	      		<li><a href="compra.php">Registrar Compra</a></li>
	      		<li><a href="compras.php">Listar Compras</a></li>
	      		<li><a href="proveedores.php">Proveedores</a></li>
	      		<li><a href="cuentas_x_pagar.php">Cuentas x pagar</a></li>
	      		<li class="active"><a href="pagos_proveedores.php">Historial de pagos</a></li>
	      	</ul>
	      </div>
	    </nav>
		<div class="kbg">
			<div class="cuerpofull">
				<div class="titulo"><h3>Historial de pagos a proveedores</h3></div>
				<div class="container-fluid">
					<form class="form-inline" method="get" style="margin-bottom:15px">
						<label>Desde</label> <input type="date" name="desde" class="form-control" value="<?php echo $desde; ?>">
						<label>Hasta</label> <input type="date" name="hasta" class="form-control" value="<?php echo $hasta; ?>">
						<select name="proveedor" class="form-control">
							<option value="0">Todos los proveedores</option>
							<?php foreach ($proveedores_lista as $pid => $pn) echo '<option value="' . $pid . '"' . ($pid == $id_prov ? ' selected' : '') . '>' . htmlspecialchars($pn) . '</option>'; ?>
						</select>
						<button type="submit" class="btn btn-default"><i class="fas fa-search"></i> Filtrar</button>
					</form>
					<div class="row">
						<div class="col-md-4"><div class="kpi"><small>Total pagado</small><h3>S/ <?php echo number_format($total, 2); ?></h3></div></div>
						<?php foreach ($por_medio as $k => $v) echo '<div class="col-md-2"><div class="kpi"><small>' . htmlspecialchars(medio_label($k)) . '</small><h3>S/ ' . number_format($v, 2) . '</h3></div></div>'; ?>
					</div>
					<div class="panel panel-default pa"><div class="panel-body table-responsive">
						<table id="datos" class="table table-hover table-condensed">
							<thead><tr><th>Fecha</th><th>Proveedor</th><th>Documento</th><th>Tipo de compra</th><th>Medio</th><th class="text-right">Monto</th><th>Registró</th><th>Opciones</th></tr></thead>
							<tbody><?php echo $datos; ?></tbody>
						</table>
					</div></div>
				</div>
			</div>
		</div>
	<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.3.1/jquery.min.js"></script>
	<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js"></script>
	<script src="//cdn.datatables.net/1.10.22/js/jquery.dataTables.min.js"></script>
	<script>
	$(document).ready(function() {
		$('#datos').DataTable({ order: [[0, 'desc']], pageLength: 25, lengthMenu: [25, 50, 100, 200] });
	});
	</script>
</body>
</html>
