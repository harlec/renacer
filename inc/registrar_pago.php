<?php
ob_start();
ini_set('display_errors', '0');
error_reporting(0);
session_start();
ob_clean();
header('Content-Type: application/json');

if (!isset($_SESSION['id_usr'])) {
    echo json_encode(['ok' => false, 'mensaje' => 'Sin sesión']);
    exit;
}

$conn = new mysqli('localhost', 'admin_renacer', 'ikm169uhn', 'admin_renacer');
$conn->set_charset('utf8');

if ($conn->connect_error) {
    echo json_encode(['ok' => false, 'mensaje' => 'DB: ' . $conn->connect_error]);
    exit;
}

$ventas_ids = json_decode($_POST['ventas'] ?? '[]', true);
$pagos      = json_decode($_POST['pagos'] ?? '[]', true);
$usuario_id = intval($_SESSION['id_usr']);
$metodos_validos = ['efectivo', 'yape', 'plin', 'tarjeta'];

if (!is_array($ventas_ids) || count($ventas_ids) === 0 || !is_array($pagos) || count($pagos) === 0) {
    echo json_encode(['ok' => false, 'mensaje' => 'Datos incompletos']);
    exit;
}
$ventas_ids = array_values(array_unique(array_map('intval', $ventas_ids)));
if (in_array(0, $ventas_ids, true)) {
    echo json_encode(['ok' => false, 'mensaje' => 'Datos incompletos']);
    exit;
}

// El total de cada venta se calcula igual que el recibo (SUM de detalle_ventas.total),
// no desde ventas.total, para que nunca se le pida cobrar al cajero un monto distinto al impreso.
$ids_sql = implode(',', $ventas_ids);
$r = $conn->query("
    SELECT v.id_venta,
           COALESCE(SUM(dv.total), 0) AS total,
           COALESCE((SELECT SUM(monto) FROM venta_pagos WHERE venta = v.id_venta), 0) AS pagado
    FROM ventas v
    LEFT JOIN detalle_ventas dv ON dv.venta = v.id_venta
    WHERE v.id_venta IN ($ids_sql)
    GROUP BY v.id_venta
");
$saldos = [];
if ($r) {
    while ($row = $r->fetch_assoc()) {
        $saldos[(int)$row['id_venta']] = round((float)$row['total'] - (float)$row['pagado'], 2);
    }
}
if (count($saldos) !== count($ventas_ids)) {
    echo json_encode(['ok' => false, 'mensaje' => 'Una o más ventas no existen']);
    exit;
}
foreach ($saldos as $s) {
    if ($s <= 0.01) {
        echo json_encode(['ok' => false, 'mensaje' => 'Una de las ventas ya no tiene saldo pendiente (puede que ya la hayan cobrado)']);
        exit;
    }
}

$saldo_combinado = round(array_sum($saldos), 2);
$suma_nueva = 0;

foreach ($pagos as $p) {
    $metodo = $p['metodo'] ?? '';
    $monto  = round(floatval($p['monto'] ?? 0), 2);
    if (!in_array($metodo, $metodos_validos, true) || $monto <= 0) {
        echo json_encode(['ok' => false, 'mensaje' => 'Método o monto inválido']);
        exit;
    }
    $suma_nueva += $monto;
}

if (abs($suma_nueva - $saldo_combinado) > 0.01) {
    echo json_encode(['ok' => false, 'mensaje' => 'La suma de los pagos (S/ ' . number_format($suma_nueva, 2) . ') no cubre el saldo pendiente (S/ ' . number_format($saldo_combinado, 2) . ')']);
    exit;
}

// Repartir cada línea de pago entre las ventas seleccionadas, proporcional a lo que
// debía cada una. La última venta de cada línea absorbe el residuo de redondeo para
// que la suma cuadre exacto. Con una sola venta esto se reduce al caso simple de siempre.
$filas = [];
foreach ($ventas_ids as $id) $filas[$id] = [];

foreach ($pagos as $p) {
    $monto_linea = round(floatval($p['monto']), 2);
    $restante = $monto_linea;
    $n = count($ventas_ids);
    foreach ($ventas_ids as $i => $id) {
        if ($i === $n - 1) {
            $monto_asignado = max(0, round($restante, 2));
        } else {
            $proporcion = $saldos[$id] / $saldo_combinado;
            $monto_asignado = round($monto_linea * $proporcion, 2);
            $restante = round($restante - $monto_asignado, 2);
        }
        if ($monto_asignado > 0.001) {
            $filas[$id][] = ['metodo' => $p['metodo'], 'monto' => $monto_asignado];
        }
    }
}

$conn->begin_transaction();
try {
    $fecha = date('Y-m-d H:i:s');
    foreach ($filas as $venta_id => $lineas_venta) {
        foreach ($lineas_venta as $l) {
            $metodo = $conn->real_escape_string($l['metodo']);
            $monto  = round($l['monto'], 2);
            $conn->query("INSERT INTO venta_pagos (venta, metodo, monto, usuario, fecha) VALUES ($venta_id, '$metodo', $monto, $usuario_id, '$fecha')");
        }
    }
    $conn->commit();
    echo json_encode(['ok' => true]);
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['ok' => false, 'mensaje' => 'Error al registrar el pago']);
}
