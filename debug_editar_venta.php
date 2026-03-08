<?php
// Archivo de prueba para debugging de la edición de ventas
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

session_start();

echo "<h2>Debug - Edición de Ventas</h2>";
echo "<h3>Datos recibidos por POST:</h3>";

if ($_POST) {
    echo "<pre>";
    print_r($_POST);
    echo "</pre>";
    
    echo "<h3>Validaciones básicas:</h3>";
    
    $errores_validacion = array();
    
    // Validar campos básicos
    if (empty($_POST['id_venta'])) $errores_validacion[] = "ID de venta faltante";
    if (empty($_POST['fecha'])) $errores_validacion[] = "Fecha faltante";
    if (empty($_POST['cliente'])) $errores_validacion[] = "Cliente faltante";
    if (empty($_POST['productos']) || !is_array($_POST['productos'])) $errores_validacion[] = "Productos faltantes o inválidos";
    
    if (empty($errores_validacion)) {
        echo "<p style='color: green;'>✓ Validaciones básicas OK</p>";
        
        echo "<h3>Productos recibidos:</h3>";
        foreach ($_POST['productos'] as $indice => $producto) {
            echo "<p><strong>Producto $indice:</strong><br>";
            echo "- ID Producto: " . ($producto['producto_id'] ?? 'NO DEFINIDO') . "<br>";
            echo "- Cantidad: " . ($producto['cantidad'] ?? 'NO DEFINIDO') . "<br>";
            echo "- Precio: " . ($producto['precio'] ?? 'NO DEFINIDO') . "<br>";
            echo "- ID VP: " . ($producto['id_vp'] ?? 'NO DEFINIDO') . "</p>";
        }
        
        // Simular respuesta exitosa
        $respuesta = array(
            'respuesta' => true,
            'mensaje' => 'Datos recibidos correctamente (modo debug)',
            'venta_id' => $_POST['id_venta']
        );
        
    } else {
        echo "<div style='color: red;'>";
        foreach ($errores_validacion as $error) {
            echo "<p>✗ $error</p>";
        }
        echo "</div>";
        
        $respuesta = array(
            'respuesta' => false,
            'mensaje' => 'Errores de validación: ' . implode(', ', $errores_validacion),
            'venta_id' => null
        );
    }
    
    // Si es una petición AJAX, devolver JSON
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        header('Content-Type: application/json');
        echo json_encode($respuesta);
        exit();
    }
    
} else {
    echo "<p>No se recibieron datos POST</p>";
}

echo "<hr>";
echo "<h3>Sesión actual:</h3>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";
?>