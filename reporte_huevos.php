<?php
include('inc/control.php');
if ($_SESSION['type']=='operador') {
    header("Location: dashboard.php");
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Sistema - Reporte Huevos por Variante</title>
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
                <li><a href="reportes.php">Reporte Stock</a></li>
                <li><a href="reportes_vd.php">Ventas diarias</a></li>
                <li><a href="reporte_ventas.php">Reporte ventas</a></li>
                <li><a href="reporte_compras.php">Reporte compras</a></li>
                <li><a href="reporte_kardex.php">Reporte Kardex</a></li>
                <li><a href="reporte_mv.php">Ventas por categoría</a></li>
                <li><a href="reporte_prediccion_stock.php">Predicción de Stock</a></li>
                <li class="active"><a href="reporte_huevos.php">Reporte Huevos</a></li>
			<li>
				<a href="reporte_productos_sin_costo.php">Productos sin costo</a>
			</li>
            </ul>
          </div>
        </nav>
        <div class="kbg">
            <div class="cuerpofull">
                <div class="titulo">
                    <h3>Reporte Huevos por Variante</h3>
                </div>
                <div class="container-fluid">
                    <div class="row">
                        <div class="col-md-12">
                            <div class="kdashboard">
                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="panel panel-default pa">
                                            <div class="panel-body table-responsive">
                                                <h3 class="">Reporte Huevos por Variante</h3>
                                                <div class="row" style="margin-bottom:16px">
                                                    <div class="col-md-3">
                                                        <select id="periodoSelect" class="form-control">
                                                            <option value="ultimo_dia">Último día</option>
                                                            <option value="ayer">Ayer</option>
                                                            <option value="ultima_semana">Última semana</option>
                                                            <option value="mes_actual">Lo que va del mes</option>
                                                            <option value="mes_anterior">Mes anterior</option>
                                                            <option value="ultimo_trimestre">Último trimestre</option>
                                                            <option value="todo_el_anio">Todo el año</option>
                                                            <option value="siempre">Siempre</option>
                                                        </select>
                                                    </div>
                                                </div>
                                                <table id="datos" class="table" style="width:100%">
                                                    <thead>
                                                        <tr>
                                                            <th>Producto</th>
                                                            <th>Cantidad</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody></tbody>
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
                "buttons": {
                    "excel": "Excel",
                    "pdf": "PDF",
                    "print": "Imprimir"
                }
            }
        });

        var tabla = $('#datos').DataTable({
            dom: 'Bfrtip',
            order: [[1, 'desc']],
            ajax: {
                url: 'inc/get_reporte_huevos.php',
                data: function (d) {
                    d.periodo = $('#periodoSelect').val();
                }
            },
            columns: [
                { title: 'Producto' },
                { title: 'Cantidad' }
            ],
            buttons: [
                'excel',
                'pdf'
            ]
        });

        $('#periodoSelect').on('change', function () {
            tabla.ajax.reload();
        });
    });
    </script>
</body>
</html>
