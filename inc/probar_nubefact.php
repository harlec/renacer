<?php
session_start();

header('Content-Type: application/json');

if ($_SESSION['type'] !== 'admin') {
    echo json_encode(array('ok' => false, 'mensaje' => 'No autorizado'));
    exit;
}

$ruta = trim($_POST['ruta']);
$token = trim($_POST['token']);

if ($ruta === '' || $token === '') {
    echo json_encode(array('ok' => false, 'mensaje' => 'Completa Ruta y Token antes de probar.'));
    exit;
}

// Operación de solo lectura, no genera ni modifica ningún comprobante.
$data = array(
    "operacion" => "consultar_comprobante",
    "tipo_de_comprobante" => "1",
    "serie" => "PRUEBA",
    "numero" => "1"
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
$respuesta = curl_exec($ch);
$curl_error = curl_error($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($respuesta === false) {
    echo json_encode(array('ok' => false, 'mensaje' => 'No se pudo conectar: ' . $curl_error));
    exit;
}

$leer = json_decode($respuesta, true);

if ($leer === null) {
    echo json_encode(array('ok' => false, 'mensaje' => 'Respuesta inesperada (HTTP ' . $http_code . '): ' . substr($respuesta, 0, 200)));
    exit;
}

if (isset($leer['errors'])) {
    $msg = strtolower($leer['errors']);
    if (strpos($msg, 'token') !== false || strpos($msg, 'autoriza') !== false || $http_code == 401) {
        echo json_encode(array('ok' => false, 'mensaje' => 'Credenciales inválidas: ' . $leer['errors']));
    } else {
        // Cualquier otro error (ej. "comprobante no encontrado") confirma que Ruta/Token son válidos.
        echo json_encode(array('ok' => true, 'mensaje' => 'Conexión correcta (Ruta y Token válidos). Respuesta: ' . $leer['errors']));
    }
    exit;
}

echo json_encode(array('ok' => true, 'mensaje' => 'Conexión correcta. Ruta y Token válidos.'));
