<?php
session_start();
include('inc/control.php');
include('inc/sdba/sdba.php');

// Control de acceso - Solo usuarios específicos
$usuarios_permitidos = ['hars', 'susan', 'robert'];
if (!in_array($_SESSION['usuario'], $usuarios_permitidos)) {
    header("Location: venta.php");
    exit;
}

$hoy = date('Y-m-d');
$mes_actual = date('Y-m');
$usuario_id = $_SESSION['id_usr'];
$es_admin = ($_SESSION['type'] == 'admin');

// Filtro de mes (GET o mes actual por defecto)
$mes_filtro = isset($_GET['mes']) && preg_match('/^\d{4}-\d{2}$/', $_GET['mes']) ? $_GET['mes'] : $mes_actual;
$es_mes_actual = ($mes_filtro === $mes_actual);

$meses_es = ['01'=>'Enero','02'=>'Febrero','03'=>'Marzo','04'=>'Abril','05'=>'Mayo','06'=>'Junio','07'=>'Julio','08'=>'Agosto','09'=>'Septiembre','10'=>'Octubre','11'=>'Noviembre','12'=>'Diciembre'];

// Generar lista de últimos 12 meses para el selector
$meses_disponibles = [];
for ($i = 0; $i < 12; $i++) {
    $meses_disponibles[] = date('Y-m', strtotime("-$i months"));
}

// VENTAS DEL DÍA (solo si es el mes actual)
$total_ventas_dia = 0;
$monto_dia = 0;
if ($es_mes_actual) {
    $result_dia = Sdba::db()->query("SELECT COUNT(*) as total FROM ventas WHERE DATE(fecha) = '$hoy' AND estado != '2'" . (!$es_admin ? " AND usuario = '$usuario_id'" : ""))->row();
    $total_ventas_dia = $result_dia['total'];

    $result_monto_dia = Sdba::db()->query("SELECT SUM(total) as monto FROM ventas WHERE DATE(fecha) = '$hoy' AND estado != '2'" . (!$es_admin ? " AND usuario = '$usuario_id'" : ""))->row();
    $monto_dia = $result_monto_dia['monto'] ?: 0;
}

// VENTAS DEL MES FILTRADO
$result_mes = Sdba::db()->query("SELECT COUNT(*) as total FROM ventas WHERE DATE_FORMAT(fecha, '%Y-%m') = '$mes_filtro' AND estado != '2'" . (!$es_admin ? " AND usuario = '$usuario_id'" : ""))->row();
$total_ventas_mes = $result_mes['total'];

$result_monto_mes = Sdba::db()->query("SELECT SUM(total) as monto FROM ventas WHERE DATE_FORMAT(fecha, '%Y-%m') = '$mes_filtro' AND estado != '2'" . (!$es_admin ? " AND usuario = '$usuario_id'" : ""))->row();
$monto_mes = $result_monto_mes['monto'] ?: 0;

// PRODUCTOS MÁS VENDIDOS (Top 20 del mes)
$q_user_prod = !$es_admin ? "AND ventas.usuario = '$usuario_id' " : "";
$productos_result = Sdba::db()->query("SELECT productos.nom_prod, SUM(detalle_ventas.cantidad) as total_vendido, SUM(detalle_ventas.cantidad * detalle_ventas.precio) as monto_total
FROM detalle_ventas
LEFT JOIN ventas ON detalle_ventas.venta = ventas.id_venta
LEFT JOIN productos ON detalle_ventas.producto = productos.id_producto
WHERE DATE_FORMAT(ventas.fecha, '%Y-%m') = '$mes_filtro'
AND ventas.estado != '2' $q_user_prod
GROUP BY detalle_ventas.producto
ORDER BY monto_total DESC
LIMIT 20")->result();

// CLIENTES CON MAYORES COMPRAS (Top 20 del mes)
$clientes_result = Sdba::db()->query("SELECT clientes.cliente as nombre_cliente, SUM(detalle_ventas.cantidad * detalle_ventas.precio) as total_compras, COUNT(DISTINCT ventas.id_venta) as num_compras
FROM ventas
LEFT JOIN detalle_ventas ON ventas.id_venta = detalle_ventas.venta
LEFT JOIN clientes ON ventas.cliente = clientes.id_cliente
WHERE DATE_FORMAT(ventas.fecha, '%Y-%m') = '$mes_filtro'
AND ventas.estado != '2'
AND ventas.cliente != '' $q_user_prod
GROUP BY ventas.cliente
ORDER BY total_compras DESC
LIMIT 20")->result();

// STOCK BAJO (Productos con stock menor a 10)
$productos_stock_bajo = Sdba::db()->query("SELECT codigo_producto, nom_prod, stockp, precio_venta FROM productos WHERE stockp < 10 AND stockp > 0 ORDER BY stockp ASC")->result();

// ── GRÁFICA ÚLTIMOS 7 DÍAS ──────────────────────────────────────────────────
$dias_es = ['Dom','Lun','Mar','Mié','Jue','Vie','Sáb'];
$dias_chart = [];
$labels_chart = [];
for ($i = 6; $i >= 0; $i--) {
    $d = date('Y-m-d', strtotime("-$i days"));
    $dias_chart[] = $d;
    $dia_nombre = $dias_es[(int)date('w', strtotime($d))];
    $labels_chart[] = $dia_nombre . ' ' . date('d/m', strtotime($d));
}

// Totales diarios
$result_7dias = Sdba::db()->query("SELECT DATE(fecha) as dia, SUM(total) as monto FROM ventas
WHERE DATE(fecha) >= DATE_SUB(CURDATE(), INTERVAL 6 DAY) AND estado != '2'
GROUP BY DATE(fecha)")->result();
$monto_por_dia = [];
foreach ($result_7dias as $r) {
    $monto_por_dia[$r['dia']] = (float)$r['monto'];
}
$totales_chart = array_map(fn($d) => $monto_por_dia[$d] ?? 0, $dias_chart);
$total_7dias = array_sum($totales_chart);

// Ventas por usuario (últimos 7 días)
$result_users = Sdba::db()->query("SELECT DATE(v.fecha) as dia, COALESCE(u.nombres, 'Sin nombre') as nombre, SUM(v.total) as monto
FROM ventas v
LEFT JOIN usuarios u ON v.usuario = u.id_usuario
WHERE DATE(v.fecha) >= DATE_SUB(CURDATE(), INTERVAL 6 DAY) AND v.estado != '2'
GROUP BY DATE(v.fecha), v.usuario
ORDER BY dia, nombre")->result();

$users_data = [];
foreach ($result_users as $r) {
    $nombre = $r['nombre'];
    if (!isset($users_data[$nombre])) {
        $users_data[$nombre] = array_fill_keys($dias_chart, 0);
    }
    $users_data[$nombre][$r['dia']] = (float)$r['monto'];
}

// Colores por usuario: azul, rojo, verde, naranja, pasteles
$user_colors = ['#4472c4','#e05252','#70c44b','#ff9a34','#a78bfa','#67c6d6','#f9a8d4'];
$user_datasets = [];
$ci = 0;
foreach ($users_data as $nombre => $por_dia) {
    $color = $user_colors[$ci % count($user_colors)];
    $user_datasets[] = [
        'label'           => $nombre,
        'data'            => array_values($por_dia),
        'backgroundColor' => $color,
        'borderRadius'    => 3,
        'borderSkipped'   => false,
        'stack'           => 'usuarios',
    ];
    $ci++;
}

$labels_json   = json_encode($labels_chart);
$datasets_json = json_encode($user_datasets);
$totales_json  = json_encode(array_values($totales_chart));
$hoy_idx       = 6; // último elemento
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Dashboard</title>
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="preconnect" href="https://ajax.googleapis.com">
    <link rel="preconnect" href="https://maxcdn.bootstrapcdn.com">
    <link rel="preconnect" href="https://use.fontawesome.com">
    <link rel="stylesheet" type="text/css" href="/assets/css/bootstrap.min.css">
    <link rel="stylesheet" type="text/css" href="/assets/css/custom.css">
    <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.7.2/css/all.css" integrity="sha384-fnmOCqbTlWIlj8LyTjo7mOUStjsKC4pOpQbqyi7RrhN7udi9RwhKkMHpvLbHG9Sr" crossorigin="anonymous" media="print" onload="this.media='all'">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <style>
    /* ── paleta Color Hunt ────────────────────────── */
    :root {
        --c-black:  #1a1a1a;
        --c-navy:   #1e3a4c;
        --c-orange: #ff5023;
        --c-light:  #f0f0f0;
    }

    /* ── reset layout ─────────────────────────────── */
    body.dashboard { background: var(--c-light); }
    .dashboard .kbg { margin-top: 56px; padding: 0; position: static; }
    .dash-wrap { padding: 20px 24px; }

    /* ── month selector ───────────────────────────── */
    .dash-month-bar {
        display: flex;
        align-items: center;
        gap: 10px;
        margin-bottom: 18px;
    }
    .dash-month-bar label { font-size: 12px; color: #888; margin: 0; white-space: nowrap; }
    .dash-month-bar select {
        font-size: 13px;
        border: 1px solid #ddd;
        border-radius: 5px;
        padding: 4px 10px;
        color: #444;
        background: #fff;
        outline: none;
    }

    /* ── stat cards (fila 1) ──────────────────────── */
    .stat-row { display: flex; gap: 14px; margin-bottom: 14px; flex-wrap: wrap; }
    .stat-card {
        flex: 1; min-width: 180px;
        background: #fff;
        border: 1px solid #e4e4e4;
        border-radius: 8px;
        padding: 18px 22px 16px;
        display: flex; flex-direction: column; gap: 4px;
    }
    .stat-card .sc-label {
        font-size: 11px; color: #aaa;
        text-transform: uppercase; letter-spacing: .6px;
    }
    .stat-card .sc-value {
        font-size: 28px; font-weight: 700;
        color: var(--c-orange); line-height: 1.1;
    }
    .stat-card .sc-value.dark { color: var(--c-navy); }
    .stat-card .sc-sub { font-size: 12px; color: #bbb; }

    /* ── panel genérico ───────────────────────────── */
    .dash-panel {
        background: #fff;
        border: 1px solid #e4e4e4;
        border-radius: 8px;
        margin-bottom: 14px;
        overflow: hidden;
    }
    .dash-panel-head {
        padding: 12px 20px;
        border-bottom: 1px solid #f0f0f0;
        font-size: 13px; font-weight: 600; color: #444;
        display: flex; align-items: center; justify-content: space-between;
    }
    .dash-panel-head .ph-icon { color: var(--c-orange); margin-right: 6px; font-size: 12px; }
    .dash-panel-head .ph-total { font-size: 13px; font-weight: 700; color: #2d3436; }
    .dash-panel-body { padding: 6px 0; }
    .dash-panel-body .table { margin-bottom: 0; font-size: 13px; }
    .dash-panel-body .table > thead > tr > th {
        border-bottom: 1px solid #f0f0f0;
        padding: 8px 16px;
        font-size: 11px; color: #aaa;
        text-transform: uppercase; letter-spacing: .4px;
        font-weight: 600; background: #fafafa;
    }
    .dash-panel-body .table > tbody > tr > td {
        padding: 9px 16px;
        border-top: 1px solid #f7f7f7;
        color: #444; vertical-align: middle;
    }
    .dash-panel-body .table > tbody > tr:hover > td { background: #fafafa; }
    .dash-panel-body .table > tbody > tr > td.monto { color: var(--c-navy); font-weight: 600; }
    .dash-panel-body .table > tbody > tr > td.num { color: #555; font-weight: 600; }

    /* stock badge */
    .stock-badge {
        display: inline-block;
        min-width: 32px; text-align: center;
        padding: 2px 8px; border-radius: 12px;
        font-size: 12px; font-weight: 700;
    }
    .stock-badge.low { background: #fff3cd; color: #856404; }
    .stock-badge.critical { background: #fde8e8; color: #c0392b; }

    /* ── gráfica 7 días ───────────────────────────── */
    .chart-panel { padding: 20px; }
    .chart-canvas-wrap { position: relative; height: 240px; }

    /* ── alertas de stock ─────────────────────────── */
    .dash-panel-head.danger { border-bottom-color: #fde8e8; }
    .dash-panel-head.danger .ph-icon { color: #e74c3c; }
    </style>
</head>

<body class="mobile dashboard escritorio">

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
            <?php menu('1'); ?>
        </div>
    </nav>

    <div class="kbg">
        <div class="dash-wrap">

            <!-- Selector de mes -->
            <form method="GET" action="dashboard.php" class="dash-month-bar">
                <label>Ver mes:</label>
                <select name="mes" onchange="this.form.submit()">
                    <?php foreach ($meses_disponibles as $m):
                        $label = $meses_es[date('m', strtotime($m.'-01'))] . ' ' . date('Y', strtotime($m.'-01')); ?>
                        <option value="<?= $m ?>" <?= $m === $mes_filtro ? 'selected' : '' ?>><?= $label ?></option>
                    <?php endforeach; ?>
                </select>
            </form>

            <!-- ── Fila 1: Estadísticas ── -->
            <div class="stat-row">

                <div class="stat-card">
                    <span class="sc-label"><i class="fas fa-sun"></i> Ventas hoy</span>
                    <span class="sc-value"><?= $es_mes_actual ? 'S/ ' . number_format($monto_dia, 2) : '—' ?></span>
                    <span class="sc-sub"><?= $es_mes_actual ? $total_ventas_dia . ' transacciones' : 'Solo mes actual' ?></span>
                </div>

                <div class="stat-card">
                    <span class="sc-label"><i class="fas fa-calendar-alt"></i> Ventas del mes</span>
                    <span class="sc-value">S/ <?= number_format($monto_mes, 2) ?></span>
                    <span class="sc-sub"><?= $total_ventas_mes ?> transacciones · <?= $meses_es[date('m', strtotime($mes_filtro.'-01'))] ?></span>
                </div>

                <div class="stat-card">
                    <span class="sc-label"><i class="fas fa-receipt"></i> Ticket promedio</span>
                    <span class="sc-value dark">S/ <?= number_format($monto_mes / max($total_ventas_mes, 1), 2) ?></span>
                    <span class="sc-sub">promedio por venta</span>
                </div>

                <div class="stat-card">
                    <span class="sc-label"><i class="fas fa-exclamation-triangle"></i> Stock bajo</span>
                    <span class="sc-value"><?= count($productos_stock_bajo) ?></span>
                    <span class="sc-sub">productos &lt; 10 unidades</span>
                </div>

            </div>

            <!-- ── Fila 2: Gráfica 7 días ── -->
            <div class="dash-panel">
                <div class="dash-panel-head">
                    <span><i class="fas fa-chart-bar ph-icon"></i> Ventas — últimos 7 días</span>
                    <span class="ph-total">Total: S/ <?= number_format($total_7dias, 2) ?></span>
                </div>
                <div class="chart-panel">
                    <div class="chart-canvas-wrap">
                        <canvas id="chartVentas7dias"></canvas>
                    </div>
                </div>
            </div>

            <!-- ── Fila 3: Productos + Clientes ── -->
            <div class="row" style="margin:0 -7px;">
                <div class="col-md-6" style="padding:0 7px;">
                    <div class="dash-panel">
                        <div class="dash-panel-head">
                            <span><i class="fas fa-trophy ph-icon"></i> Top Productos del Mes</span>
                        </div>
                        <div class="dash-panel-body">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Producto</th>
                                        <th style="text-align:right">Cant.</th>
                                        <th style="text-align:right">Monto</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (count($productos_result) > 0): ?>
                                        <?php foreach ($productos_result as $prod): ?>
                                            <tr>
                                                <td style="text-transform:uppercase"><?= htmlspecialchars($prod['nom_prod'] ?: 'Sin nombre', ENT_QUOTES, 'UTF-8') ?></td>
                                                <td class="num" style="text-align:right"><?= (int)$prod['total_vendido'] ?></td>
                                                <td class="monto" style="text-align:right">S/ <?= number_format($prod['monto_total'], 2) ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr><td colspan="3" style="text-align:center;color:#bbb;padding:24px">Sin datos este mes</td></tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="col-md-6" style="padding:0 7px;">
                    <div class="dash-panel">
                        <div class="dash-panel-head">
                            <span><i class="fas fa-users ph-icon"></i> Top Clientes del Mes</span>
                        </div>
                        <div class="dash-panel-body">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Cliente</th>
                                        <th style="text-align:right">Compras</th>
                                        <th style="text-align:right">Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (count($clientes_result) > 0): ?>
                                        <?php foreach ($clientes_result as $cli): ?>
                                            <tr>
                                                <td style="text-transform:uppercase"><?= htmlspecialchars($cli['nombre_cliente'] ?: 'SIN NOMBRE', ENT_QUOTES, 'UTF-8') ?></td>
                                                <td class="num" style="text-align:right"><?= (int)$cli['num_compras'] ?></td>
                                                <td class="monto" style="text-align:right">S/ <?= number_format($cli['total_compras'], 2) ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr><td colspan="3" style="text-align:center;color:#bbb;padding:24px">Sin datos este mes</td></tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ── Fila 4: Stock bajo ── -->
            <?php if (count($productos_stock_bajo) > 0): ?>
            <div class="dash-panel">
                <div class="dash-panel-head danger">
                    <span><i class="fas fa-box-open ph-icon"></i> Alertas de Stock Bajo</span>
                    <span style="font-size:12px;color:#e74c3c;font-weight:600"><?= count($productos_stock_bajo) ?> productos</span>
                </div>
                <div class="dash-panel-body">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Código</th>
                                <th>Producto</th>
                                <th style="text-align:center">Stock</th>
                                <th style="text-align:right">Precio</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach (array_slice($productos_stock_bajo, 0, 15) as $prod): ?>
                                <tr>
                                    <td style="color:#999;font-size:12px"><?= htmlspecialchars($prod['codigo_producto'], ENT_QUOTES, 'UTF-8') ?></td>
                                    <td style="text-transform:uppercase"><?= htmlspecialchars($prod['nom_prod'], ENT_QUOTES, 'UTF-8') ?></td>
                                    <td style="text-align:center">
                                        <span class="stock-badge <?= $prod['stockp'] <= 3 ? 'critical' : 'low' ?>"><?= $prod['stockp'] ?></span>
                                    </td>
                                    <td class="monto" style="text-align:right">S/ <?= number_format($prod['precio_venta'], 2) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>

        </div><!-- /.dash-wrap -->
    </div><!-- /.kbg -->

    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.3.1/jquery.min.js" defer></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js" defer></script>

    <script>
    (function () {
        const labels   = <?= $labels_json ?>;
        const datasets = <?= $datasets_json ?>;
        const totales  = <?= $totales_json ?>;
        const hoyIdx   = <?= $hoy_idx ?>;

        if (datasets.length === 0) return;

        // Resaltar barra de hoy
        datasets.forEach(ds => {
            if (Array.isArray(ds.backgroundColor)) return;
            const base = ds.backgroundColor;
            ds.backgroundColor = labels.map((_, i) => i === hoyIdx ? base : base + 'bb');
        });

        // Línea de total diario
        datasets.push({
            type: 'line',
            label: 'Total día',
            data: totales,
            borderColor: '#1e3a4c',
            backgroundColor: 'transparent',
            borderWidth: 2.5,
            pointBackgroundColor: labels.map((_, i) => i === hoyIdx ? '#ff5023' : '#1e3a4c'),
            pointRadius: 5,
            pointHoverRadius: 7,
            tension: 0.35,
            order: -1,
        });

        // Plugin inline: total encima de cada barra apilada
        const totalLabelPlugin = {
            id: 'stackedTotalLabel',
            afterDatasetsDraw(chart) {
                const { ctx, data } = chart;
                const lastMeta = chart.getDatasetMeta(data.datasets.length - 1);

                lastMeta.data.forEach((bar, i) => {
                    const total = data.datasets.reduce((sum, ds) => sum + (ds.data[i] || 0), 0);
                    if (total === 0) return;

                    const isHoy = i === hoyIdx;
                    const label = 'S/ ' + total.toLocaleString('es-PE', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

                    ctx.save();
                    ctx.font = 'bold 16px system-ui, sans-serif';
                    ctx.fillStyle = isHoy ? '#cc3a10' : '#666';
                    ctx.textAlign = 'center';
                    ctx.textBaseline = 'bottom';
                    ctx.fillText(label, bar.x, bar.y - 4);
                    ctx.restore();
                });
            }
        };

        const ctx = document.getElementById('chartVentas7dias').getContext('2d');
        new Chart(ctx, {
            type: 'bar',
            data: { labels, datasets },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                layout: { padding: { top: 24 } },
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: { font: { size: 12 }, boxWidth: 12, padding: 16 }
                    },
                    tooltip: {
                        callbacks: {
                            label: ctx => {
                                if (ctx.dataset.type === 'line') return null;
                                return '  ' + ctx.dataset.label + ':  S/ ' + ctx.parsed.y.toLocaleString('es-PE', { minimumFractionDigits: 2 });
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        stacked: true,
                        grid: { display: false },
                        ticks: { font: { size: 12 }, color: '#999' }
                    },
                    y: {
                        stacked: true,
                        grid: { color: '#f0f0f0' },
                        ticks: {
                            font: { size: 11 }, color: '#bbb',
                            callback: v => 'S/ ' + v.toLocaleString('es-PE')
                        }
                    }
                }
            },
            plugins: [totalLabelPlugin]
        });
    })();
    </script>
</body>
</html>
