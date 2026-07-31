<?php
include('inc/control.php');
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
        #barra-nv{
            display:none; position:fixed; bottom:0; left:0; right:0; background:#1e3a4c; color:#fff;
            padding:14px 24px; align-items:center; justify-content:space-between; z-index:90;
            box-shadow:0 -2px 10px rgba(0,0,0,.15);
        }
        #barra-nv.abierta{ display:flex; }
        #barra-nv .btn{ margin-left:10px; }
        .nv-row{ cursor:pointer; }
        .nv-row.seleccionada{ background:#fff3ee; }
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
	      		<li class="active">
	      			<a href="notas_venta.php">Facturar</a>
	      		</li>
	      	</ul>
	      </div>
	    </nav>
		<div class="kbg">
			<div class="cuerpofull">
				<div class="titulo">
					<h3>Facturar</h3>
					<p style="font-size:13px;color:#888;margin-top:-6px">
						Selecciona una o varias notas de venta pendientes (sin facturar) para emitirlas juntas en un solo comprobante.
					</p>
				</div>
				<div class="container-fluid">
					<div class="row">
						<div class="col-md-12">
							<div class="resumen-row" id="resumen-row">
								<div class="resumen-card">
									<div class="rc-label"><i class="fab fa-bitcoin"></i> Boletas hoy</div>
									<div class="rc-valor" id="resBoletas">S/ 0.00</div>
								</div>
								<div class="resumen-card">
									<div class="rc-label"><i class="fas fa-file-invoice-dollar"></i> Facturas hoy</div>
									<div class="rc-valor" id="resFacturas">S/ 0.00</div>
								</div>
								<div class="resumen-card total">
									<div class="rc-label"><i class="fas fa-cash-register"></i> Total facturado hoy</div>
									<div class="rc-valor" id="resTotal">S/ 0.00</div>
								</div>
							</div>
							<div class="panel panel-default">
								<div class="panel-body" style="display:flex;align-items:center;gap:10px;flex-wrap:wrap">
									<strong><i class="fas fa-magic"></i> Agrupar automático:</strong>
									<span>monto entre</span>
									<div class="input-group" style="width:140px">
										<span class="input-group-addon">S/</span>
										<input type="number" class="form-control" id="ag-min" value="500" step="0.01" min="0">
									</div>
									<span>y</span>
									<div class="input-group" style="width:140px">
										<span class="input-group-addon">S/</span>
										<input type="number" class="form-control" id="ag-max" value="600" step="0.01" min="0">
									</div>
									<button type="button" class="btn btn-primary" id="btn-agrupar">Buscar combinación</button>
								</div>
							</div>
							<div class="kdashboard">
								<div class="row">
									<div class="col-md-12">
										<div class="panel panel-default pa">
											<div class="panel-body">
											    <table class="table table-hover">
											    	<thead>
											    		<tr>
											    			<th style="width:36px"><input type="checkbox" id="chk-todos"></th>
											    			<th>Venta</th>
											    			<th>Cliente</th>
											    			<th>Fecha</th>
											    			<th>Monto</th>
											    		</tr>
											    	</thead>
											    	<tbody id="tbody-nv">
											    		<tr><td colspan="5" style="text-align:center;color:#888">Cargando...</td></tr>
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
	</div>

	<div id="barra-nv">
		<span id="barra-nv-texto">0 notas de venta seleccionadas</span>
		<div>
			<button type="button" class="btn btn-default" id="btn-cancelar-nv">Cancelar</button>
			<button type="button" class="btn btn-danger" id="btn-boleta-nv" disabled><i class="fab fa-bitcoin"></i> Generar boleta</button>
			<button type="button" class="btn btn-success" id="btn-factura-nv" disabled><i class="fas fa-file-invoice-dollar"></i> Generar factura</button>
		</div>
	</div>

	<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.3.1/jquery.min.js"></script>
	<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js"></script>
	<script>
	(function () {
		let items = [];
		let seleccionados = new Set();

		function money(n) { return 'S/ ' + n.toFixed(2); }

		function renderResumen(r) {
			document.getElementById('resBoletas').textContent = money(r.boletas || 0);
			document.getElementById('resFacturas').textContent = money(r.facturas || 0);
			document.getElementById('resTotal').textContent = money(r.total || 0);
		}

		function render() {
			const tbody = document.getElementById('tbody-nv');
			if (!items.length) {
				tbody.innerHTML = '<tr><td colspan="5" style="text-align:center;color:#888">No hay notas de venta pendientes</td></tr>';
				return;
			}
			tbody.innerHTML = items.map(function (v) {
				const marcada = seleccionados.has(v.id_venta) ? ' checked' : '';
				const fila = seleccionados.has(v.id_venta) ? ' seleccionada' : '';
				return '<tr class="nv-row' + fila + '" data-venta="' + v.id_venta + '">' +
					'<td><input type="checkbox" class="chk-nv"' + marcada + '></td>' +
					'<td>v-' + v.id_venta + '</td>' +
					'<td>' + v.cliente + '</td>' +
					'<td>' + v.fecha + '</td>' +
					'<td>' + money(v.total) + '</td>' +
					'</tr>';
			}).join('');
		}

		function actualizarBarra() {
			const n = seleccionados.size;
			const barra = document.getElementById('barra-nv');
			const texto = document.getElementById('barra-nv-texto');
			const btnBoleta = document.getElementById('btn-boleta-nv');
			const btnFactura = document.getElementById('btn-factura-nv');
			barra.classList.toggle('abierta', n > 0);
			let total = 0;
			items.forEach(function (v) { if (seleccionados.has(v.id_venta)) total += v.total; });
			texto.textContent = n + (n === 1 ? ' nota de venta seleccionada' : ' notas de venta seleccionadas') + ' · ' + money(total);
			btnBoleta.disabled = n === 0;
			btnFactura.disabled = n === 0;
		}

		function toggleSeleccion(id) {
			if (seleccionados.has(id)) seleccionados.delete(id);
			else seleccionados.add(id);
			render();
			actualizarBarra();
		}

		function cargar() {
			fetch('inc/get_notas_venta_pendientes.php')
				.then(function (r) { return r.json(); })
				.then(function (res) {
					if (!res.ok) return;
					items = res.data;
					// Quitar de la selección lo que ya no está pendiente (lo facturó/anuló alguien más)
					const idsPendientes = new Set(items.map(function (v) { return v.id_venta; }));
					seleccionados.forEach(function (id) { if (!idsPendientes.has(id)) seleccionados.delete(id); });
					render();
					actualizarBarra();
					if (res.resumen) renderResumen(res.resumen);
				})
				.catch(function () {});
		}
		cargar();

		document.getElementById('tbody-nv').addEventListener('click', function (e) {
			const fila = e.target.closest('.nv-row');
			if (!fila) return;
			toggleSeleccion(parseInt(fila.dataset.venta));
		});

		document.getElementById('chk-todos').addEventListener('change', function (e) {
			if (e.target.checked) {
				items.forEach(function (v) { seleccionados.add(v.id_venta); });
			} else {
				seleccionados.clear();
			}
			render();
			actualizarBarra();
		});

		document.getElementById('btn-cancelar-nv').addEventListener('click', function () {
			seleccionados.clear();
			document.getElementById('chk-todos').checked = false;
			render();
			actualizarBarra();
		});

		// ── Agrupar automático por rango de monto ────────────
		// Buscar una combinación que sume dentro de un rango es "subset sum" (NP-completo);
		// con listas de este tamaño alcanza con una heurística: probar muchos órdenes al azar,
		// sumando de forma "greedy" (agrega mientras no pase un tope) hasta juntar varias
		// combinaciones válidas, y elegir una al azar entre ellas para no repetir siempre el
		// mismo resultado pegado al máximo del rango.
		function shuffle(arr) {
			for (let i = arr.length - 1; i > 0; i--) {
				const j = Math.floor(Math.random() * (i + 1));
				[arr[i], arr[j]] = [arr[j], arr[i]];
			}
			return arr;
		}

		function greedy(lista, max) {
			let elegidos = [], suma = 0;
			for (const v of lista) {
				if (suma + v.total <= max + 0.001) {
					elegidos.push(v);
					suma = Math.round((suma + v.total) * 100) / 100;
				}
			}
			return { elegidos, suma };
		}

		function mejorCombinacion(min, max) {
			// El greedy siempre rellena hasta el tope que se le da, así que si el tope es
			// siempre "max" el resultado siempre queda pegado al máximo del rango (ej. 599.90).
			// Para dispersar el resultado dentro del rango, se prueba con un tope ALEATORIO
			// entre min y max en cada intento, se juntan TODAS las combinaciones válidas
			// encontradas, y al final se elige una al azar entre ellas.
			const validas = [];
			let mejorFueraDeRango = null;

			function evaluar(r) {
				if (!mejorFueraDeRango || r.suma > mejorFueraDeRango.suma) mejorFueraDeRango = r;
				if (r.suma >= min && r.suma <= max) validas.push(r);
			}

			// Un par de intentos "clásicos" pegados al máximo, por si el rango es tan
			// angosto que solo empaquetando al tope se logra caer dentro de él.
			evaluar(greedy(items.slice().sort(function (a, b) { return b.total - a.total; }), max));
			evaluar(greedy(items.slice().sort(function (a, b) { return a.total - b.total; }), max));

			for (let i = 0; i < 80; i++) {
				const topeAleatorio = min + Math.random() * (max - min);
				evaluar(greedy(shuffle(items.slice()), topeAleatorio));
			}

			if (validas.length) {
				return validas[Math.floor(Math.random() * validas.length)];
			}
			return mejorFueraDeRango;
		}

		document.getElementById('btn-agrupar').addEventListener('click', function () {
			const min = parseFloat(document.getElementById('ag-min').value) || 0;
			const max = parseFloat(document.getElementById('ag-max').value) || 0;
			if (max <= 0 || min > max) { alert('Rango inválido.'); return; }
			if (!items.length) { alert('No hay notas de venta pendientes.'); return; }

			const totalDisponible = items.reduce(function (s, v) { return s + v.total; }, 0);
			if (totalDisponible < min) {
				alert('El total de notas de venta pendientes (' + money(totalDisponible) + ') es menor al mínimo pedido.');
				return;
			}

			const r = mejorCombinacion(min, max);
			if (!r || !r.elegidos.length) {
				alert('No se encontró ninguna combinación posible.');
				return;
			}

			seleccionados = new Set(r.elegidos.map(function (v) { return v.id_venta; }));
			render();
			actualizarBarra();

			if (r.suma >= min && r.suma <= max) {
				alert('Combinación encontrada: ' + money(r.suma) + ' (' + r.elegidos.length + ' ventas seleccionadas).');
			} else {
				alert('No se encontró una combinación exacta en ese rango. Se seleccionó la mejor aproximación: ' + money(r.suma) + ' (' + r.elegidos.length + ' ventas). Puedes ajustar la selección a mano o cambiar el rango.');
			}
		});

		document.getElementById('btn-boleta-nv').addEventListener('click', function () {
			if (!seleccionados.size) return;
			window.location.href = 'boleta.php?ids=' + Array.from(seleccionados).join(',');
		});

		document.getElementById('btn-factura-nv').addEventListener('click', function () {
			if (!seleccionados.size) return;
			window.location.href = 'factura.php?ids=' + Array.from(seleccionados).join(',');
		});
	})();
	</script>
</body>
</html>
