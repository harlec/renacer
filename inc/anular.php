<?php
include('sdba/sdba.php');
include('config_facturacion.php');
session_start();
header('Content-Type: application/json');

$id = $_POST['id'];
$serie =$_POST['serie'];
$numero = $_POST['numero'];
$tipo = $_POST['tipo'];
$motivo = $_POST['motivo'];

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
    "operacion"             => "generar_anulacion",
    "tipo_de_comprobante"   => $tipo,
    "serie"                 => $serie,
    "numero"                => $numero,
    "motivo"                => $motivo,
    "codigo_unico"          => " ",

    
);
	
$data_json = json_encode($data);

//echo $data_json;

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

// Cualquier respuesta que no sea un JSON válido (Nubefact caído, timeout, HTML de
// error, etc.) se trata como error — nunca se marca el comprobante como "de baja"
// sin certeza de que Nubefact recibió la comunicación.
if (!is_array($leer_respuesta) || isset($leer_respuesta['errors'])) {
    $mensaje = 'No se pudo comunicar la baja (respuesta inválida de Nubefact).';
    if (is_array($leer_respuesta) && isset($leer_respuesta['errors'])) {
        $mensaje = is_array($leer_respuesta['errors']) ? implode(' ', $leer_respuesta['errors']) : $leer_respuesta['errors'];
    }
    echo json_encode(['ok' => false, 'mensaje' => $mensaje]);
} else {
	$configuracion = Sdba::table('comprobantes');
    $configuracion->where('id_comprobante', $id);
    $dataf = array('state'=>'1');
    $configuracion->update($dataf);

    echo json_encode(['ok' => true, 'mensaje' => 'Comprobante comunicado de baja. Verifica su aceptación en unos minutos.']);
}
?>