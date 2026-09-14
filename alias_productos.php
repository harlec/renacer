<?php
include('inc/control.php');
if ($_SESSION['type'] !== 'admin') { header("Location: dashboard.php"); exit; }
include('inc/sdba/sdba.php');

$conn = new mysqli('localhost', 'admin_renacer', 'ikm169uhn', 'admin_renacer');
$conn->set_charset('utf8');

$alias = [];
$r = $conn->query("
    SELECT pa.id_alias, pa.alias, pa.id_producto, pa.fecha_creacion, pr.nom_prod
    FROM producto_alias pa
    JOIN productos pr ON pr.id_producto = pa.id_producto
    ORDER BY pa.alias ASC
");
if ($r) {
    while ($row = $r->fetch_assoc()) {
        $alias[] = $row;
    }
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Diccionario de apodos – Renacer</title>
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" type="text/css" href="/assets/css/bootstrap.min.css">
    <link rel="stylesheet" type="text/css" href="/assets/css/custom.css">
    <link rel="stylesheet" type="text/css" href="/assets/css/select2.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>
<body class="mobile dashboard">
<div class="">
    <nav class="navbar navbar-inverse navbar-fixed-top">
        <div class="">
            <div class="navbar-header">
                <button type="button" class="navbar-toggle collapsed" data-toggle="collapse"
                    data-target="#navbar" aria-expanded="false" aria-controls="navbar">
                    <span class="sr-only">Toggle navigation</span>
                    <span class="icon-bar"></span><span class="icon-bar"></span><span class="icon-bar"></span>
                </button>
                <a class="navbar-brand" href="#"><img class="img-responsive logo" src="/assets/img/harlec-sistema.png"></a>
            </div>
            <?php menu('8'); ?>
        </div>
    </nav>

    <div class="container-fluid" style="margin-top:70px;max-width:900px">
        <div class="page-header">
            <h3>
                <i class="fas fa-book" style="margin-right:8px"></i>
                Diccionario de apodos (Tomar pedido con IA)
            </h3>
        </div>

        <p class="help-block">
            Cuando un cliente pide un producto con un apodo o palabra regional que no se parece en nada
            al nombre real (ej. "casillero de huevo", "cubeta de huevo", "jaba de huevo" para huevos),
            "Tomar pedido con IA" no siempre lo puede adivinar comparando letras. Agrega aquí ese apodo
            y a qué producto de tu catálogo se refiere, y la próxima vez lo va a reconocer directo.
        </p>

        <div id="alert-area"></div>

        <div class="panel panel-default">
            <div class="panel-heading"><b>Agregar apodo</b></div>
            <div class="panel-body">
                <div class="row">
                    <div class="col-sm-5">
                        <div class="form-group">
                            <label>Apodo / palabra que usa el cliente</label>
                            <input type="text" class="form-control" id="nuevo-alias" placeholder="ej. casillero de huevo">
                        </div>
                    </div>
                    <div class="col-sm-5">
                        <div class="form-group">
                            <label>Producto real en el catálogo</label>
                            <select class="form-control" id="nuevo-producto" style="width:100%"></select>
                        </div>
                    </div>
                    <div class="col-sm-2">
                        <label>&nbsp;</label>
                        <button class="btn btn-success btn-block" id="btn-agregar-alias"><i class="fas fa-plus"></i> Agregar</button>
                    </div>
                </div>
            </div>
        </div>

        <div class="panel panel-default">
            <div class="panel-heading"><b>Apodos guardados (<?= count($alias) ?>)</b></div>
            <table class="table table-hover" style="margin-bottom:0">
                <thead>
                    <tr>
                        <th>Apodo</th>
                        <th>Producto</th>
                        <th style="width:60px"></th>
                    </tr>
                </thead>
                <tbody id="tabla-alias-body">
                    <?php if (empty($alias)): ?>
                        <tr><td colspan="3" class="text-muted text-center" style="padding:20px">Todavía no hay apodos guardados.</td></tr>
                    <?php else: foreach ($alias as $a): ?>
                        <tr data-id="<?= (int)$a['id_alias'] ?>">
                            <td><?= htmlspecialchars($a['alias']) ?></td>
                            <td><?= htmlspecialchars($a['nom_prod']) ?></td>
                            <td><button type="button" class="btn btn-danger btn-xs btn-eliminar-alias"><i class="fas fa-trash"></i></button></td>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.3.1/jquery.min.js"></script>
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js"></script>
<script src="/assets/js/select2.full.min.js"></script>
<script>
function showAlert(msg, type) {
    $('#alert-area').html(`<div class="alert alert-${type} alert-dismissible">
        <button type="button" class="close" data-dismiss="alert">&times;</button>${msg}</div>`);
}

$('#nuevo-producto').select2({
    ajax: {
        url: 'inc/buscar_producto_admin.php',
        dataType: 'json',
        delay: 250,
        data: params => ({ q: params.term || '' }),
        processResults: data => ({ results: data })
    },
    placeholder: '-- buscar producto --',
    allowClear: true,
    minimumInputLength: 2,
    width: '100%'
});

$('#btn-agregar-alias').on('click', function() {
    const $btn = $(this);
    const alias = $('#nuevo-alias').val().trim();
    const idProducto = $('#nuevo-producto').val();

    if (!alias) { showAlert('Escribe el apodo que usa el cliente.', 'danger'); return; }
    if (!idProducto) { showAlert('Elige a qué producto del catálogo se refiere.', 'danger'); return; }

    $btn.prop('disabled', true);
    $.post('inc/guardar_alias_producto.php', { alias: alias, id_producto: idProducto }, function(d) {
        if (d.ok) {
            location.reload();
        } else {
            showAlert(d.mensaje || 'No se pudo guardar el apodo', 'danger');
            $btn.prop('disabled', false);
        }
    }, 'json').fail(function() {
        showAlert('Error de conexión con el servidor', 'danger');
        $btn.prop('disabled', false);
    });
});

$('#tabla-alias-body').on('click', '.btn-eliminar-alias', function() {
    const $tr = $(this).closest('tr');
    const idAlias = $tr.data('id');
    if (!confirm('¿Quitar este apodo del diccionario?')) return;

    $.post('inc/eliminar_alias_producto.php', { id_alias: idAlias }, function(d) {
        if (d.ok) {
            location.reload();
        } else {
            showAlert(d.mensaje || 'No se pudo eliminar', 'danger');
        }
    }, 'json').fail(function() {
        showAlert('Error de conexión con el servidor', 'danger');
    });
});
</script>
</body>
</html>
