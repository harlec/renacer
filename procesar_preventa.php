<?php
include('inc/control.php');

$id_preventa = (int)($_GET['id'] ?? 0);

$conn = new mysqli('localhost', 'admin_renacer', 'ikm169uhn', 'admin_renacer');
$conn->set_charset('utf8');

$stmt = $conn->prepare("
    SELECT p.id_preventa, p.fecha, p.estado, c.cliente AS nombre_cliente
    FROM preventa p
    LEFT JOIN clientes c ON c.id_cliente = p.cliente
    WHERE p.id_preventa = ?
");
$stmt->bind_param('i', $id_preventa);
$stmt->execute();
$preventa = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$preventa) {
    $conn->close();
    header('Location: preventas.php');
    exit;
}

$stmt = $conn->prepare("
    SELECT d.id_detalle, d.producto, d.cantidad, pr.nom_prod
    FROM detalle_preventa d
    LEFT JOIN productos pr ON pr.id_producto = d.producto
    WHERE d.preventa = ?
");
$stmt->bind_param('i', $id_preventa);
$stmt->execute();
$lineas = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

foreach ($lineas as &$linea) {
    $stmt = $conn->prepare("
        SELECT vp.id_vp, v.variante, vp.cantidad_vp, vp.precio_vp
        FROM variante_p vp
        JOIN variantes v ON v.id_variante = vp.variante_vp
        WHERE vp.producto_vp = ? AND vp.state_vp = '1'
        ORDER BY vp.cantidad_vp
    ");
    $stmt->bind_param('i', $linea['producto']);
    $stmt->execute();
    $linea['variantes'] = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}
unset($linea);
$conn->close();

$ya_procesada = $preventa['estado'] !== '0';
?>
<!DOCTYPE html>
<html lang="es">
<head>
	<meta charset="UTF-8">
	<title>Sistema - Procesar preventa</title>
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" type="text/css" href="/assets/css/bootstrap.min.css">
    <link rel="stylesheet" type="text/css" href="/assets/css/custom.css">
    <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.7.2/css/all.css" integrity="sha384-fnmOCqbTlWIlj8LyTjo7mOUStjsKC4pOpQbqyi7RrhN7udi9RwhKkMHpvLbHG9Sr" crossorigin="anonymous">
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
	        <?php menu('10'); ?>
	      </div>
	    </nav>
		<div class="kbg">
			<div class="cuerpofull">
				<div class="titulo">
					<h3>Procesar preventa #<?php echo $id_preventa; ?></h3>
				</div>
				<div class="container-fluid">
					<div class="row">
						<div class="col-md-12">
							<div class="panel panel-default pa">
								<div class="panel-body">
									<p><strong>Cliente:</strong> <?php echo htmlspecialchars($preventa['nombre_cliente'] ?? '—'); ?>
									&nbsp; <strong>Fecha:</strong> <?php echo htmlspecialchars($preventa['fecha']); ?></p>

									<?php if ($ya_procesada): ?>
										<div class="alert alert-info">Esta preventa ya fue procesada.</div>
									<?php else: ?>

									<div class="table-responsive">
										<table class="table table-hover" id="tabla-lineas">
											<thead>
												<tr>
													<th>Producto</th>
													<th>Cantidad</th>
													<th>Variante</th>
													<th>Precio</th>
													<th>Total</th>
												</tr>
											</thead>
											<tbody>
												<?php foreach ($lineas as $linea): ?>
												<tr class="linea" data-id-detalle="<?php echo $linea['id_detalle']; ?>">
													<td><?php echo htmlspecialchars($linea['nom_prod'] ?? ('ID ' . $linea['producto'])); ?></td>
													<td>
														<input type="number" class="form-control input-cantidad" step="0.001" min="0.001"
															value="<?php echo htmlspecialchars($linea['cantidad']); ?>">
													</td>
													<td>
														<select class="form-control select-variante">
															<option value="">-- elegir --</option>
															<?php foreach ($linea['variantes'] as $v): ?>
															<option value="<?php echo $v['id_vp']; ?>" data-precio="<?php echo $v['precio_vp']; ?>">
																<?php echo htmlspecialchars($v['variante']); ?> (<?php echo $v['cantidad_vp']; ?>) — S/ <?php echo number_format($v['precio_vp'], 2); ?>
															</option>
															<?php endforeach; ?>
														</select>
														<?php if (empty($linea['variantes'])): ?>
															<span class="text-danger">Sin variantes activas para este producto</span>
														<?php endif; ?>
													</td>
													<td class="precio-linea">S/ 0.00</td>
													<td class="total-linea">S/ 0.00</td>
												</tr>
												<?php endforeach; ?>
											</tbody>
										</table>
									</div>

									<p class="text-right"><strong>Total: S/ <span id="total-general">0.00</span></strong></p>
									<div id="msg-error" class="alert alert-danger" style="display:none"></div>
									<center>
										<button class="btn btn-primary btn-lg" id="btn-procesar">Procesar venta</button>
									</center>

									<?php endif; ?>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>

	<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.3.1/jquery.min.js"></script>
	<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js"></script>
	<script>
	function recalcularLinea($tr) {
		const cantidad = parseFloat($tr.find('.input-cantidad').val()) || 0;
		const precio = parseFloat($tr.find('.select-variante option:selected').data('precio')) || 0;
		const total = +(cantidad * precio).toFixed(2);
		$tr.find('.precio-linea').text('S/ ' + precio.toFixed(2));
		$tr.find('.total-linea').text('S/ ' + total.toFixed(2));
		recalcularTotal();
	}

	function recalcularTotal() {
		let total = 0;
		$('.linea').each(function() {
			const cantidad = parseFloat($(this).find('.input-cantidad').val()) || 0;
			const precio = parseFloat($(this).find('.select-variante option:selected').data('precio')) || 0;
			total += cantidad * precio;
		});
		$('#total-general').text(total.toFixed(2));
	}

	$(document).ready(function() {
		$('.linea').each(function() { recalcularLinea($(this)); });
		$('#tabla-lineas').on('input change', '.input-cantidad, .select-variante', function() {
			recalcularLinea($(this).closest('.linea'));
		});

		$('#btn-procesar').on('click', function() {
			const $btn = $(this);
			const $err = $('#msg-error').hide();

			const lineas = [];
			let ok = true;
			$('.linea').each(function() {
				const id_detalle = $(this).data('id-detalle');
				const cantidad = parseFloat($(this).find('.input-cantidad').val()) || 0;
				const id_vp = $(this).find('.select-variante').val();
				if (!id_vp || cantidad <= 0) { ok = false; return; }
				lineas.push({ id_detalle, id_vp, cantidad });
			});

			if (!ok || !lineas.length) {
				$err.text('Asigna una variante y una cantidad válida a cada línea antes de procesar.').show();
				return;
			}

			$btn.prop('disabled', true).text('Procesando...');

			const body = new URLSearchParams();
			body.append('id_preventa', <?php echo $id_preventa; ?>);
			lineas.forEach(l => {
				body.append('id_detalle[]', l.id_detalle);
				body.append('id_vp[]', l.id_vp);
				body.append('cantidad[]', l.cantidad);
			});

			fetch('inc/procesar_preventa.php', { method: 'POST', body })
				.then(r => r.json())
				.then(data => {
					if (data.ok) {
						window.location.href = 'preventas.php';
					} else {
						$err.text(data.mensaje || 'No se pudo procesar la preventa').show();
						$btn.prop('disabled', false).text('Procesar venta');
					}
				})
				.catch(() => {
					$err.text('Error de conexión').show();
					$btn.prop('disabled', false).text('Procesar venta');
				});
		});
	});
	</script>
</body>
</html>
