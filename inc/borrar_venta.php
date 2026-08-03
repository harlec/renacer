<?php
session_start();
if (empty($_SESSION['ingress'])) {
    echo json_encode(['respuesta' => false, 'mensaje' => 'Sin sesión']);
    exit;
}

header('Content-Type: application/json');
ob_start();
ini_set('display_errors', '0');

$respuestaOk  = false;
$mensajeError = 'Error en el proceso';

$id = intval($_GET['id'] ?? 0);

if ($id > 0) {
    $conn = new mysqli('localhost', 'admin_renacer', 'ikm169uhn', 'admin_renacer');
    $conn->set_charset('utf8');
    $conn->begin_transaction();

    try {
        $r  = $conn->query("SELECT estado FROM ventas WHERE id_venta = $id FOR UPDATE");
        $vl = $r ? $r->fetch_assoc() : null;

        if (!$vl) {
            throw new Exception('La venta no existe.');
        }
        if ($vl['estado'] == '2') {
            throw new Exception('Esta venta ya estaba anulada.');
        }
        if ($vl['estado'] != '0') {
            throw new Exception('Esta venta ya fue facturada — no se puede anular directamente. Primero anula el comprobante emitido.');
        }

        $fecha = date('Y-m-d');

        // Revierte el stock que se descontó al registrar la venta — mismo patrón que
        // inc/registrar_venta.php al descontarlo (actualiza productos.stockp, que es el
        // contador real que usan tablet.php/venta.php), solo que sumando en vez de restando.
        $motivo_original = $conn->real_escape_string('v-' . $id);
        $rs = $conn->query("SELECT producto, egreso, fv FROM stock WHERE motivo = '$motivo_original'");
        while ($mov = $rs->fetch_assoc()) {
            $pid  = (int)$mov['producto'];
            $cant = (float)$mov['egreso'];
            $fv   = $conn->real_escape_string($mov['fv'] ?? '');
            if ($pid <= 0 || $cant <= 0) continue;

            $conn->query("UPDATE productos SET stockp = ROUND(stockp + $cant, 3) WHERE id_producto = $pid");
            $rsp      = $conn->query("SELECT stockp FROM productos WHERE id_producto = $pid");
            $stocktot = round(floatval($rsp->fetch_assoc()['stockp']), 3);

            $motivo_reverso = $conn->real_escape_string('EV-' . $id);
            $conn->query("INSERT INTO stock (producto, ingreso, motivo, stock, fv, stockt, fecha, estado)
                          VALUES ($pid, $cant, '$motivo_reverso', $stocktot, '$fv', $stocktot, '$fecha', '0')");
        }

        $conn->query("UPDATE ventas SET estado = '2' WHERE id_venta = $id");

        $conn->commit();
        $respuestaOk  = true;
        $mensajeError = 'ok';

    } catch (Exception $e) {
        $conn->rollback();
        $mensajeError = $e->getMessage();
    }

    $conn->close();
} else {
    $mensajeError = 'Falta indicar la venta a anular.';
}

ob_clean();
echo json_encode(['respuesta' => $respuestaOk, 'mensaje' => $mensajeError]);
