<?php
session_start();
if (empty($_SESSION['ingress'])) {
    echo json_encode(['respuesta' => false, 'mensaje' => 'Sin sesión']);
    exit;
}

$id_usuario = (int)$_SESSION['id_usr'];

header('Content-Type: application/json');
ob_start();
ini_set('display_errors', '0');

$respuestaOk  = false;
$mensajeError = 'Error en el proceso';
$venta_id     = null;
$error_pid    = null;

if (!empty($_POST)) {

    $fecha      = $_POST['fecha']      ?? '';
    $id_cliente = isset($_POST['id_cliente']) ? (int)$_POST['id_cliente'] : null;
    $id_p       = $_POST['id_pro']     ?? [];
    $id_vp      = $_POST['id_vp']      ?? [];
    $cantidad   = $_POST['cantidad']   ?? [];
    $precio     = $_POST['precio']     ?? [];
    $total_pre  = $_POST['total_pre']  ?? [];
    $total      = floatval($_POST['total1'] ?? 0);

    if (!empty($fecha) && !empty($id_p) && !empty($total_pre)) {

        $conn = new mysqli('localhost', 'admin_renacer', 'ikm169uhn', 'admin_renacer');
        $conn->set_charset('utf8');
        $conn->begin_transaction();

        try {
            // Pre-validar stock
            for ($i = 0; $i < count($id_p); $i++) {
                $pid  = intval($id_p[$i]);
                $cant = floatval($cantidad[$i]);
                if ($cant <= 0) continue;
                $r   = $conn->query("SELECT stockp, nom_prod FROM productos WHERE id_producto = $pid FOR UPDATE");
                $row = $r ? $r->fetch_assoc() : null;
                if (!$row || floatval($row['stockp']) < $cant) {
                    $nombre    = $row['nom_prod'] ?? "ID: $pid";
                    $error_pid = $pid;
                    throw new Exception("Stock insuficiente: \"$nombre\". Disponible: " . floatval($row['stockp'] ?? 0) . ", solicitado: $cant.");
                }
            }

            // Resolver id_cliente
            if (!$id_cliente) {
                // Fallback: cliente genérico "Tablet"
                $rc = $conn->query("SELECT id_cliente FROM clientes WHERE UPPER(TRIM(cliente)) = 'TABLET' LIMIT 1");
                $cl = $rc ? $rc->fetch_assoc() : null;
                if ($cl) {
                    $id_cliente = (int)$cl['id_cliente'];
                } else {
                    $conn->query("INSERT INTO clientes (cliente, estado) VALUES ('Tablet', '1')");
                    $id_cliente = (int)$conn->insert_id;
                }
            }

            // Insertar venta
            $fecha_safe = $conn->real_escape_string($fecha);
            $fecha_ope  = date("Y-m-d H:i:s");
            $conn->query("INSERT INTO ventas (fecha, fecha_ope, total, cliente, usuario, estado)
                          VALUES ('$fecha_safe', '$fecha_ope', $total, $id_cliente, $id_usuario, '0')");
            $venta_id = $conn->insert_id;
            if (!$venta_id) throw new Exception("No se pudo crear la venta");

            // Detalle + stock
            for ($i = 0; $i < count($id_p); $i++) {
                $pid    = intval($id_p[$i]);
                $cant   = floatval($cantidad[$i]);
                $prec   = floatval($precio[$i]);
                $tot    = floatval($total_pre[$i]);
                $ivp    = intval($id_vp[$i]);
                $motivo = $conn->real_escape_string('v-' . $venta_id);

                $conn->query("INSERT INTO detalle_ventas (venta, producto, id_vp, cantidad, precio, total, estado)
                              VALUES ($venta_id, $pid, $ivp, $cant, $prec, $tot, '0')");

                $conn->query("UPDATE productos SET stockp = stockp - $cant WHERE id_producto = $pid");
                $rs       = $conn->query("SELECT stockp FROM productos WHERE id_producto = $pid");
                $stocktot = floatval($rs->fetch_assoc()['stockp']);

                $conn->query("INSERT INTO stock (producto, egreso, motivo, stock, fv, stockt, fecha, estado)
                              VALUES ($pid, $cant, '$motivo', $stocktot, '', $stocktot, '$fecha_safe', '0')");
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
    'respuesta'          => $respuestaOk,
    'mensaje'            => $mensajeError,
    'venta_id'           => $venta_id,
    'error_producto_id'  => $error_pid,
]);
