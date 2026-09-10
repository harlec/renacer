<?php
session_start();
require_once __DIR__ . '/preventa_control.php';
preventa_requerir_json();
require_once __DIR__ . '/sdba/sdba.php';

header('Content-Type: application/json');

$id_cliente = (int)$_SESSION['pv_cliente_id'];
$productos  = $_POST['producto_id'] ?? [];
$cantidades = $_POST['cantidad'] ?? [];

$items = [];
for ($i = 0; $i < count($productos); $i++) {
    $pid  = (int)$productos[$i];
    $cant = round((float)($cantidades[$i] ?? 0), 3);
    if ($pid > 0 && $cant > 0) {
        $items[] = ['producto' => $pid, 'cantidad' => $cant];
    }
}

if (empty($items)) {
    echo json_encode(['ok' => false, 'mensaje' => 'El pedido está vacío']);
    exit;
}

// Solo aceptar productos que realmente existen y están activos.
$ids_pedidos = array_unique(array_column($items, 'producto'));
$productos_tbl = Sdba::table('productos');
$productos_tbl->where('estado', '1');
$productos_tbl->where_in('id_producto', $ids_pedidos);
$ids_validos = [];
foreach ($productos_tbl->get() as $p) {
    $ids_validos[(int)$p['id_producto']] = true;
}
$items = array_values(array_filter($items, function ($it) use ($ids_validos) {
    return isset($ids_validos[$it['producto']]);
}));

if (empty($items)) {
    echo json_encode(['ok' => false, 'mensaje' => 'Ninguno de los productos del pedido es válido']);
    exit;
}

$preventa_tbl = Sdba::table('preventa');
$preventa_tbl->insert([
    'id_preventa' => '',
    'cliente'     => $id_cliente,
    'fecha'       => date('Y-m-d H:i:s'),
    'estado'      => '0',
]);
$id_preventa = $preventa_tbl->insert_id();

if (!$id_preventa) {
    echo json_encode(['ok' => false, 'mensaje' => 'No se pudo registrar el pedido']);
    exit;
}

$detalle_tbl = Sdba::table('detalle_preventa');
foreach ($items as $it) {
    $detalle_tbl->insert([
        'id_detalle' => '',
        'preventa'   => $id_preventa,
        'producto'   => $it['producto'],
        'cantidad'   => $it['cantidad'],
        'estado'     => '0',
    ]);
}

echo json_encode(['ok' => true, 'id_preventa' => $id_preventa]);
