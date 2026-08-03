<?php
session_start();
if (empty($_SESSION['ingress'])) {
    echo json_encode(['respuesta' => false, 'mensaje' => 'Sin sesión']);
    exit;
}

// Si el script muere con un error fatal (500), esto evita que el navegador reciba una
// respuesta vacía/rota — captura el error real y lo manda como JSON legible, para poder
// diagnosticarlo sin acceso a los logs del servidor. TEMPORAL: una vez identificado el
// problema real, esto se puede simplificar de vuelta a un mensaje genérico.
register_shutdown_function(function () {
    $error = error_get_last();
    if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR], true)) {
        if (ob_get_level() > 0) { ob_end_clean(); }
        http_response_code(200);
        header('Content-Type: application/json');
        echo json_encode([
            'respuesta' => false,
            'mensaje'   => 'Error interno (línea ' . $error['line'] . '): ' . $error['message'],
        ]);
    }
});

// ob_start() va ANTES de tocar Sdba: si alguna consulta falla, Sdba::error() imprime
// un <div> de error HTML directo al output (no lanza excepción) — sin el buffer abierto
// desde antes, ese HTML se cuela delante del JSON final y el navegador ya no puede
// interpretar la respuesta ("Error de conexión" aunque el servidor sí haya respondido).
ob_start();
ini_set('display_errors', '0');
include('sdba/sdba.php');

$respuestaOk  = false;
$mensajeError = 'Error en el proceso';

$id = intval($_GET['id'] ?? 0);

if ($id > 0) {
    $venta = Sdba::table('ventas');
    $venta->where('id_venta', $id);
    $vl = $venta->get_one();

    if (!$vl) {
        $mensajeError = 'La venta no existe.';
    } elseif ($vl['estado'] == '2') {
        $mensajeError = 'Esta venta ya estaba anulada.';
    } elseif ($vl['estado'] != '0') {
        $mensajeError = 'Esta venta ya fue facturada — no se puede anular directamente. Primero anula el comprobante emitido.';
    } else {
        $fecha = date("Y-m-d");

        // Revierte el stock que se descontó al registrar la venta.
        $deventa = Sdba::table('stock');
        $deventa->where('motivo', 'v-' . $id);
        $vdl = $deventa->get();

        foreach ($vdl as $value) {
            $producto = $value['producto'];
            $fv       = $value['fv'];
            $egreso   = (float)($value['egreso'] ?? 0);
            if (!$producto) continue;

            $stock  = Sdba::table('stock');
            $stock->where('producto', $producto);
            $stock->order_by('id_stock', 'desc');
            $stockl = $stock->get_one();
            $stockt_actual = (float)($stockl['stockt'] ?? 0);
            $nstockt = $stockt_actual + $egreso;
            $motivo  = 'EV-' . $id;

            $stock->reset();
            $stock->where('producto', $producto)->and_where('fv =', $fv);
            $stock->order_by('id_stock', 'desc');
            $stockl = $stock->get_one();
            $stock_actual = (float)($stockl['stock'] ?? 0);
            $nstock = $stock_actual + $egreso;

            $stock->insert(array('id_stock'=>'','producto'=>$producto,'ingreso'=>$egreso,'motivo'=>$motivo,'stock'=>$nstock,'fv'=>$fv,'stockt'=>$nstockt,'fecha'=>$fecha,'estado'=>'0'));

            // Actualiza el stock de la variante (si existe esa combinación producto+variante)
            $variacion = Sdba::table('variantes');
            $variacion->where('producto', $producto)->and_where('variante', $fv);
            $vr = $variacion->get_one();
            if ($vr) {
                $variacion->where('id_variante', $vr['id_variante']);
                $variacion->update(array('stock' => $nstock));
            }
        }

        $venta->where('id_venta', $id);
        $venta->update(array('estado' => '2'));

        $respuestaOk  = true;
        $mensajeError = 'ok';
    }
}

ob_clean();
echo json_encode(['respuesta' => $respuestaOk, 'mensaje' => $mensajeError]);
