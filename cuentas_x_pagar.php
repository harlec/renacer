<?php
include('inc/control.php');
include('inc/sdba/sdba.php'); // include main file

// Igual criterio que antes: el saldo se calcula desde los pagos registrados
// (compra_pagos), no desde un campo "pagado" en compras.
$conn = new mysqli('localhost', 'admin_renacer', 'ikm169uhn', 'admin_renacer');
$conn->set_charset('utf8');

$hoy = date('Y-m-d');
$r = $conn->query("
    SELECT c.id_compra, c.fecha, c.serie_f, c.numero_f, c.total, c.forma_pago, c.fecha_compromiso_pago, c.deuda_anterior,
           c.proveedor AS id_proveedor, p.proveedor AS nombre_proveedor,
           COALESCE(cp.pagado, 0) AS pagado
    FROM compras c
    LEFT JOIN proveedores p ON p.id_proveedor = c.proveedor
    LEFT JOIN (
        SELECT compra, SUM(monto) AS pagado FROM compra_pagos GROUP BY compra
    ) cp ON cp.compra = c.id_compra
    WHERE c.estado != '2'
    HAVING c.total - pagado > 0.01
    ORDER BY c.deuda_anterior DESC, (c.fecha_compromiso_pago IS NULL), c.fecha_compromiso_pago ASC, c.fecha ASC
");

$proveedores_lista = [];
$rp = $conn->query("SELECT id_proveedor, proveedor FROM proveedores WHERE estado = '1' ORDER BY proveedor");
if ($rp) {
    while ($row = $rp->fetch_assoc()) {
        $proveedores_lista[] = ['id' => (int)$row['id_proveedor'], 'nombre' => $row['proveedor']];
    }
}

$forma_label = ['contado' => 'Contado', 'credito' => 'Crédito'];

// Agrupamos las facturas pendientes por proveedor.
$grupos = [];
if ($r) {
    while ($value = $r->fetch_assoc()) {
        $idp = (int)$value['id_proveedor'];
        if (!isset($grupos[$idp])) {
            $grupos[$idp] = [
                'nombre'   => $value['nombre_proveedor'] ?: 'Sin proveedor',
                'facturas' => [],
                'saldo'    => 0,
            ];
        }
        $total  = round((float)$value['total'], 2);
        $pagado = round((float)$value['pagado'], 2);
        $saldo  = round($total - $pagado, 2);

        $value['total']  = $total;
        $value['pagado'] = $pagado;
        $value['saldo']  = $saldo;
        $grupos[$idp]['facturas'][] = $value;
        $grupos[$idp]['saldo'] += $saldo;
    }
}
$conn->close();

// Proveedores con más deuda primero.
uasort($grupos, function ($a, $b) { return $b['saldo'] <=> $a['saldo']; });

$datos = '';
foreach ($grupos as $idp => $g) {
    $n_facturas = count($g['facturas']);
    $saldo_total = round($g['saldo'], 2);

    $datos .= '<tr class="fila-proveedor">
        <td>' . htmlspecialchars($g['nombre']) . '</td>
        <td>' . $n_facturas . '</td>
        <td><strong>S/ ' . number_format($saldo_total, 2) . '</strong></td>
        <td>
            <button class="btn btn-default btn-xs" data-toggle="collapse" data-target="#detalle-' . $idp . '">
                <i class="fas fa-list"></i> Ver facturas
            </button>
            <button class="btn-custom btn-abonar-proveedor" data-id="' . $idp . '" data-saldo="' . $saldo_total . '" data-proveedor="' . htmlspecialchars($g['nombre']) . '">
                <i class="fas fa-hand-holding-usd"></i> Abonar a la cuenta
            </button>
        </td>
    </tr>
    <tr>
        <td colspan="4" style="padding:0;border-top:none">
            <div id="detalle-' . $idp . '" class="collapse">
                <table class="table table-condensed" style="margin:6px 0 12px">
                    <thead>
                        <tr>
                            <th>#</th><th>Fecha</th><th>Documento</th><th>Forma de pago</th>
                            <th>Compromiso</th><th>Total</th><th>Pagado</th><th>Saldo</th><th>Opciones</th>
                        </tr>
                    </thead>
                    <tbody>';

    foreach ($g['facturas'] as $f) {
        $vencida = $f['fecha_compromiso_pago'] && $f['fecha_compromiso_pago'] < $hoy;
        $fecha_compromiso = $f['fecha_compromiso_pago']
            ? '<span style="' . ($vencida ? 'color:#c0392b;font-weight:700' : '') . '">' . date('d/m/Y', strtotime($f['fecha_compromiso_pago'])) . ($vencida ? ' (vencida)' : '') . '</span>'
            : '-';

        $documento = $f['deuda_anterior'] === '1'
            ? '<span class="label label-default">Deuda anterior</span>'
            : htmlspecialchars($f['serie_f'] . '-' . $f['numero_f']);

        $datos .= '<tr>
            <th scope="row">' . $f['id_compra'] . '</th>
            <td>' . date('d/m/Y', strtotime($f['fecha'])) . '</td>
            <td>' . $documento . '</td>
            <td>' . ($forma_label[$f['forma_pago']] ?? $f['forma_pago']) . '</td>
            <td>' . $fecha_compromiso . '</td>
            <td>' . number_format($f['total'], 2) . '</td>
            <td>' . number_format($f['pagado'], 2) . '</td>
            <td><strong>' . number_format($f['saldo'], 2) . '</strong></td>
            <td>
                <a title="Ver compra" href="ver_compra.php?id=' . $f['id_compra'] . '"><i class="fas fa-eye"></i> Ver</a>
                <button class="btn-custom btn-registrar-pago" data-id="' . $f['id_compra'] . '" data-saldo="' . $f['saldo'] . '" data-proveedor="' . htmlspecialchars($g['nombre']) . '">
                    <i class="fas fa-hand-holding-usd"></i> Registrar pago
                </button>
            </td>
        </tr>';
    }

    $datos .= '          </tbody>
                </table>
            </div>
        </td>
    </tr>';
}
?>


<!DOCTYPE html>
<html lang="es">
<head>
	<meta charset="UTF-8">
	<title>Sistema - Menu Principal</title>
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" type="text/css" href="/assets/css/bootstrap.min.css">
    <link rel="stylesheet" type="text/css" href="/assets/css/custom.css">
    <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.7.2/css/all.css" integrity="sha384-fnmOCqbTlWIlj8LyTjo7mOUStjsKC4pOpQbqyi7RrhN7udi9RwhKkMHpvLbHG9Sr" crossorigin="anonymous">
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
	        <?php menu('6'); ?>
	      </div>
	      <div class="submenu">
	      	<ul class="subtop-tabs">
	      		<li>
	      			<a href="compra.php">Registrar Compra</a>
	      		</li>
	      		<li>
	      			<a href="compras.php">Listar Compras</a>
	      		</li>
	      		<li>
	      			<a href="proveedores.php">Proveedores</a>
	      		</li>
	      		<li class="active">
	      			<a href="cuentas_x_pagar.php">Cuentas x pagar</a>
	      		</li>
	      	</ul>
	      </div>
	    </nav>
		<div class="kbg">
			<div class="cuerpofull">
				<div class="titulo">
					<h3>Cuentas x pagar
						<button class="btn btn-default btn-sm" id="btn-deuda-anterior" style="margin-left:10px">
							<i class="fas fa-plus"></i> Deuda anterior
						</button>
					</h3>
				</div>
				<div class="container-fluid">
					<div class="row">
						<div class="col-md-12">
							<div class="kdashboard">
								<div class="row">
									<div class="col-md-12">
										<div class="panel panel-default pa">
											<div class="panel-body table-responsive">
											    <table id="datos" class="table table-hover">
											    	<thead>
											    		<tr>
											    			<th>Proveedor</th>
											    			<th># Facturas</th>
											    			<th>Total adeudado</th>
											    			<th>Opciones</th>
											    		</tr>
											    	</thead>
											    	<tbody>
											    		<?php echo $datos; ?>
											    		<?php if (empty($grupos)): ?>
											    		<tr><td colspan="4" class="text-center">No hay cuentas por pagar pendientes</td></tr>
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
				</div>
			</div>
		</div>
	 	<!-- Tab panes -->



	<!-- jQuery (necessary for Bootstrap's JavaScript plugins) -->
	<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.3.1/jquery.min.js"></script>
	<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js"></script>
	<script src="https://cdnjs.cloudflare.com/ajax/libs/limonte-sweetalert2/10.5.0/sweetalert2.min.js" integrity="sha512-V9JHp52ZkrbVVjJqNz/XXYMUOyUfzaGKEGrcD2Ual7n39+UR1yJK0numAHZqkhhGTAH/Klj0KUe4btAZXccw9w==" crossorigin="anonymous"></script>
	<script >
	const PROVEEDORES = <?php echo json_encode($proveedores_lista, JSON_UNESCAPED_UNICODE); ?>;

	$(document ).ready(function() {

		function pedirPago(url, data, onOk) {
			$.ajax({
				type: 'POST',
				dataType: 'json',
				url: url,
				data: data,
				success: function (resp) {
					if (resp.ok) {
						Swal.fire('Listo', 'Pago registrado', 'success').then(function () {
							document.location.href = 'cuentas_x_pagar.php';
						});
					} else {
						Swal.fire('Advertencia', resp.mensaje || 'No se pudo registrar el pago', 'warning');
					}
				},
				error: function () {
					Swal.fire('Advertencia', 'Error general del sistema', 'warning');
				}
			});
		}

		$('body').on('click', '.btn-registrar-pago', function () {
			var id = $(this).data('id');
			var saldo = parseFloat($(this).data('saldo'));
			var proveedor = $(this).data('proveedor');

			Swal.fire({
				title: 'Registrar pago',
				html:
					'<div style="text-align:left">' +
					'<p><strong>Proveedor:</strong> ' + proveedor + '</p>' +
					'<p><strong>Saldo pendiente:</strong> S/ ' + saldo.toFixed(2) + '</p>' +
					'<label style="font-size:12px">Monto a pagar</label>' +
					'<input id="swal-monto" type="number" step="0.01" min="0.01" max="' + saldo + '" class="swal2-input" value="' + saldo.toFixed(2) + '">' +
					'<label style="font-size:12px">Método</label>' +
					'<select id="swal-metodo" class="swal2-input">' +
					'<option value="efectivo">Efectivo</option>' +
					'<option value="transferencia">Transferencia</option>' +
					'<option value="deposito">Depósito</option>' +
					'<option value="cheque">Cheque</option>' +
					'<option value="otro">Otro</option>' +
					'</select>' +
					'</div>',
				showCancelButton: true,
				confirmButtonText: 'Registrar',
				cancelButtonText: 'Cancelar',
				preConfirm: function () {
					var monto = parseFloat(document.getElementById('swal-monto').value);
					if (!monto || monto <= 0) {
						Swal.showValidationMessage('Ingresa un monto válido');
						return false;
					}
					return { monto: monto, metodo: document.getElementById('swal-metodo').value };
				}
			}).then(function (result) {
				if (!result.isConfirmed) return;
				pedirPago('inc/registrar_pago_compra.php', { id_compra: id, monto: result.value.monto, metodo: result.value.metodo });
			});
		});

		$('body').on('click', '.btn-abonar-proveedor', function () {
			var id = $(this).data('id');
			var saldo = parseFloat($(this).data('saldo'));
			var proveedor = $(this).data('proveedor');

			Swal.fire({
				title: 'Abonar a la cuenta',
				html:
					'<div style="text-align:left">' +
					'<p><strong>Proveedor:</strong> ' + proveedor + '</p>' +
					'<p><strong>Total adeudado:</strong> S/ ' + saldo.toFixed(2) + '</p>' +
					'<p style="font-size:12px;color:#777">El abono se aplica primero a la factura más antigua; si sobra, pasa a la siguiente.</p>' +
					'<label style="font-size:12px">Monto a abonar</label>' +
					'<input id="swal-monto" type="number" step="0.01" min="0.01" max="' + saldo + '" class="swal2-input" value="' + saldo.toFixed(2) + '">' +
					'<label style="font-size:12px">Método</label>' +
					'<select id="swal-metodo" class="swal2-input">' +
					'<option value="efectivo">Efectivo</option>' +
					'<option value="transferencia">Transferencia</option>' +
					'<option value="deposito">Depósito</option>' +
					'<option value="cheque">Cheque</option>' +
					'<option value="otro">Otro</option>' +
					'</select>' +
					'</div>',
				showCancelButton: true,
				confirmButtonText: 'Abonar',
				cancelButtonText: 'Cancelar',
				preConfirm: function () {
					var monto = parseFloat(document.getElementById('swal-monto').value);
					if (!monto || monto <= 0) {
						Swal.showValidationMessage('Ingresa un monto válido');
						return false;
					}
					return { monto: monto, metodo: document.getElementById('swal-metodo').value };
				}
			}).then(function (result) {
				if (!result.isConfirmed) return;
				pedirPago('inc/registrar_pago_proveedor.php', { proveedor: id, monto: result.value.monto, metodo: result.value.metodo });
			});
		});

		$('#btn-deuda-anterior').on('click', function () {
			var escHtml = function (s) {
				return String(s).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
			};
			var opciones = PROVEEDORES.map(function (p) {
				return '<option value="' + p.id + '">' + escHtml(p.nombre) + '</option>';
			}).join('');

			Swal.fire({
				title: 'Registrar deuda anterior',
				html:
					'<div style="text-align:left">' +
					'<p style="font-size:12px;color:#777">Para un saldo que ya se le debía al proveedor antes de usar el sistema. Se pagará siempre antes que cualquier otra factura de este proveedor.</p>' +
					'<label style="font-size:12px">Proveedor</label>' +
					'<select id="swal-proveedor" class="swal2-input">' + opciones + '</select>' +
					'<label style="font-size:12px">Monto adeudado</label>' +
					'<input id="swal-monto" type="number" step="0.01" min="0.01" class="swal2-input" placeholder="0.00">' +
					'<label style="font-size:12px">Fecha aproximada (opcional)</label>' +
					'<input id="swal-fecha" type="date" class="swal2-input">' +
					'<label style="font-size:12px">Observación (opcional)</label>' +
					'<input id="swal-obs" type="text" class="swal2-input" placeholder="Ej: saldo al migrar de sistema">' +
					'</div>',
				showCancelButton: true,
				confirmButtonText: 'Registrar',
				cancelButtonText: 'Cancelar',
				preConfirm: function () {
					var proveedor = document.getElementById('swal-proveedor').value;
					var monto = parseFloat(document.getElementById('swal-monto').value);
					if (!proveedor) {
						Swal.showValidationMessage('Selecciona un proveedor');
						return false;
					}
					if (!monto || monto <= 0) {
						Swal.showValidationMessage('Ingresa un monto válido');
						return false;
					}
					return {
						proveedor: proveedor,
						monto: monto,
						fecha: document.getElementById('swal-fecha').value,
						observacion: document.getElementById('swal-obs').value
					};
				}
			}).then(function (result) {
				if (!result.isConfirmed) return;
				$.ajax({
					type: 'POST',
					dataType: 'json',
					url: 'inc/registrar_deuda_anterior.php',
					data: result.value,
					success: function (resp) {
						if (resp.ok) {
							Swal.fire('Listo', 'Deuda registrada', 'success').then(function () {
								document.location.href = 'cuentas_x_pagar.php';
							});
						} else {
							Swal.fire('Advertencia', resp.mensaje || 'No se pudo registrar la deuda', 'warning');
						}
					},
					error: function () {
						Swal.fire('Advertencia', 'Error general del sistema', 'warning');
					}
				});
			});
		});
	});
	</script>
</body>
</html>
