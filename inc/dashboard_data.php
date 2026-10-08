<?php
// Datos del Escritorio (dashboard.php). Solo lectura.
// Convención: ventas no anuladas (estado != '2'). Requiere inc/sdba/sdba.php cargado.

function dash_q($sql) {
    return Sdba::db()->query($sql)->result();
}

// Tono estable por vendedor (hue oklch) a partir de su id.
function dash_hue_vendedor($id) {
    static $hues = [25, 65, 215, 145, 295, 260, 340, 190, 100];
    return $hues[((int)$id) % count($hues)];
}

// Balance del mes, igual que balance.php pero solo con ventas y compras (aún sin gastos, planillas ni costos):
// entra = cobros de ventas (venta_pagos), sale = pagos a proveedores (compra_pagos).
function dash_balance($mes) {
    $r = dash_q("SELECT SUM(vp.monto) AS m FROM venta_pagos vp INNER JOIN ventas v ON v.id_venta = vp.venta
        WHERE v.estado != '2' AND vp.metodo != 'planilla' AND DATE_FORMAT(vp.fecha, '%Y-%m') = '$mes'");
    $ventas = (float)($r[0]['m'] ?? 0);

    $r = dash_q("SELECT SUM(cp.monto) AS m FROM compra_pagos cp INNER JOIN compras c ON c.id_compra = cp.compra
        WHERE c.estado != '2' AND DATE_FORMAT(cp.fecha, '%Y-%m') = '$mes'");
    $compras = (float)($r[0]['m'] ?? 0);

    return ['ventas' => $ventas, 'compras' => $compras, 'saldo' => $ventas - $compras];
}

function dash_medios_pago($mes) {
    $rows = dash_q("SELECT vp.metodo, SUM(vp.monto) AS monto
        FROM venta_pagos vp INNER JOIN ventas v ON v.id_venta = vp.venta
        WHERE v.estado != '2' AND vp.metodo != 'planilla' AND DATE_FORMAT(vp.fecha, '%Y-%m') = '$mes'
        GROUP BY vp.metodo ORDER BY monto DESC");
    $total = 0;
    foreach ($rows as $r) $total += (float)$r['monto'];
    $out = [];
    foreach ($rows as $r) {
        $out[] = ['metodo' => $r['metodo'], 'monto' => (float)$r['monto'], 'pct' => $total > 0 ? round($r['monto'] / $total * 100) : 0];
    }
    return ['total' => $total, 'items' => $out];
}

function dash_cuentas_por_pagar() {
    $rows = dash_q("SELECT c.id_compra, c.total, c.fecha_compromiso_pago AS vence, p.proveedor,
            c.total - COALESCE(cp.pagado, 0) AS saldo
        FROM compras c
        LEFT JOIN proveedores p ON p.id_proveedor = c.proveedor
        LEFT JOIN (SELECT compra, SUM(monto) AS pagado FROM compra_pagos GROUP BY compra) cp ON cp.compra = c.id_compra
        WHERE c.estado != '2'
        HAVING saldo > 0.01
        ORDER BY (c.fecha_compromiso_pago IS NULL), c.fecha_compromiso_pago ASC");
    $hoy = strtotime(date('Y-m-d'));
    $pendiente = $vencido = 0;
    $n_venc = 0;
    $items = [];
    foreach ($rows as $r) {
        $saldo = (float)$r['saldo'];
        $pendiente += $saldo;
        $dias = $r['vence'] ? (int)round((strtotime($r['vence']) - $hoy) / 86400) : null;
        if ($dias !== null && $dias < 0) { $vencido += $saldo; $n_venc++; }
        $items[] = ['proveedor' => $r['proveedor'] ?: 'Sin proveedor', 'saldo' => $saldo, 'vence' => $r['vence'], 'dias' => $dias];
    }
    return ['pendiente' => $pendiente, 'vencido' => $vencido, 'n_vencidas' => $n_venc, 'items' => array_slice($items, 0, 5), 'total_items' => count($items)];
}

// Ventas a crédito con saldo pendiente (mismo criterio que inc/get_ventas_credito.php).
function dash_cuentas_por_cobrar() {
    $rows = dash_q("SELECT v.id_venta, v.fecha_compromiso_pago AS vence, c.cliente AS nombre,
            COALESCE(SUM(dv.total), 0) AS total_real, COALESCE(MAX(vp.pagado), 0) AS pagado
        FROM ventas v
        LEFT JOIN detalle_ventas dv ON dv.venta = v.id_venta
        LEFT JOIN clientes c ON c.id_cliente = v.cliente
        LEFT JOIN (SELECT venta, SUM(monto) AS pagado FROM venta_pagos GROUP BY venta) vp ON vp.venta = v.id_venta
        WHERE v.estado != '2' AND v.id_empleado IS NULL AND v.fecha_compromiso_pago IS NOT NULL
        GROUP BY v.id_venta
        HAVING total_real - pagado > 0.01
        ORDER BY v.fecha_compromiso_pago ASC");
    $hoy = strtotime(date('Y-m-d'));
    $pendiente = $vencido = 0;
    $n_venc = 0;
    $items = [];
    foreach ($rows as $r) {
        $saldo = round((float)$r['total_real'] - (float)$r['pagado'], 2);
        $pendiente += $saldo;
        $dias = (int)round((strtotime($r['vence']) - $hoy) / 86400);
        if ($dias < 0) { $vencido += $saldo; $n_venc++; }
        $items[] = ['proveedor' => $r['nombre'] ?: 'Sin cliente', 'saldo' => $saldo, 'vence' => $r['vence'], 'dias' => $dias];
    }
    return ['pendiente' => $pendiente, 'vencido' => $vencido, 'n_vencidas' => $n_venc, 'items' => array_slice($items, 0, 5), 'total_items' => count($items)];
}

// Clientes genéricos que no son personas reales.
const DASH_CLIENTES_GENERICOS = "('VARIOS','FACTURA MANUAL','HUEVOS - VARIOS','HUEVOS-VARIOS')";

function dash_clientes_recurrentes() {
    $rows = dash_q("SELECT v.cliente AS id, c.cliente AS nombre,
            COUNT(DISTINCT DATE(v.fecha)) AS dias, AVG(v.total) AS ticket, MAX(v.fecha) AS ultima
        FROM ventas v INNER JOIN clientes c ON c.id_cliente = v.cliente
        WHERE v.estado != '2' AND v.cliente > 0 AND DATE(v.fecha) >= DATE_SUB(CURDATE(), INTERVAL 90 DAY)
          AND UPPER(c.cliente) NOT IN " . DASH_CLIENTES_GENERICOS . "
        GROUP BY v.cliente HAVING dias >= 3
        ORDER BY dias DESC, ticket DESC LIMIT 6");
    if (!$rows) return [];
    $ids = implode(',', array_map(fn($r) => (int)$r['id'], $rows));
    // Semana (0 = más antigua, 7 = actual) en que hubo compra, últimas 8 semanas.
    $sem = dash_q("SELECT cliente, FLOOR(DATEDIFF(CURDATE(), DATE(fecha)) / 7) AS atras
        FROM ventas WHERE estado != '2' AND cliente IN ($ids) AND DATE(fecha) >= DATE_SUB(CURDATE(), INTERVAL 56 DAY)
        GROUP BY cliente, atras");
    $semanas = [];
    foreach ($sem as $s) $semanas[(int)$s['cliente']][7 - (int)$s['atras']] = true;

    $out = [];
    foreach ($rows as $r) {
        $celdas = [];
        for ($i = 0; $i < 8; $i++) $celdas[] = !empty($semanas[(int)$r['id']][$i]);
        $out[] = [
            'nombre' => $r['nombre'], 'visitas_mes' => round($r['dias'] / 3, 1), 'ticket' => (float)$r['ticket'],
            'ultima' => $r['ultima'], 'semanas' => $celdas,
        ];
    }
    return $out;
}

function dash_clientes_perdidos() {
    // Ventana base: días 31 a 120 atrás (3 meses previos a los últimos 30 días).
    $rows = dash_q("SELECT v.cliente AS id, c.cliente AS nombre, c.telefono,
            COUNT(DISTINCT DATE(v.fecha)) AS dias, SUM(v.total) AS gasto
        FROM ventas v INNER JOIN clientes c ON c.id_cliente = v.cliente
        WHERE v.estado != '2' AND v.cliente > 0
          AND DATE(v.fecha) BETWEEN DATE_SUB(CURDATE(), INTERVAL 120 DAY) AND DATE_SUB(CURDATE(), INTERVAL 31 DAY)
          AND UPPER(c.cliente) NOT IN " . DASH_CLIENTES_GENERICOS . "
        GROUP BY v.cliente HAVING dias >= 12");
    if (!$rows) return ['total_riesgo' => 0, 'items' => []];
    $ids = implode(',', array_map(fn($r) => (int)$r['id'], $rows));
    $ult = dash_q("SELECT cliente, MAX(fecha) AS ultima FROM ventas WHERE estado != '2' AND cliente IN ($ids) GROUP BY cliente");
    $ultima = [];
    foreach ($ult as $u) $ultima[(int)$u['cliente']] = $u['ultima'];
    $meses = [];
    $vm = dash_q("SELECT cliente, DATE_FORMAT(fecha, '%Y-%m') AS m, COUNT(DISTINCT DATE(fecha)) AS n
        FROM ventas WHERE estado != '2' AND cliente IN ($ids) AND fecha >= DATE_SUB(DATE_FORMAT(CURDATE(), '%Y-%m-01'), INTERVAL 3 MONTH)
        GROUP BY cliente, m");
    foreach ($vm as $x) $meses[(int)$x['cliente']][$x['m']] = (int)$x['n'];

    $claves = [];
    for ($i = 3; $i >= 0; $i--) $claves[] = date('Y-m', strtotime(date('Y-m-01') . " -$i months"));
    $nom_mes = ['01'=>'ene','02'=>'feb','03'=>'mar','04'=>'abr','05'=>'may','06'=>'jun','07'=>'jul','08'=>'ago','09'=>'sep','10'=>'oct','11'=>'nov','12'=>'dic'];

    $hoy = strtotime(date('Y-m-d'));
    $out = [];
    foreach ($rows as $r) {
        $id = (int)$r['id'];
        $dias_sin = $hoy - strtotime(substr($ultima[$id] ?? '1970-01-01', 0, 10));
        $dias_sin = (int)round($dias_sin / 86400);
        if ($dias_sin <= 14) continue;
        $barras = [];
        foreach ($claves as $k) $barras[] = ['mes' => $nom_mes[substr($k, 5, 2)], 'n' => $meses[$id][$k] ?? 0];
        $out[] = [
            'nombre' => $r['nombre'], 'telefono' => preg_replace('/\D+/', '', (string)$r['telefono']),
            'ultima' => $ultima[$id], 'dias_sin' => $dias_sin, 'prom_mes' => (float)$r['gasto'] / 3, 'barras' => $barras,
        ];
    }
    usort($out, fn($a, $b) => $b['prom_mes'] <=> $a['prom_mes']);
    $out = array_slice($out, 0, 5);
    $riesgo = 0;
    foreach ($out as $o) $riesgo += $o['prom_mes'];
    return ['total_riesgo' => $riesgo, 'items' => $out];
}

// Ventas de los últimos 7 días por vendedor + KPI auxiliares.
function dash_ventas_7d() {
    $dias = [];
    for ($i = 6; $i >= 0; $i--) $dias[] = date('Y-m-d', strtotime("-$i days"));
    $rows = dash_q("SELECT DATE(v.fecha) AS dia, v.usuario AS uid, COALESCE(u.nombres, 'Sin nombre') AS nombre, SUM(v.total) AS monto
        FROM ventas v LEFT JOIN usuarios u ON v.usuario = u.id_usuario
        WHERE DATE(v.fecha) >= DATE_SUB(CURDATE(), INTERVAL 6 DAY) AND v.estado != '2'
        GROUP BY DATE(v.fecha), v.usuario ORDER BY nombre");
    $vend = [];
    $por_dia = array_fill_keys($dias, []);
    $totales = array_fill_keys($dias, 0);
    foreach ($rows as $r) {
        $uid = (int)$r['uid'];
        $vend[$uid] = ['id' => $uid, 'nombre' => $r['nombre'], 'hue' => dash_hue_vendedor($uid)];
        $por_dia[$r['dia']][$uid] = (float)$r['monto'];
        $totales[$r['dia']] += (float)$r['monto'];
    }
    return ['dias' => $dias, 'vendedores' => array_values($vend), 'por_dia' => $por_dia, 'totales' => $totales];
}

function dash_sin_stock() {
    $r = dash_q("SELECT COUNT(*) AS n FROM productos WHERE estado = '1' AND stockp <= 0");
    return (int)($r[0]['n'] ?? 0);
}
