<?php
include('inc/control.php');
include_once('inc/medios_pago.php');
if ($_SESSION['type'] != 'admin') {
	header("Location: dashboard.php");
	exit;
}

$conn = new mysqli('localhost', 'admin_renacer', 'ikm169uhn', 'admin_renacer');
$conn->set_charset('utf8');

$mes = preg_match('/^\d{4}-\d{2}$/', $_GET['mes'] ?? '') ? $_GET['mes'] : date('Y-m');
$ini = $mes . '-01';
$fin = date('Y-m-t', strtotime($ini));
$tipo_f = in_array($_GET['tipo'] ?? '', ['fijo', 'diario', 'puntual'], true) ? $_GET['tipo'] : '';

$where = "estado = '1' AND fecha BETWEEN '$ini' AND '$fin'";
if ($tipo_f !== '') $where .= " AND tipo = '$tipo_f'";

$tipo_label = ['fijo' => 'Fijo', 'diario' => 'Diario', 'puntual' => 'Puntual'];
$tipo_class = ['fijo' => 'primary', 'diario' => 'info', 'puntual' => 'warning'];
$totales = ['fijo' => 0, 'diario' => 0, 'puntual' => 0];

$datos = '';
$r = $conn->query("SELECT * FROM gastos WHERE $where ORDER BY fecha DESC, id_gasto DESC");
$total = 0;
if ($r) {
	while ($g = $r->fetch_assoc()) {
		$m = round((float)$g['monto'], 2);
		$total += $m;
		$totales[$g['tipo']] += $m;
		$datos .= '<tr>
			<td>' . date('d/m/Y', strtotime($g['fecha'])) . '</td>
			<td><span class="label label-' . $tipo_class[$g['tipo']] . '">' . $tipo_label[$g['tipo']] . '</span></td>
			<td>' . htmlspecialchars($g['categoria']) . '</td>
			<td>' . htmlspecialchars($g['descripcion'] ?? '') . '</td>
			<td>' . htmlspecialchars(medio_label($g['metodo'])) . '</td>
			<td class="text-right"><strong>S/ ' . number_format($m, 2) . '</strong></td>
			<td><button type="button" class="btn btn-danger btn-sm btn-anular" data-id="' . $g['id_gasto'] . '"><i class="fas fa-trash"></i></button></td>
		</tr>';
	}
}

// Categorías ya usadas, para sugerirlas al registrar (luz, alquiler, pasajes...)
$cats = [];
$rc = $conn->query("SELECT DISTINCT categoria FROM gastos WHERE estado = '1' ORDER BY categoria");
if ($rc) { while ($c = $rc->fetch_assoc()) $cats[] = $c['categoria']; }
$conn->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
	<meta charset="UTF-8">
	<title>Sistema - Gastos</title>
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" type="text/css" href="/assets/css/bootstrap.min.css">
    <link rel="stylesheet" type="text/css" href="/assets/css/custom.css">
    <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.7.2/css/all.css" integrity="sha384-fnmOCqbTlWIlj8LyTjo7mOUStjsKC4pOpQbqyi7RrhN7udi9RwhKkMHpvLbHG9Sr" crossorigin="anonymous">
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
	        <?php menu('11'); ?>
	      </div>
	      <div class="submenu">
	      	<ul class="subtop-tabs">
	      		<li class="active"><a href="gastos.php">Gastos</a></li>
	      		<li><a href="balance.php">Balance</a></li>
	      	</ul>
	      </div>
	    </nav>
		<div class="kbg">
			<div class="cuerpofull">
				<div class="titulo">
					<h3>Gastos
						<button type="button" id="nuevo_gasto" class="btn btn-success btn-sm pull-right">Registrar gasto</button>
					</h3>
				</div>
				<div class="container-fluid">
					<div class="row">
						<div class="col-md-12">
							<form class="form-inline" method="get" style="margin-bottom:15px">
								<input type="month" name="mes" class="form-control" value="<?php echo $mes; ?>">
								<select name="tipo" class="form-control">
									<option value="">Todos los tipos</option>
									<?php foreach ($tipo_label as $k => $v) echo '<option value="' . $k . '"' . ($tipo_f === $k ? ' selected' : '') . '>' . $v . '</option>'; ?>
								</select>
								<button class="btn btn-custom">Filtrar</button>
							</form>
							<p>
								<strong>Total del mes: S/ <?php echo number_format($total, 2); ?></strong> &nbsp;|&nbsp;
								Fijos: S/ <?php echo number_format($totales['fijo'], 2); ?> &nbsp;|&nbsp;
								Diarios: S/ <?php echo number_format($totales['diario'], 2); ?> &nbsp;|&nbsp;
								Puntuales: S/ <?php echo number_format($totales['puntual'], 2); ?>
							</p>
							<div class="panel panel-default pa">
								<div class="panel-body table-responsive">
									<table id="datos" class="table table-hover">
										<thead>
											<tr><th>Fecha</th><th>Tipo</th><th>Categoría</th><th>Descripción</th><th>Método</th><th class="text-right">Monto</th><th></th></tr>
										</thead>
										<tbody><?php echo $datos; ?></tbody>
									</table>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>

	<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.3.1/jquery.min.js"></script>
	<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js"></script>
	<script src="//cdn.datatables.net/1.10.22/js/jquery.dataTables.min.js"></script>
	<script src="https://cdnjs.cloudflare.com/ajax/libs/limonte-sweetalert2/10.5.0/sweetalert2.min.js" integrity="sha512-V9JHp52ZkrbVVjJqNz/XXYMUOyUfzaGKEGrcD2Ual7n39+UR1yJK0numAHZqkhhGTAH/Klj0KUe4btAZXccw9w==" crossorigin="anonymous"></script>
	<script>
	var CATEGORIAS = <?php echo json_encode($cats); ?>;
	$(document).ready(function() {
		$('#datos').DataTable({ order: [] });

		$('#nuevo_gasto').on('click', function() {
			var hoy = new Date();
			var f = hoy.getFullYear() + '-' + ('0' + (hoy.getMonth() + 1)).slice(-2) + '-' + ('0' + hoy.getDate()).slice(-2);
			var opts = CATEGORIAS.map(function(c) { return '<option value="' + c.replace(/"/g, '&quot;') + '">'; }).join('');
			Swal.fire({
				title: 'Registrar gasto',
				html:
					'<div style="text-align:left">' +
					'<label style="font-size:12px">Fecha</label><input id="g-fecha" type="date" class="swal2-input" value="' + f + '">' +
					'<label style="font-size:12px">Tipo</label><select id="g-tipo" class="swal2-input"><option value="puntual">Puntual</option><option value="diario">Diario</option><option value="fijo">Fijo (luz, alquiler...)</option></select>' +
					'<label style="font-size:12px">Categoría</label><input id="g-cat" list="g-cats" class="swal2-input" placeholder="Luz, Alquiler, Pasajes..."><datalist id="g-cats">' + opts + '</datalist>' +
					'<label style="font-size:12px">Descripción (opcional)</label><input id="g-desc" class="swal2-input">' +
					'<label style="font-size:12px">Monto S/</label><input id="g-monto" type="number" step="0.01" min="0" class="swal2-input">' +
					'<label style="font-size:12px">Método de pago</label><select id="g-metodo" class="swal2-input"><?php echo medios_options(); ?></select>' +
					'</div>',
				showCancelButton: true,
				confirmButtonText: 'Guardar',
				cancelButtonText: 'Cancelar',
				preConfirm: function() {
					var d = {
						fecha: $('#g-fecha').val(), tipo: $('#g-tipo').val(), categoria: $.trim($('#g-cat').val()),
						descripcion: $.trim($('#g-desc').val()), monto: $('#g-monto').val(), metodo: $('#g-metodo').val()
					};
					if (!d.fecha || !d.categoria || !(parseFloat(d.monto) > 0)) {
						Swal.showValidationMessage('Completa fecha, categoría y un monto mayor a 0');
						return false;
					}
					return d;
				}
			}).then(function(result) {
				if (!result.isConfirmed) return;
				$.post('inc/registrar_gasto.php', result.value, function(data) {
					if (data.ok) { document.location.reload(); }
					else { Swal.fire('Advertencia', data.mensaje || 'No se pudo guardar', 'warning'); }
				}, 'json').fail(function() { Swal.fire('Advertencia', 'Error general del sistema', 'warning'); });
			});
		});

		$('body').on('click', '.btn-anular', function() {
			var id = $(this).data('id');
			Swal.fire({ title: 'Anular este gasto?', text: 'Dejará de contar en el balance.', icon: 'warning', showCancelButton: true, confirmButtonText: 'Sí, anular' })
			.then(function(result) {
				if (!result.isConfirmed) return;
				$.getJSON('inc/borrar_gasto.php', { id: id }, function(data) {
					if (data.ok) { document.location.reload(); }
					else { Swal.fire('Advertencia', data.mensaje || 'No se pudo anular', 'warning'); }
				});
			});
		});
	});
	</script>
</body>
</html>
