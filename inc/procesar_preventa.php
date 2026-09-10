<?php
ob_start();
ini_set('display_errors', '0');
session_start();
if (empty($_SESSION['ingress'])) {
    ob_clean();
    http_response_code(403);
    echo json_encode(['ok' => false, 'mensaje' => 'Sin sesión']);
    exit;
}

$id_usuario = (int)$_SESSION['id_usr'];

header('Content-Type: application/json');

$id_preventa = (int)($_POST['id_preventa'] ?? 0);
$id_detalle  = $_POST['id_detalle'] ?? [];
$id_vp       = $_POST['id_vp']      ?? [];
$cantidad    = $_POST['cantidad']   ?? [];

if (!$id_preventa || empty($id_detalle)) {
    ob_clean();
    echo json_encode(['ok' => false, 'mensaje' => 'Datos incompletos']);
    exit;
}

$conn = new mysqli('localhost', 'admin_renacer', 'ikm169uhn', 'admin_renacer');
$conn->set_charset('utf8');
$conn->begin_transaction();

try {
    $r = $conn->query("SELECT cliente, estado FROM preventa WHERE id_preventa = $id_preventa FOR UPDATE");
    $preventa = $r ? $r->fetch_assoc() : null;
    if (!$preventa) throw new Exception('La preventa no existe');
    if ($preventa['estado'] !== '0') throw new Exception('Esta preventa ya fue procesada');

    // Resolver y validar cada línea contra su detalle_preventa y la variante elegida
    $lineas = [];
    $total_venta = 0;
    for ($i = 0; $i < count($id_detalle); $i++) {
        $did  = (int)$id_detalle[$i];
        $ivp  = (int)($id_vp[$i] ?? 0);
        $cant = round((float)($cantidad[$i] ?? 0), 3);
        if (!$did || !$ivp || $cant <= 0) throw new Exception('Línea inválida en el detalle');

        $stmt = $conn->prepare("SELECT producto FROM detalle_preventa WHERE id_detalle = ? AND preventa = ? AND estado = '0'");
        $stmt->bind_param('ii', $did, $id_preventa);
        $stmt->execute();
        $det = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if (!$det) throw new Exception("Línea $did no pertenece a esta preventa o ya fue procesada");
        $pid = (int)$det['producto'];

        $stmt = $conn->prepare("SELECT precio_vp FROM variante_p WHERE id_vp = ? AND producto_vp = ? AND state_vp = '1'");
        $stmt->bind_param('ii', $ivp, $pid);
        $stmt->execute();
        $vp = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if (!$vp) throw new Exception("La variante elegida no corresponde al producto de la línea $did");
        $precio = (float)$vp['precio_vp'];

        $r = $conn->query("SELECT stockp, nom_prod FROM productos WHERE id_producto = $pid FOR UPDATE");
        $prod = $r ? $r->fetch_assoc() : null;
        if (!$prod || (float)$prod['stockp'] < $cant) {
            $nombre = $prod['nom_prod'] ?? "ID: $pid";
            throw new Exception("Stock insuficiente: \"$nombre\". Disponible: " . (float)($prod['stockp'] ?? 0) . ", solicitado: $cant.");
        }

        $tot = round($cant * $precio, 2);
        $total_venta += $tot;
        $lineas[] = ['id_detalle' => $did, 'producto' => $pid, 'id_vp' => $ivp, 'cantidad' => $cant, 'precio' => $precio, 'total' => $tot];
    }

    $fecha      = date('Y-m-d');
    $fecha_ope  = date('Y-m-d H:i:s');
    $id_cliente = (int)$preventa['cliente'];

    $conn->query("INSERT INTO ventas (fecha, fecha_ope, total, cliente, usuario, estado)
                  VALUES ('$fecha', '$fecha_ope', $total_venta, $id_cliente, $id_usuario, '0')");
    $venta_id = $conn->insert_id;
    if (!$venta_id) throw new Exception('No se pudo crear la venta');

    foreach ($lineas as $l) {
        $motivo = $conn->real_escape_string('v-' . $venta_id);
        $conn->query("INSERT INTO detalle_ventas (venta, producto, id_vp, cantidad, precio, total, estado)
                      VALUES ($venta_id, {$l['producto']}, {$l['id_vp']}, {$l['cantidad']}, {$l['precio']}, {$l['total']}, '0')");

        $conn->query("UPDATE productos SET stockp = ROUND(stockp - {$l['cantidad']}, 3) WHERE id_producto = {$l['producto']}");
        $rs       = $conn->query("SELECT stockp FROM productos WHERE id_producto = {$l['producto']}");
        $stocktot = round((float)$rs->fetch_assoc()['stockp'], 3);

        $conn->query("INSERT INTO stock (producto, egreso, motivo, stock, fv, stockt, fecha, estado)
                      VALUES ({$l['producto']}, {$l['cantidad']}, '$motivo', $stocktot, '', $stocktot, '$fecha', '0')");

        $conn->query("UPDATE detalle_preventa SET id_vp = {$l['id_vp']}, precio = {$l['precio']}, estado = '1' WHERE id_detalle = {$l['id_detalle']}");
    }

    $conn->query("UPDATE preventa SET estado = '1', venta_generada = $venta_id WHERE id_preventa = $id_preventa");

    $conn->commit();
    ob_clean();
    echo json_encode(['ok' => true, 'venta_id' => $venta_id]);

} catch (Exception $e) {
    $conn->rollback();
    ob_clean();
    echo json_encode(['ok' => false, 'mensaje' => $e->getMessage()]);
}

$conn->close();
