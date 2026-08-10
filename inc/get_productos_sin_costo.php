<?php
ob_start();
ini_set('display_errors', '0');
error_reporting(0);
session_start();
ob_clean();
header('Content-Type: application/json');

if (!isset($_SESSION['id_usr'])) {
    echo json_encode(['ok' => false, 'data' => [], 'resumen' => []]);
    exit;
}

$conn = new mysqli('localhost', 'admin_renacer', 'ikm169uhn', 'admin_renacer');
$conn->set_charset('utf8');

if ($conn->connect_error) {
    echo json_encode(['ok' => false, 'data' => [], 'resumen' => []]);
    exit;
}

// "Sin costo" = precio_compra NULL, vacío, o no-numérico-positivo (la columna es VARCHAR(45)
// libre, confirmado en producción — ver Prompt 0 del análisis de márgenes).
$condicion_sin_costo = "(p.precio_compra IS NULL OR TRIM(p.precio_compra) = '' OR CAST(p.precio_compra AS DECIMAL(12,4)) <= 0)";

// Venta total de la empresa en el año en curso, para el indicador de "% del total".
$rt = $conn->query("
    SELECT COALESCE(SUM(dv.total), 0) AS total
    FROM detalle_ventas dv
    JOIN ventas v ON v.id_venta = dv.venta
    WHERE v.estado != '2' AND YEAR(v.fecha_ope) = YEAR(CURDATE())
");
$venta_total_empresa = round((float)($rt ? $rt->fetch_assoc()['total'] : 0), 2);

// Productos sin costo, con su venta/unidades acumuladas del año. El LEFT JOIN a ventas
// lleva el filtro de año/estado en el ON (no en el WHERE) para no perder productos sin
// ninguna venta este año; por eso la suma es condicional a que el JOIN haya encontrado
// una venta real (si no, arrastraría ventas de años anteriores completos).
$sql = "
    SELECT p.id_producto, p.nom_prod, COALESCE(c.nom_cat, 'Sin categoría') AS categoria,
           p.precio_venta,
           COALESCE(SUM(CASE WHEN v.id_venta IS NOT NULL THEN dv.total ELSE 0 END), 0) AS venta_anual,
           COALESCE(SUM(CASE WHEN v.id_venta IS NOT NULL THEN dv.cantidad ELSE 0 END), 0) AS unidades_anual
    FROM productos p
    LEFT JOIN categorias c ON c.id_categoria = p.categoria
    LEFT JOIN detalle_ventas dv ON dv.producto = p.id_producto
    LEFT JOIN ventas v ON v.id_venta = dv.venta AND v.estado != '2' AND YEAR(v.fecha_ope) = YEAR(CURDATE())
    WHERE p.estado = '1' AND $condicion_sin_costo
    GROUP BY p.id_producto, p.nom_prod, categoria, p.precio_venta
    ORDER BY venta_anual DESC
";

$result = $conn->query($sql);

$filas = [];
$venta_total_sin_costo = 0;
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $venta_anual = round((float)$row['venta_anual'], 2);
        $filas[] = [
            'producto'   => strtoupper($row['nom_prod']),
            'categoria'  => $row['categoria'],
            'venta'      => $venta_anual,
            'unidades'   => round((float)$row['unidades_anual'], 2),
            'precio'     => round((float)$row['precio_venta'], 2),
        ];
        $venta_total_sin_costo += $venta_anual;
    }
}
$conn->close();

// Columna de Pareto: % acumulado DENTRO del grupo "sin costo" (no del total de la empresa),
// para identificar cuántos de estos productos hay que resolver para tapar el 90% del hueco.
$acumulado = 0;
$productos_para_90 = 0;
$alcanzo_90 = false;
$data = [];
foreach ($filas as $f) {
    $acumulado += $f['venta'];
    $pct_acumulado = $venta_total_sin_costo > 0 ? round(100 * $acumulado / $venta_total_sin_costo, 1) : 0;
    // en_top_90 se calcula ANTES de incrementar productos_para_90 con esta fila, para que
    // el "punto de corte" (la fila que hace cruzar el 90%) también quede marcada como parte
    // del grupo a resolver, no la siguiente.
    $en_top_90 = !$alcanzo_90;
    if (!$alcanzo_90) {
        $productos_para_90++;
        if ($pct_acumulado >= 90) $alcanzo_90 = true;
    }
    // El flag va como último valor de la fila (columna oculta en la tabla), calculado una
    // sola vez server-side en el orden real por venta — así el resaltado no se rompe si el
    // usuario reordena o filtra la tabla en el navegador.
    $data[] = [$f['producto'], $f['categoria'], $f['venta'], $f['unidades'], $f['precio'], $pct_acumulado, $en_top_90 ? 1 : 0];
}
// Si ninguna fila llegó a 90% (venta_total_sin_costo = 0, todas en 0), no marcar ninguna.
if (!$alcanzo_90) $productos_para_90 = 0;

echo json_encode([
    'ok' => true,
    'data' => $data,
    'resumen' => [
        'cantidad_sin_costo'    => count($filas),
        'venta_sin_costo'       => round($venta_total_sin_costo, 2),
        'pct_del_total'         => $venta_total_empresa > 0 ? round(100 * $venta_total_sin_costo / $venta_total_empresa, 1) : 0,
        'productos_para_90'     => $productos_para_90,
    ],
]);
