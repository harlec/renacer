<?php
// Búsqueda de cliente por DNI para la pantalla de ventas (opcional y no bloqueante).
//  1) Si ya hay un cliente con ese DNI -> se usa ese.
//  2) Si no, se consulta el nombre completo en Migo (timeout corto) y se buscan clientes ya
//     registrados SIN DNI cuyo nombre sea parecido, para vincularlos en vez de crear otro.
// Nunca crea ni modifica nada: solo devuelve datos. El guardado ocurre al registrar la venta.
ob_start();
ini_set('display_errors', '0');
error_reporting(0);
session_start();
ob_clean();
header('Content-Type: application/json');

if (!isset($_SESSION['id_usr'])) {
    echo json_encode(['estado' => 'error', 'mensaje' => 'Sin sesión']);
    exit;
}

include_once(__DIR__ . '/sdba/sdba.php');
include_once(__DIR__ . '/config_facturacion.php');
require_once(__DIR__ . '/cliente_helper.php');
require_once(__DIR__ . '/cliente_duplicados.php');

$dni = preg_replace('/\D+/', '', $_POST['dni'] ?? '');
if (strlen($dni) !== 8) {
    echo json_encode(['estado' => 'error', 'mensaje' => 'El DNI debe tener 8 dígitos']);
    exit;
}

$conn = new mysqli('localhost', 'admin_renacer', 'ikm169uhn', 'admin_renacer');
$conn->set_charset('utf8');
if ($conn->connect_error) {
    echo json_encode(['estado' => 'error', 'mensaje' => 'DB']);
    exit;
}

$fusionados = array_flip(cliente_ids_fusionados($conn));

// 1) ¿Ya existe un cliente con este DNI?
$r = $conn->query("SELECT id_cliente, cliente FROM clientes WHERE doc_identidad = '" . $conn->real_escape_string($dni) . "'");
while ($r && $x = $r->fetch_assoc()) {
    if (isset($fusionados[(int)$x['id_cliente']])) continue;
    echo json_encode(['estado' => 'local', 'cliente' => ['id' => (int)$x['id_cliente'], 'nombre' => $x['cliente']]]);
    exit;
}

// 2) Nombre completo desde Migo (con timeout corto para no frenar la venta)
$token = function_exists('get_config') ? get_config('migo_token') : '';
if ($token === '') {
    echo json_encode(['estado' => 'sin_api', 'mensaje' => 'Consulta de DNI no configurada']);
    exit;
}
$ch = curl_init('https://api.migo.pe/api/v1/dni');
curl_setopt_array($ch, [
    CURLOPT_HTTPHEADER     => ['Accept: application/json'],
    CURLOPT_POST           => 1,
    CURLOPT_POSTFIELDS     => ['dni' => $dni, 'token' => $token],
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_CONNECTTIMEOUT => 3,
    CURLOPT_TIMEOUT        => 5,
]);
$resp = curl_exec($ch);
curl_close($ch);
$j = $resp ? json_decode($resp, true) : null;
$nombre = is_array($j) ? trim((string)($j['nombre'] ?? '')) : '';
if ($nombre === '') {
    echo json_encode(['estado' => 'sin_api', 'mensaje' => 'No se pudo obtener el nombre; puedes seguir sin DNI o escribir el nombre']);
    exit;
}
$nombre = cd_upper($nombre);

// Clientes ya registrados SIN DNI cuyo nombre esté contenido en el nombre completo.
$tokens = cd_normalizar($nombre);
$cands = [];
if ($tokens) {
    $like = [];
    foreach ($tokens as $t) { if (strlen($t) >= 3) $like[] = "UPPER(c.cliente) LIKE '%" . $conn->real_escape_string($t) . "%'"; }
    if ($like) {
        $q = $conn->query("
            SELECT c.id_cliente, c.cliente, COUNT(v.id_venta) AS ventas
            FROM clientes c LEFT JOIN ventas v ON v.cliente = c.id_cliente AND v.estado != '2'
            WHERE (c.doc_identidad IS NULL OR TRIM(c.doc_identidad) = '' OR c.doc_identidad = '-')
              AND (" . implode(' OR ', $like) . ")
            GROUP BY c.id_cliente, c.cliente LIMIT 300");
        $set = array_flip($tokens);
        $todos = [];
        while ($q && $x = $q->fetch_assoc()) {
            if (isset($fusionados[(int)$x['id_cliente']])) continue;
            $t = cd_normalizar($x['cliente']);
            if (!$t) continue;
            $dentro = true;
            foreach ($t as $w) { if (!isset($set[$w])) { $dentro = false; break; } }
            if ($dentro) $todos[] = ['id' => (int)$x['id_cliente'], 'nombre' => $x['cliente'], 'ventas' => (int)$x['ventas'], 'n' => count($t), 'tok' => $t];
        }
        // Un nombre de una sola palabra solo cuenta si esa palabra es poco común entre los clientes.
        $frec = [];
        foreach ($todos as $c) foreach ($c['tok'] as $w) $frec[$w] = ($frec[$w] ?? 0) + 1;
        foreach ($todos as $c) {
            if ($c['n'] === 1 && ($frec[$c['tok'][0]] ?? 0) > 6) continue;
            $cands[] = ['id' => $c['id'], 'nombre' => $c['nombre'], 'ventas' => $c['ventas']];
        }
        usort($cands, function ($a, $b) { return $b['ventas'] - $a['ventas']; });
        $cands = array_slice($cands, 0, 6);
    }
}

echo json_encode(['estado' => 'api', 'nombre' => $nombre, 'candidatos' => $cands]);
$conn->close();
