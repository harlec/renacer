<?php
// Crea una "venta" a partir de líneas armadas a mano en notas_venta.php ("Factura simple"),
// para poder emitirla luego con el mismo pipeline de facturar.php. A diferencia de
// inc/registrar_venta_tablet.php, esta NO valida ni descuenta stock (es para casos que no
// deben tocar inventario) — solo deja la venta y su detalle registrados.
session_start();
if (empty($_SESSION['ingress'])) {
    echo json_encode(['respuesta' => false, 'mensaje' => 'Sin sesión']);
    exit;
}

$id_usuario = (int)($_SESSION['id_usr'] ?? 0);

header('Content-Type: application/json');
ob_start();
ini_set('display_errors', '0');

$respuestaOk  = false;
$mensajeError = 'Error en el proceso';
$venta_id     = null;

if (!empty($_POST)) {

    $fecha     = $_POST['fecha']     ?? '';
    $id_p      = $_POST['id_pro']    ?? [];
    $id_vp     = $_POST['id_vp']     ?? [];
    $cantidad  = $_POST['cantidad']  ?? [];
    $precio    = $_POST['precio']    ?? [];
    $total_pre = $_POST['total_pre'] ?? [];
    $total     = floatval($_POST['total1'] ?? 0);

    if (!empty($fecha) && !empty($id_p) && !empty($total_pre)) {

        $conn = new mysqli('localhost', 'admin_renacer', 'ikm169uhn', 'admin_renacer');
        $conn->set_charset('utf8');
        $conn->begin_transaction();

        try {
            // Cliente genérico para facturas armadas a mano (no viene de una venta real)
            $rc = $conn->query("SELECT id_cliente FROM clientes WHERE UPPER(TRIM(cliente)) = 'FACTURA MANUAL' LIMIT 1");
            $cl = $rc ? $rc->fetch_assoc() : null;
            if ($cl) {
                $id_cliente = (int)$cl['id_cliente'];
            } else {
                $conn->query("INSERT INTO clientes (cliente, estado) VALUES ('FACTURA MANUAL', '1')");
                $id_cliente = (int)$conn->insert_id;
            }

            $fecha_safe = $conn->real_escape_string($fecha);
            $fecha_ope  = date("Y-m-d H:i:s");
            $conn->query("INSERT INTO ventas (fecha, fecha_ope, total, cliente, usuario, estado)
                          VALUES ('$fecha_safe', '$fecha_ope', $total, $id_cliente, $id_usuario, '0')");
            $venta_id = $conn->insert_id;
            if (!$venta_id) throw new Exception("No se pudo crear la venta");

            for ($i = 0; $i < count($id_p); $i++) {
                $pid  = intval($id_p[$i]);
                $ivp  = intval($id_vp[$i]);
                $cant = floatval($cantidad[$i]);
                $prec = floatval($precio[$i]);
                $tot  = floatval($total_pre[$i]);
                if ($cant <= 0 || $tot <= 0) continue;

                $conn->query("INSERT INTO detalle_ventas (venta, producto, id_vp, cantidad, precio, total, estado)
                              VALUES ($venta_id, $pid, $ivp, $cant, $prec, $tot, '0')");
            }

            $conn->commit();
            $respuestaOk  = true;
            $mensajeError = 'ok';

        } catch (Exception $e) {
            $conn->rollback();
            $respuestaOk  = false;
            $mensajeError = $e->getMessage();
            $venta_id     = null;
        }

        $conn->close();
    }
}

ob_clean();
echo json_encode([
    'respuesta' => $respuestaOk,
    'mensaje'   => $mensajeError,
    'venta_id'  => $venta_id,
]);
