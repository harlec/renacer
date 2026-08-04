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

$metodos_validos = ['efectivo', 'yape', 'plin', 'bbva', 'yape_susan', 'tarjeta'];

$id_pago = intval($_POST['id_pago'] ?? 0);
$metodo_nuevo = $_POST['metodo'] ?? '';
$monto_nuevo = round(floatval($_POST['monto'] ?? 0), 2);
$usuario_id = intval($_SESSION['id_usr']);

if ($id_pago <= 0 || !in_array($metodo_nuevo, $metodos_validos, true) || $monto_nuevo <= 0) {
    echo json_encode(['ok' => false, 'mensaje' => 'Datos inválidos']);
    exit;
}

$conn = new mysqli('localhost', 'admin_renacer', 'ikm169uhn', 'admin_renacer');
$conn->set_charset('utf8');

if ($conn->connect_error) {
    echo json_encode(['ok' => false, 'mensaje' => 'DB: ' . $conn->connect_error]);
    exit;
}

$r = $conn->query("SELECT id_pago, venta, metodo, monto FROM venta_pagos WHERE id_pago = $id_pago");
$actual = $r ? $r->fetch_assoc() : null;

if (!$actual) {
    echo json_encode(['ok' => false, 'mensaje' => 'El pago no existe']);
    exit;
}
$venta_id = (int)$actual['venta'];

if ($actual['metodo'] === $metodo_nuevo && abs((float)$actual['monto'] - $monto_nuevo) < 0.001) {
    echo json_encode(['ok' => true]);
    exit;
}

// El total de la venta se calcula igual que en el resto del sistema (SUM de
// detalle_ventas.total), no desde ventas.total, para no desalinearse con el recibo.
$rt = $conn->query("SELECT COALESCE(SUM(total), 0) AS total FROM detalle_ventas WHERE venta = $venta_id");
$total_venta = round((float)($rt ? $rt->fetch_assoc()['total'] : 0), 2);

// Las demás líneas de pago de esta misma venta (todas menos la que se está editando),
// para revalidar que la suma siga cuadrando con el total una vez aplicado el cambio.
$hay_efectivo_otras = false;
$pagado_otras = 0;
$ro = $conn->query("SELECT metodo, monto FROM venta_pagos WHERE venta = $venta_id AND id_pago != $id_pago");
if ($ro) {
    while ($row = $ro->fetch_assoc()) {
        $pagado_otras = round($pagado_otras + (float)$row['monto'], 2);
        if ($row['metodo'] === 'efectivo') $hay_efectivo_otras = true;
    }
}

$nuevo_total_pagado = round($pagado_otras + $monto_nuevo, 2);
$diferencia = round($nuevo_total_pagado - $total_venta, 2);
$hay_efectivo = $hay_efectivo_otras || $metodo_nuevo === 'efectivo';

// Mismo margen de redondeo comercial que en inc/registrar_pago.php: en efectivo se
// tolera hasta 9 céntimos de más (no hay monedas para el vuelto exacto); en cualquier
// otro caso, o si el exceso es mayor, se rechaza para no inflar el total cobrado.
if ($diferencia > 0.01 && (!$hay_efectivo || $diferencia > 0.09)) {
    echo json_encode(['ok' => false, 'mensaje' => 'Ese monto (S/ ' . number_format($nuevo_total_pagado, 2) . ' en total) supera el total de la venta (S/ ' . number_format($total_venta, 2) . ') más del margen de redondeo permitido']);
    exit;
}

$metodo_nuevo_esc = $conn->real_escape_string($metodo_nuevo);
$ok = $conn->query("UPDATE venta_pagos SET metodo = '$metodo_nuevo_esc', monto = $monto_nuevo WHERE id_pago = $id_pago");

if (!$ok) {
    echo json_encode(['ok' => false, 'mensaje' => 'No se pudo actualizar: ' . $conn->error]);
    exit;
}

// Auditoría best-effort: si la tabla de logs no existe o falla, no debe bloquear
// la actualización del pago en sí, que ya quedó guardada arriba.
$observaciones = $conn->real_escape_string('Edición de pago desde caja_pagos.php (venta v-' . $venta_id . ')');
$datos_anteriores = $conn->real_escape_string(json_encode(['metodo' => $actual['metodo'], 'monto' => (float)$actual['monto']]));
$datos_nuevos = $conn->real_escape_string(json_encode(['metodo' => $metodo_nuevo, 'monto' => $monto_nuevo]));
$fecha_log = date('Y-m-d H:i:s');
$ip = $conn->real_escape_string($_SERVER['REMOTE_ADDR'] ?? '');
$conn->query("INSERT INTO log_ediciones (tabla_afectada, id_registro, accion, usuario_id, fecha_edicion, datos_anteriores, datos_nuevos, ip_usuario, observaciones)
              VALUES ('venta_pagos', $id_pago, 'EDIT', $usuario_id, '$fecha_log', '$datos_anteriores', '$datos_nuevos', '$ip', '$observaciones')");

$conn->close();
echo json_encode(['ok' => true]);
