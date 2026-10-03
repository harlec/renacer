<?php
include('inc/control.php');
if ($_SESSION['type']=='operador') {
    header("Location: dashboard.php");
    exit;
}
$conn = new mysqli('localhost', 'admin_renacer', 'ikm169uhn', 'admin_renacer');
$conn->set_charset('utf8');
$anios = [];
$r = $conn->query("SELECT DISTINCT YEAR(fecha_ope) AS a FROM ventas WHERE fecha_ope IS NOT NULL ORDER BY a DESC");
while ($r && $x = $r->fetch_assoc()) { if ($x['a']) $anios[] = (int)$x['a']; }
if (!$anios) $anios = [(int)date('Y')];
$conn->close();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Sistema - Evolución de ventas</title>
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" type="text/css" href="/assets/css/bootstrap.min.css">
    <link rel="stylesheet" type="text/css" href="/assets/css/custom.css">
    <link rel="stylesheet" type="text/css" href="/assets/css/select2.min.css">
    <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.7.2/css/all.css">
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/buttons/1.6.4/css/buttons.dataTables.min.css">
    <style>
        :root { --c-navy: #1e3a4c; --c-orange: #ff5023; }
        .resumen-row { display:grid; grid-template-columns: repeat(4, 1fr); gap:14px; margin-bottom:20px; }
        .resumen-card { background:#fff; border-radius:12px; padding:16px 18px; box-shadow:0 1px 3px rgba(0,0,0,.08); }
        .resumen-card .rc-label { font-size:12px; color:#888; font-weight:600; text-transform:uppercase; display:flex; align-items:center; gap:6px; }
        .resumen-card .rc-valor { font-size:24px; font-weight:800; color:var(--c-navy); margin-top:6px; }
        .resumen-card.total { background:var(--c-navy); }
        .resumen-card.total .rc-label { color:#bcd; }
        .resumen-card.total .rc-valor { color:#fff; }
        @media (max-width: 700px) { .resumen-row { grid-template-columns: 1fr 1fr; } }
        @media print {
            .navbar, .submenu, #periodoSelect, .dt-buttons, .dataTables_filter, .dataTables_paginate, .dataTables_length, .dataTables_info { display:none !important; }
        }
            .ctl-label { font-size:12px; color:#888; font-weight:600; }
        .chart-box { position:relative; height:420px; margin:10px 0 24px; }
        .evo-wrap { overflow-x:auto; }
        table.evo { border-collapse:collapse; font-size:13px; min-width:100%; }
        table.evo th, table.evo td { padding:7px 10px; border-bottom:1px solid #eee; white-space:nowrap; text-align:right; }
        table.evo th:first-child, table.evo td:first-child { text-align:left; position:sticky; left:0; background:#fff; z-index:1; font-weight:600; }
        table.evo thead th { font-size:11px; text-transform:uppercase; color:#888; border-bottom:2px solid #eee; }
        table.evo td.cero { color:#ccc; }
        table.evo td.tot, table.evo th.tot { background:#f7f7f7; font-weight:700; }
        .dot { display:inline-block; width:10px; height:10px; border-radius:50%; margin-right:6px; }
        .select2-container { width:100% !important; }
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
            <?php menu('5'); ?>
          </div>
          <div class="submenu">
            <ul class="subtop-tabs">
                <li><a href="reportes.php">Reporte Stock</a></li>
                <li><a href="reportes_vd.php">Ventas diarias</a></li>
                <li><a href="reporte_ventas.php">Reporte ventas</a></li>
                <li><a href="reporte_compras.php">Reporte compras</a></li>
                <li><a href="reporte_kardex.php">Reporte Kardex</a></li>
                <li><a href="reporte_mv.php">Ventas por categoría</a></li>
                <li class="active"><a href="reporte_evolucion.php">Evolución de ventas</a></li>
                <li><a href="reporte_prediccion_stock.php">Predicción de Stock</a></li>
                <li><a href="reporte_huevos.php">Reporte Huevos</a></li>
            </ul>
          </div>
        </nav>
        <div class="kbg">
            <div class="cuerpofull">
                <div class="titulo">
                    <h3>Evolución de ventas</h3>
                </div>
                <div class="container-fluid">
                    <div class="row"><div class="col-md-12">
                        <div class="panel panel-default pa">
                            <div class="panel-body">
                                <div class="row" style="margin-bottom:12px">
                                    <div class="col-md-2 col-xs-6">
                                        <label class="ctl-label">Agrupar por</label>
                                        <select id="agrupar" class="form-control">
                                            <option value="categoria">Categoría</option>
                                            <option value="producto">Producto</option>
                                        </select>
                                    </div>
                                    <div class="col-md-2 col-xs-6">
                                        <label class="ctl-label">Período</label>
                                        <select id="periodo" class="form-control">
                                            <option value="este_anio">Este año</option>
                                            <option value="2m">Últimos 2 meses</option>
                                            <option value="3m">Último trimestre</option>
                                            <option value="6m">Últimos 6 meses</option>
                                            <?php foreach ($anios as $a) { if ($a < (int)date('Y')) echo '<option value="anio:' . $a . '">Año ' . $a . '</option>'; } ?>
                                            <option value="custom">Rango personalizado...</option>
                                        </select>
                                    </div>
                                    <div class="col-md-2 col-xs-6">
                                        <label class="ctl-label">Vista</label>
                                        <select id="gran" class="form-control">
                                            <option value="mes">Por meses</option>
                                            <option value="semana">Por semanas</option>
                                        </select>
                                    </div>
                                    <div class="col-md-3 col-xs-6">
                                        <label class="ctl-label">Medir y ordenar por</label>
                                        <select id="metrica" class="form-control">
                                            <option value="monto">Dinero (S/)</option>
                                            <option value="unidades">Cantidad (unidades)</option>
                                        </select>
                                    </div>
                                    <div class="col-md-3 col-xs-12">
                                        <label class="ctl-label">Si no eliges, mostrar los</label>
                                        <select id="top" class="form-control">
                                            <option value="3">3 principales</option>
                                            <option value="5" selected>5 principales</option>
                                            <option value="8">8 principales</option>
                                            <option value="12">12 principales</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="row" id="rangoCustom" style="margin-bottom:12px;display:none">
                                    <div class="col-md-2 col-xs-6">
                                        <label class="ctl-label">Desde</label>
                                        <input type="date" id="desde" class="form-control">
                                    </div>
                                    <div class="col-md-2 col-xs-6">
                                        <label class="ctl-label">Hasta</label>
                                        <input type="date" id="hasta" class="form-control">
                                    </div>
                                </div>
                                <div class="row" style="margin-bottom:8px">
                                    <div class="col-md-12">
                                        <label class="ctl-label">Comparar (opcional: elige las categorías / productos a mostrar)</label>
                                        <select id="items" multiple="multiple"></select>
                                    </div>
                                </div>

                                <div id="msg" class="text-muted" style="margin:10px 0"></div>
                                <div class="chart-box"><canvas id="grafica"></canvas></div>
                                <div class="evo-wrap"><table class="evo" id="tabla"><thead></thead><tbody></tbody></table></div>
                            </div>
                        </div>
                    </div></div>
                </div>
            </div>
        </div>

    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.3.1/jquery.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js"></script>
    <script src="/assets/js/select2.full.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.7.2/Chart.bundle.min.js"></script>
    <script>
    var COLORES = ['#1e3a4c', '#ff5023', '#2d7dd2', '#27ae60', '#8e44ad', '#f39c12', '#16a085', '#c0392b', '#7f8c8d', '#e84393', '#6c5ce7', '#00b894'];
    var grafica = null, reqActual = null;

    function money(v) { return 'S/ ' + Number(v).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }); }
    function num(v) { return Number(v).toLocaleString('en-US', { maximumFractionDigits: 2 }); }
    function esc(s) { return $('<div>').text(s).html(); }

    function iso(d) { return d.getFullYear() + '-' + ('0' + (d.getMonth() + 1)).slice(-2) + '-' + ('0' + d.getDate()).slice(-2); }
    // Meses calendario completos: "Últimos 2 meses" = mes anterior + mes actual (hasta hoy), etc.
    function rango() {
        var p = $('#periodo').val(), hoy = new Date(), y = hoy.getFullYear(), m = hoy.getMonth();
        if (p === 'este_anio') return [y + '-01-01', iso(hoy)];
        if (p === '2m') return [iso(new Date(y, m - 1, 1)), iso(hoy)];
        if (p === '3m') return [iso(new Date(y, m - 2, 1)), iso(hoy)];
        if (p === '6m') return [iso(new Date(y, m - 5, 1)), iso(hoy)];
        if (p.indexOf('anio:') === 0) { var a = p.split(':')[1]; return [a + '-01-01', a + '-12-31']; }
        return [$('#desde').val(), $('#hasta').val()];
    }

    function cargar() {
        var metrica = $('#metrica').val();
        var r = rango();
        if (!r[0] || !r[1] || r[0] > r[1]) { $('#msg').text('Elige un rango de fechas válido (desde no puede ser posterior a hasta).'); return; }
        if (reqActual) reqActual.abort();
        $('#msg').text('Cargando...');
        reqActual = $.ajax({
            url: 'inc/get_evolucion.php', dataType: 'json',
            data: { agrupar: $('#agrupar').val(), desde: r[0], hasta: r[1], gran: $('#gran').val(), metrica: metrica, top: $('#top').val(), items: $('#items').val() || [] },
            success: function(d) {
                reqActual = null;
                if (!d.ok) { $('#msg').text('No se pudo cargar el reporte.'); return; }
                $('#msg').text(d.series.length ? '' : 'No hay ventas en ese período para mostrar.');
                pintarOpciones(d);
                pintarGrafica(d);
                pintarTabla(d);
            },
            error: function(x, st) { if (st !== 'abort') $('#msg').text('No se pudo cargar el reporte.'); }
        });
    }

    // Llena el selector con todo lo que tuvo ventas en el año (ordenado por la métrica), sin perder la selección.
    var opcionesClave = '';
    function pintarOpciones(d) {
        var clave = d.agrupar + '|' + d.desde + '|' + d.hasta + '|' + d.metrica;
        if (clave === opcionesClave) return;
        var previo = $('#items').val() || [];
        var mismoTipo = opcionesClave.split('|')[0] === d.agrupar;
        opcionesClave = clave;
        var $s = $('#items').empty();
        $.each(d.opciones, function(i, o) {
            var etq = o.nombre + '  ·  ' + (d.metrica === 'monto' ? money(o.total) : num(o.total) + ' u.');
            $s.append($('<option>').val(o.id).text(etq));
        });
        if (mismoTipo) $s.val(previo.filter(function(id) { return d.opciones.some(function(o) { return o.id === id; }); }));
        $s.trigger('change.select2');
    }

    function pintarGrafica(d) {
        var usarMonto = d.metrica === 'monto';
        var datasets = d.series.map(function(s, i) {
            var c = COLORES[i % COLORES.length];
            return { label: s.nombre, data: usarMonto ? s.montos : s.unidades, borderColor: c, backgroundColor: c, fill: false,
                     lineTension: 0.25, borderWidth: 2.5, pointRadius: d.labels.length > 20 ? 1.5 : 3.5, pointHoverRadius: 5 };
        });
        if (grafica) grafica.destroy();
        grafica = new Chart(document.getElementById('grafica').getContext('2d'), {
            type: 'line',
            data: { labels: d.labels, datasets: datasets },
            options: {
                responsive: true, maintainAspectRatio: false,
                legend: { position: 'bottom' },
                tooltips: { mode: 'index', intersect: false,
                    callbacks: { label: function(it, data) { return data.datasets[it.datasetIndex].label + ': ' + (usarMonto ? money(it.yLabel) : num(it.yLabel) + ' u.'); } } },
                hover: { mode: 'index', intersect: false },
                scales: { yAxes: [{ ticks: { beginAtZero: true, callback: function(v) { return usarMonto ? 'S/ ' + Number(v).toLocaleString('en-US') : Number(v).toLocaleString('en-US'); } } }],
                          xAxes: [{ ticks: { autoSkip: true, maxRotation: 60 } }] }
            }
        });
    }

    function pintarTabla(d) {
        var usarMonto = d.metrica === 'monto';
        var fmt = usarMonto ? money : num;
        var h = '<tr><th>' + (d.agrupar === 'producto' ? 'Producto' : 'Categoría') + '</th>';
        $.each(d.labels, function(i, l) { h += '<th>' + esc(l) + '</th>'; });
        h += '<th class="tot">' + (usarMonto ? 'Total S/' : 'Total unid.') + '</th><th class="tot">' + (usarMonto ? 'Total unid.' : 'Total S/') + '</th></tr>';
        $('#tabla thead').html(h);
        var b = '';
        $.each(d.series, function(i, s) {
            var vals = usarMonto ? s.montos : s.unidades;
            b += '<tr><td><span class="dot" style="background:' + COLORES[i % COLORES.length] + '"></span>' + esc(s.nombre) + '</td>';
            $.each(vals, function(j, v) { b += '<td class="' + (v ? '' : 'cero') + '">' + (v ? fmt(v) : '-') + '</td>'; });
            b += '<td class="tot">' + (usarMonto ? money(s.total_monto) : num(s.total_unidades)) + '</td><td class="tot">' + (usarMonto ? num(s.total_unidades) : money(s.total_monto)) + '</td></tr>';
        });
        $('#tabla tbody').html(b);
    }

    $(function() {
        $('#items').select2({ placeholder: 'Todos los principales (o elige para comparar)', closeOnSelect: false, width: '100%' });
        $('#agrupar').on('change', function() { $('#items').val(null).trigger('change.select2'); opcionesClave = ''; cargar(); });
        $('#periodo').on('change', function() {
            var custom = $(this).val() === 'custom';
            $('#rangoCustom').toggle(custom);
            if (custom && !$('#desde').val()) {
                var h = new Date(); $('#hasta').val(iso(h)); $('#desde').val(iso(new Date(h.getFullYear(), h.getMonth() - 1, 1)));
            }
            opcionesClave = ''; cargar();
        });
        $('#desde, #hasta').on('change', function() { opcionesClave = ''; cargar(); });
        $('#metrica').on('change', function() { opcionesClave = ''; cargar(); });
        $('#gran, #top').on('change', cargar);
        $('#items').on('change', cargar);
        cargar();
    });
    </script>
</body>
</html>
