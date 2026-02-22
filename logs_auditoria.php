<?php
include('inc/control.php');

// Solo administradores pueden ver los logs
if ($_SESSION['type'] != 'admin') {
    header("Location: dashboard.php");
    exit();
}

include('inc/sdba/sdba.php');

// Obtener logs de auditoría
$logs = Sdba::table('log_ediciones');
$logs->order_by('fecha_edicion', 'desc');
$logs->limit(100); // Mostrar solo los últimos 100 registros
$logs_list = $logs->get();

$datos = '';
$i = 1;
foreach ($logs_list as $log) {
    
    // Obtener nombre del usuario
    $usuario = Sdba::table('usuarios');
    $usuario->where('id_usuario', $log['usuario_id']);
    $usuario_data = $usuario->get_one();
    $nombre_usuario = $usuario_data ? $usuario_data['usuario'] : 'Usuario #' . $log['usuario_id'];
    
    // Formatear fecha
    $fecha_formateada = date('d/m/Y H:i:s', strtotime($log['fecha_edicion']));
    
    // Botón para ver detalles
    $btn_detalles = '<button class="btn btn-sm btn-info ver-detalles" data-log="'.$log['id_log'].'">Ver Detalles</button>';
    
    $datos .= '<tr>
                <td>'.$i.'</td>
                <td>'.$log['tabla_afectada'].'</td>
                <td>'.$log['id_registro'].'</td>
                <td><span class="label label-warning">'.$log['accion'].'</span></td>
                <td>'.$nombre_usuario.'</td>
                <td>'.$fecha_formateada.'</td>
                <td>'.$log['ip_usuario'].'</td>
                <td>'.$btn_detalles.'</td>
              </tr>';
    $i++;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Sistema - Logs de Auditoría</title>
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" type="text/css" href="/assets/css/bootstrap.min.css">
    <link rel="stylesheet" type="text/css" href="/assets/css/custom.css">
    <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.7.2/css/all.css">
    <link rel="stylesheet" type="text/css" href="//cdn.datatables.net/1.10.22/css/jquery.dataTables.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/limonte-sweetalert2/10.5.0/sweetalert2.min.css">
</head>

<body class="mobile dashboard">
    <div class="">
        <nav class="navbar navbar-inverse navbar-fixed-top">
          <div class="">
            <div class="navbar-header">
              <button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#navbar" aria-expanded="false" aria-controls="navbar">
                <span class="sr-only">Toggle navigation</span>
                <span class="icon-bar"></span>
                <span class="icon-bar"></span>
                <span class="icon-bar"></span>
              </button>
              <a class="navbar-brand" href="#"><img class="img-responsive logo" src="/assets/img/harlec-sistema.png"></a>
            </div>
            <?php menu('4'); ?>
          </div>
        </nav>
        
        <div class="kbg">
            <div class="cuerpofull">
                <div class="titulo">
                    <h3>Logs de Auditoría</h3>
                    <p class="text-muted">Registro de todas las ediciones realizadas en el sistema</p>
                </div>
                <div class="container-fluid">
                    <div class="row">
                        <div class="col-md-12">
                            <div class="kdashboard">
                                <div class="panel panel-default pa">
                                    <div class="panel-body">
                                        <table id="tabla_logs" class="table table-hover table-striped"> 
                                            <thead> 
                                                <tr> 
                                                    <th>#</th>
                                                    <th>Tabla</th>
                                                    <th>Registro ID</th>
                                                    <th>Acción</th>
                                                    <th>Usuario</th>
                                                    <th>Fecha</th>
                                                    <th>IP</th>
                                                    <th>Acciones</th>
                                                </tr> 
                                            </thead> 
                                            <tbody> 
                                                <?php echo $datos; ?>
                                            </tbody> 
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal para ver detalles -->
    <div class="modal fade" id="modalDetalles" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                    <h4 class="modal-title">Detalles del Log de Auditoría</h4>
                </div>
                <div class="modal-body" id="contenido-detalles">
                    <!-- Contenido cargado por AJAX -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.3.1/jquery.min.js"></script>
    <script src="/assets/js/bootstrap.min.js"></script>
    <script src="//cdn.datatables.net/1.10.22/js/jquery.dataTables.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/limonte-sweetalert2/10.5.0/sweetalert2.min.js"></script>

    <script>
    $(document).ready(function() {
        // Inicializar DataTable
        $('#tabla_logs').DataTable({
            "order": [[5, "desc"]], // Ordenar por fecha descendente
            "language": {
                "url": "//cdn.datatables.net/plug-ins/1.10.22/i18n/Spanish.json"
            }
        });

        // Ver detalles del log
        $(document).on('click', '.ver-detalles', function() {
            var logId = $(this).data('log');
            
            $.ajax({
                url: '/inc/ver_detalle_log.php',
                method: 'POST',
                data: {log_id: logId},
                success: function(response) {
                    $('#contenido-detalles').html(response);
                    $('#modalDetalles').modal('show');
                },
                error: function() {
                    swal('Error', 'No se pudieron cargar los detalles del log', 'error');
                }
            });
        });
    });
    </script>
</body>
</html>