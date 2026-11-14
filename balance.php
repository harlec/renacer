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

$periodos = [];
$rp = $conn->query("SELECT * FROM caja_periodos ORDER BY fecha_inicio DESC, id_cperiodo DESC");
if ($rp) { while ($p = $rp->fetch_assoc()) $periodos[(int)$p['id_cperiodo']] = $p; }

$id = intval($_GET['id'] ?? 0);
if (!isset($periodos[$id])) $id = $periodos ? (int)array_key_first($periodos) : 0;
$per = $id ? $periodos[$id] : null;

// Sugerencia de caja inicial para un periodo nuevo: lo que cerró el periodo anterior.


$b = $per ? calcular_balance($conn, $per) : null;

// Caja inicial sugerida para el siguiente periodo: saldo final del más reciente.
$sugeridos = [];
if ($periodos) {
	$ult = calcular_balance($conn, reset($periodos));
	foreach ($ult['medios'] as $k => $md) $sugeridos[$k] = round($md['ini'] + $md['debe'] - $md['haber'], 2);
}
$sugerida_inicio = $periodos ? date('Y-m-d', strtotime(reset($periodos)['fecha_fin'] . ' +1 day')) : date('Y-m-01');
$conn->close();
function s($n) { return 'S/ ' . number_format($n, 2); }
?>
<!DOCTYPE html>
<html lang="es">
<head>
	<meta charset="UTF-8">
	<title>Sistema - Balance</title>
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" type="text/css" href="/assets/css/bootstrap.min.css">
    <link rel="stylesheet" type="text/css" href="/assets/css/custom.css">
    <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.7.2/css/all.css" integrity="sha384-fnmOCqbTlWIlj8LyTjo7mOUStjsKC4pOpQbqyi7RrhN7udi9RwhKkMHpvLbHG9Sr" crossorigin="anonymous">
    <link rel="stylesheet" type="text/css" href="//cdn.datatables.net/1.10.22/css/jquery.dataTables.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/limonte-sweetalert2/10.5.0/sweetalert2.min.css" integrity="sha512-YpZXdiMhuP3woCdvg0ou2UPj6l4KQUuf3gbMXTNMgtqTakMInX7h+64CTh+UIvYdA7ctBU2BAA/h4eEhoMEmsg==" crossorigin="anonymous" />
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
	      		<li class="active"><a href="balance.php">Balance</a></li>
	      		<li><a href="balance_diario.php">Balance diario</a></li>
	      	</ul>
	      </div>
	    </nav>
		<div class="kbg">
			<div class="cuerpofull">
				<div class="titulo">
					<h3>Balance
						<button type="button" id="nuevo_periodo" class="btn btn-success btn-sm pull-right">Nuevo periodo</button>
					</h3>
				</div>
				<div class="container-fluid">
					<?php if (!$per): ?>
						<p class="text-muted">Aún no hay periodos. Crea el primero con su caja inicial para empezar a llevar el balance.</p>
					<?php else: ?>
					<form class="form-inline" method="get" style="margin-bottom:15px">
						<select name="id" class="form-control" onchange="this.form.submit()">
							<?php foreach ($periodos as $pid => $p) echo '<option value="' . $pid . '"' . ($pid == $id ? ' selected' : '') . '>' . htmlspecialchars($p['nombre']) . ' (' . date('d/m/Y', strtotime($p['fecha_inicio'])) . ' - ' . date('d/m/Y', strtotime($p['fecha_fin'])) . ')</option>'; ?>
						</select>
						<button type="button" class="btn btn-danger btn-sm" id="borrar_periodo" data-id="<?php echo $id; ?>"><i class="fas fa-trash"></i></button>
					</form>
					<div class="row">
						<div class="col-md-3"><div class="kpi"><small>Caja inicial</small><h3><?php echo s($b['inicial']); ?></h3></div></div>
						<div class="col-md-3"><div class="kpi"><small>Debe (ingresos)</small><h3 class="pos"><?php echo s($b['debe']); ?></h3></div></div>
						<div class="col-md-3"><div class="kpi"><small>Haber (egresos)</small><h3 class="neg"><?php echo s($b['haber']); ?></h3></div></div>
						<div class="col-md-3"><div class="kpi"><small>Saldo / caja final</small><h3 class="<?php echo $b['saldo_final'] >= 0 ? 'pos' : 'neg'; ?>"><?php echo s($b['saldo_final']); ?></h3></div></div>
					</div>
					<div class="panel panel-default pa"><div class="panel-body table-responsive">
						<h4>Saldo por medio de pago</h4>
						<table class="table table-condensed">
							<thead><tr><th>Medio</th><th class="text-right">Inicial</th><th class="text-right">Entra</th><th class="text-right">Sale</th><th class="text-right">Saldo</th></tr></thead>
							<tbody>
							<?php foreach ($b['medios'] as $k => $md) { if ($k === 'otro' && !$md['ini'] && !$md['debe'] && !$md['haber']) continue; $sd = $md['ini'] + $md['debe'] - $md['haber']; ?>
								<tr><td><?php echo $k === 'otro' ? 'Otros (sin clasificar)' : medio_label($k); ?></td><td class="text-right"><?php echo number_format($md['ini'], 2); ?></td><td class="text-right pos"><?php echo number_format($md['debe'], 2); ?></td><td class="text-right neg"><?php echo number_format($md['haber'], 2); ?></td><td class="text-right <?php echo $sd >= 0 ? 'pos' : 'neg'; ?>"><strong><?php echo number_format($sd, 2); ?></strong></td></tr>
							<?php } ?>
							</tbody>
						</table>
					</div></div>
					<div class="row">
						<div class="col-md-4">
							<div class="panel panel-default pa"><div class="panel-body">
								<h4>Resumen por concepto</h4>
								<table class="table table-condensed">
									<?php foreach (['Ventas', 'Proveedores', 'Gastos', 'Planillas'] as $g) { $v = $b['resumen'][$g] ?? 0; echo '<tr><td>' . $g . '</td><td class="text-right ' . ($v >= 0 ? 'pos' : 'neg') . '">' . s($v) . '</td></tr>'; } ?>
									<tr><td><strong>Resultado del periodo</strong></td><td class="text-right"><strong><?php echo s($b['debe'] - $b['haber']); ?></strong></td></tr>
								</table>
							</div></div>
						</div>
						<div class="col-md-8">
							<div class="panel panel-default pa"><div class="panel-body table-responsive">
								<h4>Libro de movimientos</h4>
								<p class="text-muted" style="margin:0 0 8px">Caja inicial al <?php echo date('d/m/Y', strtotime($per['fecha_inicio'])); ?>: <strong><?php echo s($b['inicial']); ?></strong> &mdash; el saldo de cada fila es acumulado desde ahí.</p>
								<table id="datos" class="table table-hover table-condensed">
									<thead><tr><th>Fecha</th><th>Concepto</th><th>Medio</th><th>Detalle</th><th class="text-right">Debe</th><th class="text-right">Haber</th><th class="text-right">Saldo</th></tr></thead>
									<tbody>
										<?php $saldo = $b['inicial']; foreach ($b['mov'] as $m) { $saldo += $m['debe'] - $m['haber']; ?>
										<tr>
											<td><?php echo date('d/m/Y', strtotime($m['fecha'])); ?></td>
											<td><?php echo $m['grupo']; ?></td>
											<td><?php echo $m['medio'] === 'otro' ? 'Otro' : medio_label($m['medio']); ?></td>
											<td><?php echo htmlspecialchars($m['detalle']); ?></td>
											<td class="text-right"><?php echo $m['debe'] ? number_format($m['debe'], 2) : ''; ?></td>
											<td class="text-right"><?php echo $m['haber'] ? number_format($m['haber'], 2) : ''; ?></td>
											<td class="text-right"><?php echo number_format($saldo, 2); ?></td>
										</tr>
										<?php } ?>
									</tbody>
								</table>
							</div></div>
						</div>
					</div>
					<p class="text-muted" style="font-size:12px">Ingresos = cobros de ventas (no anuladas). Egresos = pagos a proveedores, gastos y planillas (neto a pagar, contado en la fecha fin de cada quincena). Base de caja: cuenta lo realmente cobrado/pagado.</p>
					<?php endif; ?>
				</div>
			</div>
		</div>

	<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.3.1/jquery.min.js"></script>
	<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js"></script>
	<script src="//cdn.datatables.net/1.10.22/js/jquery.dataTables.min.js"></script>
	<script src="https://cdnjs.cloudflare.com/ajax/libs/limonte-sweetalert2/10.5.0/sweetalert2.min.js" integrity="sha512-V9JHp52ZkrbVVjJqNz/XXYMUOyUfzaGKEGrcD2Ual7n39+UR1yJK0numAHZqkhhGTAH/Klj0KUe4btAZXccw9w==" crossorigin="anonymous"></script>
	<script>
	$(document).ready(function() {
		if ($('#datos').length) {
			$('#datos').DataTable({ order: [], pageLength: 25, lengthMenu: [25, 50, 100, 200] });
		}

		$('#nuevo_periodo').on('click', function() {
			Swal.fire({
				title: 'Nuevo periodo',
				html:
					'<div style="text-align:left">' +
					'<label style="font-size:12px">Nombre</label><input id="p-nombre" class="swal2-input" placeholder="Ej. Octubre 2026">' +
					'<label style="font-size:12px">Fecha inicio</label><input id="p-ini" type="date" class="swal2-input" value="<?php echo $sugerida_inicio; ?>">' +
					'<label style="font-size:12px">Fecha fin</label><input id="p-fin" type="date" class="swal2-input" value="<?php echo date('Y-m-t', strtotime($sugerida_inicio)); ?>">' +
					'<label style="font-size:12px">Caja inicial por medio S/ <?php echo $periodos ? "(sugerida: saldo final del periodo anterior)" : ""; ?></label>' +
					<?php foreach ($MEDIOS_PAGO as $k => $v) echo "'<div style=\"display:flex;align-items:center;gap:8px\"><span style=\"width:90px\">$v</span><input id=\"p-s-$k\" type=\"number\" step=\"0.01\" class=\"swal2-input\" style=\"margin:4px 0\" value=\"" . number_format($sugeridos[$k] ?? 0, 2, '.', '') . "\"></div>' +\n"; ?>
					'</div>',
				showCancelButton: true,
				confirmButtonText: 'Crear',
				cancelButtonText: 'Cancelar',
				preConfirm: function() {
					var d = { nombre: $.trim($('#p-nombre').val()), fecha_inicio: $('#p-ini').val(), fecha_fin: $('#p-fin').val(), saldo: {} };
					<?php foreach ($MEDIOS_PAGO as $k => $v) echo "d.saldo['$k'] = $('#p-s-$k').val() || 0;\n"; ?>
					if (!d.nombre || !d.fecha_inicio || !d.fecha_fin) { Swal.showValidationMessage('Completa nombre y fechas'); return false; }
					if (d.fecha_fin < d.fecha_inicio) { Swal.showValidationMessage('La fecha fin no puede ser anterior al inicio'); return false; }
					return d;
				}
			}).then(function(result) {
				if (!result.isConfirmed) return;
				$.post('inc/registrar_caja_periodo.php', result.value, function(data) {
					if (data.ok) { document.location.href = 'balance.php?id=' + data.id; }
					else { Swal.fire('Advertencia', data.mensaje || 'No se pudo crear', 'warning'); }
				}, 'json').fail(function() { Swal.fire('Advertencia', 'Error general del sistema', 'warning'); });
			});
		});

		$('#borrar_periodo').on('click', function() {
			var id = $(this).data('id');
			Swal.fire({ title: 'Borrar este periodo?', text: 'Solo se borra el periodo y su caja inicial; ventas, compras y gastos no se tocan.', icon: 'warning', showCancelButton: true, confirmButtonText: 'Sí, borrar' })
			.then(function(result) {
				if (!result.isConfirmed) return;
				$.getJSON('inc/borrar_caja_periodo.php', { id: id }, function(data) {
					if (data.ok) { document.location.href = 'balance.php'; }
					else { Swal.fire('Advertencia', data.mensaje || 'No se pudo borrar', 'warning'); }
				});
			});
		});
	});
	</script>
</body>
</html>
