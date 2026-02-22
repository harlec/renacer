<?php
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

session_start();
$id_usuario = $_SESSION['id_usr']; 
$tienda = $_SESSION['tienda'];

include('sdba/sdba.php');
include('logs_auditoria.php');

$respuestaOk = false;
$mensajeError = 'Error en el proceso';
$venta_id = null;

if (isset($_POST) && !empty($_POST)) {
    
    $id_venta = $_POST['id_venta'];
    $fecha = $_POST['fecha'];
    $cliente = $_POST['cliente'];
    $tipo = $_POST['tipo'];
    $forma = $_POST['forma'];
    $productos = $_POST['productos'];
    $fecha_ope = date("Y-m-d H:i:s");

    // Validaciones de seguridad
    if (empty($id_venta) || empty($fecha) || empty($cliente) || empty($productos)) {
        $mensajeError = 'Faltan datos obligatorios';
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
                // Obtener detalle actual de la venta
                $detalle_actual = Sdba::table('detalle_ventas');
                $detalle_actual->where('venta', $id_venta);
                $detalles_actuales = $detalle_actual->get();
                
                // Crear array para facilitar comparaciones
                $productos_actuales = array();
                foreach ($detalles_actuales as $detalle) {
                    $key = $detalle['producto'] . '_' . $detalle['id_vp'];
                    $productos_actuales[$key] = $detalle;
                }
                
                // Obtener/crear cliente
                $clientes = Sdba::table('clientes');
                $clientes->where('cliente', $cliente);
                $cliente_existente = $clientes->get_one();
                
                if ($cliente_existente) {
                    $id_cliente = $cliente_existente['id_cliente'];
                } else {
                    $data_cliente = array(
                        'id_cliente' => '',
                        'cliente' => $cliente,
                        'estado' => '1'
                    );
                    $clientes->insert($data_cliente);
                    $id_cliente = $clientes->insert_id();
                }
                
                // PASO 1: Revertir stock de productos eliminados o modificados
                foreach ($productos_actuales as $key => $producto_actual) {
                    $encontrado = false;
                    $nueva_cantidad = 0;
                    
                    // Buscar si el producto sigue en la nueva lista
                    foreach ($productos as $producto_nuevo) {
                        if ($producto_nuevo['producto_id'] == $producto_actual['producto'] && 
                            $producto_nuevo['id_vp'] == $producto_actual['id_vp']) {
                            $encontrado = true;
                            $nueva_cantidad = floatval($producto_nuevo['cantidad']);
                            break;
                        }
                    }
                    
                    $cantidad_actual = floatval($producto_actual['cantidad']);
                    
                    // Si el producto fue eliminado o se redujo la cantidad, devolver stock
                    if (!$encontrado || $nueva_cantidad < $cantidad_actual) {
                        $cantidad_a_devolver = $encontrado ? ($cantidad_actual - $nueva_cantidad) : $cantidad_actual;
                        
                        // Obtener stock actual
                        $stock = Sdba::table('stock');
                        $stock->where('producto', $producto_actual['producto']);
                        $stock->order_by('id_stock', 'desc');
                        $stockl = $stock->get_one();
                        $stock_atual = floatval($stockl['stockt']);
                        $nuevo_stock = $stock_atual + $cantidad_a_devolver;
                        
                        // Registrar devolución en historial de stock
                        $motivo = 'EV-' . $id_venta . '-EDIT';
                        $data_stock = array(
                            'id_stock' => '',
                            'producto' => $producto_actual['producto'],
                            'ingreso' => $cantidad_a_devolver,
                            'egreso' => 0,
                            'motivo' => $motivo,
                            'stock' => $nuevo_stock,
                            'fv' => '',
                            'stockt' => $nuevo_stock,
                            'fecha' => $fecha,
                            'estado' => '0'
                        );
                        $stock->insert($data_stock);
                        
                        // Actualizar stock en tabla productos
                        $productos_tabla = Sdba::table('productos');
                        $productos_tabla->where('id_producto', $producto_actual['producto']);
                        $data_producto = array('stockp' => $nuevo_stock);
                        $productos_tabla->update($data_producto);
                    }
                }
                
                // PASO 2: Eliminar registros del detalle actual
                $detalle_eliminar = Sdba::table('detalle_ventas');
                $detalle_eliminar->where('venta', $id_venta);
                $detalle_eliminar->delete();
                
                // PASO 3: Procesar productos nuevos y modificados
                $total_venta = 0;
                
                foreach ($productos as $producto) {
                    $id_producto = $producto['producto_id'];
                    $id_vp = $producto['id_vp'];
                    $cantidad = floatval($producto['cantidad']);
                    $precio = floatval($producto['precio']);
                    $total_producto = $cantidad * $precio;
                    $total_venta += $total_producto;
                    
                    if ($cantidad <= 0) continue;
                    
                    // Verificar stock disponible
                    $stock = Sdba::table('stock');
                    $stock->where('producto', $id_producto);
                    $stock->order_by('id_stock', 'desc');
                    $stockl = $stock->get_one();
                    $stock_disponible = floatval($stockl['stockt']);
                    
                    $key = $id_producto . '_' . $id_vp;
                    $cantidad_previa = isset($productos_actuales[$key]) ? floatval($productos_actuales[$key]['cantidad']) : 0;
                    $cantidad_adicional = $cantidad - $cantidad_previa;
                    
                    // Solo validar stock si se está agregando cantidad
                    if ($cantidad_adicional > 0 && $stock_disponible < $cantidad_adicional) {
                        throw new Exception("Stock insuficiente para el producto ID: $id_producto. Stock disponible: $stock_disponible, requerido: $cantidad_adicional");
                    }
                    
                    // Si hay cantidad adicional, descontarla del stock
                    if ($cantidad_adicional > 0) {
                        $nuevo_stock = $stock_disponible - $cantidad_adicional;
                        
                        // Registrar egreso en historial de stock
                        $motivo = 'V-' . $id_venta . '-EDIT';
                        $data_stock = array(
                            'id_stock' => '',
                            'producto' => $id_producto,
                            'ingreso' => 0,
                            'egreso' => $cantidad_adicional,
                            'motivo' => $motivo,
                            'stock' => $nuevo_stock,
                            'fv' => '',
                            'stockt' => $nuevo_stock,
                            'fecha' => $fecha,
                            'estado' => '0'
                        );
                        $stock->insert($data_stock);
                        
                        // Actualizar stock en tabla productos
                        $productos_tabla = Sdba::table('productos');
                        $productos_tabla->where('id_producto', $id_producto);
                        $data_producto = array('stockp' => $nuevo_stock);
                        $productos_tabla->update($data_producto);
                    }
                    
                    // Insertar nuevo detalle
                    $detalle_ventas = Sdba::table('detalle_ventas');
                    $data_detalle = array(
                        'id_detalle' => '',
                        'venta' => $id_venta,
                        'producto' => $id_producto,
                        'id_vp' => $id_vp,
                        'cantidad' => $cantidad,
                        'precio' => $precio,
                        'total' => $total_producto,
                        'estado' => '0'
                    );
                    $detalle_ventas->insert($data_detalle);
                }
                
                // PASO 4: Actualizar datos de la venta
                $ventas = Sdba::table('ventas');
                $ventas->where('id_venta', $id_venta);
                $data_venta = array(
                    'fecha' => $fecha,
                    'total' => $total_venta,
                    'cliente' => $id_cliente,
                    'tipo' => $tipo,
                    'forma' => $forma
                );
                $ventas->update($data_venta);
                
                // PASO 5: Registrar en log de auditoría
                $observaciones = "Venta editada. Total anterior: {$venta_data['total']}, Total nuevo: $total_venta";
                registrarLog(
                    'ventas', 
                    $id_venta, 
                    'EDIT', 
                    $id_usuario, 
                    $venta_data, 
                    array(
                        'fecha' => $fecha,
                        'total' => $total_venta,
                        'cliente' => $id_cliente,
                        'tipo' => $tipo,
                        'forma' => $forma,
                        'productos_count' => count($productos)
                    ), 
                    $observaciones
                );
                
                $respuestaOk = true;
                $mensajeError = 'Venta actualizada correctamente';
                $venta_id = $id_venta;
                
            } catch (Exception $e) {
                $respuestaOk = false;
                $mensajeError = $e->getMessage();
            }
        }
    }
}

$salidaJson = array(
    'respuesta' => $respuestaOk,
    'mensaje' => $mensajeError,
    'venta_id' => $venta_id
);

echo json_encode($salidaJson);
?>