<?php
// Archivo de verificación de instalación - Edición de Ventas
echo "<h2>Verificación de Instalación - Edición de Ventas</h2>";

include('inc/sdba/sdba.php');

$errores = array();
$advertencias = array();
$exitos = array();

// 1. Verificar archivos necesarios
$archivos_necesarios = [
    'editar_venta.php',
    'inc/procesar_edicion_venta.php',
    'inc/logs_auditoria.php',
    'inc/ver_detalle_log.php',
    'logs_auditoria.php'
];

foreach ($archivos_necesarios as $archivo) {
    if (file_exists($archivo)) {
        $exitos[] = "✓ Archivo $archivo existe";
    } else {
        $errores[] = "✗ Archivo $archivo NO encontrado";
    }
}

// 2. Verificar estructura de tabla log_ediciones
try {
    $log_test = Sdba::table('log_ediciones');
    $test_log = $log_test->limit(1)->get();
    $exitos[] = "✓ Tabla log_ediciones existe y es accesible";
} catch (Exception $e) {
    $advertencias[] = "⚠ Tabla log_ediciones no existe - se creará automáticamente en el primer uso";
}

// 3. Verificar permisos de archivos
$archivos_permisos = ['inc/procesar_edicion_venta.php', 'inc/logs_auditoria.php'];
foreach ($archivos_permisos as $archivo) {
    if (file_exists($archivo)) {
        if (is_readable($archivo)) {
            $exitos[] = "✓ Permisos de lectura OK en $archivo";
        } else {
            $errores[] = "✗ No se puede leer el archivo $archivo";
        }
    }
}

// 4. Verificar estructura de tablas principales
$tablas_necesarias = ['ventas', 'detalle_ventas', 'productos', 'stock'];
foreach ($tablas_necesarias as $tabla) {
    try {
        $test_tabla = Sdba::table($tabla);
        $test_tabla->limit(1)->get();
        $exitos[] = "✓ Tabla $tabla existe y es accesible";
    } catch (Exception $e) {
        $errores[] = "✗ Tabla $tabla no es accesible: " . $e->getMessage();
    }
}

// 5. Verificar que ventas.php fue modificado correctamente
if (file_exists('ventas.php')) {
    $contenido_ventas = file_get_contents('ventas.php');
    if (strpos($contenido_ventas, 'editar_venta.php') !== false) {
        $exitos[] = "✓ Botón de editar agregado correctamente en ventas.php";
    } else {
        $advertencias[] = "⚠ El botón de editar podría no estar agregado en ventas.php";
    }
}

// Mostrar resultados
echo "<h3 style='color: green;'>Verificaciones Exitosas</h3>";
foreach ($exitos as $exito) {
    echo "<p style='color: green;'>$exito</p>";
}

if (!empty($advertencias)) {
    echo "<h3 style='color: orange;'>Advertencias</h3>";
    foreach ($advertencias as $advertencia) {
        echo "<p style='color: orange;'>$advertencia</p>";
    }
}

if (!empty($errores)) {
    echo "<h3 style='color: red;'>Errores</h3>";
    foreach ($errores as $error) {
        echo "<p style='color: red;'>$error</p>";
    }
    echo "<p><strong>Hay errores que deben corregirse antes de usar la funcionalidad.</strong></p>";
} else {
    echo "<h3 style='color: blue;'>Estado General</h3>";
    echo "<p style='color: green; font-size: 18px;'><strong>✓ ¡Instalación completada exitosamente!</strong></p>";
    echo "<p>La funcionalidad de edición de ventas está lista para usar.</p>";
    
    echo "<h4>Próximos pasos:</h4>";
    echo "<ol>";
    echo "<li>Ir a 'Listar ventas' y buscar una venta sin comprobante</li>";
    echo "<li>Hacer clic en el botón amarillo 'Editar' (ícono lápiz)</li>";
    echo "<li>Probar agregar, modificar o quitar productos</li>";
    echo "<li>Verificar que el stock se actualiza correctamente</li>";
    echo "<li>Como admin: Revisar logs en logs_auditoria.php</li>";
    echo "</ol>";
}

echo "<hr>";
echo "<p><em>Archivo de verificación ejecutado el " . date('Y-m-d H:i:s') . "</em></p>";
?>

<style>
body { font-family: Arial, sans-serif; margin: 20px; }
h2, h3, h4 { margin-top: 20px; }
p { margin: 5px 0; }
ol { margin-left: 20px; }
</style>