<?php
/**
 * Predicción de días de stock restante por producto.
 * Ventana móvil de $ventana_dias; productos con menos historial usan los días disponibles.
 */
function obtener_prediccion_stock($ventana_dias = 30, $umbral_urgente = 7, $umbral_atencion = 15) {
    $ventana_dias = (int)$ventana_dias;

    $sql = "
        SELECT
            p.id_producto,
            p.codigo_producto,
            p.nom_prod,
            p.stockp AS stock_actual,
            COALESCE(v30.unidades_30d, 0) AS unidades_vendidas,
            DATEDIFF(CURDATE(), hist.primera_venta) + 1 AS dias_con_historial
        FROM productos p
        LEFT JOIN (
            SELECT dv.producto, SUM(dv.cantidad) AS unidades_30d
            FROM detalle_ventas dv
            JOIN ventas v ON v.id_venta = dv.venta
            WHERE v.estado != '2'
              AND v.fecha >= DATE_SUB(CURDATE(), INTERVAL $ventana_dias DAY)
            GROUP BY dv.producto
        ) v30 ON v30.producto = p.id_producto
        LEFT JOIN (
            SELECT dv.producto, MIN(v.fecha) AS primera_venta
            FROM detalle_ventas dv
            JOIN ventas v ON v.id_venta = dv.venta
            WHERE v.estado != '2'
            GROUP BY dv.producto
        ) hist ON hist.producto = p.id_producto
        WHERE p.estado = '1'
    ";

    $rows = Sdba::db()->query($sql)->result();

    $prediccion = [];
    $sin_movimiento = [];
    $agotados = [];

    foreach ($rows as $r) {
        $stock_actual = round((float)$r['stock_actual'], 3);
        $unidades     = (float)$r['unidades_vendidas'];

        if ($unidades <= 0) {
            if ($stock_actual > 0 && $stock_actual < 10) {
                $sin_movimiento[] = [
                    'id_producto'      => (int)$r['id_producto'],
                    'codigo_producto'  => $r['codigo_producto'],
                    'nombre'           => $r['nom_prod'],
                    'stock_actual'     => $stock_actual,
                ];
            }
            continue;
        }

        // Ya se agotó: es reactivo (ya pasó), no predictivo. Se reporta aparte
        // para que no tape en el ranking a los productos que todavía se pueden salvar.
        if ($stock_actual <= 0) {
            $agotados[] = [
                'id_producto'       => (int)$r['id_producto'],
                'codigo_producto'   => $r['codigo_producto'],
                'nombre'            => $r['nom_prod'],
                'unidades_vendidas' => round($unidades, 3),
            ];
            continue;
        }

        $dias_con_historial = $r['dias_con_historial'] !== null
            ? max(1, min($ventana_dias, (int)$r['dias_con_historial']))
            : $ventana_dias;

        $velocidad_diaria = $unidades / $dias_con_historial;
        if ($velocidad_diaria <= 0) continue;

        $dias_restantes = round($stock_actual / $velocidad_diaria, 1);

        if ($dias_restantes <= $umbral_urgente) {
            $nivel = 'urgente';
        } elseif ($dias_restantes <= $umbral_atencion) {
            $nivel = 'atencion';
        } else {
            $nivel = 'normal';
        }

        $falta_manana     = round(max(0, $velocidad_diaria - $stock_actual), 2);
        $necesario_semana = round($velocidad_diaria * 7, 2);

        $prediccion[] = [
            'id_producto'      => (int)$r['id_producto'],
            'codigo_producto'  => $r['codigo_producto'],
            'nombre'           => $r['nom_prod'],
            'stock_actual'     => $stock_actual,
            'velocidad_diaria' => round($velocidad_diaria, 3),
            'dias_restantes'   => $dias_restantes,
            'nivel'            => $nivel,
            'falta_manana'     => $falta_manana,
            'necesario_semana' => $necesario_semana,
        ];
    }

    usort($prediccion, fn($a, $b) => $a['dias_restantes'] <=> $b['dias_restantes']);
    usort($agotados, fn($a, $b) => $b['unidades_vendidas'] <=> $a['unidades_vendidas']);

    return ['prediccion' => $prediccion, 'sin_movimiento' => $sin_movimiento, 'agotados' => $agotados];
}

/**
 * Convierte días restantes (float) en una frase corta y accionable, tomando en cuenta
 * el horario de atención de la tienda (7:00am - 1:00pm): si todavía se está vendiendo
 * hoy, el mensaje habla de "hoy"; si ya se cerró el turno, deja de importar si alcanza
 * para lo que resta del día y pasa a hablar de "mañana" (igual que antes de este cambio).
 */
function formatear_dias_restantes($dias, $hora_actual = null) {
    $hora_actual = $hora_actual !== null ? (int)$hora_actual : (int)date('G');
    $tienda_abierta = $hora_actual >= 7 && $hora_actual < 13;

    if ($tienda_abierta) {
        if ($dias < 1) return 'No alcanza para hoy';
        if ($dias < 2) return 'Alcanza para hoy';
        return 'Te queda para ' . (int)floor($dias) . ' días';
    }

    if ($dias < 1) return 'No alcanza para mañana';
    if ($dias < 2) return 'Alcanza solo para mañana';
    return 'Te queda para ' . (int)floor($dias) . ' días';
}
