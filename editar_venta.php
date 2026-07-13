<?php
session_start();
$usuario = $_SESSION['usuario'];
$tienda = $_SESSION['tienda'];

include('inc/control.php');
include('inc/sdba/sdba.php');

// Validar que se recibió un ID de venta
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: ventas.php");
    exit();
}

$id_venta = $_GET['id'];

// Obtener datos de la venta
$venta = Sdba::table('ventas');
$venta->where('id_venta', $id_venta);
$venta_data = $venta->get_one();

// Validaciones de seguridad
if (!$venta_data) {
    echo "<script>alert('Venta no encontrada'); window.location.href='ventas.php';</script>";
    exit();
}

// Solo admin o el usuario que creó la venta pueden editarla
if ($_SESSION['type'] != 'admin' && $venta_data['usuario'] != $_SESSION['id_usr']) {
    echo "<script>alert('No tienes permisos para editar esta venta'); window.location.href='ventas.php';</script>";
    exit();
}

// Solo permitir editar ventas sin comprobante (estado = 0)
if ($venta_data['estado'] != '0') {
    echo "<script>alert('No se puede editar una venta que ya tiene comprobante generado'); window.location.href='ventas.php';</script>";
    exit();
}

$fecha = date('Y-m-d', strtotime($venta_data['fecha']));

// Obtener detalle actual de la venta
$detalle_venta = Sdba::table('detalle_ventas');
$detalle_venta->where('venta', $id_venta);
$detalle_venta->left_join('producto','productos','id_producto');
$detalle_actual = $detalle_venta->get();

// Obtener precio_vp y cantidad_vp para cálculo correcto de totales
$variante_precios = [];
if (!empty($detalle_actual)) {
    $id_vps = array_unique(array_column($detalle_actual, 'id_vp'));
    $conn_vp = new mysqli('localhost', 'admin_renacer', 'ikm169uhn', 'admin_renacer');
    $conn_vp->set_charset('utf8');
    $ids_str = implode(',', array_map('intval', $id_vps));
    $vp_result = $conn_vp->query("SELECT id_vp, precio_vp, cantidad_vp FROM variante_p WHERE id_vp IN ($ids_str)");
    while ($vp_row = $vp_result->fetch_assoc()) {
        $variante_precios[$vp_row['id_vp']] = $vp_row;
    }
    $conn_vp->close();
}

// Obtener cliente actual
$cliente_actual = Sdba::table('clientes');
$cliente_actual->where('id_cliente', $venta_data['cliente']);
$cliente_data = $cliente_actual->get_one();

// Obtener variantes disponibles para agregar
$variantes_p = Sdba::table('variante_p');
$variantes_p->left_join('variante_vp','variantes','id_variante');
$variantes_p->left_join('producto_vp','productos','id_producto');
$variantes_p->left_join('producto_vp','marca','id_marca');
$variantes_p_l = $variantes_p->get();

$datos = '';
$i = 1;
foreach ($variantes_p_l as $value) {
    if (empty($value['nom_prod'])) continue;
    $ventas = Sdba::table('marca');
    $ventas->where('id_marca',$value['marca']);
    $ventas_l = $ventas->get_one();

    $stocktt = round($value['stockp']/$value['cantidad_vp'], 3);
    $marcan = $ventas_l['marca'];
    $precio_final = $value['precio_vp']/$value['cantidad_vp'];

    $datos .='<tr> 
                <td style="text-transform:uppercase;" class="nom_prod">'.$value['codigo_producto'].' '.$value['nom_prod'].' '.$marcan.'</td>
                <td style="text-transform:uppercase;" class="unidad"><input type="hidden" class="id_vp" value="'.$value['id_vp'].'">'.'<input type="hidden" class="cantidad_vp" value="'.$value['cantidad_vp'].'">'.$value['variante'].'('.$value['cantidad_vp'].')</td>
                <td class="stock">'.$stocktt.'</td>
                <td><input type="hidden" class="precio_venta" value="'.$precio_final.'"><input type="hidden" class="precio_vp_orig" value="'.$value['precio_vp'].'">'.$value['precio_vp'].'</td>
                <td><button id="agregar" value="'.$value['id_producto'].'" class="btn btn-lg btn-success"> + </button></td>
              </tr>';
    $i++;
}

// Obtener clientes
$clientes = Sdba::table('clientes');
$el = $clientes->get();
$emplel = array();
foreach ($el as $value) {
    $emplel[]= $value['cliente'];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Sistema - Editar Venta</title>
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" type="text/css" href="/assets/css/bootstrap.min.css">
    <link rel="stylesheet" type="text/css" href="/assets/css/custom.css">
    <link rel="stylesheet" type="text/css" href="https://cdnjs.cloudflare.com/ajax/libs/limonte-sweetalert2/7.0.5/sweetalert2.min.css">
    <link rel="stylesheet" type="text/css" href="//cdn.datatables.net/1.10.22/css/jquery.dataTables.min.css">
    <link rel="stylesheet" type="text/css" href="/assets/css/select2.min.css">
    <link rel="stylesheet" type="text/css" href="/assets/css/jquery-ui.min.css">
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
          <div class="submenu">
            <ul class="subtop-tabs">
                <li>
                    <a href="venta.php">Registrar venta</a>
                </li>
                <li class="active">
                    <a href="ventas.php">Listar ventas</a>
                </li>
            </ul>
          </div>
        </nav>
        <div class="kbg">
            <div class="cuerpo">
                <div class="titulo">
                    <h3>Editar Venta ID: <?php echo $id_venta; ?></h3>
                    <div class="alert alert-warning">
                        <strong>Atención:</strong> Al editar la venta se actualizará automáticamente el stock de los productos modificados.
                    </div>
                </div>
                <div class="container-fluid">
                    <div class="row">
                        <div class="col-md-12">
                            <div class="kdashboard">
                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="panel panel-default pa">
                                            <div class="panel-body">
                                                <form id="form_editar_venta">
                                                    <input type="hidden" name="id_venta" value="<?php echo $id_venta; ?>">
                                                    
                                                    <div class="form-group">
                                                        <label for="fecha">Fecha</label>
                                                        <input type="date" class="form-control" name="fecha" id="fecha" value="<?php echo $fecha; ?>">
                                                    </div>
                                                    
                                                    <div class="form-group">
                                                        <label for="cliente">Cliente</label>
                                                        <input type="text" class="form-control" name="cliente" id="cliente" value="<?php echo $cliente_data['cliente']; ?>" readonly style="background-color: #f5f5f5;">
                                                        <small class="text-muted">El cliente no se puede modificar en la edición</small>
                                                    </div>
                                                    
                                                    <div class="form-group" style="display: none;">
                                                        <label for="tipo">Tipo</label>
                                                        <select class="form-control" name="tipo" id="tipo">
                                                            <option value="1" <?php echo ($venta_data['tipo'] == '1') ? 'selected' : ''; ?>>Contado</option>
                                                            <option value="2" <?php echo ($venta_data['tipo'] == '2') ? 'selected' : ''; ?>>Crédito</option>
                                                        </select>
                                                        <small class="text-muted">Campo oculto - se mantiene valor original</small>
                                                    </div>
                                                    
                                                    <div class="form-group" style="display: none;">
                                                        <label for="forma">Forma de Pago</label>
                                                        <select class="form-control" name="forma" id="forma">
                                                            <option value="1" <?php echo ($venta_data['forma'] == '1') ? 'selected' : ''; ?>>Efectivo</option>
                                                            <option value="2" <?php echo ($venta_data['forma'] == '2') ? 'selected' : ''; ?>>Tar. Débito</option>
                                                            <option value="3" <?php echo ($venta_data['forma'] == '3') ? 'selected' : ''; ?>>Tar. Crédito</option>
                                                        </select>
                                                        <small class="text-muted">Campo oculto - se mantiene valor original</small>
                                                    </div>

                                                    <h4>Productos en la Venta</h4>
                                                    <div id="productos_venta">
                                                        <?php
                                                        $total_venta = 0;
                                                        foreach ($detalle_actual as $det):
                                                            $total_venta += $det['total'];
                                                            $precio_vp_orig = $variante_precios[$det['id_vp']]['precio_vp'] ?? 0;
                                                            $cant_vp_orig = $variante_precios[$det['id_vp']]['cantidad_vp'] ?? 1;
                                                        ?>
                                                        <div class="producto-item" data-detalle-id="<?php echo $det['id_detalle']; ?>">
                                                            <div class="row">
                                                                <div class="col-md-5">
                                                                    <strong><?php echo $det['nom_prod']; ?></strong>
                                                                </div>
                                                                <div class="col-md-2">
                                                                    <input type="text" pattern="[0-9]*[.]?[0-9]*" class="form-control cantidad-prod" value="<?php echo $det['cantidad']; ?>">
                                                                </div>
                                                                <div class="col-md-2">
                                                                    <input type="text" pattern="[0-9]*[.]?[0-9]*" class="form-control precio-prod" value="<?php echo number_format($det['precio'], 3, '.', ''); ?>">
                                                                </div>
                                                                <div class="col-md-2">
                                                                    <span class="total-prod"><?php echo $det['total']; ?></span>
                                                                </div>
                                                                <div class="col-md-1">
                                                                    <button type="button" class="btn btn-danger btn-sm quitar-producto">×</button>
                                                                </div>
                                                            </div>
                                                            <input type="hidden" class="producto-id" value="<?php echo $det['producto']; ?>">
                                                            <input type="hidden" class="id-vp" value="<?php echo $det['id_vp']; ?>">
                                                            <input type="hidden" class="cantidad-original" value="<?php echo $det['cantidad']; ?>">
                                                            <input type="hidden" class="precio-paquete" value="<?php echo $precio_vp_orig; ?>">
                                                            <input type="hidden" class="cant-vp" value="<?php echo $cant_vp_orig; ?>">
                                                            <hr>
                                                        </div>
                                                        <?php endforeach; ?>
                                                    </div>

                                                    <div class="row">
                                                        <div class="col-md-8">
                                                            <strong>Total: S/</strong>
                                                        </div>
                                                        <div class="col-md-4">
                                                            <strong><span id="total_general"><?php echo number_format($total_venta, 2); ?></span></strong>
                                                        </div>
                                                    </div>

                                                    <br>
                                                    <button type="submit" class="btn btn-primary btn-block btn-lg">Guardar Cambios</button>
                                                    <a href="ventas.php" class="btn btn-default btn-block">Cancelar</a>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                    </div>
                </div>
            </div>
            <div class="detalles">    
                <div class="titulo">
                    <h3>Agregar productos</h3>
                </div>
                <div class="panel panel-default pa">
                    <div class="panel-body">
                        <table id="datos" class="table table-hover table-responsive"> 
                            <thead> 
                                <tr> 
                                    <th>Producto</th>
                                    <th>Unidad</th>
                                    <th>Stock</th>
                                    <th>Precio</th> 
                                    <th></th> 
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

    <!-- jQuery y otros scripts -->
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.3.1/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/limonte-sweetalert2/7.0.5/sweetalert2.min.js"></script>
    <script src="/assets/js/bootstrap.min.js"></script>
    <script src="//cdn.datatables.net/1.10.22/js/jquery.dataTables.min.js"></script>
    <script src="/assets/js/select2.min.js"></script>

    <script>
    $(document).ready(function() {
        
        // CSS para campos con errores
        $('<style>').prop('type', 'text/css').html(`
            .error {
                border-color: #d9534f !important;
                box-shadow: inset 0 1px 1px rgba(0,0,0,.075), 0 0 0 3px rgba(217,83,79,.1);
            }
        `).appendTo('head');
        
        // Inicializar DataTable
        $('#datos').DataTable({
            "language": {
                "url": "//cdn.datatables.net/plug-ins/1.10.22/i18n/Spanish.json"
            }
        });

        // Función para recalcular totales
        function recalcularTotales() {
            var total = 0;
            $('.producto-item').each(function() {
                var cantidad = parseFloat($(this).find('.cantidad-prod').val().replace(',', '.')) || 0;
                var precioVp = parseFloat($(this).find('.precio-paquete').val()) || 0;
                var cantVp = parseFloat($(this).find('.cant-vp').val()) || 1;
                var subtotal = Math.round((cantidad / cantVp) * precioVp * 100) / 100;
                $(this).find('.total-prod').text(subtotal.toFixed(2));
                total += subtotal;
            });
            $('#total_general').text(total.toFixed(2));
        }

        // Event listeners para cambios en cantidad y precio
        $(document).on('input keyup', '.cantidad-prod, .precio-prod', function() {
            // Remover clase de error al empezar a escribir
            $(this).removeClass('error');
            recalcularTotales();
        });

        // Quitar producto
        $(document).on('click', '.quitar-producto', function() {
            $(this).closest('.producto-item').remove();
            recalcularTotales();
        });

        // Agregar producto desde la tabla
        $(document).on('click', '#agregar', function(e) {
            e.preventDefault();
            
            var fila = $(this).closest('tr');
            var nomProd = fila.find('.nom_prod').text();
            var unidad = fila.find('.unidad').text();
            var stock = parseFloat(fila.find('.stock').text());
            var precio = parseFloat(fila.find('.precio_venta').val());
            var precioVpOrig = parseFloat(fila.find('.precio_vp_orig').val()) || precio;
            var idProducto = $(this).val();
            var idVp = fila.find('.id_vp').val();
            var cantidad_vp = fila.find('.cantidad_vp').val();
            
            if (stock <= 0) {
                swal('Advertencia', 'No hay stock disponible para este producto', 'warning');
                return;
            }

            // Verificar si el producto ya está en la venta
            var productoExiste = false;
            $('.producto-item').each(function() {
                if ($(this).find('.producto-id').val() == idProducto && $(this).find('.id-vp').val() == idVp) {
                    productoExiste = true;
                    return false;
                }
            });

            if (productoExiste) {
                swal('Advertencia', 'Este producto ya está en la venta. Puede modificar la cantidad en la lista.', 'warning');
                return;
            }

            // Agregar nuevo producto
            var nuevoProducto = `
                <div class="producto-item" data-detalle-id="nuevo">
                    <div class="row">
                        <div class="col-md-5">
                            <strong>${nomProd}</strong>
                        </div>
                        <div class="col-md-2">
                            <input type="text" pattern="[0-9]*[.]?[0-9]*" class="form-control cantidad-prod" value="${cantidad_vp}">
                        </div>
                        <div class="col-md-2">
                            <input type="text" pattern="[0-9]*[.]?[0-9]*" class="form-control precio-prod" value="${precio.toFixed(3)}">
                        </div>
                        <div class="col-md-2">
                            <span class="total-prod">${precio.toFixed(2)}</span>
                        </div>
                        <div class="col-md-1">
                            <button type="button" class="btn btn-danger btn-sm quitar-producto">×</button>
                        </div>
                    </div>
                    <input type="hidden" class="producto-id" value="${idProducto}">
                    <input type="hidden" class="id-vp" value="${idVp}">
                    <input type="hidden" class="cantidad-original" value="0">
                    <input type="hidden" class="precio-paquete" value="${precioVpOrig}">
                    <input type="hidden" class="cant-vp" value="${cantidad_vp}">
                    <hr>
                </div>
            `;
            
            $('#productos_venta').append(nuevoProducto);
            recalcularTotales();
        });

        // Enviar formulario
        $('#form_editar_venta').on('submit', function(e) {
            e.preventDefault();
            
            // Validar campos del formulario
            var fecha = $('input[name="fecha"]').val();
            
            if (!fecha) {
                swal('Error', 'La fecha es obligatoria', 'error');
                return;
            }
            
            // Limpiar y validar campos numéricos
            var hayErrores = false;
            $('.cantidad-prod, .precio-prod').each(function() {
                var valor = $(this).val().replace(',', '.'); // Reemplazar coma por punto
                var numero = parseFloat(valor);
                
                if (isNaN(numero) || numero < 0) {
                    $(this).addClass('error');
                    hayErrores = true;
                } else {
                    $(this).removeClass('error');
                    $(this).val(numero); // Normalizar el valor
                }
            });
            
            if (hayErrores) {
                swal('Error', 'Hay errores en los campos numéricos. Verifique las cantidades y precios.', 'error');
                return;
            }
            
            // Recopilar datos de productos
            var productos = [];
            $('.producto-item').each(function() {
                var cantidadVal = $(this).find('.cantidad-prod').val().replace(',', '.');
                var precioVal = $(this).find('.precio-prod').val().replace(',', '.');
                var cantidad = parseFloat(cantidadVal) || 0;
                var precio = parseFloat(precioVal) || 0;
                
                if (cantidad > 0) {
                    productos.push({
                        detalle_id: $(this).data('detalle-id'),
                        producto_id: $(this).find('.producto-id').val(),
                        id_vp: $(this).find('.id-vp').val(),
                        cantidad: cantidad,
                        precio: precio,
                        precio_vp: parseFloat($(this).find('.precio-paquete').val()) || 0,
                        cantidad_vp: parseFloat($(this).find('.cant-vp').val()) || 1,
                        cantidad_original: parseFloat($(this).find('.cantidad-original').val()) || 0
                    });
                }
            });

            if (productos.length === 0) {
                swal('Error', 'Debe tener al menos un producto en la venta', 'error');
                return;
            }

            var datosFormulario = {
                id_venta: $('input[name="id_venta"]').val(),
                fecha: fecha,
                productos: productos
            };

            console.log('Datos que se enviarán:', datosFormulario);
            console.log('Productos:', productos);

            $.ajax({
                cache: false,
                type: "POST",
                dataType: "json",
                url: "/inc/procesar_edicion_venta.php",
                data: datosFormulario,
                success: function(response) {
                    console.log('Respuesta del servidor:', response);
                    if (response.respuesta == false) {
                        swal('Error', response.mensaje, 'error');
                    } else {
                        swal('Perfecto', 'Venta actualizada correctamente', 'success').then(function() {
                            window.location.href = "ver_venta.php?id=" + response.venta_id;
                        });
                    }
                },
                error: function(xhr, status, error) {
                    console.log('Error AJAX:', {xhr: xhr, status: status, error: error});
                    console.log('Respuesta del servidor:', xhr.responseText);
                    swal('Error', 'Error del sistema: ' + error + '. Ver consola para más detalles.', 'error');
                }
            });
        });

    });
    </script>
</body>
</html>