<?php
include('inc/control.php');
$v = '20260913';
?>
<!DOCTYPE html>
<html lang="es">
<head>
	<meta charset="UTF-8">
	<title>Sistema - Pedido con IA</title>
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" type="text/css" href="/assets/css/bootstrap.min.css">
    <link rel="stylesheet" type="text/css" href="/assets/css/custom.css?v=<?= $v ?>">
    <link rel="stylesheet" type="text/css" href="/assets/css/select2.min.css?v=<?= $v ?>">
    <link rel="stylesheet" type="text/css" href="/assets/css/jquery-ui.min.css?v=<?= $v ?>">
    <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.7.2/css/all.css" integrity="sha384-fnmOCqbTlWIlj8LyTjo7mOUStjsKC4pOpQbqyi7RrhN7udi9RwhKkMHpvLbHG9Sr" crossorigin="anonymous">
    <link rel="stylesheet" type="text/css" href="https://cdnjs.cloudflare.com/ajax/libs/limonte-sweetalert2/7.0.5/sweetalert2.min.css">
    <style>
        .tab-pane-body{padding:18px 4px}
        .drop-zone{border:2px dashed #ccc;border-radius:8px;padding:30px;text-align:center;color:#888;cursor:pointer;background:#fafafa}
        .drop-zone:hover{border-color:#5cb85c}
        .preview-img{max-width:100%;max-height:260px;margin-top:12px;border-radius:6px}
        .fila-revision.sin-match .select-producto-container{border:1px solid #d9534f;border-radius:4px;padding:4px}
        .badge-score{font-size:11px}
        #resultado-revision{display:none}
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
	        <?php menu('10'); ?>
	      </div>
	    </nav>
		<div class="kbg">
			<div class="cuerpofull">
				<div class="titulo">
					<h3>Tomar pedido con IA <a href="preventas.php" class="btn btn-default btn-sm pull-right">Ver preventas pendientes</a></h3>
				</div>
				<div class="container-fluid">
					<div class="row">
						<div class="col-md-12">

							<!-- Paso 1: captura del pedido -->
							<div class="panel panel-default pa" id="panel-captura">
								<div class="panel-body">
									<div class="form-group">
										<label>Cliente</label>
										<input type="text" id="cliente" class="form-control" placeholder="Escribe para buscar un cliente existente o escribe uno nuevo" autocomplete="off">
									</div>

									<ul class="nav nav-tabs" id="tabs-tipo">
										<li class="active"><a href="#tab-foto" data-tipo="foto" data-toggle="tab"><i class="fas fa-camera"></i> Foto</a></li>
										<li><a href="#tab-audio" data-tipo="audio" data-toggle="tab"><i class="fas fa-microphone"></i> Audio</a></li>
										<li><a href="#tab-texto" data-tipo="texto" data-toggle="tab"><i class="fas fa-keyboard"></i> Texto</a></li>
									</ul>

									<div class="tab-content">
										<div class="tab-pane tab-pane-body active" id="tab-foto">
											<div class="drop-zone" onclick="document.getElementById('input-foto').click()">
												<i class="fas fa-camera fa-2x"></i>
												<p>Toca para subir o tomar una foto de la hoja del pedido</p>
											</div>
											<input type="file" id="input-foto" accept="image/*" capture="environment" style="display:none">
											<img id="preview-foto" class="preview-img" style="display:none">
										</div>
										<div class="tab-pane tab-pane-body" id="tab-audio">
											<div class="drop-zone" onclick="document.getElementById('input-audio').click()">
												<i class="fas fa-microphone fa-2x"></i>
												<p>Toca para subir la nota de voz del pedido</p>
											</div>
											<input type="file" id="input-audio" accept="audio/*" style="display:none">
											<audio id="preview-audio" controls style="display:none;margin-top:12px;width:100%"></audio>
										</div>
										<div class="tab-pane tab-pane-body" id="tab-texto">
											<textarea id="input-texto" class="form-control" rows="6" placeholder="Pega aquí el texto del pedido tal cual lo mandó el cliente..."></textarea>
										</div>
									</div>

									<div id="msg-error-captura" class="alert alert-danger" style="display:none"></div>

									<center>
										<button class="btn btn-primary btn-lg" id="btn-interpretar">
											<i class="fas fa-magic"></i> Interpretar pedido
										</button>
									</center>
								</div>
							</div>

							<!-- Paso 2: revisión / validación antes de crear el prepedido -->
							<div class="panel panel-default pa" id="resultado-revision">
								<div class="panel-body">
									<p id="texto-leido-info" class="text-muted"></p>

									<div class="table-responsive">
										<table class="table table-hover" id="tabla-revision">
											<thead>
												<tr>
													<th style="width:30%">Lo que se leyó/escuchó</th>
													<th style="width:40%">Producto en catálogo</th>
													<th style="width:15%">Cantidad</th>
													<th style="width:10%"></th>
												</tr>
											</thead>
											<tbody></tbody>
										</table>
									</div>

									<button class="btn btn-default btn-sm" id="btn-agregar-linea" type="button"><i class="fas fa-plus"></i> Agregar línea manual</button>

									<div id="msg-error-revision" class="alert alert-danger" style="display:none;margin-top:14px"></div>

									<center style="margin-top:16px">
										<button class="btn btn-default" id="btn-cancelar-revision" type="button">Cancelar</button>
										<button class="btn btn-success btn-lg" id="btn-crear-prepedido" type="button">
											<i class="fas fa-check"></i> Crear prepedido
										</button>
									</center>
								</div>
							</div>

						</div>
					</div>
				</div>
			</div>
		</div>
	</div>

	<div id="overlay-cargando" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:999;color:#fff;align-items:center;justify-content:center;font-size:20px;text-align:center">
		<div><i class="fas fa-spinner fa-spin fa-2x"></i><br><span id="overlay-texto">Interpretando pedido...</span></div>
	</div>

	<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.3.1/jquery.min.js"></script>
	<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js"></script>
	<script src="/assets/js/jquery-ui.min.js?v=<?= $v ?>"></script>
	<script src="/assets/js/select2.full.min.js?v=<?= $v ?>"></script>
	<script src="https://cdnjs.cloudflare.com/ajax/libs/limonte-sweetalert2/7.0.5/sweetalert2.min.js"></script>
	<script>
	let ultimoResultado = null;

	$('#cliente').autocomplete({
		source: function(request, response) {
			$.ajax({
				url: '/inc/autocomplete-cliente.php',
				data: { term: request.term },
				dataType: 'json',
				success: function(data) { response(data); }
			});
		},
		minLength: 2
	});

	$('#input-foto').on('change', function() {
		const file = this.files[0];
		if (!file) return;
		const reader = new FileReader();
		reader.onload = e => { $('#preview-foto').attr('src', e.target.result).show(); };
		reader.readAsDataURL(file);
	});

	$('#input-audio').on('change', function() {
		const file = this.files[0];
		if (!file) return;
		$('#preview-audio').attr('src', URL.createObjectURL(file)).show();
	});

	function tipoActivo() {
		return $('#tabs-tipo li.active a').data('tipo');
	}

	$('#btn-interpretar').on('click', function() {
		const $err = $('#msg-error-captura').hide();
		const cliente = $('#cliente').val().trim();
		if (!cliente) {
			$err.text('Indica el nombre del cliente antes de interpretar el pedido.').show();
			return;
		}

		const tipo = tipoActivo();
		const formData = new FormData();
		formData.append('tipo', tipo);
		formData.append('cliente', cliente);

		if (tipo === 'foto') {
			const f = $('#input-foto')[0].files[0];
			if (!f) { $err.text('Sube una foto del pedido.').show(); return; }
			formData.append('archivo', f);
		} else if (tipo === 'audio') {
			const f = $('#input-audio')[0].files[0];
			if (!f) { $err.text('Sube el audio del pedido.').show(); return; }
			formData.append('archivo', f);
		} else {
			const t = $('#input-texto').val().trim();
			if (!t) { $err.text('Escribe o pega el texto del pedido.').show(); return; }
			formData.append('texto', t);
		}

		$('#overlay-texto').text(tipo === 'audio' ? 'Transcribiendo y leyendo pedido...' : 'Leyendo pedido...');
		$('#overlay-cargando').css('display', 'flex');
		$('#btn-interpretar').prop('disabled', true);

		fetch('inc/interpretar_pedido_ia.php', { method: 'POST', body: formData })
			.then(r => r.json())
			.then(data => {
				if (!data.ok) {
					$err.text(data.mensaje || 'No se pudo interpretar el pedido').show();
					return;
				}
				ultimoResultado = data;
				pintarRevision(data);
			})
			.catch(() => $err.text('Error de conexión con el servidor').show())
			.finally(() => {
				$('#overlay-cargando').hide();
				$('#btn-interpretar').prop('disabled', false);
			});
	});

	function filaHtml(item) {
		item = item || { texto_leido: '', cantidad: 1, producto_id: null, nom_prod: null, score: 0 };
		const sinMatch = !item.producto_id;
		const notaMatch = sinMatch
			? '<span class="text-danger" style="font-size:11px">Sin coincidencia, elige el producto manualmente</span>'
			: '<span class="badge-score text-muted">match ' + item.score + '%</span>';
		return `
			<tr class="fila-revision ${sinMatch ? 'sin-match' : ''}">
				<td class="text-muted" style="font-size:13px">${escHtml(item.texto_leido || '')}</td>
				<td class="select-producto-container">
					<select class="form-control select-producto" style="width:100%"></select>
					${notaMatch}
				</td>
				<td><input type="number" class="form-control input-cantidad" step="0.001" min="0.001" value="${item.cantidad}"></td>
				<td><button type="button" class="btn btn-danger btn-xs btn-quitar-fila"><i class="fas fa-trash"></i></button></td>
			</tr>
		`;
	}

	function initSelectProducto($select, preId, preTexto) {
		$select.select2({
			ajax: {
				url: 'inc/buscar_producto_admin.php',
				dataType: 'json',
				delay: 250,
				data: params => ({ q: params.term || '' }),
				processResults: data => ({ results: data })
			},
			placeholder: '-- buscar producto --',
			allowClear: true,
			minimumInputLength: 2,
			width: '100%'
		});
		if (preId) {
			const option = new Option(preTexto, preId, true, true);
			$select.append(option).trigger('change');
		}
	}

	function pintarRevision(data) {
		let info = '<strong>Texto interpretado:</strong> ' + escHtml(data.texto_leido_completo || '(desde la imagen)');
		if (data.historial_usado > 0) {
			info += '<br><span class="text-success"><i class="fas fa-check"></i> Se usó el historial de compras de este cliente (' + data.historial_usado + ' productos) para interpretar mejor el pedido</span>';
		}
		$('#texto-leido-info').html(info);

		const $tbody = $('#tabla-revision tbody').empty();
		data.items.forEach(item => {
			const $tr = $(filaHtml(item));
			$tbody.append($tr);
			initSelectProducto($tr.find('.select-producto'), item.producto_id, item.nom_prod);
		});

		$('#panel-captura').hide();
		$('#resultado-revision').show();
	}

	$('#btn-agregar-linea').on('click', function() {
		const $tr = $(filaHtml());
		$('#tabla-revision tbody').append($tr);
		initSelectProducto($tr.find('.select-producto'), null, null);
	});

	$('#tabla-revision').on('click', '.btn-quitar-fila', function() {
		$(this).closest('tr').remove();
	});

	$('#btn-cancelar-revision').on('click', function() {
		$('#resultado-revision').hide();
		$('#panel-captura').show();
	});

	$('#btn-crear-prepedido').on('click', function() {
		const $err = $('#msg-error-revision').hide();
		const cliente = $('#cliente').val().trim();
		if (!cliente) {
			$err.text('Indica el cliente del pedido.').show();
			return;
		}

		const lineas = [];
		let ok = true;
		$('#tabla-revision tbody tr').each(function() {
			const idProducto = $(this).find('.select-producto').val();
			const cantidad = parseFloat($(this).find('.input-cantidad').val()) || 0;
			if (!idProducto || cantidad <= 0) { ok = false; return; }
			lineas.push({ producto_id: idProducto, cantidad });
		});

		if (!ok || !lineas.length) {
			$err.text('Asigna un producto de tu catálogo y una cantidad válida a cada línea antes de crear el prepedido.').show();
			return;
		}

		const $btn = $(this).prop('disabled', true).text('Creando...');
		const body = new URLSearchParams();
		body.append('cliente', cliente);
		body.append('texto_ia', (ultimoResultado && ultimoResultado.texto_leido_completo) || '');
		lineas.forEach(l => {
			body.append('producto_id[]', l.producto_id);
			body.append('cantidad[]', l.cantidad);
		});

		fetch('inc/registrar_preventa_ia.php', { method: 'POST', body })
			.then(r => r.json())
			.then(data => {
				if (data.ok) {
					window.location.href = 'procesar_preventa.php?id=' + data.id_preventa;
				} else {
					$err.text(data.mensaje || 'No se pudo crear el prepedido').show();
					$btn.prop('disabled', false).html('<i class="fas fa-check"></i> Crear prepedido');
				}
			})
			.catch(() => {
				$err.text('Error de conexión').show();
				$btn.prop('disabled', false).html('<i class="fas fa-check"></i> Crear prepedido');
			});
	});

	function escHtml(s) {
		return String(s).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
	}
	</script>
</body>
</html>
