<?php
include('sdba/sdba.php');
session_start();
$usuario = $_SESSION['usuario']; 
/*
 #########################################################
#### INTEGRACIÓN FÁCIL ####
+++++++++++++++++++++++++++++++++++++++++++++++++++++++
# ESTE CÓDIGO FUNCIONA PARA LA VERSIÓN ONLINE Y OFFLINE
# Visita www.nubefact.com/integracion para más información
+++++++++++++++++++++++++++++++++++++++++++++++++++++++

#########################################################
#### FORMA DE TRABAJO ####
+++++++++++++++++++++++++++++++++++++++++++++++++++++++
# PASO 1: Conseguir una RUTA y un TOKEN para trabajar con NUBEFACT (Regístrate o ingresa a tu cuenta en www.nubefact.com).
# PASO 2: Generar un archivo en formato .JSON o .TXT con una estructura que se detalla en este documento.
# PASO 3: Enviar el archivo generado a nuestra WEB SERVICE ONLINE u OFFLINE según corresponda usando la RUTA y el TOKEN.
# PASO 4: Generamos el archivo XML y PDF (Según especificaciones de la SUNAT) y te devolveremos INSTANTÁNEAMENTE los datos del documento generado.
# Para ver el documento generado ingresa a www.nubefact.com/login con tus datos de acceso, y luego a la opción "Ver Facturas, Boletas y Notas"
# IMPORTANTE: Enviaremos el XML generado a la SUNAT y lo almacenaremos junto con el PDF, XML y CDR en la NUBE para que tu cliente pueda consultarlo en cualquier momento, si así lo desea.
+++++++++++++++++++++++++++++++++++++++++++++++++++++++


#########################################################
#### PASO 1: CONSEGUIR LA RUTA Y TOKEN ####
+++++++++++++++++++++++++++++++++++++++++++++++++++++++
# - Regístrate gratis en www.nubefact.com/register
# - Ir la opción API (Integración).
# IMPORTANTE: Para que la opción API esté activada necesitas escribirnos a soporte@nubefact.com o llámanos al teléfono: 01 468 3535 (opción 2) o celular (WhatsApp) 955 598762.
+++++++++++++++++++++++++++++++++++++++++++++++++++++++
 * 
 * 
 */

//OBTENEMOS LOS PRODUCTOS
if ($_POST['placa']) {
    $placa = $_POST['placa'];
}else{
    $placa = '';
}
$fechita = $_POST['fechita'];
$tipo_doc = $_POST['tipo_doc'];
$venta_id = $_POST['venta_id'];
$ruc =$_POST['ruc'];
$r_social = $_POST['r_social'];
$direccion = $_POST['direccion'];
$facturan = $_POST['facturan'];
$codigo = $_POST['codigo'];
$platos = $_POST['plato'];
$unidad = $_POST['unidad'];
$cantidad = $_POST['cantidad'];
$precio = $_POST['precio'];
$totalp = $_POST['totalp'];
$total = $_POST['total'];
$totalg = $total/1.18;
$totaligv = $total -$totalg;
$detalle = array();
for ($i=0; $i < count($platos); $i++) { 
    $valor_unitario = $precio[$i]/1.18;
    $subtotal = $valor_unitario*$cantidad[$i];
    $igv1 = $totalp[$i]-$subtotal;
    $igv = $igv1;
    $detalle [$i]=array(
        "unidad_de_medida"          => $unidad[$i],
        "codigo"                    => $codigo[$i],
        "descripcion"               => $platos[$i],
        "cantidad"                  => $cantidad[$i],
        "valor_unitario"            => $valor_unitario,
        "precio_unitario"           => $precio[$i],
        "descuento"                 => "",
        "subtotal"                  => $subtotal,
        "tipo_de_igv"               => "1",
        "igv"                       => $igv,
        "total"                     => $totalp[$i],
        "anticipo_regularizacion"   => "false",
        "anticipo_documento_serie"  => "",
        "anticipo_documento_numero" => ""
    );
    
}

// RUTA para enviar documentos
//$ruta = "https://api.nubefact.com/api/v1/c4718034-54de-4e07-a9d7-52794d607dcc";

$ruta = "https://api.nubefact.com/api/v1/507c5938-d28d-482d-90fc-915cc932e31b";

//TOKEN para enviar documentos
//$token = "84e0dde914dc4d96adeedfb73562b144268ef8f883a0444a80ef582cd06a2dfa";
$token = "13655cf405e945d19ca6859472139199796eadfba113483997c82870d657b644";

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
    "tipo_de_comprobante"               => "2",
    "serie"                             => "BPP1",
    "numero"				=> $facturan,
    "sunat_transaction"			=> "1",
    "cliente_tipo_de_documento"		=> $tipo_doc,
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
    "total_exonerada"                   => "",
    "total_igv"                         => $totaligv,
    "total_gratuita"                    => "",
    "total_otros_cargos"                => "",
    "total"                             => $total,
    "percepcion_tipo"                   => "",
    "percepcion_base_imponible"         => "",
    "total_percepcion"                  => "",
    "total_incluido_percepcion"         => "",
    "detraccion"                        => "false",
    "observaciones"                     => $usuario,
    "documento_que_se_modifica_tipo"    => "",
    "documento_que_se_modifica_serie"   => "",
    "documento_que_se_modifica_numero"  => "",
    "tipo_de_nota_de_credito"           => "",
    "tipo_de_nota_de_debito"            => "",
    "enviar_automaticamente_a_la_sunat" => "true",
    "enviar_automaticamente_al_cliente" => "false",
    "codigo_unico"                      => "",
    "condiciones_de_pago"               => "",
    "medio_de_pago"                     => "",
    "placa_vehiculo"                    => $placa,
    "orden_compra_servicio"             => "",
    "tabla_personalizada_codigo"        => "",
    "formato_de_pdf"                    => "TICKET",
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
if (isset($leer_respuesta['errors'])) {
	//Mostramos los errores si los hay
    echo json_encode($leer_respuesta['errors']);
} else {
    $fecha = date('d-m-Y');
	$configuracion = Sdba::table('comprobantes');
    $data = array('id_comprobante'=>'','serie'=>'BPP1','numero'=>$facturan,'url'=>$leer_respuesta['enlace'],'tipo'=>'B','venta'=>$venta_id,'tipo_doc'=>$tipo_doc,'doc'=>$ruc,'nombre'=>$r_social,'moneda'=>'PEN','tipo_cambio'=>'','grabada'=>$totalg,'igv'=>$totaligv,'total'=>$total,'fecha'=>$fecha,'state'=>'0');
    $configuracion->insert($data);

    $venta = Sdba::table('ventas');
    $venta->where('id_venta', $venta_id);
    $data = array('estado'=>'1');
    $venta->update($data);

    $numerof = Sdba::table('configuracion');
    $numerof->where('parametro', 'boleta');
    $dataf = array('valor'=>$facturan);
    $numerof->update($dataf);

    echo json_encode($respuesta);

}
?>