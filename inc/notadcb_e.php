<?php
include('sdba/sdba.php');
session_start();
$usuario = $_SESSION['usuario']; 

//OBTENEMOS LOS PRODUCTOS
if ($_POST['placa']) {
    $placa = $_POST['placa'];
}else{
    $placa = '';
}

$numero_compro = $_POST['numero_compro'];

$fechita = $_POST['fechita'];
$tipo_doc = $_POST['tipo_doc'];
$venta_id = $_POST['venta_id'];
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

$detalle = array();
$igvtot = 0;
$total_gravada = 0;
$total_exonerada = 0;
for ($i=0; $i < count($platos); $i++) { 
    if ($exonerada[$i]=='no') {
        $valor_unitario = $precio[$i]/1.18;
        $subtotal = $valor_unitario*$cantidad[$i];
        $igv1 = $totalp[$i]-$subtotal;
        $igv = $igv1; 
        $igvtot = $igvtot + $igv;
        $tipo_igv = '1';
        $total_gravada = $total_gravada + $totalp[$i];
    }
    else{
        $valor_unitario = $precio[$i];
        $subtotal = $valor_unitario*$cantidad[$i];
        $igv = '0';
        $tipo_igv = '8';
        $total_exonerada = $total_exonerada + $totalp[$i];
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
        "total"                     => $totalp[$i],
        "anticipo_regularizacion"   => "false",
        "anticipo_documento_serie"  => "",
        "anticipo_documento_numero" => ""
    );
    
}
$totalg = $total_gravada/1.18;
$totaligv = $total_gravada - $totalg;

// RUTA para enviar documentos
//$ruta = "https://api.nubefact.com/api/v1/c4718034-54de-4e07-a9d7-52794d607dcc";

$ruta = "https://www.pse.pe/api/v1/48a600f7000a40b0adb189d78fc14187706fae317b1e4465b0560dc04aa0783c";

//TOKEN para enviar documentos
//$token = "84e0dde914dc4d96adeedfb73562b144268ef8f883a0444a80ef582cd06a2dfa";
$token = "eyJhbGciOiJIUzI1NiJ9.IjI1OGE0OWI5YTI5MTQwMmQ4NGI0MTFiNWI5YjIxYTljN2MxNTA4NTJiNDJjNDI1YmFlNjEzNmEyMjM2N2VkYTEi.YFS-7HeD1d0gBLO4ATncm_aFe1jULPVnZz4iMihsvcc";

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
    "tipo_de_comprobante"               => "3",
    "serie"                             => "BC03",
    "numero"				=> null,
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
    "observaciones"                     => $user,
    "documento_que_se_modifica_tipo"    => "1",
    "documento_que_se_modifica_serie"   => "BV02",
    "documento_que_se_modifica_numero"  => $numero_compro,
    "tipo_de_nota_de_credito"           => "1",
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



$leer_respuesta = json_decode($respuesta, true);
if (isset($leer_respuesta['errors'])) {
	//Mostramos los errores si los hay
    echo json_encode($leer_respuesta['errors']);
} else {
    $fecha = date("Y-m-d", strtotime($fechita));
	$configuracion = Sdba::table('comprobantes');
    $data = array('id_comprobante'=>'','serie'=>'BC03','numero'=>$leer_respuesta['numero'],'url'=>$leer_respuesta['enlace'],'tipo'=>'NB','venta'=>$venta_id,'tipo_doc'=>$tipo_doc,'doc'=>$ruc,'nombre'=>$r_social,'moneda'=>'PEN','tipo_cambio'=>'','grabada'=>$totalg,'igv'=>$totaligv,'total'=>$total,'fecha'=>$fecha,'state'=>'0');
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