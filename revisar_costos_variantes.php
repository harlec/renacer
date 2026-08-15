<?php
// Vista temporal, sin link en ningún menú — accesible solo por URL directa.
// Objetivo: ver qué variantes de qué productos todavía no tienen el precio de costo
// (precioc_vp) cargado, para poder ir completándolos. Se puede borrar cuando ya no haga falta.
include('inc/control.php');
include('inc/sdba/sdba.php');

$conn = new mysqli('localhost', 'admin_renacer', 'ikm169uhn', 'admin_renacer');
$conn->set_charset('utf8');

$datos = '';
$total_variantes = 0;
$sin_costo = 0;
$productos_afectados = [];

$r = $conn->query("
    SELECT p.id_producto, p.nom_prod, COALESCE(c.nom_cat, 'Sin categoría') AS categoria,
           vp.id_vp, va.variante AS variante_nombre, vp.cantidad_vp, vp.precioc_vp
    FROM variante_p vp
    JOIN productos p ON p.id_producto = vp.producto_vp
    LEFT JOIN categorias c ON c.id_categoria = p.categoria
    LEFT JOIN variantes va ON va.id_variante = vp.variante_vp
    WHERE p.estado = '1'
    ORDER BY p.nom_prod ASC, vp.cantidad_vp DESC
");

if ($r) {
    while ($row = $r->fetch_assoc()) {
        $total_variantes++;
        $costo = (float)$row['precioc_vp'];
        $tiene_costo = $costo > 0;
        if (!$tiene_costo) {
            $sin_costo++;
            $productos_afectados[$row['id_producto']] = true;
        }
        $clase_fila = $tiene_costo ? '' : 'sin-costo';
        $datos .= '<tr class="' . $clase_fila . '" data-sin-costo="' . ($tiene_costo ? '0' : '1') . '">
            <td>' . htmlspecialchars($row['nom_prod'], ENT_QUOTES, 'UTF-8') . '</td>
            <td>' . htmlspecialchars($row['categoria'], ENT_QUOTES, 'UTF-8') . '</td>
            <td>' . htmlspecialchars($row['variante_nombre'] ?: 'Sin nombre', ENT_QUOTES, 'UTF-8') . '</td>
            <td>' . number_format((float)$row['cantidad_vp'], 2) . '</td>
            <td>' . ($tiene_costo ? number_format($costo, 2) : '<strong style="color:#c0392b">— vacío —</strong>') . '</td>
            <td><a href="editar_producto.php?id=' . $row['id_producto'] . '" target="_blank">Editar producto ↗</a></td>
        </tr>';
    }
}
$conn->close();

$productos_afectados_n = count($productos_afectados);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>[Temporal] Costos de variantes sin llenar</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" type="text/css" href="/assets/css/bootstrap.min.css">
    <link rel="stylesheet" type="text/css" href="//cdn.datatables.net/1.10.22/css/jquery.dataTables.min.css">
    <style>
        body { padding: 24px; font-family: -apple-system, Segoe UI, Roboto, sans-serif; }
        .aviso { background:#fff3cd; border:1px solid #ffe08a; color:#8a6100; padding:10px 14px; border-radius:8px; margin-bottom:18px; font-size:13px; }
        .resumen-row { display:grid; grid-template-columns: repeat(3, 1fr); gap:14px; margin-bottom:20px; max-width:800px; }
        .resumen-card { background:#fff; border-radius:12px; padding:14px 16px; box-shadow:0 1px 3px rgba(0,0,0,.08); border:1px solid #eee; }
        .resumen-card .rc-label { font-size:12px; color:#888; font-weight:600; text-transform:uppercase; }
        .resumen-card .rc-valor { font-size:22px; font-weight:800; color:#1e3a4c; margin-top:4px; }
        tr.sin-costo { background:#fdecea; }
        .toggle-wrap { margin-bottom:14px; }
    </style>
</head>
<body>
    <div class="aviso">Vista temporal para revisar costos de variantes — no está enlazada en ningún menú. Se puede borrar el archivo <code>revisar_costos_variantes.php</code> cuando ya no haga falta.</div>
    <h3>Costos de variantes sin llenar</h3>

    <div class="resumen-row">
        <div class="resumen-card">
            <div class="rc-label">Variantes totales</div>
            <div class="rc-valor"><?= $total_variantes ?></div>
        </div>
        <div class="resumen-card">
            <div class="rc-label">Variantes sin costo</div>
            <div class="rc-valor" style="color:#c0392b"><?= $sin_costo ?></div>
        </div>
        <div class="resumen-card">
            <div class="rc-label">Productos afectados</div>
            <div class="rc-valor"><?= $productos_afectados_n ?></div>
        </div>
    </div>

    <div class="toggle-wrap">
        <label>
            <input type="checkbox" id="soloFaltantes" checked>
            Mostrar solo variantes sin costo
        </label>
    </div>

    <table id="datos" class="table table-hover table-bordered" style="width:100%">
        <thead>
            <tr>
                <th>Producto</th>
                <th>Categoría</th>
                <th>Variante</th>
                <th>Cantidad</th>
                <th>Costo (precioc_vp)</th>
                <th>Acción</th>
            </tr>
        </thead>
        <tbody><?php echo $datos; ?></tbody>
    </table>

    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.3.1/jquery.min.js"></script>
    <script src="//cdn.datatables.net/1.10.22/js/jquery.dataTables.min.js"></script>
    <script>
    $(document).ready(function () {
        var tabla = $('#datos').DataTable({
            order: [[1, 'asc'], [0, 'asc']],
            pageLength: 50,
            language: {
                search: 'Buscar:',
                info: 'Mostrando _START_ a _END_ de _TOTAL_',
                paginate: { first: 'Primero', last: 'Último', next: 'Siguiente', previous: 'Anterior' },
                zeroRecords: 'No hay filas que coincidan'
            }
        });

        $.fn.dataTable.ext.search.push(function (settings, data, dataIndex) {
            if (!$('#soloFaltantes').is(':checked')) return true;
            var row = tabla.row(dataIndex).node();
            return $(row).data('sin-costo') == 1;
        });

        $('#soloFaltantes').on('change', function () {
            tabla.draw();
        });

        tabla.draw();
    });
    </script>
</body>
</html>
