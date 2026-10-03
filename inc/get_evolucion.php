<?php
// Evolución de ventas por mes o por semana, agrupada por categoría o por producto.
// Solo lectura. Misma convención que get_reporte_categorias.php: ventas no anuladas (estado != 2),
// fecha de la venta = v.fecha_ope, y categorías agrupadas por NOMBRE (la tabla categorias tiene duplicados).
ob_start();
ini_set('display_errors', '0');
error_reporting(0);
session_start();
ob_clean();
header('Content-Type: application/json');

if (!isset($_SESSION['id_usr']) || ($_SESSION['type'] ?? '') === 'operador') {
    echo json_encode(['ok' => false]);
    exit;
}

$conn = new mysqli('localhost', 'admin_renacer', 'ikm169uhn', 'admin_renacer');
$conn->set_charset('utf8');
if ($conn->connect_error) {
    echo json_encode(['ok' => false]);
    exit;
}

$agrupar = ($_GET['agrupar'] ?? 'categoria') === 'producto' ? 'producto' : 'categoria';
$gran    = ($_GET['gran'] ?? 'mes') === 'semana' ? 'semana' : 'mes';
$metrica = ($_GET['metrica'] ?? 'monto') === 'unidades' ? 'unidades' : 'monto';
$anio    = intval($_GET['anio'] ?? date('Y'));
if ($anio < 2000 || $anio > 2100) $anio = (int)date('Y');
$top     = max(1, min(20, intval($_GET['top'] ?? 5)));
$items   = array_values(array_filter(array_map('strval', (array)($_GET['items'] ?? [])), 'strlen'));

$ini = "$anio-01-01";
$fin = min("$anio-12-31", date('Y-m-d'));   // no se muestran periodos futuros
if ($fin < $ini) $fin = $ini;

// ---- Periodos (columnas) del rango
$claves = []; $labels = [];
if ($gran === 'mes') {
    $ultimo = (int)date('n', strtotime($fin));
    $meses = ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];
    for ($m = 1; $m <= $ultimo; $m++) { $claves[] = sprintf('%04d-%02d', $anio, $m); $labels[] = $meses[$m - 1]; }
    $expr = "DATE_FORMAT(v.fecha_ope, '%Y-%m')";
} else {
    // Semanas ISO (lunes a domingo): misma numeración que YEARWEEK(...,3) de MySQL.
    for ($t = strtotime($ini); $t <= strtotime($fin); $t += 86400) {
        $k = date('o', $t) . date('W', $t);
        if (!isset($labels[$k])) {
            $lunes = strtotime('monday this week', $t);
            $claves[] = $k;
            $labels[$k] = 'S' . date('W', $t) . ' · ' . date('d/m', $lunes);
        }
    }
    $labels = array_values($labels);
    $expr = "YEARWEEK(v.fecha_ope, 3)";
}
$pos = array_flip($claves);

// ---- Catálogo producto -> (nombre, categoría por nombre)
$nombres_cat = [];
$rc = $conn->query("SELECT DISTINCT id_categoria, nom_cat FROM categorias");
while ($rc && $x = $rc->fetch_assoc()) $nombres_cat[$x['id_categoria']] = $x['nom_cat'];
$prod = [];
$rp = $conn->query("SELECT id_producto, nom_prod, categoria FROM productos");
while ($rp && $x = $rp->fetch_assoc()) {
    $prod[(int)$x['id_producto']] = [
        'nombre' => $x['nom_prod'],
        'cat'    => ($x['categoria'] !== null && isset($nombres_cat[$x['categoria']])) ? $nombres_cat[$x['categoria']] : 'Sin categoría',
    ];
}

// ---- Ventas por producto y periodo (la agrupación por categoría se hace después, en PHP,
//      para no multiplicar filas por los duplicados de la tabla categorias)
$ini_s = $conn->real_escape_string($ini);
$fin_s = $conn->real_escape_string(date('Y-m-d', strtotime($fin . ' +1 day')));
$r = $conn->query("
    SELECT dv.producto AS pid, $expr AS clave, SUM(dv.cantidad) AS unidades, SUM(dv.total) AS monto
    FROM detalle_ventas dv
    JOIN ventas v ON v.id_venta = dv.venta
    WHERE v.estado != '2' AND v.fecha_ope >= '$ini_s' AND v.fecha_ope < '$fin_s'
    GROUP BY dv.producto, clave");

$n = count($claves);
$series = [];
while ($r && $x = $r->fetch_assoc()) {
    $pid = (int)$x['pid'];
    $i = $pos[$x['clave']] ?? null;
    if ($i === null) continue;
    if ($agrupar === 'producto') {
        $key = (string)$pid;
        $nom = $prod[$pid]['nombre'] ?? ('Producto #' . $pid);
    } else {
        $nom = $prod[$pid]['cat'] ?? 'Sin categoría';
        $key = $nom;
    }
    if (!isset($series[$key])) {
        $series[$key] = ['id' => $key, 'nombre' => $nom, 'unidades' => array_fill(0, $n, 0), 'montos' => array_fill(0, $n, 0),
                         'total_unidades' => 0, 'total_monto' => 0];
    }
    $u = (float)$x['unidades']; $m = (float)$x['monto'];
    $series[$key]['unidades'][$i] += $u;
    $series[$key]['montos'][$i]   += $m;
    $series[$key]['total_unidades'] += $u;
    $series[$key]['total_monto']    += $m;
}

$campo_total = $metrica === 'monto' ? 'total_monto' : 'total_unidades';
uasort($series, function ($a, $b) use ($campo_total) { return $b[$campo_total] <=> $a[$campo_total]; });

// Opciones para elegir qué comparar (todo lo que tuvo ventas en el año, ordenado por la métrica)
$opciones = [];
foreach ($series as $s) $opciones[] = ['id' => $s['id'], 'nombre' => $s['nombre'], 'total' => round($s[$campo_total], 2)];

// Qué se devuelve: los elegidos, o el top N por la métrica
if ($items) {
    $sel = [];
    foreach ($items as $k) if (isset($series[$k])) $sel[$k] = $series[$k];
    // el orden de la tabla/gráfica sigue siendo por la métrica, no por el orden de selección
} else {
    $sel = array_slice($series, 0, $top, true);
}

$out = [];
foreach ($sel as $s) {
    $s['unidades'] = array_map(function ($v) { return round($v, 2); }, $s['unidades']);
    $s['montos']   = array_map(function ($v) { return round($v, 2); }, $s['montos']);
    $s['total_unidades'] = round($s['total_unidades'], 2);
    $s['total_monto']    = round($s['total_monto'], 2);
    $out[] = $s;
}

echo json_encode([
    'ok' => true, 'labels' => $labels, 'series' => $out, 'opciones' => $opciones,
    'agrupar' => $agrupar, 'gran' => $gran, 'metrica' => $metrica, 'anio' => $anio,
], JSON_UNESCAPED_UNICODE);
$conn->close();
