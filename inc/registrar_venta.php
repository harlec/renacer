<?php
session_start();
$id_usuario = $_SESSION['id_usr'];

header('Content-Type: application/json');
ob_start();
ini_set('display_errors', '0');

$respuestaOk = false;
$mensajeError = 'Error en el proceso';
$venta_id      = null;
$error_pid     = null;

if (isset($_POST) && !empty($_POST)) {

    $fecha     = $_POST['fecha'];
    $cliente   = $_POST['cliente'];
    $fecha_ope = date("Y-m-d H:i:s");
    $id_p      = $_POST['id_pro'];
    $cantidad  = $_POST['cantidad'];
    $precio    = $_POST['precio'];
    $total_pre = $_POST['total_pre'];
    $total     = $_POST['total1'];
    $id_vp     = $_POST['id_vp'];

    if (!empty($fecha) && !empty($id_p) && !empty($total_pre)) {

        $conn = new mysqli('localhost', 'admin_renacer', 'ikm169uhn', 'admin_renacer');
        $conn->set_charset('utf8');
        $conn->begin_transaction();

        try {
            // PRE-VALIDACIÓN: bloquear filas y verificar stock antes de tocar nada
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

            // Normalizar nombre: sin espacios extra, todo en mayúsculas
            $cliente = strtoupper(trim($cliente));
            $cliente_safe = $conn->real_escape_string($cliente);
            // Buscar cliente ignorando mayúsculas/minúsculas
            $rc = $conn->query("SELECT id_cliente FROM clientes WHERE UPPER(TRIM(cliente)) = UPPER('$cliente_safe') LIMIT 1");
            $cl = $rc ? $rc->fetch_assoc() : null;
            if ($cl) {
                $id_cliente = $cl['id_cliente'];
            } else {
                $conn->query("INSERT INTO clientes (cliente, estado) VALUES ('$cliente_safe', '1')");
                $id_cliente = $conn->insert_id;
            }

            // Insertar venta
            $fecha_safe = $conn->real_escape_string($fecha);
            $total_safe = floatval($total);
            $conn->query("INSERT INTO ventas (fecha, fecha_ope, total, cliente, usuario, estado) VALUES ('$fecha_safe', '$fecha_ope', $total_safe, $id_cliente, $id_usuario, '0')");
            $venta_id = $conn->insert_id;
            if (!$venta_id) throw new Exception("No se pudo crear la venta");

            // Detalle + movimiento de stock
            for ($i = 0; $i < count($id_p); $i++) {
                $pid    = intval($id_p[$i]);
                $cant   = floatval($cantidad[$i]);
                $prec   = floatval($precio[$i]);
                $tot    = floatval($total_pre[$i]);
                $ivp    = intval($id_vp[$i]);
                $motivo = $conn->real_escape_string('v-' . $venta_id);

                // Detalle venta
                $conn->query("INSERT INTO detalle_ventas (venta, producto, id_vp, cantidad, precio, total, estado)
                              VALUES ($venta_id, $pid, $ivp, $cant, $prec, $tot, '0')");

                // Descontar stock atómicamente y leer resultado
                $conn->query("UPDATE productos SET stockp = stockp - $cant WHERE id_producto = $pid");
                $rs       = $conn->query("SELECT stockp FROM productos WHERE id_producto = $pid");
                $stocktot = floatval($rs->fetch_assoc()['stockp']);

                // Registrar movimiento
                $conn->query("INSERT INTO stock (producto, egreso, motivo, stock, fv, stockt, fecha, estado)
                              VALUES ($pid, $cant, '$motivo', $stocktot, '', $stocktot, '$fecha_safe', '0')");
            }

            $conn->commit();
            $respuestaOk = true;
            $mensajeError = 'entro';

        } catch (Exception $e) {
            $conn->rollback();
            $respuestaOk = false;
            $mensajeError = $e->getMessage();
            $venta_id     = null;
        }

        $conn->close();
    }
}

ob_clean();
echo json_encode(['respuesta' => $respuestaOk, 'mensaje' => $mensajeError, 'venta_id' => $venta_id, 'error_producto_id' => $error_pid]);
