<?php
include('sdba/sdba.php');
include('config_facturacion.php');
include('nubefact_correlativo.php');
session_start();
$usuario = $_SESSION['usuario'];

//OBTENEMOS LOS PRODUCTOS
if ($_POST['placa']) {
    $placa = $_POST['placa'];
}else{
    $placa = '';
}
$fechita = $_POST['fechita'];
$venta_id = $_POST['venta_id'];
$fechac = $_POST['fechac'];
$montoc = $_POST['montoc'];
$numero_compro = $_POST['numero_compro'];

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
$forma = $_POST['forma'];


$detalle = array();
$igvtot = 0;
$total_gravada = 0;
$total_exonerada = 0;
$total = 0;
for ($i=0; $i < count($platos); $i++) {
    $cantidad_i = (float)$cantidad[$i];
    $precio_i   = (float)$precio[$i];
    // El total de línea se recalcula desde precio×cantidad — NO se usa el totalp[]
    // guardado tal cual. Si la venta es antigua o por peso, ese total guardado puede
    // no coincidir exactamente con precio×cantidad (redondeos históricos), y esa
    // diferencia se le cargaría entera al IGV de esa línea, dando un valor absurdo
    // que Nubefact rechaza al pre-validar.
    $totalp_i = round($precio_i * $cantidad_i, 2);
    $total    = round($total + $totalp_i, 2);

    if ($exonerada[$i]=='no') {
        $valor_unitario = $precio_i/1.18;
        $subtotal = round($valor_unitario*$cantidad_i, 2);
        $igv = round($totalp_i - $subtotal, 2);
        $igvtot = round($igvtot + $igv, 2);
        $tipo_igv = '1';
        $total_gravada = round($total_gravada + $totalp_i, 2);
    }
    else{
        $valor_unitario = $precio_i;
        $subtotal = round($valor_unitario*$cantidad_i, 2);
        $igv = 0;
        $tipo_igv = '8';
        $total_exonerada = round($total_exonerada + $totalp_i, 2);
    }

    $detalle [$i]=array(
        "unidad_de_medida"          => $unidad[$i],
        "codigo"                    => $codigo[$i],
        "descripcion"               => $platos[$i],
        "cantidad"                  => $cantidad_i,
        "valor_unitario"            => $valor_unitario,
        "precio_unitario"           => $precio_i,
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
// separado desde el agregado), para que nunca pueda desalinearse con las líneas.
$totaligv = $igvtot;
$totalg   = round($total_gravada - $totaligv, 2);

// RUTA y TOKEN configurados en Configuración > Facturación Electrónica
$ruta = get_config('nubefact_ruta');
$token = get_config('nubefact_token');
$serie = get_config('serie_nota_credito_factura', 'FC03');

// Verificar contra Nubefact cuál es el siguiente correlativo realmente libre para esta
// serie, en vez de mandar "numero": null (el manual de Nubefact indica que es obligatorio,
// y confiar en su auto-asignación es lo que probablemente causó el correlativo faltante).
$conn_corr = new mysqli('localhost', 'admin_renacer', 'ikm169uhn', 'admin_renacer');
$conn_corr->set_charset('utf8');
$numero_esperado = numero_esperado($conn_corr, 'FC', $serie);
$conn_corr->close();

$verificacion = siguiente_numero_verificado($ruta, $token, 3, $serie, $numero_esperado);
if (!$verificacion['ok']) {
    echo json_encode(['ok' => false, 'mensaje' => $verificacion['mensaje']]);
    exit;
}
$numero = $verificacion['numero'];
$aviso  = $verificacion['salto']
    ? "Se detectó un desfase con Nubefact: se saltó del correlativo $numero_esperado esperado al $numero real. Revisa que no falte un comprobante anterior de la serie $serie."
    : null;

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
    "tipo_de_comprobante"   => 3,
    "serie"                 => $serie,
    "numero"				=> $numero,
    "sunat_transaction"			=> 1,
    "cliente_tipo_de_documento"		=> 6,
    "cliente_numero_de_documento"	=> $ruc,
    "cliente_denominacion"              => $r_social,
    "cliente_direccion"                 => $direccion,
    "cliente_email"                     => "",
    "cliente_email_1"                   => "",
    "cliente_email_2"                   => "",
    "fecha_de_emision"                  => date('d-m-Y'),
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
    "observaciones"                     => "Anulacion de la operacion",
    "documento_que_se_modifica_tipo"    => 1,
    "documento_que_se_modifica_serie"   => "F003",
    "documento_que_se_modifica_numero"  => $facturan,
    "tipo_de_nota_de_credito"           => 1,
    "tipo_de_nota_de_debito"            => "",
    "enviar_automaticamente_a_la_sunat" => "true",
    "enviar_automaticamente_al_cliente" => "false",
    "codigo_unico"                      => "",
    "condiciones_de_pago"               => "",
    "medio_de_pago"                     => "",
    "placa_vehiculo"                    => "",
    "orden_compra_servicio"             => "",
    "tabla_personalizada_codigo"        => "",
    "formato_de_pdf"                    => "TICKET",
    "venta_al_credito"                  => "",
    "items" => $detalle
);
	
$data_json = json_encode($data);

//echo $data_json;


//echo json_encode($facturan);

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
    $data = array('id_comprobante'=>'','serie'=>$serie,'numero'=>$leer_respuesta['numero'],'url'=>$leer_respuesta['enlace'],'tipo'=>'FC','venta'=>$venta_id,'tipo_doc'=>'6','doc'=>$ruc,'nombre'=>$r_social,'moneda'=>'PEN','tipo_cambio'=>'','grabada'=>$totalg,'igv'=>$totaligv,'total'=>$total,'fecha'=>$fecha,'state'=>'0');
    $configuracion->insert($data);

    $venta = Sdba::table('ventas');
    $venta->where('id_venta', $venta_id);
    $data = array('estado'=>'1');
    $venta->update($data);

    echo json_encode([
        'ok'             => true,
        'numero'         => $leer_respuesta['numero'],
        'enlace'         => $leer_respuesta['enlace'] ?? null,
        'enlace_del_pdf' => $leer_respuesta['enlace_del_pdf'] ?? null,
        'aviso'          => $aviso,
    ]);

}
?>