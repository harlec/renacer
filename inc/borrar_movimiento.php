<?php
// Solo se puede borrar un movimiento mientras siga pendiente (no aplicado a ninguna
// planilla todavía) - una vez aplicado, se borra como cualquier otro descuento desde
// ver_planilla_detalle.php (inc/borrar_planilla_descuento.php).
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['id_usr'])) {
    echo json_encode(['ok' => false, 'mensaje' => 'Sin sesión']);
    exit;
}

$id = intval($_GET['id'] ?? 0);
if ($id <= 0) {
    echo json_encode(['ok' => false, 'mensaje' => 'Falta indicar el movimiento']);
    exit;
}

$conn = new mysqli('localhost', 'admin_renacer', 'ikm169uhn', 'admin_renacer');
$conn->set_charset('utf8');

if ($conn->connect_error) {
    echo json_encode(['ok' => false, 'mensaje' => 'DB: ' . $conn->connect_error]);
    exit;
}

$r = $conn->query("SELECT id_detalle_aplicado, id_venta FROM movimientos_empleado WHERE id_movimiento = $id");
$mov = $r ? $r->fetch_assoc() : null;

if (!$mov) {
    echo json_encode(['ok' => false, 'mensaje' => 'El movimiento no existe']);
    exit;
}
if ($mov['id_detalle_aplicado'] !== null) {
    echo json_encode(['ok' => false, 'mensaje' => 'Este movimiento ya se aplicó a una planilla; bórralo desde el detalle de esa planilla']);
    exit;
}
if ($mov['id_venta'] !== null) {
    echo json_encode(['ok' => false, 'mensaje' => 'Este movimiento viene de una venta real; anúlala desde ver_venta.php en vez de borrar el movimiento']);
    exit;
}

$conn->query("DELETE FROM movimientos_empleado WHERE id_movimiento = $id");
$ok = !$conn->error;
$conn->close();

echo json_encode(['ok' => $ok]);
