<?php
include('inc/control.php');
if ($_SESSION['type'] != 'admin') {
	header("Location: dashboard.php");
	exit;
}
include('inc/cliente_duplicados.php');
ini_set('memory_limit', '256M');
set_time_limit(60);

$conn = new mysqli('localhost', 'admin_renacer', 'ikm169uhn', 'admin_renacer');
$conn->set_charset('utf8');

$grupos = detectar_clientes_duplicados($conn);

// Historial de fusiones (si la migración aún no se corrió, simplemente no hay historial)
$fusiones = [];
$rf = false;
$sin_migracion = false;
try {
$rf = $conn->query("
	SELECT f.id_fusion, f.fecha, f.deshecha, f.id_principal, f.id_duplicado,
	       p.cliente AS nom_principal, d.cliente AS nom_duplicado,
	       (LENGTH(COALESCE(f.ventas_ids,'[]')) - LENGTH(REPLACE(COALESCE(f.ventas_ids,'[]'), ',', ''))) + (f.ventas_ids IS NOT NULL AND f.ventas_ids != '[]') AS n_ventas
	FROM cliente_fusiones f
	LEFT JOIN clientes p ON p.id_cliente = f.id_principal
	LEFT JOIN clientes d ON d.id_cliente = f.id_duplicado
	ORDER BY f.id_fusion DESC LIMIT 50");
} catch (Throwable $e) { $sin_migracion = true; } // la tabla cliente_fusiones aún no existe
if ($rf) { while ($x = $rf->fetch_assoc()) $fusiones[] = $x; }
$conn->close();
$esc = function($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); };
?>
<!DOCTYPE html>
<html lang="es">
<head>
	<meta charset="UTF-8">
	<title>Sistema - Clientes duplicados</title>
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" type="text/css" href="/assets/css/bootstrap.min.css">
    <link rel="stylesheet" type="text/css" href="/assets/css/custom.css">
    <link rel="stylesheet" type="text/css" href="//cdn.datatables.net/1.10.22/css/jquery.dataTables.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/limonte-sweetalert2/10.5.0/sweetalert2.min.css" integrity="sha512-YpZXdiMhuP3woCdvg0ou2UPj6l4KQUuf3gbMXTNMgtqTakMInX7h+64CTh+UIvYdA7ctBU2BAA/h4eEhoMEmsg==" crossorigin="anonymous" />
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
	        <?php menu('7'); ?>
	      </div>
	      <div class="submenu">
	      	<ul class="subtop-tabs">
	      		<li >
	      			<a class="" href="agregar_cliente.php">Registrar Cliente</a>
	      		</li>
	      		<li>
	      			<a class="" href="ver_clientes.php">Listar Clientes</a>
	      		</li>
	      		<li class="active">
	      			<a class="" href="clientes_duplicados.php">Duplicados</a>
	      		</li>
	      	</ul>
	      </div>
	    </nav>
		<div class="kbg">
			<div class="cuerpofull">
				<div class="titulo">
					<h3>Clientes duplicados <small><?php echo count($grupos); ?> grupos sugeridos</small></h3>
				</div>
				<div class="container-fluid">
					<div class="alert alert-info" style="font-size:13px">
						Son <strong>sugerencias</strong> por parecido de nombre o mismo documento: revisa cada grupo.
						Elige el cliente <strong>principal</strong> (el que se queda), marca los que quieres fusionar en él, y se pasan todas sus ventas.
						Los fusionados no se borran, quedan inactivos y se pueden <strong>deshacer</strong> abajo.
					</div>
					<input type="text" id="buscar" class="form-control" placeholder="Filtrar por nombre..." style="max-width:320px;margin-bottom:15px">

					<?php if ($sin_migracion): ?>
						<div class="alert alert-warning">Falta correr <code>sql/add_fusion_clientes.sql</code> en la base de datos antes de poder fusionar.</div>
					<?php endif; ?>
					<?php if (!$grupos): ?>
						<p class="text-muted">No se encontraron posibles duplicados.</p>
					<?php endif; ?>

					<?php foreach ($grupos as $gi => $g): ?>
					<div class="panel panel-default grupo" data-nombres="<?php echo $esc(strtolower(implode(' ', array_column($g, 'cliente')))); ?>">
						<div class="panel-body table-responsive">
							<table class="table table-condensed" style="margin-bottom:8px">
								<thead><tr><th>Principal</th><th>Fusionar</th><th>Id</th><th>Cliente</th><th>Ventas</th><th>Última venta</th><th>Documento</th><th>Teléfono</th><th>Email</th></tr></thead>
								<tbody>
								<?php foreach ($g as $k => $c): ?>
									<tr>
										<td><input type="radio" name="principal_<?php echo $gi; ?>" value="<?php echo $c['id_cliente']; ?>" <?php echo $k === 0 ? 'checked' : ''; ?>></td>
										<td><input type="checkbox" class="chk" value="<?php echo $c['id_cliente']; ?>" data-doc="<?php echo $esc($c['doc']); ?>" <?php echo $k === 0 ? '' : 'checked'; ?>></td>
										<td><?php echo $c['id_cliente']; ?></td>
										<td><strong><?php echo $esc($c['cliente']); ?></strong></td>
										<td><?php echo $c['ventas']; ?></td>
										<td><?php echo $c['ultima'] ? date('d/m/Y', strtotime($c['ultima'])) : '-'; ?></td>
										<td><?php echo $esc($c['doc_identidad']); ?></td>
										<td><?php echo $esc($c['telefono']); ?></td>
										<td><?php echo $esc($c['email']); ?></td>
									</tr>
								<?php endforeach; ?>
								</tbody>
							</table>
							<button type="button" class="btn btn-custom btn-sm btn-fusionar">Fusionar seleccionados en el principal</button>
						</div>
					</div>
					<?php endforeach; ?>

					<h4 style="margin-top:30px">Fusiones realizadas</h4>
					<div class="panel panel-default"><div class="panel-body table-responsive">
						<table class="table table-condensed">
							<thead><tr><th>Fecha</th><th>Duplicado</th><th>Se fusionó en</th><th>Ventas movidas</th><th></th></tr></thead>
							<tbody>
							<?php foreach ($fusiones as $f): ?>
								<tr>
									<td><?php echo date('d/m/Y H:i', strtotime($f['fecha'])); ?></td>
									<td>#<?php echo $f['id_duplicado'] . ' ' . $esc($f['nom_duplicado']); ?></td>
									<td>#<?php echo $f['id_principal'] . ' ' . $esc($f['nom_principal']); ?></td>
									<td><?php echo (int)$f['n_ventas']; ?></td>
									<td><?php echo $f['deshecha'] ? '<span class="label label-default">Deshecha</span>' : '<button type="button" class="btn btn-default btn-xs btn-deshacer" data-id="' . $f['id_fusion'] . '">Deshacer</button>'; ?></td>
								</tr>
							<?php endforeach; ?>
							<?php if (!$fusiones): ?><tr><td colspan="5" class="text-muted">Aún no hay fusiones.</td></tr><?php endif; ?>
							</tbody>
						</table>
					</div></div>
				</div>
			</div>
		</div>

	<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.3.1/jquery.min.js"></script>
	<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js"></script>
	<script src="https://cdnjs.cloudflare.com/ajax/libs/limonte-sweetalert2/10.5.0/sweetalert2.min.js" integrity="sha512-V9JHp52ZkrbVVjJqNz/XXYMUOyUfzaGKEGrcD2Ual7n39+UR1yJK0numAHZqkhhGTAH/Klj0KUe4btAZXccw9w==" crossorigin="anonymous"></script>
	<script>
	$(function() {
		$('#buscar').on('input', function() {
			var q = $.trim($(this).val()).toLowerCase();
			$('.grupo').each(function() { $(this).toggle(!q || $(this).data('nombres').indexOf(q) !== -1); });
		});

		// El principal no puede estar marcado para fusionar.
		$('.grupo').on('change', 'input[type=radio]', function() {
			var $g = $(this).closest('.grupo');
			$g.find('.chk').prop('disabled', false);
			$g.find('.chk[value="' + this.value + '"]').prop({ checked: false, disabled: true });
		});
		$('.grupo').each(function() { $(this).find('input[type=radio]:checked').trigger('change'); });

		$('.btn-fusionar').on('click', function() {
			var $g = $(this).closest('.grupo');
			var principal = $g.find('input[type=radio]:checked').val();
			var dups = $g.find('.chk:checked').map(function() { return this.value; }).get();
			if (!dups.length) { Swal.fire('Atención', 'Marca al menos un cliente para fusionar.', 'info'); return; }

			var docs = {};
			$g.find('.chk:checked, .chk:disabled').each(function() { if ($(this).data('doc')) docs[$(this).data('doc')] = 1; });
			var aviso = Object.keys(docs).length > 1 ? '<br><b style="color:#c0392b">Ojo: tienen documentos distintos, podrían ser personas diferentes.</b>' : '';

			Swal.fire({
				title: 'Fusionar ' + dups.length + ' cliente(s)?',
				html: 'Sus ventas pasarán al cliente principal. Podrás deshacerlo después.' + aviso,
				icon: 'question', showCancelButton: true, confirmButtonText: 'Sí, fusionar', cancelButtonText: 'Cancelar'
			}).then(function(res) {
				if (!res.isConfirmed) return;
				$.post('inc/fusionar_clientes.php', { principal: principal, duplicados: dups }, function(d) {
					if (d.ok) { document.location.reload(); }
					else { Swal.fire('Advertencia', d.mensaje || 'No se pudo fusionar', 'warning'); }
				}, 'json').fail(function() { Swal.fire('Advertencia', 'Error general del sistema', 'warning'); });
			});
		});

		$('.btn-deshacer').on('click', function() {
			var id = $(this).data('id');
			Swal.fire({ title: 'Deshacer esta fusión?', text: 'Las ventas vuelven al cliente duplicado y este se reactiva.', icon: 'warning', showCancelButton: true, confirmButtonText: 'Sí, deshacer' })
			.then(function(res) {
				if (!res.isConfirmed) return;
				$.post('inc/deshacer_fusion.php', { id: id }, function(d) {
					if (d.ok) { document.location.reload(); }
					else { Swal.fire('Advertencia', d.mensaje || 'No se pudo deshacer', 'warning'); }
				}, 'json');
			});
		});
	});
	</script>
</body>
</html>
