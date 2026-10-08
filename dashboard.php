<?php
session_start();
include('inc/control.php');
include('inc/sdba/sdba.php');
include_once('inc/medios_pago.php');
require_once('inc/dashboard_data.php');

// Control de acceso - Solo usuarios específicos
$usuarios_permitidos = ['hars', 'susan', 'robert'];
if (!in_array(strtolower(trim($_SESSION['usuario'] ?? '')), $usuarios_permitidos)) {
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

// PREDICCIÓN DE QUIEBRE DE STOCK (velocidad de venta últimos 30 días)
require_once('inc/prediccion_stock.php');
$prediccion_data = obtener_prediccion_stock(30);
$prediccion_stock = array_slice($prediccion_data['prediccion'], 0, 10);


// ── Datos del rediseño (inc/dashboard_data.php) ─────────────────────────────
$v7 = dash_ventas_7d();
$total_7dias = array_sum($v7['totales']);
$sin_stock = dash_sin_stock();
$balance = $es_admin ? dash_balance($mes_filtro) : null;
$medios = dash_medios_pago($mes_filtro);
$cxp = $es_admin ? dash_cuentas_por_pagar() : null;
$cxc = $es_admin ? dash_cuentas_por_cobrar() : null;
$recurrentes = dash_clientes_recurrentes();
$perdidos = dash_clientes_perdidos();

function h($t) { return htmlspecialchars((string)$t, ENT_QUOTES, 'UTF-8'); }
function sol($n) { return 'S/ ' . number_format((float)$n, 2); }
function sol0($n) { return 'S/ ' . number_format((float)$n, 0); }

$dias_es_largo = ['Domingo','Lunes','Martes','Miércoles','Jueves','Viernes','Sábado'];
$dias_es = ['Dom','Lun','Mar','Mié','Jue','Vie','Sáb'];
$fecha_larga = $dias_es_largo[(int)date('w')] . ', ' . (int)date('j') . ' de ' . strtolower($meses_es[date('m')]) . ' de ' . date('Y');

$labels7 = [];
$nombre_dia7 = [];
foreach ($v7['dias'] as $d) {
    $labels7[] = $dias_es[(int)date('w', strtotime($d))] . ' ' . date('d/m', strtotime($d));
    $nombre_dia7[] = $dias_es_largo[(int)date('w', strtotime($d))] . ' ' . date('d/m', strtotime($d));
}

$medio_hue = ['efectivo'=>145,'yape'=>300,'transferencia'=>230,'credito'=>25,'plin'=>190,'tarjeta'=>75,'bbva'=>260,'yape_susan'=>340];
$digitales = 0;
foreach ($medios['items'] as &$mi) {
    $mi['hue'] = $medio_hue[$mi['metodo']] ?? 100;
    $mi['label'] = medio_label($mi['metodo']);
    if (in_array($mi['metodo'], ['yape','plin','transferencia','yape_susan','bbva'])) $digitales += $mi['pct'];
}
unset($mi);

$max_prod = 0; foreach ($productos_result as $p) $max_prod = max($max_prod, (float)$p['monto_total']);

$js = [
    'dias'       => $v7['dias'],
    'labels'     => $labels7,
    'nombres'    => $nombre_dia7,
    'vendedores' => $v7['vendedores'],
    'porDia'     => array_values(array_map(fn($d) => (object)$v7['por_dia'][$d], $v7['dias'])),
    'totales'    => array_values($v7['totales']),
    'medios'     => $medios['items'],
];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Escritorio</title>
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" type="text/css" href="/assets/css/bootstrap.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans:wght@400;500;600;700&family=IBM+Plex+Mono:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" type="text/css" href="/assets/css/escritorio.css?v=<?= filemtime(__DIR__ . '/assets/css/escritorio.css') ?>">
    <script src="https://unpkg.com/@phosphor-icons/web@2.1.1"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
</head>

<body class="esc">
<?php menu_v2('1'); ?>

<main class="esc-main">

    <!-- Título + selector de mes -->
    <div class="esc-title">
        <div>
            <div class="fecha"><?= h($fecha_larga) ?> · actualizado <?= date('H:i') ?></div>
            <h1>Escritorio</h1>
        </div>
        <form method="GET" action="dashboard.php">
            <label for="mes">Mes</label>
            <select name="mes" id="mes" onchange="this.form.submit()">
                <?php foreach ($meses_disponibles as $m):
                    $label = $meses_es[date('m', strtotime($m.'-01'))] . ' ' . date('Y', strtotime($m.'-01')); ?>
                    <option value="<?= $m ?>" <?= $m === $mes_filtro ? 'selected' : '' ?>><?= $label ?></option>
                <?php endforeach; ?>
            </select>
        </form>
    </div>

    <!-- KPIs -->
    <div class="esc-row">
        <div class="esc-kpis">
            <div class="esc-kpi">
                <span class="esc-chip" style="background:oklch(0.94 0.05 37);color:oklch(0.58 0.17 37)"><i class="ph-duotone ph-coins"></i></span>
                <div class="txt">
                    <span class="lbl">Ventas hoy</span>
                    <span class="val"><?= $es_mes_actual ? sol($monto_dia) : '—' ?></span>
                    <span class="sub"><?= $es_mes_actual ? $total_ventas_dia . ' transacciones' : 'Solo mes actual' ?></span>
                </div>
                <?php $mx = max(1, max($v7['totales'])); ?>
                <div class="esc-minibars" aria-hidden="true">
                    <?php foreach (array_values($v7['totales']) as $k => $t): ?>
                        <i class="<?= $k === 6 ? 'hoy' : '' ?>" style="height:<?= max(2, round($t / $mx * 30)) ?>px"></i>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="esc-kpi">
                <span class="esc-chip" style="background:oklch(0.94 0.05 145);color:oklch(0.55 0.13 145)"><i class="ph-duotone ph-calendar-check"></i></span>
                <div class="txt">
                    <span class="lbl">Ventas del mes</span>
                    <span class="val"><?= sol($monto_mes) ?></span>
                    <span class="sub"><?= number_format($total_ventas_mes) ?> trans.<?= $es_mes_actual ? ' · día ' . (int)date('j') . ' de ' . (int)date('t') : ' · ' . $meses_es[date('m', strtotime($mes_filtro.'-01'))] ?></span>
                </div>
            </div>
        </div>
        <div class="esc-kpis">
            <div class="esc-kpi">
                <span class="esc-chip" style="background:oklch(0.94 0.05 270);color:oklch(0.55 0.15 270)"><i class="ph-duotone ph-receipt"></i></span>
                <div class="txt">
                    <span class="lbl">Ticket promedio</span>
                    <span class="val"><?= sol($monto_mes / max($total_ventas_mes, 1)) ?></span>
                    <span class="sub"><?= $es_mes_actual ? 'hoy ' . sol($monto_dia / max($total_ventas_dia, 1)) : 'promedio por venta' ?></span>
                </div>
            </div>
            <a class="esc-kpi" href="#inventario">
                <span class="esc-chip" style="background:oklch(0.94 0.05 25);color:oklch(0.58 0.18 25)"><i class="ph-duotone ph-warning"></i></span>
                <div class="txt">
                    <span class="lbl">Stock bajo</span>
                    <span class="val" style="color:var(--danger-strong)"><?= count($productos_stock_bajo) ?></span>
                    <span class="sub"><?= $sin_stock ?> sin stock</span>
                </div>
            </a>
        </div>
    </div>

    <!-- Ventas 7 días + detalle del día -->
    <div class="esc-row">
        <section class="esc-card" style="flex:999 1 560px">
            <div class="esc-head">
                <span class="esc-chip" style="background:oklch(0.94 0.05 37);color:oklch(0.58 0.17 37)"><i class="ph-duotone ph-chart-bar"></i></span>
                <h2>Ventas · últimos 7 días</h2>
                <span class="esc-meta mono">Total <?= sol0($total_7dias) ?></span>
            </div>
            <div class="esc-chart"><canvas id="chart7"></canvas></div>
            <div class="esc-legend" id="legend7"></div>
        </section>
        <section class="esc-card" style="flex:1 1 300px">
            <div>
                <div class="esc-day-label">Detalle del día</div>
                <h2 class="esc-day-title" id="dayTitle"></h2>
            </div>
            <div class="esc-day-total" id="dayTotal"></div>
            <div class="esc-list" id="dayList"></div>
        </section>
    </div>

    <!-- Balance · Medios de pago · Cuentas por pagar -->
    <div class="esc-row">
        <?php if ($balance): $bv = max($balance['ventas'], $balance['compras'], 1); ?>
        <section class="esc-card" style="flex:1 1 320px">
            <div class="esc-head">
                <span class="esc-chip" style="background:oklch(0.94 0.05 145);color:oklch(0.55 0.13 145)"><i class="ph-duotone ph-scales"></i></span>
                <h2>Balance del mes</h2>
                <a class="esc-meta" href="balance.php">Ver balance →</a>
            </div>
            <div>
                <div class="esc-note">Ventas − compras</div>
                <span class="esc-util <?= $balance['saldo'] < 0 ? 'neg' : '' ?>"><?= sol0($balance['saldo']) ?></span>
            </div>
            <?php foreach ([['Ventas cobradas', $balance['ventas'], 'oklch(0.65 0.14 145)'], ['Compras pagadas', $balance['compras'], 'oklch(0.7 0.14 25)']] as $b): ?>
                <div class="esc-hbar">
                    <div class="t"><span><?= $b[0] ?></span><b><?= sol0($b[1]) ?></b></div>
                    <div class="b"><i style="width:<?= min(100, round($b[1] / $bv * 100)) ?>%;background:<?= $b[2] ?>"></i></div>
                </div>
            <?php endforeach; ?>
            <div class="esc-note">Aún no incluye gastos, planillas ni costos.</div>
        </section>
        <?php endif; ?>

        <section class="esc-card" style="flex:1 1 320px">
            <div class="esc-head">
                <span class="esc-chip" style="background:oklch(0.94 0.05 300);color:oklch(0.55 0.15 300)"><i class="ph-duotone ph-credit-card"></i></span>
                <h2>Medios de pago</h2>
                <span class="esc-meta"><?= $meses_es[date('m', strtotime($mes_filtro.'-01'))] ?></span>
            </div>
            <?php if ($medios['items']): $top = $medios['items'][0]; ?>
            <div class="esc-pay">
                <div class="esc-donut">
                    <canvas id="chartMedios" width="118" height="118"></canvas>
                    <div class="ctr">más usado<b><?= h($top['label']) ?></b><?= $top['pct'] ?>%</div>
                </div>
                <div class="esc-pay-list">
                    <?php foreach ($medios['items'] as $m): ?>
                        <div class="esc-pay-row">
                            <i class="dot" style="background:oklch(0.7 0.13 <?= $m['hue'] ?>)"></i>
                            <span class="nm"><?= h($m['label']) ?></span>
                            <span class="pc"><?= $m['pct'] ?>%</span>
                            <span class="mt"><?= sol0($m['monto']) ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="esc-callout"><?= $digitales ?>% de los pagos fueron digitales (Yape, Plin, transferencia, banco).</div>
            <?php else: ?>
                <div class="esc-empty">Sin pagos este mes</div>
            <?php endif; ?>
        </section>

        <?php if ($cxp): ?>
        <section class="esc-card" style="flex:1 1 320px">
            <div class="esc-head">
                <span class="esc-chip" style="background:oklch(0.94 0.05 75);color:oklch(0.55 0.13 75)"><i class="ph-duotone ph-hand-coins"></i></span>
                <h2>Cuentas</h2>
                <div class="esc-seg" id="segCuentas"><button class="on" data-k="pagar">Por pagar</button><button data-k="cobrar">Por cobrar</button></div>
            </div>
            <?php foreach ([['pagar', $cxp, 'cuentas_x_pagar.php', 'Sin deudas pendientes'], ['cobrar', $cxc, 'caja_pagos.php', 'Sin cobros pendientes']] as [$k, $cx, $href, $vacio]): ?>
            <div id="cuentas-<?= $k ?>" class="esc-list <?= $k === 'pagar' ? '' : 'esc-hide' ?>">
                <a class="esc-more" href="<?= $href ?>" style="align-self:flex-end">Ver todas →</a>
                <div class="esc-mini2">
                    <div class="esc-mini"><div class="l">Pendiente</div><div class="v"><?= sol0($cx['pendiente']) ?></div></div>
                    <div class="esc-mini red"><div class="l">Vencido · <?= $cx['n_vencidas'] ?></div><div class="v"><?= sol0($cx['vencido']) ?></div></div>
                </div>
                <div>
                    <?php foreach ($cx['items'] as $c):
                        if ($c['dias'] === null) { $cls = ''; $txt = 'Sin fecha'; }
                        elseif ($c['dias'] < 0) { $cls = 'red'; $txt = 'Vencida hace ' . (-$c['dias']) . ' d'; }
                        elseif ($c['dias'] <= 3) { $cls = 'amber'; $txt = $c['dias'] == 0 ? 'Vence hoy' : 'En ' . $c['dias'] . ' días'; }
                        else { $cls = ''; $txt = 'Vence ' . date('d/m', strtotime($c['vence'])); } ?>
                        <div class="esc-row-item">
                            <span class="nm"><?= h($c['proveedor']) ?></span>
                            <span class="esc-status <?= $cls ?>"><?= $txt ?></span>
                            <span class="mt"><?= sol0($c['saldo']) ?></span>
                        </div>
                    <?php endforeach; ?>
                    <?php if (!$cx['items']): ?><div class="esc-empty"><?= $vacio ?></div><?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </section>
        <?php endif; ?>
    </div>

    <!-- Top productos · Clientes · Perdidos -->
    <div class="esc-row">
        <section class="esc-card" style="flex:1 1 320px">
            <div class="esc-head">
                <span class="esc-chip" style="background:oklch(0.94 0.05 75);color:oklch(0.55 0.13 75)"><i class="ph-duotone ph-package"></i></span>
                <h2>Top productos</h2>
                <div class="esc-seg" id="segProd"><button class="on" data-k="monto">Monto</button><button data-k="cant">Cant.</button></div>
            </div>
            <div id="topProd"></div>
            <a class="esc-more" href="#" id="moreProd"></a>
        </section>

        <section class="esc-card" style="flex:1 1 360px">
            <div class="esc-head">
                <span class="esc-chip" style="background:oklch(0.94 0.05 230);color:oklch(0.55 0.13 230)"><i class="ph-duotone ph-users-three"></i></span>
                <h2>Clientes</h2>
                <div class="esc-seg" id="segCli"><button class="on" data-k="top">Más consumen</button><button data-k="freq">Recurrentes</button></div>
            </div>
            <div id="cliTop">
                <?php $max_cli = 0; foreach ($clientes_result as $c) $max_cli = max($max_cli, (float)$c['total_compras']);
                foreach (array_slice($clientes_result, 0, 6) as $k => $c): ?>
                    <div class="esc-rank">
                        <span class="n"><?= $k + 1 ?></span>
                        <div class="mid"><span class="nm"><?= h($c['nombre_cliente'] ?: 'SIN NOMBRE') ?></span>
                            <div class="tr"><i style="width:<?= $max_cli ? round($c['total_compras'] / $max_cli * 100) : 0 ?>%;background:oklch(0.62 0.12 230)"></i></div></div>
                        <span class="cn"><?= (int)$c['num_compras'] ?> compras</span>
                        <span class="vl"><?= sol0($c['total_compras']) ?></span>
                    </div>
                <?php endforeach; if (!$clientes_result): ?><div class="esc-empty">Sin datos este mes</div><?php endif; ?>
            </div>
            <div id="cliFreq" class="esc-hide">
                <?php foreach ($recurrentes as $c):
                    $ult = substr($c['ultima'], 0, 10);
                    $ult_txt = $ult === $hoy ? 'hoy' : date('d/m', strtotime($ult)); ?>
                    <div class="esc-rank">
                        <div class="mid"><span class="nm"><?= h($c['nombre']) ?></span>
                            <span class="cn">Última: <?= $ult_txt ?> · ticket <?= sol0($c['ticket']) ?></span></div>
                        <div class="esc-weeks" title="Últimas 8 semanas"><?php foreach ($c['semanas'] as $on): ?><i class="<?= $on ? 'on' : '' ?>"></i><?php endforeach; ?></div>
                        <span class="vl" style="color:oklch(0.5 0.14 230);font-weight:600"><?= $c['visitas_mes'] ?>/mes</span>
                    </div>
                <?php endforeach; if (!$recurrentes): ?><div class="esc-empty">Sin datos suficientes</div><?php endif; ?>
            </div>
        </section>

        <section class="esc-card" style="flex:1 1 360px">
            <div class="esc-head">
                <span class="esc-chip" style="background:oklch(0.94 0.05 25);color:oklch(0.58 0.18 25)"><i class="ph-duotone ph-user-minus"></i></span>
                <h2>Clientes que dejaron de venir</h2>
            </div>
            <div class="esc-note">Compraban 4+ veces al mes y llevan más de 2 semanas sin volver</div>
            <?php if ($perdidos['items']): ?>
            <div class="esc-risk"><span>Venta mensual en riesgo</span><b><?= sol0($perdidos['total_riesgo']) ?></b></div>
            <div>
                <?php foreach ($perdidos['items'] as $c):
                    $mxb = max(1, max(array_column($c['barras'], 'n'))); ?>
                    <div class="esc-lost">
                        <div class="mid">
                            <div class="nm"><?= h($c['nombre']) ?></div>
                            <div class="sb">Última compra <?= date('d/m', strtotime($c['ultima'])) ?> · <em>hace <?= $c['dias_sin'] ?> días</em> · <?= sol0($c['prom_mes']) ?>/mes</div>
                        </div>
                        <div class="esc-vbars" title="Visitas por mes">
                            <?php foreach ($c['barras'] as $k => $b):
                                $col = $b['n'] == 0 ? 'oklch(0.6 0.19 25)' : ($k >= 2 ? 'oklch(0.8 0.1 60)' : 'oklch(0.75 0.06 230)'); ?>
                                <i title="<?= $b['mes'] ?>: <?= $b['n'] ?>" style="height:<?= $b['n'] == 0 ? 2 : max(4, round($b['n'] / $mxb * 26)) ?>px;background:<?= $col ?>"></i>
                            <?php endforeach; ?>
                        </div>
                        <?php $tel = $c['telefono']; $tel = (strlen($tel) === 9) ? '51' . $tel : $tel; ?>
                        <a class="esc-wa <?= $tel ? '' : 'off' ?>" <?= $tel ? 'href="https://wa.me/' . h($tel) . '" target="_blank" rel="noopener"' : '' ?>><i class="ph ph-whatsapp-logo"></i> Contactar</a>
                    </div>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
                <div class="esc-empty">Ningún cliente frecuente se ha ausentado 🎉</div>
            <?php endif; ?>
        </section>
    </div>

    <!-- Inventario -->
    <div class="esc-row" id="inventario">
        <section class="esc-card" style="flex:1 1 440px">
            <div class="esc-head">
                <span class="esc-chip" style="background:oklch(0.94 0.05 25);color:oklch(0.58 0.18 25)"><i class="ph-duotone ph-trend-down"></i></span>
                <h2>Quiebre previsto</h2>
                <span class="esc-meta"><?= count($prediccion_data['prediccion']) ?> por agotarse</span>
            </div>
            <?php if (count($prediccion_data['agotados']) > 0): ?>
                <div class="esc-band"><?= count($prediccion_data['agotados']) ?> productos ya sin stock con ventas en los últimos 30 días</div>
            <?php endif; ?>
            <div>
                <?php foreach (array_slice($prediccion_stock, 0, 6) as $p): ?>
                    <div class="esc-row-item">
                        <span class="nm"><?= h($p['nombre'] ?: 'Sin nombre') ?></span>
                        <span class="mt"><?= rtrim(rtrim(number_format($p['stock_actual'], 2), '0'), '.') ?></span>
                        <span class="esc-status <?= $p['dias_restantes'] < 1 ? 'red' : 'amber' ?>"><?= h(formatear_dias_restantes($p['dias_restantes'])) ?></span>
                    </div>
                <?php endforeach; if (!$prediccion_stock): ?><div class="esc-empty">Sin riesgo de quiebre</div><?php endif; ?>
            </div>
            <a class="esc-more" href="reporte_prediccion_stock.php">Ver reporte completo →</a>
        </section>

        <section class="esc-card" style="flex:1 1 440px">
            <div class="esc-head">
                <span class="esc-chip" style="background:oklch(0.94 0.05 75);color:oklch(0.55 0.13 75)"><i class="ph-duotone ph-warning"></i></span>
                <h2>Stock bajo</h2>
                <span class="esc-meta"><?= count($productos_stock_bajo) ?> productos</span>
            </div>
            <div>
                <?php foreach (array_slice($productos_stock_bajo, 0, 7) as $p): ?>
                    <div class="esc-row-item">
                        <span class="nm"><?= h($p['nom_prod']) ?></span>
                        <span class="esc-stock <?= $p['stockp'] < 0.5 ? 'red' : '' ?>"><?= rtrim(rtrim(number_format($p['stockp'], 2), '0'), '.') ?></span>
                        <span class="mt" style="min-width:70px;text-align:right"><?= $p['precio_venta'] > 0 ? sol($p['precio_venta']) : '—' ?></span>
                    </div>
                <?php endforeach; if (!$productos_stock_bajo): ?><div class="esc-empty">Todo en orden</div><?php endif; ?>
            </div>
        </section>
    </div>

</main>

<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.3.1/jquery.min.js"></script>
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js"></script>
<script>
(function () {
    const D = <?= json_encode($js, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) ?>;
    const PROD = <?= json_encode(array_map(fn($p) => ['n' => $p['nom_prod'] ?: 'Sin nombre', 'c' => (int)$p['total_vendido'], 'm' => (float)$p['monto_total']], $productos_result), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) ?>;
    const $ = id => document.getElementById(id);
    const fmt = n => 'S/ ' + Math.round(n).toLocaleString('es-PE');
    const esc = s => String(s).replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
    const color = h => `oklch(0.7 0.12 ${h})`;
    const hoyIdx = D.dias.length - 1;
    let diaSel = hoyIdx, foco = null, chart7 = null;

    /* ── Detalle del día ── */
    function pintarDia() {
        const i = diaSel, tot = D.totales[i];
        $('dayTitle').textContent = i === hoyIdx ? 'Hoy · ' + D.nombres[i].toLowerCase() : D.nombres[i];
        $('dayTotal').textContent = 'S/ ' + tot.toLocaleString('es-PE', {minimumFractionDigits: 2, maximumFractionDigits: 2});
        const filas = D.vendedores.map(v => ({v, m: D.porDia[i][v.id] || 0})).filter(x => x.m > 0).sort((a, b) => b.m - a.m);
        $('dayList').innerHTML = filas.length ? filas.map(x => {
            const p = tot ? x.m / tot * 100 : 0;
            return `<div class="esc-bar-row"><div class="top"><i class="dot" style="background:${color(x.v.hue)}"></i><span class="nm">${esc(x.v.nombre)}</span><span class="mono">${fmt(x.m)}</span><span class="pc">${Math.round(p)}%</span></div><div class="esc-track"><i style="width:${p}%;background:${color(x.v.hue)}"></i></div></div>`;
        }).join('') : '<div class="esc-empty">Sin ventas ese día</div>';
    }

    /* ── Gráfica 7 días ── */
    if (D.vendedores.length) {
        const datasets = D.vendedores.map(v => ({
            label: v.nombre, vid: v.id, stack: 's', backgroundColor: color(v.hue),
            data: D.dias.map((_, i) => D.porDia[i][v.id] || 0),
            borderRadius: {topLeft: 5, topRight: 5}, borderSkipped: false, barPercentage: 0.8, maxBarThickness: 60,
        }));
        const plugin = {
            id: 'etiquetas',
            afterDatasetsDraw(c) {
                const {ctx} = c;
                const y = c.scales.y;
                D.totales.forEach((t, i) => {
                    if (!t) return;
                    const x = c.getDatasetMeta(0).data[i].x;
                    ctx.save();
                    ctx.font = (i === diaSel ? '600 ' : '500 ') + '11px "IBM Plex Mono", monospace';
                    ctx.fillStyle = i === hoyIdx ? '#c2361a' : '#57524d';
                    ctx.textAlign = 'center'; ctx.textBaseline = 'bottom';
                    ctx.fillText((t / 1000).toFixed(t >= 10000 ? 1 : 2).replace(/\.?0+$/, '') + 'k', x, y.getPixelForValue(t) - 5);
                    if (i === diaSel) {
                        const m = c.getDatasetMeta(0).data[i], w = m.width / 2 + 3;
                        ctx.strokeStyle = '#1f1d1b'; ctx.lineWidth = 2;
                        ctx.strokeRect(x - w, y.getPixelForValue(t) - 2, w * 2, y.getPixelForValue(0) - y.getPixelForValue(t) + 4);
                    }
                    ctx.restore();
                });
            }
        };
        chart7 = new Chart($('chart7'), {
            type: 'bar',
            data: {labels: D.labels, datasets},
            options: {
                responsive: true, maintainAspectRatio: false, layout: {padding: {top: 22}},
                interaction: {mode: 'index', intersect: false},
                plugins: {legend: {display: false}, tooltip: {enabled: false}},
                scales: {
                    x: {stacked: true, grid: {display: false}, border: {color: '#dcd8d3'},
                        ticks: {font: {size: 11.5, family: 'IBM Plex Sans'}, color: (c) => c.index === hoyIdx ? '#c2361a' : '#7a746e'}},
                    y: {stacked: true, grid: {color: '#ebe7e2', borderDash: [3, 3]}, border: {display: false},
                        ticks: {font: {size: 11, family: 'IBM Plex Mono'}, color: '#9a948d', callback: v => v >= 1000 ? (v / 1000) + 'k' : v}}
                },
                onHover: (e, _, c) => {
                    const pts = c.getElementsAtEventForMode(e, 'index', {intersect: false}, false);
                    if (pts.length && pts[0].index !== diaSel) { diaSel = pts[0].index; pintarDia(); c.draw(); }
                }
            },
            plugins: [plugin]
        });
        $('legend7').innerHTML = D.vendedores.map(v => `<button type="button" class="esc-pill" data-id="${v.id}"><i style="background:${color(v.hue)}"></i>${esc(v.nombre)}</button>`).join('');
        $('legend7').addEventListener('click', ev => {
            const b = ev.target.closest('.esc-pill'); if (!b) return;
            const id = +b.dataset.id; foco = foco === id ? null : id;
            document.querySelectorAll('#legend7 .esc-pill').forEach(p => p.classList.toggle('on', foco !== null && +p.dataset.id === foco));
            chart7.data.datasets.forEach(ds => { ds.backgroundColor = foco === null || ds.vid === foco ? color(D.vendedores.find(v => v.id === ds.vid).hue) : 'oklch(0.7 0.12 ' + D.vendedores.find(v => v.id === ds.vid).hue + ' / 0.18)'; });
            chart7.update('none');
        });
    } else {
        $('chart7').parentNode.innerHTML = '<div class="esc-empty">Sin ventas en los últimos 7 días</div>';
    }
    pintarDia();

    /* ── Medios de pago (dona) ── */
    if (D.medios.length && $('chartMedios')) {
        new Chart($('chartMedios'), {
            type: 'doughnut',
            data: {labels: D.medios.map(m => m.label), datasets: [{data: D.medios.map(m => m.monto), backgroundColor: D.medios.map(m => `oklch(0.7 0.13 ${m.hue})`), borderWidth: 0}]},
            options: {cutout: '66%', responsive: false, plugins: {legend: {display: false}, tooltip: {enabled: false}}}
        });
    }

    /* ── Top productos ── */
    let orden = 'monto', verTodos = false;
    function pintarProd() {
        const lista = PROD.slice().sort((a, b) => orden === 'monto' ? b.m - a.m : b.c - a.c);
        const mx = lista.length ? (orden === 'monto' ? lista[0].m : lista[0].c) : 1;
        const vis = verTodos ? lista : lista.slice(0, 6);
        $('topProd').innerHTML = vis.length ? vis.map((p, i) => {
            const v = orden === 'monto' ? p.m : p.c;
            return `<div class="esc-rank"><span class="n">${i + 1}</span><div class="mid"><span class="nm">${esc(p.n)}</span><div class="tr"><i style="width:${mx ? v / mx * 100 : 0}%;background:var(--primary)"></i></div></div><span class="vl">${orden === 'monto' ? fmt(p.m) : p.c.toLocaleString('es-PE') + ' u.'}</span></div>`;
        }).join('') : '<div class="esc-empty">Sin datos este mes</div>';
        $('moreProd').textContent = PROD.length > 6 ? (verTodos ? 'Ver menos' : 'Ver los ' + PROD.length + ' →') : '';
    }
    $('moreProd').addEventListener('click', e => { e.preventDefault(); verTodos = !verTodos; pintarProd(); });
    function seg(id, cb) {
        $(id).addEventListener('click', e => {
            const b = e.target.closest('button'); if (!b) return;
            $(id).querySelectorAll('button').forEach(x => x.classList.toggle('on', x === b)); cb(b.dataset.k);
        });
    }
    seg('segProd', k => { orden = k; pintarProd(); });
    seg('segCuentas', k => { $('cuentas-pagar').classList.toggle('esc-hide', k !== 'pagar'); $('cuentas-cobrar').classList.toggle('esc-hide', k !== 'cobrar'); });
    seg('segCli', k => { $('cliTop').classList.toggle('esc-hide', k !== 'top'); $('cliFreq').classList.toggle('esc-hide', k !== 'freq'); });
    pintarProd();
})();
</script>
</body>
</html>
