<?php
include('sdba/sdba.php');
session_start();


$id = $_POST['id'];
$serie =$_POST['serie'];
$numero = $_POST['numero'];
$tipo = $_POST['tipo'];

$ruta = "https://www.pse.pe/api/v1/48a600f7000a40b0adb189d78fc14187706fae317b1e4465b0560dc04aa0783c";

//TOKEN para enviar documentos
$token = "eyJhbGciOiJIUzI1NiJ9.IjRjNmM3NTU1YzFjNTQ5MDY5MzJmZWEyMDZiNjgyNTFlOWVhNTY2Y2U2MTE4NGVjMjlmMjA4ZTQyNWRhM2U5OTIi.cUvMBtUfm0j4_OUTRbUysBZhBzXnWWv9KsX1apQZA0U";

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
    "operacion"             => "consultar_anulacion",
    "tipo_de_comprobante"   => $tipo,
    "serie"                 => $serie,
    "numero"                => $numero,

    
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
if (isset($leer_respuesta['errors'])) {
	//Mostramos los errores si los hay
    echo json_encode($leer_respuesta['errors']);
} else {
    if ($leer_respuesta['aceptada_por_sunat']==true) {
        $fecha1 = date("Y-m-d");
    	$configuracion = Sdba::table('comprobantes');
        $configuracion->where('id_comprobante', $id);
        $dataf = array('state'=>'2');
        $configuracion->update($dataf);

        //guardamos en tabla detalle de compra
        $dventas = Sdba::table('detalle_comprobantes');
        $dventas->where('venta',$id);
        $dventasl = $dventas->get();

        $motivo = '';

        foreach ($dventasl as $value) {
            $stock = Sdba::table('stock');
            $stock->where('producto',$value['producto']);
            $stock->order_by('id_stock','desc');
            $stockl = $stock->get_one();
            $cstock = $stockl['stock'];

            $nstock = $cstock + $value['cantidad'];
            $motivo = 'E-'.$id;
            $datas = array('id_stock'=>'','producto'=>$value['producto'],'ingreso'=>$value['cantidad'],'motivo'=>$motivo,'stock'=>$nstock,'fecha'=>$fecha1, 'estado'=>'0');
            $stock->insert($datas);
        }

        echo json_encode($leer_respuesta['sunat_description']);
    }
    else{

        echo json_encode('Intentelo en otro momento');
    }


    

    

}
?>