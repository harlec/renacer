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
    $es_empleado  = ($_POST['es_empleado'] ?? '0') === '1';
    $id_empleado  = $es_empleado ? intval($_POST['id_empleado'] ?? 0) : 0;

    if (!empty($fecha) && !empty($id_p) && !empty($total_pre) && (!$es_empleado || $id_empleado > 0)) {

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
            $id_empleado_sql = $id_empleado > 0 ? $id_empleado : 'NULL';
            $conn->query("INSERT INTO ventas (fecha, fecha_ope, total, cliente, usuario, estado, id_empleado) VALUES ('$fecha_safe', '$fecha_ope', $total_safe, $id_cliente, $id_usuario, '0', $id_empleado_sql)");
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
                $conn->query("UPDATE productos SET stockp = ROUND(stockp - $cant, 3) WHERE id_producto = $pid");
                $rs       = $conn->query("SELECT stockp FROM productos WHERE id_producto = $pid");
                $stocktot = round(floatval($rs->fetch_assoc()['stockp']), 3);

                // Registrar movimiento
                $conn->query("INSERT INTO stock (producto, egreso, motivo, stock, fv, stockt, fecha, estado)
                              VALUES ($pid, $cant, '$motivo', $stocktot, '', $stocktot, '$fecha_safe', '0')");
            }

            // Consumo de abarrotes de un empleado: se registra como movimiento pendiente
            // para que se descuente automáticamente de las próximas planillas que se
            // generen (ver inc/registrar_planilla_periodo.php), en la cantidad de cuotas
            // indicada (por defecto 1, en partes iguales). No se cobra en caja.
            if ($id_empleado > 0) {
                $cuotas = intval($_POST['cuotas'] ?? 1);
                if ($cuotas < 1) $cuotas = 1;
                if ($cuotas > 24) $cuotas = 24;

                $conn->query("INSERT INTO movimientos_empleado (id_empleado, tipo, fecha, importe, descripcion, usuario, id_venta, partes)
                              VALUES ($id_empleado, 'abarrotes', '$fecha_safe', $total_safe, 'Consumo abarrotes - venta #$venta_id', $id_usuario, $venta_id, $cuotas)");
                $id_movimiento = $conn->insert_id;

                // Reparte el total en cuotas iguales; la última absorbe el redondeo para
                // que la suma exacta siempre coincida con el total de la venta.
                $base = floor(($total_safe / $cuotas) * 100) / 100;
                $acumulado = 0;
                for ($i = 1; $i < $cuotas; $i++) {
                    $conn->query("INSERT INTO movimiento_cuotas (id_movimiento, numero_cuota, monto) VALUES ($id_movimiento, $i, $base)");
                    $acumulado += $base;
                }
                $ultimo_monto = round($total_safe - $acumulado, 2);
                $conn->query("INSERT INTO movimiento_cuotas (id_movimiento, numero_cuota, monto) VALUES ($id_movimiento, $cuotas, $ultimo_monto)");
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
