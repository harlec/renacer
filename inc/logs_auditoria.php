<?php
// Archivo para crear tabla de logs de auditoría
include('inc/sdba/sdba.php');

function crearTablaLogs() {
    try {
        // Intentar crear la tabla de logs
        $sql = "CREATE TABLE IF NOT EXISTS `log_ediciones` (
          `id_log` int(11) NOT NULL AUTO_INCREMENT,
          `tabla_afectada` varchar(50) NOT NULL,
          `id_registro` int(11) NOT NULL,
          `accion` varchar(20) NOT NULL,
          `usuario_id` int(11) NOT NULL,
          `fecha_edicion` datetime NOT NULL,
          `datos_anteriores` text,
          `datos_nuevos` text,
          `ip_usuario` varchar(45),
          `observaciones` text,
          PRIMARY KEY (`id_log`),
          KEY `idx_tabla_registro` (`tabla_afectada`, `id_registro`),
          KEY `idx_usuario` (`usuario_id`),
          KEY `idx_fecha` (`fecha_edicion`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8";
        
        // Ejecutar usando la conexión de Sdba
        $conexion = Sdba::connection();
        $resultado = $conexion->query($sql);
        
        if ($resultado) {
            echo "Tabla de logs creada exitosamente";
        } else {
            echo "Error al crear tabla de logs: " . $conexion->error;
        }
        
    } catch (Exception $e) {
        echo "Error: " . $e->getMessage();
    }
}

function registrarLog($tabla, $id_registro, $accion, $usuario_id, $datos_anteriores = null, $datos_nuevos = null, $observaciones = '') {
    try {
        $log = Sdba::table('log_ediciones');
        
        $data_log = array(
            'id_log' => '',
            'tabla_afectada' => $tabla,
            'id_registro' => $id_registro,
            'accion' => $accion,
            'usuario_id' => $usuario_id,
            'fecha_edicion' => date('Y-m-d H:i:s'),
            'datos_anteriores' => $datos_anteriores ? json_encode($datos_anteriores) : null,
            'datos_nuevos' => $datos_nuevos ? json_encode($datos_nuevos) : null,
            'ip_usuario' => $_SERVER['REMOTE_ADDR'] ?? '',
            'observaciones' => $observaciones
        );
        
        return $log->insert($data_log);
        
    } catch (Exception $e) {
        // Si falla, intentar crear la tabla primero
        crearTablaLogs();
        // Intentar de nuevo
        try {
            $log = Sdba::table('log_ediciones');
            return $log->insert($data_log);
        } catch (Exception $e2) {
            return false;
        }
    }
}

// Si se ejecuta directamente, crear la tabla
if (basename(__FILE__) == basename($_SERVER["SCRIPT_NAME"])) {
    crearTablaLogs();
}
?>