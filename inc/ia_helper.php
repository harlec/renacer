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

// Historial de productos que un cliente ya compró antes, del más reciente al más antiguo,
// como nom_prod => id_producto (para poder resolver directamente a un producto del catálogo
// sin volver a buscarlo por nombre).
function ia_historial_cliente_con_id(mysqli $conn, int $idCliente, int $limite = 40): array
{
    $stmt = $conn->prepare("
        SELECT pr.id_producto, pr.nom_prod
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

    $productos = []; // nom_prod => id_producto, dedupe conservando el orden (más reciente primero)
    while ($row = $res->fetch_assoc()) {
        $productos[$row['nom_prod']] = (int)$row['id_producto'];
        if (count($productos) >= $limite) {
            break;
        }
    }
    $stmt->close();
    return $productos;
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
- Para cada línea, revisa la lista de "Historial de productos que este cliente ya compró antes" (si se proporcionó) e intenta reconocer si el cliente se refiere a alguno de esos productos usando un apodo, sinónimo regional, abreviatura o marca incompleta (por ejemplo: "casillero de huevo", "cubeta de huevo" o "jaba de huevo" suelen referirse a un producto de huevos aunque la palabra "huevo(s)" no aparezca igual en el nombre del catálogo; "de cuatrocientos" puede referirse a un producto con "400" en el nombre). Si encuentras una coincidencia razonable, copia el nombre EXACTAMENTE tal como aparece en esa lista en el campo "producto_historial". Si no hay una coincidencia clara, o no se te dio historial, usa null en ese campo — no inventes ni copies un nombre que no esté literalmente en la lista.

Responde ÚNICAMENTE con JSON válido, sin texto antes ni después, con este formato exacto:
{"cliente_texto": "nombre del cliente si aparece, o null", "items": [{"texto": "descripción del producto tal cual se leyó/escuchó", "cantidad": numero, "producto_historial": "nombre exacto copiado del historial, o null"}]}
TXT;

    $contextoHistorial = '';
    if (!empty($historialProductos)) {
        $contextoHistorial = "\n\nHistorial de productos que este cliente ya compró antes (úsalo para interpretar mejor apodos, sinónimos, abreviaturas o medidas incompletas):\n- " . implode("\n- ", $historialProductos);
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

// Palabras de cantidad/medida/relleno que no aportan a identificar QUÉ producto es — al dejarlas
// en la comparación, frases largas ("5 kilos de azúcar") ganan similitud falsa contra cualquier
// nombre de catálogo que también tenga "DE", "KILO", etc., sin relación real con el producto.
const IA_PALABRAS_VACIAS_MATCH = [
    'DE', 'DEL', 'LA', 'EL', 'LOS', 'LAS', 'UN', 'UNA', 'UNOS', 'UNAS', 'Y', 'CON', 'PARA', 'A',
    'SOLO', 'SOLA', 'NOMAS',
    'KILO', 'KILOS', 'KG', 'GR', 'GRAMO', 'GRAMOS', 'LITRO', 'LITROS', 'LT', 'ML',
    'UNIDAD', 'UNIDADES', 'PAQUETE', 'PAQUETES', 'CAJA', 'CAJAS', 'BOLSA', 'BOLSAS',
    'MEDIO', 'MEDIA', 'CUARTO', 'CUARTOS', 'DOCENA', 'DOCENAS',
];

function ia_texto_para_match(string $texto): string
{
    $t = preg_replace('/\d+/', ' ', ia_normalizar_texto($texto));
    $palabras = array_filter(
        preg_split('/\s+/', trim($t)),
        fn($p) => $p !== '' && !in_array($p, IA_PALABRAS_VACIAS_MATCH, true)
    );
    $limpio = implode(' ', $palabras);
    return $limpio !== '' ? $limpio : trim($t);
}

// Compara dos textos ya limpios palabra por palabra (en vez de como una sola cadena larga).
// Comparar la frase completa de un tirón deja que dos palabras totalmente distintas "empaten"
// por casualidad de letras (el español reusa mucho A, R, I, N, S...); palabra por palabra ese
// ruido se reduce. Además le da más peso a la primera palabra de la búsqueda porque, en el
// catálogo de este negocio, el nombre del producto casi siempre empieza con su palabra base
// (ej. "QUESO FRESCO", "AZUCAR RUBIA...") — así una descripción que sólo MENCIONA esa palabra
// más adelante (ej. "BOLSAS PARA ENVASAR AZUCAR") no le puede ganar al producto real.
function ia_similitud_texto(string $textoA, string $textoB): float
{
    $palabrasA = array_values(array_filter(preg_split('/\s+/', trim($textoA))));
    $palabrasB = array_values(array_filter(preg_split('/\s+/', trim($textoB))));
    if (empty($palabrasA) || empty($palabrasB)) {
        return 0.0;
    }

    $sumaPonderada = 0.0;
    $pesoTotal = 0.0;
    foreach ($palabrasA as $i => $pa) {
        $mejor = 0.0;
        foreach ($palabrasB as $pb) {
            similar_text($pa, $pb, $pct);
            if ($pct > $mejor) {
                $mejor = $pct;
            }
        }
        $peso = ($i === 0) ? 2.0 : 1.0;
        $sumaPonderada += $mejor * $peso;
        $pesoTotal += $peso;
    }
    $score = $sumaPonderada / $pesoTotal;

    similar_text($palabrasA[0], $palabrasB[0], $pctInicio);
    if ($pctInicio >= 80) {
        $score += 15;
    }

    return $score;
}

// $historial: nom_prod => id_producto, de lo que este cliente ya compró antes (puede venir vacío).
// $sugeridoHistorial: nombre que la IA identificó, dentro de ese historial, como lo que el cliente
// probablemente quiso decir (por apodo/sinónimo) — viene de ia_interpretar_pedido().
function ia_buscar_producto_similar(mysqli $conn, string $texto, array $historial = [], ?string $sugeridoHistorial = null): ?array
{
    // 1) Si la IA ya identificó, usando el historial de este cliente, a qué producto se refiere
    //    (por ejemplo "casillero de huevo" -> el producto de huevos que ya le compró antes),
    //    confiamos en esa coincidencia semántica en vez de comparar caracteres a ciegas.
    if ($sugeridoHistorial !== null && $sugeridoHistorial !== '') {
        $sugeridoNorm = ia_normalizar_texto($sugeridoHistorial);
        foreach ($historial as $nombreHist => $idHist) {
            if (ia_normalizar_texto($nombreHist) === $sugeridoNorm) {
                return ['id_producto' => $idHist, 'nom_prod' => $nombreHist, 'score' => 96];
            }
        }
    }

    $textoLimpio = ia_texto_para_match($texto);

    // 2) Comparamos historial del cliente y catálogo acotado (por nombre) EN CONJUNTO, dándole al
    //    historial una pequeña ventaja — pero sin dejar que gane sólo por casualidad de caracteres:
    //    si el catálogo tiene algo genuinamente más parecido, debe poder ganarle.
    $candidatos = [];
    $idsVistos = [];
    foreach ($historial as $nombreHist => $idHist) {
        $candidatos[] = ['id_producto' => $idHist, 'nom_prod' => $nombreHist, 'bono' => 12];
        $idsVistos[$idHist] = true;
    }

    $palabras = preg_split('/\s+/', trim($textoLimpio));
    $primerasPalabras = implode(' ', array_slice($palabras, 0, 2));
    if ($primerasPalabras !== '') {
        $like = '%' . $conn->real_escape_string($primerasPalabras) . '%';
        $r = $conn->query("SELECT id_producto, nom_prod FROM productos WHERE estado = '1' AND nom_prod LIKE '$like' LIMIT 30");
        while ($row = $r->fetch_assoc()) {
            $id = (int)$row['id_producto'];
            if (!isset($idsVistos[$id])) {
                $candidatos[] = ['id_producto' => $id, 'nom_prod' => $row['nom_prod'], 'bono' => 0];
                $idsVistos[$id] = true;
            }
        }
    }

    $mejor = null;
    $mejorPuntaje = 0.0;
    foreach ($candidatos as $c) {
        $puntaje = ia_similitud_texto($textoLimpio, ia_texto_para_match($c['nom_prod'])) + $c['bono'];
        if ($puntaje > $mejorPuntaje) {
            $mejorPuntaje = $puntaje;
            $mejor = $c;
        }
    }

    // 3) Si nada de lo anterior dio una coincidencia razonable, caemos al catálogo completo.
    if (!$mejor || $mejorPuntaje < 45) {
        $r = $conn->query("SELECT id_producto, nom_prod FROM productos WHERE estado = '1'");
        while ($row = $r->fetch_assoc()) {
            $id = (int)$row['id_producto'];
            if (isset($idsVistos[$id])) {
                continue;
            }
            $porcentaje = ia_similitud_texto($textoLimpio, ia_texto_para_match($row['nom_prod']));
            if ($porcentaje > $mejorPuntaje) {
                $mejorPuntaje = $porcentaje;
                $mejor = ['id_producto' => $id, 'nom_prod' => $row['nom_prod']];
            }
        }
    }

    if ($mejor && $mejorPuntaje >= 45) {
        return ['id_producto' => $mejor['id_producto'], 'nom_prod' => $mejor['nom_prod'], 'score' => round(min($mejorPuntaje, 99))];
    }
    return null;
}
