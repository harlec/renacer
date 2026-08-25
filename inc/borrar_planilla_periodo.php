<?php
// Borra un periodo de planilla completo: sus descuentos, sus líneas de detalle por
// empleado, y el periodo en sí. Solo se permite mientras el periodo sigue 'abierto'
// (igual que inc/registrar_planilla_descuento.php bloquea agregar descuentos a un
// periodo 'cerrado').
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['id_usr'])) {
    echo json_encode(['respuesta' => false, 'mensaje' => 'Sin sesión']);
    exit;
}

$id_periodo = intval($_GET['id'] ?? 0);
if ($id_periodo <= 0) {
    echo json_encode(['respuesta' => false, 'mensaje' => 'Falta indicar el periodo']);
    exit;
}

$conn = new mysqli('localhost', 'admin_renacer', 'ikm169uhn', 'admin_renacer');
$conn->set_charset('utf8');

if ($conn->connect_error) {
    echo json_encode(['respuesta' => false, 'mensaje' => 'DB: ' . $conn->connect_error]);
    exit;
}

$r = $conn->query("SELECT estado FROM planilla_periodos WHERE id_periodo = $id_periodo");
$periodo = $r ? $r->fetch_assoc() : null;

if (!$periodo) {
    echo json_encode(['respuesta' => false, 'mensaje' => 'El periodo no existe']);
    exit;
}
if ($periodo['estado'] === 'cerrado') {
    echo json_encode(['respuesta' => false, 'mensaje' => 'Esta planilla ya está cerrada, no se puede borrar']);
    exit;
}

// Los movimientos (adelantos/abarrotes) que ya se habían aplicado a este periodo deben
// volver a quedar "pendientes" para que se apliquen a la siguiente planilla que los
// cubra — si no, quedan huérfanos apuntando a un id_detalle que ya no existe (se ve como
// fecha inválida en movimientos.php) y su descuento se pierde para siempre.
$rm = $conn->query("SELECT m.id_movimiento, m.id_venta FROM movimientos_empleado m
                     INNER JOIN planilla_detalle pd ON pd.id_detalle = m.id_detalle_aplicado
                     WHERE pd.id_periodo = $id_periodo");
if ($rm) {
    while ($m = $rm->fetch_assoc()) {
        // Si además se había marcado como pagada una venta vía planilla, revertir ese pago.
        if (!empty($m['id_venta'])) {
            $conn->query("DELETE FROM venta_pagos WHERE venta = " . (int)$m['id_venta'] . " AND metodo = 'planilla'");
        }
        $conn->query("UPDATE movimientos_empleado SET id_detalle_aplicado = NULL WHERE id_movimiento = " . (int)$m['id_movimiento']);
    }
}

$conn->query("DELETE pdesc FROM planilla_descuentos pdesc
              INNER JOIN planilla_detalle pd ON pd.id_detalle = pdesc.id_detalle
              WHERE pd.id_periodo = $id_periodo");
$conn->query("DELETE FROM planilla_detalle WHERE id_periodo = $id_periodo");
$conn->query("DELETE FROM planilla_periodos WHERE id_periodo = $id_periodo");

$ok = !$conn->error;
$conn->close();

echo json_encode(['respuesta' => $ok, 'mensaje' => $ok ? 'entro' : 'No se pudo borrar la planilla']);
