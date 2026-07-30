<?php
include('inc/control.php');
if ($_SESSION['type'] !== 'admin') { header("Location: dashboard.php"); exit; }
include('inc/sdba/sdba.php');
include('inc/config_facturacion.php');

$nubefact_ruta   = get_config('nubefact_ruta');
$nubefact_token  = get_config('nubefact_token');
$nubefact_activo = get_config('nubefact_activo', '0');
$migo_token      = get_config('migo_token');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Configuración Facturación Electrónica – Renacer</title>
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" type="text/css" href="/assets/css/bootstrap.min.css">
    <link rel="stylesheet" type="text/css" href="/assets/css/custom.css">
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
                <i class="fas fa-file-invoice" style="margin-right:8px"></i>
                Configuración Facturación Electrónica
            </h3>
        </div>

        <div id="alert-area"></div>

        <div class="panel panel-default">
            <div class="panel-heading"><b>Nubefact (facturación electrónica SUNAT)</b></div>
            <div class="panel-body">
                <div class="form-group">
                    <label>Ruta (URL de la API)</label>
                    <input type="text" class="form-control" id="nubefact_ruta" value="<?php echo htmlspecialchars($nubefact_ruta); ?>" placeholder="https://api.nubefact.com/api/v1/xxxxxxxx-...">
                </div>
                <div class="form-group">
                    <label>Token</label>
                    <input type="text" class="form-control" id="nubefact_token" value="<?php echo htmlspecialchars($nubefact_token); ?>">
                </div>
                <div class="checkbox">
                    <label>
                        <input type="checkbox" id="nubefact_activo" <?php echo $nubefact_activo == '1' ? 'checked' : ''; ?>>
                        Activo
                    </label>
                </div>
                <button class="btn btn-default" id="btn-probar-nubefact"><i class="fas fa-plug"></i> Probar conexión</button>
                <button class="btn btn-success" id="btn-guardar" style="float:right"><i class="fas fa-save"></i> Guardar</button>
            </div>
        </div>

        <div class="panel panel-default">
            <div class="panel-heading"><b>Migo (consulta RUC / DNI)</b></div>
            <div class="panel-body">
                <div class="form-group">
                    <label>Token</label>
                    <input type="text" class="form-control" id="migo_token" value="<?php echo htmlspecialchars($migo_token); ?>">
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.3.1/jquery.min.js"></script>
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js"></script>
<script>
function showAlert(msg, type) {
    $('#alert-area').html(`<div class="alert alert-${type} alert-dismissible">
        <button type="button" class="close" data-dismiss="alert">&times;</button>${msg}</div>`);
}

$('#btn-probar-nubefact').on('click', function(){
    var btn = $(this).prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Probando...');
    $.post('/inc/probar_nubefact.php', {
        ruta: $('#nubefact_ruta').val(),
        token: $('#nubefact_token').val()
    }, function(d){
        showAlert(d.mensaje, d.ok ? 'success' : 'danger');
    }, 'json').fail(function(xhr){
        showAlert('Error del servidor: ' + xhr.status, 'danger');
    }).always(function(){
        btn.prop('disabled', false).html('<i class="fas fa-plug"></i> Probar conexión');
    });
});

$('#btn-guardar').on('click', function(){
    $.post('/inc/guardar_config_facturacion.php', {
        nubefact_ruta: $('#nubefact_ruta').val(),
        nubefact_token: $('#nubefact_token').val(),
        nubefact_activo: $('#nubefact_activo').is(':checked') ? '1' : '0',
        migo_token: $('#migo_token').val()
    }, function(d){
        showAlert(d.ok ? 'Configuración guardada.' : 'Error al guardar.', d.ok ? 'success' : 'danger');
    }, 'json').fail(function(xhr){
        showAlert('Error del servidor: ' + xhr.status, 'danger');
    });
});
</script>
</body>
</html>
