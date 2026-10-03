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

// Nombre completo desde Migo, con timeout corto para no frenar la venta. '' si no se pudo.
function migo_nombre_dni($dni)
{
    $token = function_exists('get_config') ? get_config('migo_token') : '';
    if ($token === '') return ['', 'Consulta de DNI no configurada'];
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
    $n = is_array($j) ? trim((string)($j['nombre'] ?? '')) : '';
    return [$n, $n === '' ? 'No se pudo obtener el nombre; puedes seguir sin DNI o escribir el nombre' : ''];
}

// 1) ¿Ya existe un cliente con este DNI?
$r = $conn->query("SELECT id_cliente, cliente FROM clientes WHERE doc_identidad = '" . $conn->real_escape_string($dni) . "'");
while ($r && $x = $r->fetch_assoc()) {
    if (isset($fusionados[(int)$x['id_cliente']])) continue;
    $out = ['estado' => 'local', 'cliente' => ['id' => (int)$x['id_cliente'], 'nombre' => $x['cliente']]];
    // Si el nombre guardado no es el nombre completo de Migo, se ofrece actualizarlo.
    list($oficial) = migo_nombre_dni($dni);
    if ($oficial !== '') {
        $oficial = cd_upper($oficial);
        $a1 = cd_normalizar($x['cliente']); $a2 = cd_normalizar($oficial);
        sort($a1); sort($a2);
        if ($a1 !== $a2) $out['nombre_oficial'] = $oficial;
    }
    echo json_encode($out);
    exit;
}

// 2) No hay cliente con ese DNI: se busca el nombre completo y candidatos para vincular
list($nombre, $err) = migo_nombre_dni($dni);
if ($nombre === '') {
    echo json_encode(['estado' => 'sin_api', 'mensaje' => $err]);
    exit;
}
$nombre = cd_upper($nombre);

// Clientes ya registrados cuyo nombre esté contenido en el nombre completo. Se incluyen también
// los que tienen otro DNI (puede estar mal digitado); la persona decide si lo corrige.
$tokens = cd_normalizar($nombre);
$cands = [];
if ($tokens) {
    // Se revisan todos los clientes (unos miles, es liviano) y se queda con los que cumplen la
    // regla: TODAS las palabras del cliente aparecen en el nombre completo de Migo.
    // Ej.: Migo "ROMERO SANTOS HUGO ALONSO" -> sí "HUGO ROMERO", no "JESUS ROMERO" ni "SANTOS LOPEZ".
    $set = array_flip($tokens);
    $todos = [];
    $df = []; // en cuántos clientes (de toda la base) aparece cada palabra
    $q = $conn->query("SELECT id_cliente, cliente, doc_identidad FROM clientes");
    while ($q && $x = $q->fetch_assoc()) {
        if (isset($fusionados[(int)$x['id_cliente']])) continue;
        $t = cd_normalizar($x['cliente']);
        if (!$t) continue;
        $dentro = true;
        foreach ($t as $w) {
            $df[$w] = ($df[$w] ?? 0) + 1;
            if (!isset($set[$w])) $dentro = false;
        }
        if (!$dentro) continue;
        $doc = trim((string)$x['doc_identidad']);
        $todos[] = ['id' => (int)$x['id_cliente'], 'nombre' => $x['cliente'], 'n' => count($t), 'tok' => $t,
                    'doc' => ($doc === '-' ? '' : $doc)];
    }
    // Un nombre de una sola palabra (ej. "ROMERO") solo cuenta si esa palabra es poco común en la base.
    $frec = $df;
    $ids = [];
    foreach ($todos as $c) {
        if ($c['n'] === 1 && ($frec[$c['tok'][0]] ?? 0) > 6) continue;
        $cands[$c['id']] = ['id' => $c['id'], 'nombre' => $c['nombre'], 'ventas' => 0, 'doc' => $c['doc'], 'n' => $c['n']];
    }
    if ($cands) {
        $r2 = $conn->query("SELECT cliente, COUNT(*) AS n FROM ventas WHERE estado != '2' AND cliente IN (" . implode(',', array_keys($cands)) . ") GROUP BY cliente");
        while ($r2 && $x = $r2->fetch_assoc()) $cands[(int)$x['cliente']]['ventas'] = (int)$x['n'];
    }
    // Más palabras en común primero (más específico), luego sin DNI antes que con DNI, luego más ventas.
    usort($cands, function ($a, $b) {
        if ($a['n'] !== $b['n']) return $b['n'] - $a['n'];
        if (($a['doc'] !== '') !== ($b['doc'] !== '')) return ($a['doc'] !== '') ? 1 : -1;
        return $b['ventas'] - $a['ventas'];
    });
    $cands = array_map(function ($c) { unset($c['n']); return $c; }, array_slice($cands, 0, 5));
}

echo json_encode(['estado' => 'api', 'nombre' => $nombre, 'candidatos' => $cands]);
$conn->close();
