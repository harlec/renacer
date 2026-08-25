<?php
include('inc/control.php');
if ($_SESSION['type'] !== 'admin') { header("Location: dashboard.php"); exit; }
include('inc/sdba/sdba.php');
include('inc/config_facturacion.php');

function solo_hora_minuto($valor) {
    return $valor ? substr($valor, 0, 5) : '';
}

$horario_lv_ingreso  = solo_hora_minuto(get_config('planilla_horario_lv_ingreso'));
$horario_lv_salida   = solo_hora_minuto(get_config('planilla_horario_lv_salida'));
$horario_sab_ingreso = solo_hora_minuto(get_config('planilla_horario_sab_ingreso'));
$horario_sab_salida  = solo_hora_minuto(get_config('planilla_horario_sab_salida'));
$horario_dom_ingreso = solo_hora_minuto(get_config('planilla_horario_dom_ingreso'));
$horario_dom_salida  = solo_hora_minuto(get_config('planilla_horario_dom_salida'));
$factor_tardanza      = get_config('planilla_factor_tardanza', '2');
$dias_mes_referencia  = get_config('planilla_dias_mes_referencia', '30');

$conn_cargos = new mysqli('localhost', 'admin_renacer', 'ikm169uhn', 'admin_renacer');
$conn_cargos->set_charset('utf8');
$cargos_filas = '';
$rc = $conn_cargos->query("SELECT id_cargo, nombre FROM cargos WHERE estado = '1' ORDER BY nombre");
if ($rc) {
    while ($c = $rc->fetch_assoc()) {
        $cargos_filas .= '<tr>
            <td>' . htmlspecialchars($c['nombre']) . '</td>
            <td><button type="button" class="btn btn-danger btn-xs btn-borrar-cargo" data-id="' . $c['id_cargo'] . '"><i class="fa fa-trash"></i></button></td>
        </tr>';
    }
}
$conn_cargos->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Configuración de Planillas – Renacer</title>
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
            <?php menu('2'); ?>
        </div>
        <div class="submenu">
            <ul class="subtop-tabs">
                <li><a class="" href="agregar_usuario.php">Registrar usuario</a></li>
                <li><a class="" href="ver_usuarios.php">Listar usuarios</a></li>
                <li><a class="" href="agregar_empleado.php">Agregar colaborador</a></li>
                <li><a class="" href="ver_empleados.php">Listar colaboradores</a></li>
                <li><a class="" href="asistencia.php">Asistencia</a></li>
                <li><a class="" href="planillas.php">Planillas</a></li>
                <li><a class="" href="movimientos.php">Adelantos y abarrotes</a></li>
                <li class="active"><a class="" href="configuracion_planillas.php">Config. planillas</a></li>
            </ul>
        </div>
    </nav>

    <div class="container-fluid" style="margin-top:70px;max-width:900px">
        <div class="page-header">
            <h3>
                <i class="fas fa-business-time" style="margin-right:8px"></i>
                Configuración de Planillas
            </h3>
        </div>

        <div id="alert-area"></div>

        <div class="panel panel-default">
            <div class="panel-heading"><b>Horario general por tipo de día</b></div>
            <div class="panel-body">
                <p class="help-block">
                    Horario que aplica por defecto a todos los colaboradores. Un colaborador puede tener un
                    horario propio distinto (se configura en su ficha), que tiene prioridad sobre este general.
                </p>
                <div class="row">
                    <div class="col-sm-4">
                        <label><b>Lunes a viernes</b></label>
                        <div class="row">
                            <div class="col-sm-6">
                                <div class="form-group">
                                    <label>Ingreso</label>
                                    <input type="time" class="form-control" id="horario_lv_ingreso" value="<?php echo htmlspecialchars($horario_lv_ingreso); ?>">
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="form-group">
                                    <label>Salida</label>
                                    <input type="time" class="form-control" id="horario_lv_salida" value="<?php echo htmlspecialchars($horario_lv_salida); ?>">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-4">
                        <label><b>Sábado</b></label>
                        <div class="row">
                            <div class="col-sm-6">
                                <div class="form-group">
                                    <label>Ingreso</label>
                                    <input type="time" class="form-control" id="horario_sab_ingreso" value="<?php echo htmlspecialchars($horario_sab_ingreso); ?>">
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="form-group">
                                    <label>Salida</label>
                                    <input type="time" class="form-control" id="horario_sab_salida" value="<?php echo htmlspecialchars($horario_sab_salida); ?>">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-4">
                        <label><b>Domingo</b></label>
                        <div class="row">
                            <div class="col-sm-6">
                                <div class="form-group">
                                    <label>Ingreso</label>
                                    <input type="time" class="form-control" id="horario_dom_ingreso" value="<?php echo htmlspecialchars($horario_dom_ingreso); ?>">
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="form-group">
                                    <label>Salida</label>
                                    <input type="time" class="form-control" id="horario_dom_salida" value="<?php echo htmlspecialchars($horario_dom_salida); ?>">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="panel panel-default">
            <div class="panel-heading"><b>Cálculo de tardanzas y planilla</b></div>
            <div class="panel-body">
                <div class="row">
                    <div class="col-sm-6">
                        <div class="form-group">
                            <label>Factor de penalización por tardanza</label>
                            <input type="number" step="0.1" min="1" class="form-control" id="factor_tardanza" value="<?php echo htmlspecialchars($factor_tardanza); ?>">
                            <p class="help-block">Los minutos reales de tardanza se multiplican por este factor antes de calcular el descuento. Ej. con factor 2, llegar 15 min tarde se cobra como 30 min.</p>
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="form-group">
                            <label>Días de referencia para el sueldo diario</label>
                            <input type="number" step="1" min="1" class="form-control" id="dias_mes_referencia" value="<?php echo htmlspecialchars($dias_mes_referencia); ?>">
                            <p class="help-block">Sueldo diario = sueldo mensual / este número (por defecto 30, uso estándar). El pago de cada periodo de planilla es ese valor día por los días reales del periodo.</p>
                        </div>
                    </div>
                </div>
                <button class="btn btn-success" id="btn-guardar"><i class="fas fa-save"></i> Guardar</button>
            </div>
        </div>

        <div class="panel panel-default">
            <div class="panel-heading"><b>Cargos / Ocupaciones</b></div>
            <div class="panel-body">
                <p class="help-block" style="margin-top:0">Lista de cargos que se pueden elegir al agregar o editar un colaborador.</p>
                <table class="table table-bordered">
                    <thead><tr><th>Cargo</th><th></th></tr></thead>
                    <tbody id="cargos-body"><?php echo $cargos_filas; ?></tbody>
                </table>
                <div class="row">
                    <div class="col-sm-4">
                        <input type="text" class="form-control" id="nuevo-cargo" placeholder="Ej. BOLETEADORA">
                    </div>
                    <div class="col-sm-2">
                        <button type="button" class="btn btn-success" id="btn-agregar-cargo">Agregar</button>
                    </div>
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

$('#btn-guardar').on('click', function(){
    $.post('/inc/guardar_config_planillas.php', {
        horario_lv_ingreso: $('#horario_lv_ingreso').val(),
        horario_lv_salida: $('#horario_lv_salida').val(),
        horario_sab_ingreso: $('#horario_sab_ingreso').val(),
        horario_sab_salida: $('#horario_sab_salida').val(),
        horario_dom_ingreso: $('#horario_dom_ingreso').val(),
        horario_dom_salida: $('#horario_dom_salida').val(),
        factor_tardanza: $('#factor_tardanza').val(),
        dias_mes_referencia: $('#dias_mes_referencia').val()
    }, function(d){
        showAlert(d.ok ? 'Configuración guardada.' : 'Error al guardar.', d.ok ? 'success' : 'danger');
    }, 'json').fail(function(xhr){
        showAlert('Error del servidor: ' + xhr.status, 'danger');
    });
});

$('#btn-agregar-cargo').on('click', function(){
    var nombre = $('#nuevo-cargo').val().trim();
    if (!nombre) { showAlert('Escribe el nombre del cargo.', 'warning'); return; }
    $.post('/inc/registrar_cargo.php', { nombre: nombre }, function(d){
        if (d.ok) {
            document.location.reload();
        } else {
            showAlert(d.mensaje || 'No se pudo agregar el cargo.', 'danger');
        }
    }, 'json').fail(function(xhr){
        showAlert('Error del servidor: ' + xhr.status, 'danger');
    });
});

$('#cargos-body').on('click', '.btn-borrar-cargo', function(){
    var id = $(this).data('id');
    $.get('/inc/borrar_cargo.php', { id: id }, function(d){
        if (d.ok) {
            document.location.reload();
        } else {
            showAlert(d.mensaje || 'No se pudo borrar el cargo.', 'danger');
        }
    }, 'json').fail(function(xhr){
        showAlert('Error del servidor: ' + xhr.status, 'danger');
    });
});
</script>
</body>
</html>
