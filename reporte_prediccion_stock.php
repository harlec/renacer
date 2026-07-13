<?php
include('inc/control.php');
$usuarios_permitidos = ['hars', 'susan', 'robert'];
if (!in_array($_SESSION['usuario'], $usuarios_permitidos)) {
    header("Location: dashboard.php");
    exit;
}

include('inc/sdba/sdba.php');
require_once('inc/prediccion_stock.php');

$prediccion_data = obtener_prediccion_stock(30);

$filas = '';

foreach ($prediccion_data['prediccion'] as $prod) {
    $nivel_label = ucfirst($prod['nivel']);
    $falta_manana_label = $prod['falta_manana'] > 0 ? number_format($prod['falta_manana'], 2) : 'Alcanza';
    $filas .= '<tr>
                <td>' . htmlspecialchars($prod['codigo_producto'], ENT_QUOTES, 'UTF-8') . '</td>
                <td style="text-transform:uppercase">' . htmlspecialchars($prod['nombre'] ?: 'Sin nombre', ENT_QUOTES, 'UTF-8') . '</td>
                <td>' . number_format($prod['stock_actual'], 2) . '</td>
                <td>' . number_format($prod['velocidad_diaria'], 2) . '</td>
                <td>' . $falta_manana_label . '</td>
                <td>' . number_format($prod['necesario_semana'], 2) . '</td>
                <td>' . formatear_dias_restantes($prod['dias_restantes']) . '</td>
                <td>' . $nivel_label . '</td>
              </tr>';
}

foreach ($prediccion_data['agotados'] as $prod) {
    $filas .= '<tr>
                <td>' . htmlspecialchars($prod['codigo_producto'], ENT_QUOTES, 'UTF-8') . '</td>
                <td style="text-transform:uppercase">' . htmlspecialchars($prod['nombre'] ?: 'Sin nombre', ENT_QUOTES, 'UTF-8') . '</td>
                <td>0.00</td>
                <td>-</td>
                <td>-</td>
                <td>-</td>
                <td>Ya sin stock</td>
                <td>Agotado</td>
              </tr>';
}

foreach ($prediccion_data['sin_movimiento'] as $prod) {
    $filas .= '<tr>
                <td>' . htmlspecialchars($prod['codigo_producto'], ENT_QUOTES, 'UTF-8') . '</td>
                <td style="text-transform:uppercase">' . htmlspecialchars($prod['nombre'] ?: 'Sin nombre', ENT_QUOTES, 'UTF-8') . '</td>
                <td>' . number_format($prod['stock_actual'], 2) . '</td>
                <td>-</td>
                <td>-</td>
                <td>-</td>
                <td>Sin ventas en 30 días</td>
                <td>Sin movimiento</td>
              </tr>';
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Sistema - Predicción de Quiebre de Stock</title>
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" type="text/css" href="/assets/css/bootstrap.min.css">
    <link rel="stylesheet" type="text/css" href="/assets/css/custom.css">
    <link rel="stylesheet" type="text/css" href="//cdn.datatables.net/1.10.22/css/jquery.dataTables.min.css">
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/buttons/1.6.4/css/buttons.dataTables.min.css">
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
            <?php menu('5'); ?>
          </div>
          <div class="submenu">
            <ul class="subtop-tabs">
                <li>
                    <a href="reportes.php">Reporte Stock</a>
                </li>
                <li>
                    <a href="reportes_vd.php">Ventas diarias</a>
                </li>
                <li>
                    <a href="reporte_ventas.php">Reporte ventas</a>
                </li>
                <li>
                    <a href="reporte_compras.php">Reporte compras</a>
                </li>
                <li>
                    <a href="reporte_kardex.php">Reporte Kardex</a>
                </li>
                <li>
                    <a href="reporte_mv.php">Reporte Mas vendido</a>
                </li>
                <li class="active">
                    <a href="reporte_prediccion_stock.php">Predicción de Stock</a>
                </li>
            </ul>
          </div>
        </nav>
        <div class="kbg">
            <div class="cuerpofull">
                <div class="titulo">
                    <h3>Predicción de Quiebre de Stock</h3>
                </div>
                <div class="container-fluid">
                    <div class="row">
                        <div class="col-md-12">
                            <div class="kdashboard">
                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="panel panel-default pa">
                                            <div class="panel-body table-responsive">
                                                <h3 class="">Predicción de Quiebre de Stock — ventana de 30 días</h3>
                                                <p style="color:#888">
                                                    <?= count($prediccion_data['prediccion']) ?> productos por agotarse ·
                                                    <?= count($prediccion_data['agotados']) ?> ya sin stock ·
                                                    <?= count($prediccion_data['sin_movimiento']) ?> con stock bajo sin ventas recientes
                                                </p>
                                                <table id="datos">
                                                    <thead>
                                                        <tr>
                                                            <th>Código</th>
                                                            <th>Producto</th>
                                                            <th>Stock Actual</th>
                                                            <th>Venta Diaria Prom.</th>
                                                            <th>Falta para Mañana</th>
                                                            <th>Necesario para la Semana</th>
                                                            <th>Cobertura</th>
                                                            <th>Estado</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <?php echo $filas; ?>
                                                    </tbody>
                                                </table>
                                                <br><br>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.3.1/jquery.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js"></script>
    <script src="//cdn.datatables.net/1.10.22/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/1.6.1/js/dataTables.buttons.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/1.6.1/js/buttons.flash.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.1.3/jszip.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/pdfmake.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/vfs_fonts.js"></script>
    <script src="https://cdn.datatables.net/buttons/1.6.1/js/buttons.html5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/1.6.1/js/buttons.print.min.js"></script>
    <script>
    $(document).ready(function() {
        $.extend(true, $.fn.dataTable.defaults, {
            "language": {
                "decimal": ",",
                "thousands": ".",
                "info": "Mostrando registros del _START_ al _END_ de un total de _TOTAL_ registros",
                "infoEmpty": "Mostrando registros del 0 al 0 de un total de 0 registros",
                "infoPostFix": "",
                "infoFiltered": "(filtrado de un total de _MAX_ registros)",
                "loadingRecords": "Cargando...",
                "lengthMenu": "Mostrar _MENU_ registros",
                "paginate": {
                    "first": "Primero",
                    "last": "Último",
                    "next": "Siguiente",
                    "previous": "Anterior"
                },
                "processing": "Procesando...",
                "search": "Buscar:",
                "searchPlaceholder": "Término de búsqueda",
                "zeroRecords": "No se encontraron resultados",
                "emptyTable": "Ningún dato disponible en esta tabla",
                "aria": {
                    "sortAscending": ": Activar para ordenar la columna de manera ascendente",
                    "sortDescending": ": Activar para ordenar la columna de manera descendente"
                },
                "buttons": {
                    "excel": "Excel",
                    "pdf": "PDF",
                    "print": "Imprimir"
                }
            }
        });

        $('#datos').DataTable({
            dom: 'Bfrtip',
            order: [],
            buttons: [
                'excel',
                'pdf'
            ]
        });
    });
    </script>
</body>
</html>
