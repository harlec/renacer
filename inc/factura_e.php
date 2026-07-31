<?php
include('sdba/sdba.php');
include('config_facturacion.php');
session_start();
$usuario = $_SESSION['usuario']; 

//OBTENEMOS LOS PRODUCTOS
if ($_POST['placa']) {
    $placa = $_POST['placa'];
}else{
    $placa = '';
}
$fechita = $_POST['fechita'];
$venta_ids = array_values(array_unique(array_filter(array_map('intval', explode(',', $_POST['venta_ids'] ?? '')))));
$fechac = $_POST['fechac'];
$montoc = $_POST['montoc'];

$user = $_POST['user'];
$ruc =$_POST['ruc'];
$r_social = $_POST['r_social'];
$direccion = $_POST['direccion'];
$facturan = $_POST['facturan'];
$codigo = $_POST['codigo'];
$platos = $_POST['plato'];
$unidad = $_POST['unidad'];
$cantidad = $_POST['cantidad'];
$precio = $_POST['precio'];
$exonerada = $_POST['exonerada'];
$totalp = $_POST['totalp'];
$total = $_POST['total'];
$forma = $_POST['forma'];

// Re-chequear que ninguna venta se haya facturado/anulado entre que se abrió
// el formulario y se confirmó el envío (ej. otra pestaña ya la facturó).
if (empty($venta_ids)) {
    echo json_encode(['ok' => false, 'mensaje' => 'Falta indicar la(s) venta(s) a facturar.']);
    exit;
}
$chk = Sdba::table('ventas');
$chk->where_in('id_venta', $venta_ids);
$chk->where('estado', '0');
$chk_list = $chk->get();
if (count($chk_list) !== count($venta_ids)) {
    echo json_encode(['ok' => false, 'mensaje' => 'Una o más de las ventas seleccionadas ya no está pendiente (puede que ya se haya facturado o anulado).']);
    exit;
}

$detalle = array();
$igvtot = 0;
$total_gravada = 0;
$total_exonerada = 0;
for ($i=0; $i < count($platos); $i++) {
    $totalp_i = round((float)$totalp[$i], 2);
    if ($exonerada[$i]=='no') {
        $valor_unitario = $precio[$i]/1.18;
        $subtotal = round($valor_unitario*$cantidad[$i], 2);
        $igv = round($totalp_i - $subtotal, 2);
        $igvtot = round($igvtot + $igv, 2);
        $tipo_igv = '1';
        $total_gravada = round($total_gravada + $totalp_i, 2);
    }
    else{
        $valor_unitario = $precio[$i];
        $subtotal = round($valor_unitario*$cantidad[$i], 2);
        $igv = 0;
        $tipo_igv = '8';
        $total_exonerada = round($total_exonerada + $totalp_i, 2);
    }

    $detalle [$i]=array(
        "unidad_de_medida"          => $unidad[$i],
        "codigo"                    => $codigo[$i],
        "descripcion"               => $platos[$i],
        "cantidad"                  => $cantidad[$i],
        "valor_unitario"            => $valor_unitario,
        "precio_unitario"           => $precio[$i],
        "descuento"                 => "",
        "subtotal"                  => $subtotal,
        "tipo_de_igv"               => $tipo_igv,
        "igv"                       => $igv,
        "total"                     => $totalp_i,
        "anticipo_regularizacion"   => "false",
        "anticipo_documento_serie"  => "",
        "anticipo_documento_numero" => ""
    );

}

$ventac = array(
        array(
            "cuota" => 1,
            "fecha_de_pago" => $fechac,
            "importe" => $montoc
        )
    );
// total_igv del comprobante = suma EXACTA de los IGV de línea (no se recalcula por
// separado desde el agregado), para que nunca pueda desalinearse con las líneas al
// unir varias ventas con precios de decimales largos (ej. huevos a 0.4666.../unidad).
$totaligv = $igvtot;
$totalg   = round($total_gravada - $totaligv, 2);

// RUTA y TOKEN configurados en Configuración > Facturación Electrónica
$ruta = get_config('nubefact_ruta');
$token = get_config('nubefact_token');

/*
#########################################################
#### PASO 2: GENERAR EL ARCHIVO PARA ENVIAR A NUBEFACT ####
+++++++++++++++++++++++++++++++++++++++++++++++++++++++
# - MANUAL para archivo JSON en el link: https://goo.gl/WHMmSb
# - MANUAL para archivo TXT en el link: https://goo.gl/Lz7hAq
+++++++++++++++++++++++++++++++++++++++++++++++++++++++
 */
//"serie"                             => "F001",
$data = array(
    "operacion"				=> "generar_comprobante",
    "tipo_de_comprobante"               => "1",
    "serie"                             => "F003",
    "numero"				=> null,
    "sunat_transaction"			=> "1",
    "cliente_tipo_de_documento"		=> "6",
    "cliente_numero_de_documento"	=> $ruc,
    "cliente_denominacion"              => $r_social,
    "cliente_direccion"                 => $direccion,
    "cliente_email"                     => "",
    "cliente_email_1"                   => "",
    "cliente_email_2"                   => "",
    "fecha_de_emision"                  => $fechita,
    "fecha_de_vencimiento"              => "",
    "moneda"                            => "1",
    "tipo_de_cambio"                    => "",
    "porcentaje_de_igv"                 => "18.00",
    "descuento_global"                  => "",
    "descuento_global"                  => "",
    "total_descuento"                   => "",
    "total_anticipo"                    => "",
    "total_gravada"                     => $totalg,
    "total_inafecta"                    => "",
    "total_exonerada"                   => $total_exonerada,
    "total_igv"                         => $totaligv,
    "total_gratuita"                    => "",
    "total_otros_cargos"                => "",
    "total"                             => $total,
    "percepcion_tipo"                   => "",
    "percepcion_base_imponible"         => "",
    "total_percepcion"                  => "",
    "total_incluido_percepcion"         => "",
    "detraccion"                        => "false",
    "observaciones"                     => $user . ' - ' . implode(',', array_map(function($v){ return 'v-'.$v; }, $venta_ids)),
    "documento_que_se_modifica_tipo"    => "",
    "documento_que_se_modifica_serie"   => "",
    "documento_que_se_modifica_numero"  => "",
    "tipo_de_nota_de_credito"           => "",
    "tipo_de_nota_de_debito"            => "",
    "enviar_automaticamente_a_la_sunat" => "true",
    "enviar_automaticamente_al_cliente" => "false",
    "codigo_unico"                      => "",
    "condiciones_de_pago"               => "",
    "medio_de_pago"                     => $forma,
    "placa_vehiculo"                    => "",
    "orden_compra_servicio"             => "",
    "tabla_personalizada_codigo"        => "",
    "formato_de_pdf"                    => "TICKET",
    "venta_al_credito"                  => $ventac,
    "items" => $detalle
);
	
$data_json = json_encode($data);

/*
#########################################################
#### PASO 3: ENVIAR EL ARCHIVO A NUBEFACT ####
+++++++++++++++++++++++++++++++++++++++++++++++++++++++
# SI ESTÁS TRABAJANDO CON ARCHIVO JSON
# - Debes enviar en el HEADER de tu solicitud la siguiente lo siguiente:
# Authorization = Token token="8d19d8c7c1f6402687720eab85cd57a54f5a7a3fa163476bbcf381ee2b5e0c69"
# Content-Type = application/json
# - Adjuntar en el CUERPO o BODY el archivo JSON o TXT
# SI ESTÁS TRABAJANDO CON ARCHIVO TXT
# - Debes enviar en el HEADER de tu solicitud la siguiente lo siguiente:
# Authorization = Token token="8d19d8c7c1f6402687720eab85cd57a54f5a7a3fa163476bbcf381ee2b5e0c69"
# Content-Type = text/plain
# - Adjuntar en el CUERPO o BODY el archivo JSON o TXT
+++++++++++++++++++++++++++++++++++++++++++++++++++++++
*/

//Invocamos el servicio de NUBEFACT
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $ruta);
curl_setopt(
	$ch, CURLOPT_HTTPHEADER, array(
	'Authorization: Token token="'.$token.'"',
	'Content-Type: application/json',
	)
);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_POSTFIELDS,$data_json);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$respuesta  = curl_exec($ch);
curl_close($ch);

/*
 #########################################################
#### PASO 4: LEER RESPUESTA DE NUBEFACT ####
+++++++++++++++++++++++++++++++++++++++++++++++++++++++
# Recibirás una respuesta de NUBEFACT inmediatamente lo cual se debe leer, verificando que no haya errores.
# Debes guardar en la base de datos la respuesta que te devolveremos.
# Escríbenos a soporte@nubefact.com o llámanos al teléfono: 01 468 3535 (opción 2) o celular (WhatsApp) 955 598762
# Puedes imprimir el PDF que nosotros generamos como también generar tu propia representación impresa previa coordinación con nosotros.
# La impresión del documento seguirá haciéndose desde tu sistema. Enviaremos el documento por email a tu cliente si así lo indicas en el archivo JSON o TXT.
+++++++++++++++++++++++++++++++++++++++++++++++++++++++++
 */

$leer_respuesta = json_decode($respuesta, true);

// Cualquier respuesta que no sea un JSON válido con "numero" se trata como error
// (Nubefact caído, timeout, HTML de error, etc.) — nunca se marca la venta como
// facturada si no hay certeza de que Nubefact aceptó el comprobante.
if (!is_array($leer_respuesta) || isset($leer_respuesta['errors']) || empty($leer_respuesta['numero'])) {
    $mensaje = 'No se pudo generar el comprobante (respuesta inválida de Nubefact).';
    if (is_array($leer_respuesta) && isset($leer_respuesta['errors'])) {
        $mensaje = is_array($leer_respuesta['errors']) ? implode(' ', $leer_respuesta['errors']) : $leer_respuesta['errors'];
    }
    echo json_encode(['ok' => false, 'mensaje' => $mensaje]);
} else {
    $fecha = date("Y-m-d", strtotime($fechita));
	$configuracion = Sdba::table('comprobantes');
    $data = array('id_comprobante'=>'','serie'=>'F003','numero'=>$leer_respuesta['numero'],'url'=>$leer_respuesta['enlace'],'tipo'=>'F','venta'=>$venta_ids[0],'tipo_doc'=>'6','doc'=>$ruc,'nombre'=>$r_social,'moneda'=>'PEN','tipo_cambio'=>'','grabada'=>$totalg,'igv'=>$totaligv,'total'=>$total,'fecha'=>$fecha,'state'=>'0');
    $configuracion->insert($data);
    $comprobante_id = $configuracion->insert_id();

    // Vincula el comprobante a TODAS las ventas del grupo (tabla puente) y las marca como facturadas
    foreach ($venta_ids as $vid) {
        Sdba::table('comprobante_ventas')->insert(array('comprobante'=>$comprobante_id, 'venta'=>$vid));

        $venta = Sdba::table('ventas');
        $venta->where('id_venta', $vid);
        $venta->update(array('estado'=>'1'));
    }

    $numerof = Sdba::table('configuracion');
    $numerof->where('parametro', 'factura');
    $dataf = array('valor'=>$facturan);
    $numerof->update($dataf);

    echo json_encode([
        'ok'             => true,
        'numero'         => $leer_respuesta['numero'],
        'enlace'         => $leer_respuesta['enlace'] ?? null,
        'enlace_del_pdf' => $leer_respuesta['enlace_del_pdf'] ?? null,
    ]);

}
?>