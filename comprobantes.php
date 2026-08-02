<?php
include('inc/control.php');

$mes_actual  = (int)date('n');
$anio_actual = (int)date('Y');
$meses = [
    1=>'Enero', 2=>'Febrero', 3=>'Marzo', 4=>'Abril', 5=>'Mayo', 6=>'Junio',
    7=>'Julio', 8=>'Agosto', 9=>'Septiembre', 10=>'Octubre', 11=>'Noviembre', 12=>'Diciembre'
];
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
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/buttons/1.6.4/css/buttons.dataTables.min.css">
    <style>
        :root { --c-navy: #1e3a4c; --c-orange: #ff5023; }
        .resumen-row { display:grid; grid-template-columns: repeat(3, 1fr); gap:14px; margin-bottom:20px; }
        .resumen-card { background:#fff; border-radius:12px; padding:16px 18px; box-shadow:0 1px 3px rgba(0,0,0,.08); }
        .resumen-card .rc-label { font-size:12px; color:#888; font-weight:600; text-transform:uppercase; display:flex; align-items:center; gap:6px; }
        .resumen-card .rc-valor { font-size:24px; font-weight:800; color:var(--c-navy); margin-top:6px; }
        .resumen-card.total { background:var(--c-navy); }
        .resumen-card.total .rc-label { color:#bcd; }
        .resumen-card.total .rc-valor { color:#fff; }
        @media (max-width: 700px) { .resumen-row { grid-template-columns: 1fr; } }
        .estado-badge{ padding:2px 8px; border-radius:10px; font-size:11px; font-weight:700; text-transform:uppercase; }
        .estado-0{ background:#e6f4ea; color:#1e7e34; }
        .estado-1{ background:#fff3cd; color:#8a6100; }
        .estado-2{ background:#f8d7da; color:#a71d2a; }
    </style>
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
	        <?php menu('4'); ?>
	      </div>
	      <div class="submenu">
	      	<ul class="subtop-tabs">
	      		<li >
	      			<a href="venta.php">Registrar venta</a>
	      		</li>
	      		<li >
	      			<a href="ventas.php">Listar ventas</a>
	      		</li>
	      		<li>
	      			<a href="notas_venta.php">Facturar</a>
	      		</li>
	      		<li class="active">
	      			<a href="comprobantes.php">Comprobantes</a>
	      		</li>
	      	</ul>
	      </div>
	    </nav>
		<div class="kbg">
			<div class="cuerpofull">
				<div class="titulo">
					<h3>Comprobantes</h3>
				</div>
				<div class="container-fluid">
					<div class="row">
						<div class="col-md-12">

							<div class="panel panel-default">
								<div class="panel-body" style="display:flex;align-items:center;gap:10px;flex-wrap:wrap">
									<strong>Período:</strong>
									<select class="form-control" id="f-mes" style="width:150px">
										<?php foreach ($meses as $n => $nombre): ?>
										<option value="<?= $n ?>" <?= $n == $mes_actual ? 'selected' : '' ?>><?= $nombre ?></option>
										<?php endforeach; ?>
									</select>
									<select class="form-control" id="f-anio" style="width:110px">
										<?php for ($a = $anio_actual; $a >= $anio_actual - 4; $a--): ?>
										<option value="<?= $a ?>" <?= $a == $anio_actual ? 'selected' : '' ?>><?= $a ?></option>
										<?php endfor; ?>
									</select>
									<button type="button" class="btn btn-default" id="btn-mes-actual">Mes actual</button>
									<button type="button" class="btn btn-default" id="btn-mes-anterior">Mes anterior</button>

									<span style="flex:1"></span>
									<button type="button" class="btn btn-default" id="btn-zip-periodo"><i class="fas fa-file-archive"></i> Descargar ZIP</button>
								</div>
							</div>

							<div class="resumen-row" id="resumen-row">
								<div class="resumen-card">
									<div class="rc-label"><i class="fab fa-bitcoin"></i> Boletas</div>
									<div class="rc-valor" id="resBoletas">S/ 0.00</div>
								</div>
								<div class="resumen-card">
									<div class="rc-label"><i class="fas fa-file-invoice-dollar"></i> Facturas</div>
									<div class="rc-valor" id="resFacturas">S/ 0.00</div>
								</div>
								<div class="resumen-card total">
									<div class="rc-label"><i class="fas fa-cash-register"></i> Total del período</div>
									<div class="rc-valor" id="resTotal">S/ 0.00</div>
								</div>
							</div>

							<div class="panel panel-default pa">
								<div class="panel-body">
								    <table id="datos" class="table table-hover" style="width:100%">
								    	<thead>
								    		<tr>
								    			<th>Fecha</th>
								    			<th>Tipo</th>
								    			<th>Serie-Número</th>
								    			<th>Cliente</th>
								    			<th>Total</th>
								    			<th>Estado</th>
								    			<th>Acciones</th>
								    		</tr>
								    	</thead>
								    	<tbody></tbody>
								    </table>
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
	<script src="//cdn.datatables.net/1.10.22/js/jquery.dataTables.min.js"></script>
	<script src="https://cdn.datatables.net/buttons/1.6.1/js/dataTables.buttons.min.js"></script>
	<script src="https://cdn.datatables.net/buttons/1.6.1/js/buttons.flash.min.js"></script>
	<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.1.3/jszip.min.js"></script>
	<script src="https://cdn.datatables.net/buttons/1.6.1/js/buttons.html5.min.js"></script>
	<script>
	(function () {
		function money(n) { return 'S/ ' + (parseFloat(n) || 0).toFixed(2); }

		function formatFecha(f) {
			if (!f) return '-';
			var p = f.substring(0, 10).split('-');
			return p.length === 3 ? (p[2] + '/' + p[1] + '/' + p[0]) : f;
		}

		function renderResumen(r) {
			r = r || {};
			document.getElementById('resBoletas').textContent = money(r.boletas);
			document.getElementById('resFacturas').textContent = money(r.facturas);
			document.getElementById('resTotal').textContent = money(r.total);
		}

		var estadoClase = { '0': 'estado-0', '1': 'estado-1', '2': 'estado-2' };

		var tabla = $('#datos').DataTable({
			ajax: {
				url: 'inc/get_comprobantes_periodo.php',
				data: function (d) {
					d.mes  = $('#f-mes').val();
					d.anio = $('#f-anio').val();
				},
				dataSrc: function (json) {
					renderResumen(json.resumen);
					return json.ok ? json.data : [];
				}
			},
			columns: [
				{ data: 'fecha', render: function (d) { return formatFecha(d); } },
				{ data: 'tipo_label' },
				{ data: null, render: function (row) { return row.serie + '-' + row.numero; } },
				{ data: 'cliente' },
				{ data: 'total', render: function (d) { return money(d); } },
				{ data: null, orderable: false, render: function (row) {
					var c = estadoClase[row.state] || '';
					return '<span class="estado-badge ' + c + '">' + row.estado_label + '</span>';
				} },
				{ data: null, orderable: false, render: function (row) {
					var acciones = '';
					if (row.url_pdf) {
						acciones += '<a href="' + row.url_pdf + '" target="_blank" class="btn btn-primary btn-xs" title="Ver PDF"><i class="fas fa-eye"></i> Ver</a> ';
					}
					if (row.state === '0') {
						acciones += '<a href="anular_comprobante.php?id=' + row.id_comprobante + '" class="btn btn-danger btn-xs" title="Anular / comunicar baja"><i class="fas fa-ban"></i> Anular</a> ';
					}
					if ((row.tipo === 'B' || row.tipo === 'F') && row.state !== '2') {
						var url = row.tipo === 'F' ? 'generar_nota_credito_f.php' : 'generar_nota_credito_b.php';
						acciones += '<a href="' + url + '?id=' + row.id_comprobante + '" class="btn btn-warning btn-xs" title="Generar nota de crédito"><i class="fas fa-file-invoice"></i> N. Créd.</a>';
					}
					return acciones;
				} }
			],
			order: [[0, 'desc']],
			dom: 'Bfrtip',
			buttons: [
				{
					extend: 'excelHtml5',
					text: '<i class="fas fa-file-excel"></i> Exportar Excel',
					title: function () { return 'Comprobantes_' + $('#f-anio').val() + '-' + $('#f-mes').val(); },
					exportOptions: { columns: [0, 1, 2, 3, 4, 5] }
				}
			],
			language: {
				info: "Mostrando registros del _START_ al _END_ de un total de _TOTAL_ registros",
				infoEmpty: "Sin registros",
				infoFiltered: "(filtrado de _MAX_ registros)",
				loadingRecords: "Cargando...",
				lengthMenu: "Mostrar _MENU_ registros",
				processing: "Procesando...",
				search: "Buscar:",
				searchPlaceholder: "Término de búsqueda",
				zeroRecords: "No hay comprobantes en este período",
				emptyTable: "No hay comprobantes en este período",
				paginate: { first: "Primero", last: "Último", next: "Siguiente", previous: "Anterior" }
			}
		});

		$('#f-mes, #f-anio').on('change', function () {
			tabla.ajax.reload();
		});

		$('#btn-mes-actual').on('click', function () {
			$('#f-mes').val(<?= $mes_actual ?>);
			$('#f-anio').val(<?= $anio_actual ?>);
			tabla.ajax.reload();
		});

		$('#btn-mes-anterior').on('click', function () {
			var mes = parseInt($('#f-mes').val(), 10) - 1;
			var anio = parseInt($('#f-anio').val(), 10);
			if (mes < 1) { mes = 12; anio -= 1; }
			$('#f-mes').val(mes);
			if ($('#f-anio').val() != anio) {
				// Si el año no está en el select (fuera de los últimos 5 años), se agrega.
				if ($('#f-anio option[value="' + anio + '"]').length === 0) {
					$('#f-anio').prepend('<option value="' + anio + '">' + anio + '</option>');
				}
				$('#f-anio').val(anio);
			}
			tabla.ajax.reload();
		});

		$('#btn-zip-periodo').on('click', function () {
			var url = 'inc/zip_comprobantes_periodo.php?mes=' + $('#f-mes').val() + '&anio=' + $('#f-anio').val();
			window.open(url, '_blank');
		});
	})();
	</script>
</body>
</html>
