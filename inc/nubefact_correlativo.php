<?php
/**
 * Verificación de correlativo contra Nubefact antes de emitir un comprobante.
 *
 * El manual oficial de Nubefact indica que "numero" es un campo Obligatorio en
 * "generar_comprobante" — no está pensado para enviarse en null. Este helper calcula
 * cuál debería ser el siguiente número (desde nuestro propio historial en la tabla
 * `comprobantes`) y lo confirma contra Nubefact con "consultar_comprobante" antes de
 * usarlo, para nunca emitir con un número que no se verificó como libre.
 */

// Siguiente número esperado para una serie+tipo, según nuestro propio historial.
function numero_esperado($conn, $tipo, $serie)
{
    $tipo_esc  = $conn->real_escape_string($tipo);
    $serie_esc = $conn->real_escape_string($serie);
    // numero está guardado como texto en la tabla, así que MAX(numero) sin más
    // compara alfabéticamente (p.ej. "9" > "29") y devuelve el máximo equivocado.
    // Se castea a entero para que la comparación sea numérica de verdad.
    $r = $conn->query("SELECT MAX(CAST(numero AS UNSIGNED)) AS ultimo FROM comprobantes WHERE tipo = '$tipo_esc' AND serie = '$serie_esc'");
    $row = $r ? $r->fetch_assoc() : null;
    $ultimo = ($row && $row['ultimo'] !== null) ? (int)$row['ultimo'] : 0;
    return $ultimo + 1;
}

// Confirma con Nubefact que un número está libre (consultar_comprobante → error código 24);
// si ya existe, prueba el siguiente. Devuelve:
//   ['ok'=>true,  'numero'=>int, 'salto'=>bool, 'mensaje'=>'']
//   ['ok'=>false, 'numero'=>null,'salto'=>bool, 'mensaje'=>'...']
function siguiente_numero_verificado($ruta, $token, $tipo_de_comprobante, $serie, $numero_inicial, $max_intentos = 20)
{
    $numero = (int)$numero_inicial;
    $salto  = false;

    for ($i = 0; $i < $max_intentos; $i++) {
        $data = array(
            "operacion"            => "consultar_comprobante",
            "tipo_de_comprobante"  => (int)$tipo_de_comprobante,
            "serie"                => $serie,
            "numero"               => $numero,
        );

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $ruta);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Authorization: Token token="' . $token . '"',
            'Content-Type: application/json',
        ));
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        $respuesta  = curl_exec($ch);
        $curl_error = curl_error($ch);
        curl_close($ch);

        if ($respuesta === false) {
            return array('ok' => false, 'numero' => null, 'salto' => $salto,
                'mensaje' => 'No se pudo verificar el correlativo con Nubefact: ' . $curl_error);
        }

        $leer = json_decode($respuesta, true);
        if (!is_array($leer)) {
            return array('ok' => false, 'numero' => null, 'salto' => $salto,
                'mensaje' => 'Respuesta inválida de Nubefact al verificar el correlativo.');
        }

        if (isset($leer['errors'])) {
            if (isset($leer['codigo']) && (int)$leer['codigo'] === 24) {
                // Confirmado: este número todavía no existe en Nubefact, está libre.
                return array('ok' => true, 'numero' => $numero, 'salto' => $salto, 'mensaje' => '');
            }
            // Cualquier otro error (auth, formato, etc.) — no se puede confiar en seguir.
            return array('ok' => false, 'numero' => null, 'salto' => $salto,
                'mensaje' => 'Nubefact respondió un error al verificar el correlativo: ' . $leer['errors']);
        }

        // Sin "errors" => el número YA EXISTE en Nubefact (desfase). Se prueba el siguiente.
        $numero++;
        $salto = true;
    }

    return array('ok' => false, 'numero' => null, 'salto' => true,
        'mensaje' => 'No se encontró un correlativo libre tras ' . $max_intentos . ' intentos para la serie ' . $serie . '.');
}
