<?php
require_once __DIR__ . '/sdba/sdba.php';
require_once __DIR__ . '/config_facturacion.php';

function ia_transcribir_audio(string $rutaArchivo, string $nombreOriginal): string
{
    $apiKey = get_config('openai_api_key');
    if ($apiKey === '') {
        throw new Exception('Falta configurar la API Key de OpenAI en Configuración > Facturación Electrónica');
    }

    $tipo = mime_content_type($rutaArchivo) ?: 'audio/mpeg';
    $ch = curl_init('https://api.openai.com/v1/audio/transcriptions');
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . $apiKey,
        ],
        CURLOPT_POSTFIELDS => [
            'file'     => new CURLFile($rutaArchivo, $tipo, $nombreOriginal),
            'model'    => 'gpt-4o-mini-transcribe',
            'language' => 'es',
        ],
        CURLOPT_TIMEOUT => 60,
    ]);
    $respuesta = curl_exec($ch);
    $error = curl_error($ch);
    $codigo = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($error) {
        throw new Exception('Error de conexión con el transcriptor de audio: ' . $error);
    }
    $data = json_decode($respuesta, true);
    if ($codigo !== 200 || !isset($data['text'])) {
        $msg = $data['error']['message'] ?? 'Respuesta inesperada del transcriptor de audio';
        throw new Exception('No se pudo transcribir el audio: ' . $msg);
    }
    return trim($data['text']);
}

function ia_interpretar_pedido(?string $texto, ?string $rutaImagen = null): array
{
    $apiKey = get_config('openai_api_key');
    if ($apiKey === '') {
        throw new Exception('Falta configurar la API Key de OpenAI en Configuración > Facturación Electrónica');
    }

    $instrucciones = <<<TXT
Eres un asistente que interpreta pedidos de una tienda mayorista/minorista, enviados por clientes vía WhatsApp (texto tipeado, nota de voz transcrita, o foto de una hoja escrita a mano).

Extrae cada línea de producto pedido con su cantidad. Reglas:
- Si el cliente pide "un paquete mitad X mitad Y", sepáralo en dos líneas con cantidad proporcional.
- Si una parte no se entiende bien (letra ilegible, audio confuso), igual inclúyela tal cual la leíste/escuchaste en "texto" para que un humano la revise después.
- No incluyas saludos ni nombres de quien pide (eso va aparte en "cliente_texto").
- Las cantidades son números; usa 1 si no se especifica.

Responde ÚNICAMENTE con JSON válido, sin texto antes ni después, con este formato exacto:
{"cliente_texto": "nombre del cliente si aparece, o null", "items": [{"texto": "descripción del producto tal cual se leyó/escuchó", "cantidad": numero}]}
TXT;

    $contenido = [
        [
            'type' => 'input_text',
            'text' => $instrucciones . "\n\nTexto del pedido a interpretar:\n" . ($texto !== null && $texto !== '' ? $texto : '(ver imagen adjunta)'),
        ],
    ];
    if ($rutaImagen) {
        $tipoImagen = mime_content_type($rutaImagen) ?: 'image/jpeg';
        $contenido[] = [
            'type' => 'input_image',
            'image_url' => 'data:' . $tipoImagen . ';base64,' . base64_encode(file_get_contents($rutaImagen)),
        ];
    }

    $body = json_encode([
        'model' => 'gpt-5.6-luna',
        'input' => [
            ['role' => 'user', 'content' => $contenido],
        ],
    ]);

    $ch = curl_init('https://api.openai.com/v1/responses');
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'content-type: application/json',
            'Authorization: Bearer ' . $apiKey,
        ],
        CURLOPT_POSTFIELDS => $body,
        CURLOPT_TIMEOUT => 60,
    ]);
    $respuesta = curl_exec($ch);
    $error = curl_error($ch);
    $codigo = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($error) {
        throw new Exception('Error de conexión con el intérprete de pedidos: ' . $error);
    }
    $data = json_decode($respuesta, true);
    if ($codigo !== 200) {
        $msg = $data['error']['message'] ?? 'Respuesta inesperada de la IA';
        throw new Exception('No se pudo interpretar el pedido: ' . $msg);
    }

    $textoRespuesta = '';
    foreach ($data['output'] ?? [] as $itemSalida) {
        if (($itemSalida['type'] ?? '') !== 'message') {
            continue;
        }
        foreach ($itemSalida['content'] ?? [] as $bloque) {
            if (($bloque['type'] ?? '') === 'output_text') {
                $textoRespuesta .= $bloque['text'];
            }
        }
    }

    if (!preg_match('/\{.*\}/s', $textoRespuesta, $m)) {
        throw new Exception('La IA no devolvió un formato entendible. Intenta de nuevo o cárgalo manualmente.');
    }
    $json = json_decode($m[0], true);
    if (!$json || !isset($json['items'])) {
        throw new Exception('La IA no devolvió un formato entendible. Intenta de nuevo o cárgalo manualmente.');
    }
    return $json;
}

function ia_buscar_producto_similar(mysqli $conn, string $texto): ?array
{
    $palabras = preg_split('/\s+/', trim($texto));
    $primerasPalabras = implode(' ', array_slice($palabras, 0, 2));
    $like = '%' . $conn->real_escape_string($primerasPalabras) . '%';

    $candidatos = [];
    $r = $conn->query("SELECT id_producto, nom_prod FROM productos WHERE estado = '1' AND nom_prod LIKE '$like' LIMIT 30");
    while ($row = $r->fetch_assoc()) {
        $candidatos[] = $row;
    }

    // Si la búsqueda acotada no encontró nada, probamos contra todo el catálogo activo.
    if (empty($candidatos)) {
        $r = $conn->query("SELECT id_producto, nom_prod FROM productos WHERE estado = '1'");
        while ($row = $r->fetch_assoc()) {
            $candidatos[] = $row;
        }
    }

    $mejor = null;
    $mejorPorcentaje = 0.0;
    foreach ($candidatos as $c) {
        similar_text(mb_strtoupper($texto), mb_strtoupper($c['nom_prod']), $porcentaje);
        if ($porcentaje > $mejorPorcentaje) {
            $mejorPorcentaje = $porcentaje;
            $mejor = $c;
        }
    }

    if ($mejor && $mejorPorcentaje >= 35) {
        $mejor['score'] = round($mejorPorcentaje);
        return $mejor;
    }
    return null;
}
