<?php
include('inc/control.php');
include('inc/sdba/sdba.php'); // include main file

// Igual criterio que en caja_pagos.php: el saldo se calcula desde los pagos
// registrados (compra_pagos), no desde un campo "pagado" en compras.
$conn = new mysqli('localhost', 'admin_renacer', 'ikm169uhn', 'admin_renacer');
$conn->set_charset('utf8');

$datos = '';
$hoy = date('Y-m-d');
$r = $conn->query("
    SELECT c.id_compra, c.fecha, c.serie_f, c.numero_f, c.total, c.forma_pago, c.fecha_compromiso_pago,
           p.proveedor, p.doc_identidad,
           COALESCE(cp.pagado, 0) AS pagado
    FROM compras c
    LEFT JOIN proveedores p ON p.id_proveedor = c.proveedor
    LEFT JOIN (
        SELECT compra, SUM(monto) AS pagado FROM compra_pagos GROUP BY compra
    ) cp ON cp.compra = c.id_compra
    WHERE c.estado != '2'
    HAVING c.total - pagado > 0.01
    ORDER BY (c.fecha_compromiso_pago IS NULL), c.fecha_compromiso_pago ASC, c.fecha DESC
");

$forma_label = ['contado' => 'Contado', 'credito' => 'Crédito'];

if ($r) {
    while ($value = $r->fetch_assoc()) {
        $total  = round((float)$value['total'], 2);
        $pagado = round((float)$value['pagado'], 2);
        $saldo  = round($total - $pagado, 2);

        $vencida = $value['fecha_compromiso_pago'] && $value['fecha_compromiso_pago'] < $hoy;
        $fecha_compromiso = $value['fecha_compromiso_pago']
            ? '<span style="' . ($vencida ? 'color:#c0392b;font-weight:700' : '') . '">' . date('d/m/Y', strtotime($value['fecha_compromiso_pago'])) . ($vencida ? ' (vencida)' : '') . '</span>'
            : '-';

        $datos .= '<tr>
            <th scope="row">' . $value['id_compra'] . '</th>
            <td>' . date('d/m/Y', strtotime($value['fecha'])) . '</td>
            <td>' . htmlspecialchars($value['proveedor'] ?: 'Sin proveedor') . '</td>
            <td>' . htmlspecialchars($value['serie_f'] . '-' . $value['numero_f']) . '</td>
            <td>' . ($forma_label[$value['forma_pago']] ?? $value['forma_pago']) . '</td>
            <td>' . $fecha_compromiso . '</td>
            <td>' . number_format($total, 2) . '</td>
            <td>' . number_format($pagado, 2) . '</td>
            <td><strong>' . number_format($saldo, 2) . '</strong></td>
            <td>
                <a title="Ver compra" href="ver_compra.php?id=' . $value['id_compra'] . '"><i class="fas fa-eye"></i> Ver</a>
                <button class="btn-custom btn-registrar-pago" data-id="' . $value['id_compra'] . '" data-saldo="' . $saldo . '" data-proveedor="' . htmlspecialchars($value['proveedor'] ?: 'Sin proveedor') . '">
                    <i class="fas fa-hand-holding-usd"></i> Registrar pago
                </button>
            </td>
        </tr>';
    }
}
$conn->close();
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
					<h3>Cuentas x pagar</h3>
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
											    			<th>#</th>
											    			<th>Fecha</th>
											    			<th>Proveedor</th>
											    			<th>Documento</th>
											    			<th>Forma de pago</th>
											    			<th>Compromiso</th>
											    			<th>Total</th>
											    			<th>Pagado</th>
											    			<th>Saldo</th>
											    			<th>Opciones</th>
											    		</tr>
											    	</thead>
											    	<tbody>
											    		<?php echo $datos; ?>
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
	<script src="//cdn.datatables.net/1.10.22/js/jquery.dataTables.min.js"></script>
	<script src="https://cdnjs.cloudflare.com/ajax/libs/limonte-sweetalert2/10.5.0/sweetalert2.min.js" integrity="sha512-V9JHp52ZkrbVVjJqNz/XXYMUOyUfzaGKEGrcD2Ual7n39+UR1yJK0numAHZqkhhGTAH/Klj0KUe4btAZXccw9w==" crossorigin="anonymous"></script>
	<script >
	$(document ).ready(function() {
		$.extend( true, $.fn.dataTable.defaults, {
		    "language": {
		        "decimal": ",",
		        "thousands": ".",
		        "info": "Mostrando registros del _START_ al _END_ de un total de _TOTAL_ registros",
		        "infoEmpty": "Mostrando registros del 0 al 0 de un total de 0 registros",
		        "infoPostFix": "",
		        "infoFiltered": "(filtrado de un total de _MAX_ registros)",
		        "loadingRecords": "Cargando...",
		        "lengthMenu": "Mostrar _MENU_ registros",
		        "paginate": {
		            "first": "Primero",
		            "last": "Último",
		            "next": "Siguiente",
		            "previous": "Anterior"
		        },
		        "processing": "Procesando...",
		        "search": "Buscar:",
		        "searchPlaceholder": "Término de búsqueda",
		        "zeroRecords": "No se encontraron resultados",
		        "emptyTable": "Ningún dato disponible en esta tabla",
		        "aria": {
		            "sortAscending":  ": Activar para ordenar la columna de manera ascendente",
		            "sortDescending": ": Activar para ordenar la columna de manera descendente"
		        }
		    }
		} );
		$('#datos').DataTable({ order: [] });

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
				$.ajax({
					type: 'POST',
					dataType: 'json',
					url: 'inc/registrar_pago_compra.php',
					data: { id_compra: id, monto: result.value.monto, metodo: result.value.metodo },
					success: function (data) {
						if (data.ok) {
							Swal.fire('Listo', 'Pago registrado', 'success').then(function () {
								document.location.href = 'cuentas_x_pagar.php';
							});
						} else {
							Swal.fire('Advertencia', data.mensaje || 'No se pudo registrar el pago', 'warning');
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
