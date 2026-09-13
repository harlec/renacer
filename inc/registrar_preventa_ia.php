<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['ingress']) || $_SESSION['ingress'] !== true) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'mensaje' => 'Sesión no válida']);
    exit;
}

$conn = new mysqli('localhost', 'admin_renacer', 'ikm169uhn', 'admin_renacer');
$conn->set_charset('utf8');

$clienteNombre = strtoupper(trim($_POST['cliente'] ?? ''));
$productos  = $_POST['producto_id'] ?? [];
$cantidades = $_POST['cantidad'] ?? [];
$textoIa    = $_POST['texto_ia'] ?? '';

if ($clienteNombre === '') {
    echo json_encode(['ok' => false, 'mensaje' => 'Indica el cliente del pedido']);
    exit;
}

$items = [];
for ($i = 0; $i < count($productos); $i++) {
    $pid  = (int)$productos[$i];
    $cant = round((float)($cantidades[$i] ?? 0), 3);
    if ($pid > 0 && $cant > 0) {
        $items[] = ['producto' => $pid, 'cantidad' => $cant];
    }
}

if (empty($items)) {
    echo json_encode(['ok' => false, 'mensaje' => 'El pedido está vacío o no tiene productos válidos asignados']);
    exit;
}

// Solo aceptar productos que realmente existen y están activos.
$ids = array_unique(array_column($items, 'producto'));
$idsStr = implode(',', array_map('intval', $ids));
$validos = [];
$r = $conn->query("SELECT id_producto FROM productos WHERE estado = '1' AND id_producto IN ($idsStr)");
while ($row = $r->fetch_assoc()) {
    $validos[(int)$row['id_producto']] = true;
}
$items = array_values(array_filter($items, function ($it) use ($validos) {
    return isset($validos[$it['producto']]);
}));

if (empty($items)) {
    echo json_encode(['ok' => false, 'mensaje' => 'Ninguno de los productos asignados es válido']);
    exit;
}

$conn->begin_transaction();
try {
    $cliente_safe = $conn->real_escape_string($clienteNombre);
    $rc = $conn->query("SELECT id_cliente FROM clientes WHERE UPPER(TRIM(cliente)) = UPPER('$cliente_safe') LIMIT 1");
    $cl = $rc ? $rc->fetch_assoc() : null;
    if ($cl) {
        $id_cliente = $cl['id_cliente'];
    } else {
        $conn->query("INSERT INTO clientes (cliente, estado) VALUES ('$cliente_safe', '1')");
        $id_cliente = $conn->insert_id;
    }

    $texto_safe = $conn->real_escape_string($textoIa);
    $conn->query("INSERT INTO preventa (cliente, fecha, estado, origen, texto_ia) VALUES ($id_cliente, NOW(), '0', 'ia', '$texto_safe')");
    $id_preventa = $conn->insert_id;
    if (!$id_preventa) {
        throw new Exception('No se pudo registrar el pedido');
    }

    $stmt = $conn->prepare("INSERT INTO detalle_preventa (preventa, producto, cantidad, estado) VALUES (?, ?, ?, '0')");
    foreach ($items as $it) {
        $stmt->bind_param('iid', $id_preventa, $it['producto'], $it['cantidad']);
        $stmt->execute();
    }
    $stmt->close();

    $conn->commit();
    echo json_encode(['ok' => true, 'id_preventa' => $id_preventa]);
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['ok' => false, 'mensaje' => $e->getMessage()]);
} finally {
    $conn->close();
}
