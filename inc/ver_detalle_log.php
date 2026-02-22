<?php
session_start();

// Solo administradores pueden ver los detalles
if ($_SESSION['type'] != 'admin') {
    echo 'No autorizado';
    exit();
}

include('sdba/sdba.php');

if (isset($_POST['log_id'])) {
    $log_id = $_POST['log_id'];
    
    $log = Sdba::table('log_ediciones');
    $log->where('id_log', $log_id);
    $log_data = $log->get_one();
    
    if ($log_data) {
        // Obtener información del usuario
        $usuario = Sdba::table('usuarios');
        $usuario->where('id_usuario', $log_data['usuario_id']);
        $usuario_data = $usuario->get_one();
        $nombre_usuario = $usuario_data ? $usuario_data['usuario'] : 'Usuario #' . $log_data['usuario_id'];
        
        echo '<div class="row">';
        echo '<div class="col-md-6">';
        echo '<h5>Información General</h5>';
        echo '<p><strong>Tabla:</strong> ' . $log_data['tabla_afectada'] . '</p>';
        echo '<p><strong>ID Registro:</strong> ' . $log_data['id_registro'] . '</p>';
        echo '<p><strong>Acción:</strong> ' . $log_data['accion'] . '</p>';
        echo '<p><strong>Usuario:</strong> ' . $nombre_usuario . '</p>';
        echo '<p><strong>Fecha:</strong> ' . date('d/m/Y H:i:s', strtotime($log_data['fecha_edicion'])) . '</p>';
        echo '<p><strong>IP:</strong> ' . $log_data['ip_usuario'] . '</p>';
        echo '</div>';
        
        echo '<div class="col-md-6">';
        echo '<h5>Observaciones</h5>';
        echo '<p>' . ($log_data['observaciones'] ?: 'Sin observaciones') . '</p>';
        echo '</div>';
        echo '</div>';
        
        if ($log_data['datos_anteriores'] || $log_data['datos_nuevos']) {
            echo '<hr>';
            echo '<div class="row">';
            
            if ($log_data['datos_anteriores']) {
                echo '<div class="col-md-6">';
                echo '<h5>Datos Anteriores</h5>';
                $datos_anteriores = json_decode($log_data['datos_anteriores'], true);
                if ($datos_anteriores) {
                    echo '<pre style="max-height: 300px; overflow-y: auto;">';
                    echo formatearDatos($datos_anteriores);
                    echo '</pre>';
                } else {
                    echo '<p class="text-muted">Datos no disponibles</p>';
                }
                echo '</div>';
            }
            
            if ($log_data['datos_nuevos']) {
                echo '<div class="col-md-6">';
                echo '<h5>Datos Nuevos</h5>';
                $datos_nuevos = json_decode($log_data['datos_nuevos'], true);
                if ($datos_nuevos) {
                    echo '<pre style="max-height: 300px; overflow-y: auto;">';
                    echo formatearDatos($datos_nuevos);
                    echo '</pre>';
                } else {
                    echo '<p class="text-muted">Datos no disponibles</p>';
                }
                echo '</div>';
            }
            
            echo '</div>';
        }
        
    } else {
        echo '<p class="text-danger">Log no encontrado</p>';
    }
} else {
    echo '<p class="text-danger">ID de log no proporcionado</p>';
}

function formatearDatos($datos) {
    $output = '';
    foreach ($datos as $key => $value) {
        if (is_array($value)) {
            $output .= "<strong>$key:</strong> " . json_encode($value, JSON_PRETTY_PRINT) . "\n";
        } else {
            $output .= "<strong>$key:</strong> $value\n";
        }
    }
    return $output;
}
?>