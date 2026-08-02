<?php
session_start();
if (!isset($_SESSION['id_usr'])) {
    http_response_code(403);
    exit('Sin sesión');
}

$id_usuario = intval($_SESSION['id_usr']);
$es_admin   = ($_SESSION['type'] == 'admin');
$where_user = $es_admin ? "" : "AND v.usuario = $id_usuario";

$mes  = isset($_GET['mes'])  ? max(1, min(12, intval($_GET['mes'])))  : intval(date('n'));
$anio = isset($_GET['anio']) ? intval($_GET['anio'])                  : intval(date('Y'));

$desde = sprintf('%04d-%02d-01', $anio, $mes);
$hasta = date('Y-m-d', strtotime($desde . ' +1 month'));

$conn = new mysqli('localhost', 'admin_renacer', 'ikm169uhn', 'admin_renacer');
$conn->set_charset('utf8');

if ($conn->connect_error) {
    http_response_code(500);
    exit('Error de conexión a la base de datos.');
}

// Incluye todos los estados (activo, baja comunicada, anulado) — el ZIP debe
// contener exactamente lo que el listado del período está mostrando.
$r = $conn->query("
    SELECT c.id_comprobante, c.serie, c.numero, c.url
    FROM comprobantes c
    WHERE c.fecha >= '$desde' AND c.fecha < '$hasta'
      AND c.id_comprobante IN (
          SELECT DISTINCT cv.comprobante
          FROM comprobante_ventas cv
          JOIN ventas v ON v.id_venta = cv.venta
          WHERE 1=1 $where_user
      )
    ORDER BY c.id_comprobante
");

$comprobantes = [];
if ($r) {
    while ($row = $r->fetch_assoc()) $comprobantes[] = $row;
}
$conn->close();

if (empty($comprobantes)) {
    http_response_code(404);
    exit('No hay comprobantes emitidos en ese período.');
}

$tmpZip = tempnam(sys_get_temp_dir(), 'cmp_');
// Garantiza el borrado del temporal aunque la descarga se corte a la mitad
// (cliente cierra la pestaña, se cae la conexión, error fatal, etc.).
register_shutdown_function(function () use ($tmpZip) {
    if (file_exists($tmpZip)) unlink($tmpZip);
});

$zip = new ZipArchive();
if ($zip->open($tmpZip, ZipArchive::OVERWRITE) !== true) {
    http_response_code(500);
    exit('No se pudo crear el archivo ZIP.');
}

$agregados = 0;
foreach ($comprobantes as $c) {
    if (empty($c['url'])) continue;

    // Convención de Nubefact: el link "enlace" + ".pdf" da la representación en PDF.
    $pdfUrl = rtrim($c['url'], '/') . '.pdf';

    $ch = curl_init($pdfUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 20);
    $pdf = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($pdf === false || $http_code != 200) continue;

    $nombre = $c['serie'] . '-' . $c['numero'] . '.pdf';
    $zip->addFromString($nombre, $pdf);
    $agregados++;
}
$zip->close();

if ($agregados === 0) {
    http_response_code(502);
    exit('No se pudo descargar ningún comprobante desde Nubefact. Intenta de nuevo en unos minutos.');
}

$nombreDescarga = 'comprobantes_' . sprintf('%04d-%02d', $anio, $mes) . '.zip';
header('Content-Type: application/zip');
header('Content-Disposition: attachment; filename="' . $nombreDescarga . '"');
header('Content-Length: ' . filesize($tmpZip));
readfile($tmpZip);
exit;
