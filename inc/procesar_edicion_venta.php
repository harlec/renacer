<?php
// Asegurar que siempre devolvemos JSON
header('Content-Type: application/json');

// Capturar cualquier salida no deseada
ob_start();

ini_set('display_errors', '0'); // Ocultar errores en producción
error_reporting(E_ALL);

session_start();
$id_usuario = $_SESSION['id_usr']; 
$tienda = $_SESSION['tienda'];

include('sdba/sdba.php');

$respuestaOk = false;
$mensajeError = 'Error en el proceso';
$venta_id = null;

if (isset($_POST) && !empty($_POST)) {
    
    $id_venta = $_POST['id_venta'];
    $fecha = $_POST['fecha'];
    $productos = $_POST['productos'];
    $fecha_ope = date("Y-m-d H:i:s");

    // Validaciones de seguridad
    if (empty($id_venta) || empty($fecha)) {
        $mensajeError = 'Faltan datos obligatorios (ID venta o fecha)';
    } elseif (empty($productos) || !is_array($productos)) {
        $mensajeError = 'No se recibieron productos válidos para la venta';
    } else {
        
        // Verificar que la venta existe y se puede editar
        $venta_actual = Sdba::table('ventas');
        $venta_actual->where('id_venta', $id_venta);
        $venta_data = $venta_actual->get_one();
        
        if (!$venta_data) {
            $mensajeError = 'La venta no existe';
        } elseif ($venta_data['estado'] != '0') {
            $mensajeError = 'No se puede editar una venta que ya tiene comprobante generado';
        } elseif ($_SESSION['type'] != 'admin' && $venta_data['usuario'] != $id_usuario) {
            $mensajeError = 'No tienes permisos para editar esta venta';
        } else {
            
            try {
                // PRE-VALIDACIÓN: verificar stock suficiente ANTES de tocar nada
                // Calculamos el stock efectivo = stock actual + lo que ya ocupa esta venta
                $detalle_precheck = Sdba::table('detalle_ventas');
                $detalle_precheck->where('venta', $id_venta);
                $detalles_precheck = $detalle_precheck->get();

                $stock_virtual = array(); // stock simulado por producto
                foreach ($detalles_precheck as $detalle) {
                    $pid = $detalle['producto'];
                    if (!isset($stock_virtual[$pid])) {
                        $sv = Sdba::table('stock');
                        $sv->where('producto', $pid);
                        $sv->order_by('id_stock', 'desc');
                        $sl = $sv->get_one();
                        $stock_virtual[$pid] = floatval($sl['stockt']);
                    }
                    // devolvemos virtualmente lo que esta venta ya consume
                    $stock_virtual[$pid] += floatval($detalle['cantidad']);
                }

                foreach ($productos as $producto) {
                    $pid = $producto['producto_id'];
                    $cant = floatval($producto['cantidad']);
                    if ($cant <= 0) continue;
                    if (!isset($stock_virtual[$pid])) {
                        $sv = Sdba::table('stock');
                        $sv->where('producto', $pid);
                        $sv->order_by('id_stock', 'desc');
                        $sl = $sv->get_one();
                        $stock_virtual[$pid] = floatval($sl['stockt']);
                    }
                    if ($stock_virtual[$pid] < $cant) {
                        throw new Exception('Stock insuficiente para el producto ID: ' . $pid . '. Disponible: ' . $stock_virtual[$pid] . ', solicitado: ' . $cant . '. No se realizaron cambios.');
                    }
                    $stock_virtual[$pid] -= $cant; // descontar virtualmente para acumulados
                }
                // FIN PRE-VALIDACIÓN — a partir de aquí el stock es suficiente

                // PASO 1: Obtener detalle actual y devolver stock
                $detalle_actual = Sdba::table('detalle_ventas');
                $detalle_actual->where('venta', $id_venta);
                $detalles_actuales = $detalle_actual->get();
                
                // Devolver stock de productos actuales
                foreach ($detalles_actuales as $detalle) {
                    $stock = Sdba::table('stock');
                    $stock->where('producto', $detalle['producto']);
                    $stock->order_by('id_stock', 'desc');
                    $stockl = $stock->get_one();
                    $stock_actual = floatval($stockl['stockt']);
                    $nuevo_stock = $stock_actual + floatval($detalle['cantidad']);
                    
                    // Registrar devolución en historial
                    $motivo = 'EV-' . $id_venta . '-EDIT';
                    $data_stock = array(
                        'id_stock' => '',
                        'producto' => $detalle['producto'],
                        'ingreso' => floatval($detalle['cantidad']),
                        'egreso' => 0,
                        'motivo' => $motivo,
                        'stock' => $nuevo_stock,
                        'fv' => '',
                        'stockt' => $nuevo_stock,
                        'fecha' => $fecha,
                        'estado' => '0'
                    );
                    $stock->insert($data_stock);
                    
                    // Actualizar stock en productos
                    $productos_tabla = Sdba::table('productos');
                    $productos_tabla->where('id_producto', $detalle['producto']);
                    $productos_tabla->update(array('stockp' => $nuevo_stock));
                }
                
                // PASO 2: Eliminar detalle actual
                $detalle_eliminar = Sdba::table('detalle_ventas');
                $detalle_eliminar->where('venta', $id_venta);
                $detalle_eliminar->delete();
                
                // PASO 3: Agregar nuevos productos
                $total_venta = 0;
                foreach ($productos as $producto) {
                    $id_producto = $producto['producto_id'];
                    $cantidad = floatval($producto['cantidad']);
                    $precio = floatval($producto['precio']);
                    $precio_vp = floatval($producto['precio_vp'] ?? 0);
                    $cantidad_vp = floatval($producto['cantidad_vp'] ?? 1);
                    if ($precio_vp > 0 && $cantidad_vp > 0) {
                        $total_producto = round(($cantidad / $cantidad_vp) * $precio_vp, 2);
                    } else {
                        $total_producto = round($cantidad * $precio, 2);
                    }
                    $total_venta += $total_producto;
                    
                    if ($cantidad <= 0) continue;
                    
                    // Verificar stock disponible
                    $stock = Sdba::table('stock');
                    $stock->where('producto', $id_producto);
                    $stock->order_by('id_stock', 'desc');
                    $stockl = $stock->get_one();
                    $stock_disponible = floatval($stockl['stockt']);
                    
                    // Stock ya fue validado en PRE-VALIDACIÓN, esta línea es solo por seguridad
                    if ($stock_disponible < $cantidad) {
                        throw new Exception("Stock insuficiente para producto ID: $id_producto (verificación final)");
                    }
                    
                    // Descontar stock
                    $nuevo_stock = $stock_disponible - $cantidad;
                    $motivo = 'V-' . $id_venta . '-EDIT';
                    $data_stock = array(
                        'id_stock' => '',
                        'producto' => $id_producto,
                        'ingreso' => 0,
                        'egreso' => $cantidad,
                        'motivo' => $motivo,
                        'stock' => $nuevo_stock,
                        'fv' => '',
                        'stockt' => $nuevo_stock,
                        'fecha' => $fecha,
                        'estado' => '0'
                    );
                    $stock->insert($data_stock);
                    
                    // Actualizar productos
                    $productos_tabla = Sdba::table('productos');
                    $productos_tabla->where('id_producto', $id_producto);
                    $productos_tabla->update(array('stockp' => $nuevo_stock));
                    
                    // Insertar nuevo detalle
                    $detalle_ventas = Sdba::table('detalle_ventas');
                    $data_detalle = array(
                        'id_detalle' => '',
                        'venta' => $id_venta,
                        'producto' => $id_producto,
                        'id_vp' => $producto['id_vp'],
                        'cantidad' => $cantidad,
                        'precio' => $precio,
                        'total' => $total_producto,
                        'estado' => '0'
                    );
                    $detalle_ventas->insert($data_detalle);
                }
                
                // PASO 4: Actualizar venta
                $ventas = Sdba::table('ventas');
                $ventas->where('id_venta', $id_venta);
                $ventas->update(array(
                    'fecha' => $fecha,
                    'total' => $total_venta
                ));
                
                $respuestaOk = true;
                $mensajeError = 'Venta actualizada correctamente';
                $venta_id = $id_venta;
                
            } catch (Exception $e) {
                $respuestaOk = false;
                $mensajeError = "Error en el proceso: " . $e->getMessage();
                // Log del error para debugging
                error_log("Error en edición de venta ID $id_venta: " . $e->getMessage());
            }
        }
    }
}

$salidaJson = array(
    'respuesta' => $respuestaOk,
    'mensaje' => $mensajeError,
    'venta_id' => $venta_id
);

// Limpiar cualquier salida previa
ob_clean();

// Devolver solo JSON limpio
echo json_encode($salidaJson);
exit();
?>