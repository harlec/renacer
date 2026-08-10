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
    <title>Sistema - Productos sin Costo</title>
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" type="text/css" href="/assets/css/bootstrap.min.css">
    <link rel="stylesheet" type="text/css" href="/assets/css/custom.css">
    <link rel="stylesheet" type="text/css" href="//cdn.datatables.net/1.10.22/css/jquery.dataTables.min.css">
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/buttons/1.6.4/css/buttons.dataTables.min.css">
    <style>
        :root { --c-navy: #1e3a4c; --c-orange: #ff5023; }
        .resumen-row { display:grid; grid-template-columns: repeat(3, 1fr); gap:14px; margin-bottom:20px; }
        .resumen-card { background:#fff; border-radius:12px; padding:16px 18px; box-shadow:0 1px 3px rgba(0,0,0,.08); }
        .resumen-card .rc-label { font-size:12px; color:#888; font-weight:600; text-transform:uppercase; display:flex; align-items:center; gap:6px; }
        .resumen-card .rc-valor { font-size:24px; font-weight:800; color:var(--c-navy); margin-top:6px; }
        .resumen-card.alerta { background:#fff3ee; }
        .resumen-card.alerta .rc-valor { color:var(--c-orange); }
        @media (max-width: 700px) { .resumen-row { grid-template-columns: 1fr; } }
        tr.fila-90 { background:#fff3ee !important; }
        tr.fila-90 td:first-child { border-left:3px solid var(--c-orange); }
        @media print {
            .navbar, .submenu, .dt-buttons, .dataTables_filter, .dataTables_paginate, .dataTables_length, .dataTables_info { display:none !important; }
        }
    </style>
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
                <li><a href="reporte_huevos.php">Reporte Huevos</a></li>
                <li class="active"><a href="reporte_productos_sin_costo.php">Productos sin costo</a></li>
            </ul>
          </div>
        </nav>
        <div class="kbg">
            <div class="cuerpofull">
                <div class="titulo">
                    <h3>Productos sin Costo</h3>
                </div>
                <div class="container-fluid">
                    <div class="row">
                        <div class="col-md-12">
                            <div class="kdashboard">
                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="panel panel-default pa">
                                            <div class="panel-body table-responsive">
                                                <h3 class="">Productos sin Costo — venta del año en curso</h3>

                                                <div class="resumen-row" id="resumenRow">
                                                    <div class="resumen-card alerta">
                                                        <div class="rc-label"><i class="fas fa-exclamation-triangle"></i> Productos sin costo</div>
                                                        <div class="rc-valor" id="resCantidad">0</div>
                                                    </div>
                                                    <div class="resumen-card alerta">
                                                        <div class="rc-label"><i class="fas fa-cash-register"></i> Venta que representan</div>
                                                        <div class="rc-valor" id="resVenta">S/ 0.00</div>
                                                        <div style="font-size:12px;color:#888;margin-top:2px" id="resVentaPct">0% del total</div>
                                                    </div>
                                                    <div class="resumen-card">
                                                        <div class="rc-label"><i class="fas fa-bullseye"></i> Para cubrir el 90%</div>
                                                        <div class="rc-valor" id="resPara90">0 productos</div>
                                                    </div>
                                                </div>

                                                <p style="color:#888">Las filas resaltadas en naranja son las que hay que cargar primero — suman el 90% de la venta sin costo.</p>

                                                <table id="datos" class="table" style="width:100%">
                                                    <thead>
                                                        <tr>
                                                            <th>Producto</th>
                                                            <th>Categoría</th>
                                                            <th>Venta acumulada del año (S/)</th>
                                                            <th>Unidades vendidas</th>
                                                            <th>Precio de venta actual</th>
                                                            <th>% acumulado</th>
                                                            <th>Top90</th>
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
    <script src="https://use.fontawesome.com/releases/v5.7.2/css/all.css"></script>
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
                "paginate": { "first": "Primero", "last": "Último", "next": "Siguiente", "previous": "Anterior" },
                "processing": "Procesando...",
                "search": "Buscar:",
                "searchPlaceholder": "Término de búsqueda",
                "zeroRecords": "No se encontraron resultados",
                "emptyTable": "Ningún dato disponible en esta tabla",
                "buttons": { "excel": "Excel", "pdf": "PDF", "print": "Imprimir" }
            }
        });

        function money(n) { return 'S/ ' + (parseFloat(n) || 0).toFixed(2); }

        function renderMonto(data, type) {
            if (type !== 'display') return data;
            return (parseFloat(data) || 0).toLocaleString('es-PE', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        }

        var productosPara90 = 0;

        function renderResumen(r) {
            r = r || {};
            productosPara90 = r.productos_para_90 || 0;
            document.getElementById('resCantidad').textContent = r.cantidad_sin_costo || 0;
            document.getElementById('resVenta').textContent = money(r.venta_sin_costo);
            document.getElementById('resVentaPct').textContent = (r.pct_del_total || 0) + '% del total facturado este año';
            document.getElementById('resPara90').textContent = productosPara90 + (productosPara90 === 1 ? ' producto' : ' productos');
        }

        var tabla = $('#datos').DataTable({
            dom: 'Bfrtip',
            order: [[2, 'desc']],
            ajax: {
                url: 'inc/get_productos_sin_costo.php',
                dataSrc: function (json) {
                    renderResumen(json.resumen);
                    return json.ok ? json.data : [];
                }
            },
            columns: [
                { title: 'Producto' },
                { title: 'Categoría' },
                { title: 'Venta acumulada del año (S/)', render: renderMonto },
                { title: 'Unidades' },
                { title: 'Precio de venta actual', render: renderMonto },
                { title: '% acumulado', render: function (data, type) { return type === 'display' ? data + '%' : data; } },
                { title: 'Top90', visible: false, searchable: false }
            ],
            // El flag "en_top_90" viene calculado del backend en el orden real por venta
            // (columna 6, oculta) — así el resaltado se mantiene correcto sin importar cómo
            // el usuario reordene, filtre o pagine la tabla en el navegador.
            rowCallback: function (row, data) {
                $(row).toggleClass('fila-90', parseInt(data[6]) === 1);
            },
            buttons: [
                { extend: 'excel', title: 'Productos sin Costo', filename: 'productos_sin_costo' },
                { extend: 'pdf', title: 'Productos sin Costo', filename: 'productos_sin_costo' },
                { extend: 'print', title: 'Productos sin Costo' }
            ]
        });
    });
    </script>
</body>
</html>
