<?php
include('inc/control.php');
if ($_SESSION['type']=='operador') {
    header("Location: dashboard.php");
}
include('inc/sdba/sdba.php');
$categorias = Sdba::table('categorias');
$categorias_list = $categorias->get();
$categorias_options = '';
foreach ($categorias_list as $cat) {
    $categorias_options .= '<option value="' . $cat['id_categoria'] . '">' . htmlspecialchars($cat['nom_cat'], ENT_QUOTES, 'UTF-8') . '</option>';
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Sistema - Ventas por Categoría</title>
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" type="text/css" href="/assets/css/bootstrap.min.css">
    <link rel="stylesheet" type="text/css" href="/assets/css/custom.css">
    <link rel="stylesheet" type="text/css" href="//cdn.datatables.net/1.10.22/css/jquery.dataTables.min.css">
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/buttons/1.6.4/css/buttons.dataTables.min.css">
    <style>
        :root { --c-navy: #1e3a4c; --c-orange: #ff5023; }
        .resumen-row { display:grid; grid-template-columns: repeat(4, 1fr); gap:14px; margin-bottom:20px; }
        .resumen-card { background:#fff; border-radius:12px; padding:16px 18px; box-shadow:0 1px 3px rgba(0,0,0,.08); }
        .resumen-card .rc-label { font-size:12px; color:#888; font-weight:600; text-transform:uppercase; display:flex; align-items:center; gap:6px; }
        .resumen-card .rc-valor { font-size:24px; font-weight:800; color:var(--c-navy); margin-top:6px; }
        .resumen-card.total { background:var(--c-navy); }
        .resumen-card.total .rc-label { color:#bcd; }
        .resumen-card.total .rc-valor { color:#fff; }
        @media (max-width: 700px) { .resumen-row { grid-template-columns: 1fr 1fr; } }
        @media print {
            .navbar, .submenu, #periodoSelect, .dt-buttons, .dataTables_filter, .dataTables_paginate, .dataTables_length, .dataTables_info { display:none !important; }
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
                <li class="active"><a href="reporte_mv.php">Ventas por categoría</a></li>
                <li><a href="reporte_prediccion_stock.php">Predicción de Stock</a></li>
                <li><a href="reporte_huevos.php">Reporte Huevos</a></li>
            </ul>
          </div>
        </nav>
        <div class="kbg">
            <div class="cuerpofull">
                <div class="titulo">
                    <h3>Ventas por Categoría</h3>
                </div>
                <div class="container-fluid">
                    <div class="row">
                        <div class="col-md-12">
                            <div class="kdashboard">
                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="panel panel-default pa">
                                            <div class="panel-body table-responsive">
                                                <h3 class="">Ventas por Categoría</h3>
                                                <div class="row" style="margin-bottom:16px">
                                                    <div class="col-md-3">
                                                        <label style="font-size:12px;color:#888;font-weight:600">Período</label>
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
                                                    <div class="col-md-3">
                                                        <label style="font-size:12px;color:#888;font-weight:600">Ordenar por</label>
                                                        <select id="ordenSelect" class="form-control">
                                                            <option value="2">Monto (mayor a menor)</option>
                                                            <option value="1">Unidades (mayor a menor)</option>
                                                        </select>
                                                    </div>
                                                </div>

                                                <div class="resumen-row" id="resumenRow">
                                                    <div class="resumen-card">
                                                        <div class="rc-label"><i class="fas fa-tags"></i> Categorías con ventas</div>
                                                        <div class="rc-valor" id="resCategorias">0</div>
                                                    </div>
                                                    <div class="resumen-card">
                                                        <div class="rc-label"><i class="fas fa-boxes"></i> Unidades vendidas</div>
                                                        <div class="rc-valor" id="resUnidades">0</div>
                                                    </div>
                                                    <div class="resumen-card">
                                                        <div class="rc-label"><i class="fas fa-star"></i> Categoría top</div>
                                                        <div class="rc-valor" id="resTop" style="font-size:16px">-</div>
                                                    </div>
                                                    <div class="resumen-card total">
                                                        <div class="rc-label"><i class="fas fa-cash-register"></i> Total vendido</div>
                                                        <div class="rc-valor" id="resTotal">S/ 0.00</div>
                                                    </div>
                                                </div>

                                                <table id="datos" class="table" style="width:100%">
                                                    <thead>
                                                        <tr>
                                                            <th>Categoría</th>
                                                            <th>Unidades</th>
                                                            <th>Monto (S/)</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody></tbody>
                                                </table>
                                                <br><br>

                                                <h3 class="">Detalle de productos por categoría</h3>
                                                <div class="row" style="margin-bottom:16px">
                                                    <div class="col-md-4">
                                                        <label style="font-size:12px;color:#888;font-weight:600">Categoría</label>
                                                        <select id="categoriaSelect" class="form-control">
                                                            <option value="">— Elige una categoría —</option>
                                                            <?php echo $categorias_options; ?>
                                                        </select>
                                                    </div>
                                                    <div class="col-md-3">
                                                        <label style="font-size:12px;color:#888;font-weight:600">Ordenar por</label>
                                                        <select id="ordenDetalleSelect" class="form-control">
                                                            <option value="2">Monto (mayor a menor)</option>
                                                            <option value="1">Unidades (mayor a menor)</option>
                                                        </select>
                                                    </div>
                                                </div>
                                                <div id="detalleWrap" style="display:none">
                                                    <table id="datosDetalle" class="table" style="width:100%">
                                                        <thead>
                                                            <tr>
                                                                <th>Producto</th>
                                                                <th>Unidades</th>
                                                                <th>Monto (S/)</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody></tbody>
                                                    </table>
                                                </div>
                                                <div id="detalleVacio" style="color:#888">Elige una categoría para ver el detalle de sus productos.</div>
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

        function money(n) { return 'S/ ' + (parseFloat(n) || 0).toFixed(2); }

        // El dato que llega del backend es numérico crudo (para que DataTables ordene bien);
        // esto solo lo formatea con comas de miles al mostrarlo, sin tocar el valor real
        // que se usa para ordenar/filtrar/exportar.
        function renderMonto(data, type) {
            if (type !== 'display') return data;
            return (parseFloat(data) || 0).toLocaleString('es-PE', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        }

        function renderResumen(r) {
            r = r || {};
            document.getElementById('resCategorias').textContent = r.categorias || 0;
            document.getElementById('resUnidades').textContent = (parseFloat(r.total_unidades) || 0).toFixed(2);
            document.getElementById('resTop').textContent = r.top || '-';
            document.getElementById('resTotal').textContent = money(r.total_monto);
        }

        function periodoLabel() {
            return $('#periodoSelect option:selected').text();
        }

        function periodoSlug() {
            return $('#periodoSelect').val();
        }

        var tabla = $('#datos').DataTable({
            dom: 'Bfrtip',
            order: [[2, 'desc']],
            ajax: {
                url: 'inc/get_reporte_categorias.php',
                data: function (d) {
                    d.periodo = $('#periodoSelect').val();
                },
                dataSrc: function (json) {
                    renderResumen(json.resumen);
                    return json.ok ? json.data : [];
                }
            },
            columns: [
                { title: 'Categoría' },
                { title: 'Unidades' },
                { title: 'Monto (S/)', render: renderMonto }
            ],
            buttons: [
                { extend: 'excel', title: function () { return 'Ventas por Categoría - ' + periodoLabel(); }, filename: function () { return 'ventas_por_categoria_' + periodoSlug(); } },
                { extend: 'pdf', title: function () { return 'Ventas por Categoría - ' + periodoLabel(); }, filename: function () { return 'ventas_por_categoria_' + periodoSlug(); } },
                { extend: 'print', title: function () { return 'Ventas por Categoría - ' + periodoLabel(); } }
            ]
        });

        $('#periodoSelect').on('change', function () {
            tabla.ajax.reload();
        });

        $('#ordenSelect').on('change', function () {
            tabla.order([parseInt($(this).val()), 'desc']).draw();
        });

        // ── Detalle de productos por categoría ───────────────
        var tablaDetalle = null;
        var categoriaSelect = $('#categoriaSelect');
        var detalleWrap = $('#detalleWrap');
        var detalleVacio = $('#detalleVacio');

        function cargarDetalle() {
            var categoria = categoriaSelect.val();
            if (!categoria) {
                detalleWrap.hide();
                detalleVacio.show();
                return;
            }
            detalleVacio.hide();
            detalleWrap.show();

            if (tablaDetalle) {
                tablaDetalle.ajax.reload();
                return;
            }

            tablaDetalle = $('#datosDetalle').DataTable({
                dom: 'Bfrtip',
                order: [[2, 'desc']],
                ajax: {
                    url: 'inc/get_detalle_categoria.php',
                    data: function (d) {
                        d.periodo = $('#periodoSelect').val();
                        d.categoria = categoriaSelect.val();
                    },
                    dataSrc: function (json) {
                        return json.ok ? json.data : [];
                    }
                },
                columns: [
                    { title: 'Producto' },
                    { title: 'Unidades' },
                    { title: 'Monto (S/)', render: renderMonto }
                ],
                buttons: [
                    { extend: 'excel', title: function () { return 'Detalle ' + categoriaSelect.find('option:selected').text() + ' - ' + periodoLabel(); }, filename: function () { return 'detalle_categoria_' + categoriaSelect.val() + '_' + periodoSlug(); } },
                    { extend: 'pdf', title: function () { return 'Detalle ' + categoriaSelect.find('option:selected').text() + ' - ' + periodoLabel(); }, filename: function () { return 'detalle_categoria_' + categoriaSelect.val() + '_' + periodoSlug(); } },
                    { extend: 'print', title: function () { return 'Detalle ' + categoriaSelect.find('option:selected').text() + ' - ' + periodoLabel(); } }
                ]
            });
        }

        categoriaSelect.on('change', cargarDetalle);

        // El período y el orden de la tabla principal también afectan el detalle,
        // si ya hay una categoría elegida.
        $('#periodoSelect').on('change', function () {
            if (tablaDetalle && categoriaSelect.val()) tablaDetalle.ajax.reload();
        });

        $('#ordenDetalleSelect').on('change', function () {
            if (tablaDetalle) tablaDetalle.order([parseInt($(this).val()), 'desc']).draw();
        });
    });
    </script>
</body>
</html>
