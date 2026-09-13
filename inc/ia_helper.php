<?php
require_once __DIR__ . '/sdba/sdba.php';
require_once __DIR__ . '/config_facturacion.php';

// OpenAI solo acepta mp3, mp4, mpeg, mpga, m4a, wav y webm. Las notas de voz de
// WhatsApp llegan en .opus, así que si hace falta lo convertimos con ffmpeg antes
// de mandarlo. Devuelve la ruta del archivo convertido, o null si no hacía falta.
function ia_convertir_audio_si_hace_falta(string $rutaArchivo, string $nombreOriginal): ?string
{
    $extension = strtolower(pathinfo($nombreOriginal, PATHINFO_EXTENSION));
    $formatosOk = ['mp3', 'mp4', 'mpeg', 'mpga', 'm4a', 'wav', 'webm'];
    if (in_array($extension, $formatosOk, true)) {
        return null;
    }

    if (!function_exists('exec')) {
        throw new Exception('El formato de audio ".' . $extension . '" no es compatible con la IA y el servidor no puede convertirlo automáticamente (la función exec de PHP está deshabilitada). Convierte el audio a MP3 antes de subirlo, o pide a tu hosting que habilite exec + ffmpeg.');
    }

    $rutaDestino = $rutaArchivo . '_conv.mp3';
    $comando = 'ffmpeg -y -i ' . escapeshellarg($rutaArchivo) . ' -ar 16000 -ac 1 ' . escapeshellarg($rutaDestino) . ' 2>&1';
    exec($comando, $salida, $codigoSalida);

    if ($codigoSalida !== 0 || !file_exists($rutaDestino)) {
        throw new Exception('No se pudo convertir automáticamente el audio ".' . $extension . '" (parece que ffmpeg no está instalado en el servidor). Convierte el audio a MP3 antes de subirlo, o pide a tu hosting que instale ffmpeg.');
    }

    return $rutaDestino;
}

function ia_transcribir_audio(string $rutaArchivo, string $nombreOriginal): string
{
    $apiKey = get_config('openai_api_key');
    if ($apiKey === '') {
        throw new Exception('Falta configurar la API Key de OpenAI en Configuración > Facturación Electrónica');
    }

    $rutaConvertida = ia_convertir_audio_si_hace_falta($rutaArchivo, $nombreOriginal);
    $rutaFinal = $rutaConvertida ?? $rutaArchivo;
    $nombreFinal = $rutaConvertida ? 'audio.mp3' : $nombreOriginal;

    try {
        $tipo = mime_content_type($rutaFinal) ?: 'audio/mpeg';
        $ch = curl_init('https://api.openai.com/v1/audio/transcriptions');
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $apiKey,
            ],
            CURLOPT_POSTFIELDS => [
                'file'     => new CURLFile($rutaFinal, $tipo, $nombreFinal),
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
    } finally {
        if ($rutaConvertida) {
            @unlink($rutaConvertida);
        }
    }
}

function ia_normalizar_texto(string $s): string
{
    $s = mb_strtoupper($s, 'UTF-8');
    return strtr($s, [
        'Á' => 'A', 'É' => 'E', 'Í' => 'I', 'Ó' => 'O', 'Ú' => 'U',
        'Ü' => 'U', 'Ñ' => 'N',
    ]);
}

function ia_buscar_id_cliente(mysqli $conn, string $nombre): ?int
{
    $nombre = trim($nombre);
    if ($nombre === '') {
        return null;
    }
    $safe = $conn->real_escape_string($nombre);
    $r = $conn->query("SELECT id_cliente FROM clientes WHERE UPPER(TRIM(cliente)) = UPPER('$safe') LIMIT 1");
    $row = $r ? $r->fetch_assoc() : null;
    return $row ? (int)$row['id_cliente'] : null;
}

function ia_historial_cliente(mysqli $conn, int $idCliente, int $limite = 40): array
{
    $stmt = $conn->prepare("
        SELECT pr.nom_prod
        FROM detalle_ventas dv
        JOIN ventas v ON v.id_venta = dv.venta
        JOIN productos pr ON pr.id_producto = dv.producto
        WHERE v.cliente = ? AND v.estado != '2'
        ORDER BY v.id_venta DESC
        LIMIT 200
    ");
    $stmt->bind_param('i', $idCliente);
    $stmt->execute();
    $res = $stmt->get_result();

    $nombres = [];
    while ($row = $res->fetch_assoc()) {
        $nombres[$row['nom_prod']] = true; // dedupe conservando el orden (más reciente primero)
        if (count($nombres) >= $limite) {
            break;
        }
    }
    $stmt->close();
    return array_keys($nombres);
}

function ia_interpretar_pedido(?string $texto, ?string $rutaImagen = null, array $historialProductos = []): array
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

    $contextoHistorial = '';
    if (!empty($historialProductos)) {
        $contextoHistorial = "\n\nHistorial de productos que este cliente ya compró antes (úsalo para interpretar mejor apodos, abreviaturas o medidas incompletas — por ejemplo, si pide \"de cuatrocientos\" y en el historial hay un producto con \"400\" en el nombre, probablemente se refiere a ese):\n- " . implode("\n- ", $historialProductos);
    }

    $contenido = [
        [
            'type' => 'input_text',
            'text' => $instrucciones . $contextoHistorial . "\n\nTexto del pedido a interpretar:\n" . ($texto !== null && $texto !== '' ? $texto : '(ver imagen adjunta)'),
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
        similar_text(ia_normalizar_texto($texto), ia_normalizar_texto($c['nom_prod']), $porcentaje);
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
