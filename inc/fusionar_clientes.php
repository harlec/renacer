<?php
// Fusiona clientes duplicados en un cliente principal: las ventas y preventas del duplicado
// pasan al principal y el duplicado queda registrado como fusionado en cliente_fusiones. No se borra nada; cada
// fusión queda registrada en cliente_fusiones y se puede deshacer.
ob_start();
ini_set('display_errors', '0');
error_reporting(0);
session_start();
ob_clean();
header('Content-Type: application/json');

if (!isset($_SESSION['id_usr']) || ($_SESSION['type'] ?? '') !== 'admin') {
    echo json_encode(['ok' => false, 'mensaje' => 'Sin permiso']);
    exit;
}

$principal = intval($_POST['principal'] ?? 0);
$dups = array_values(array_unique(array_filter(array_map('intval', (array)($_POST['duplicados'] ?? [])))));
$dups = array_values(array_diff($dups, [$principal]));
$usuario = intval($_SESSION['id_usr']);

if ($principal <= 0 || !$dups) {
    echo json_encode(['ok' => false, 'mensaje' => 'Elige un cliente principal y al menos un duplicado']);
    exit;
}

$conn = new mysqli('localhost', 'admin_renacer', 'ikm169uhn', 'admin_renacer');
$conn->set_charset('utf8');
if ($conn->connect_error) {
    echo json_encode(['ok' => false, 'mensaje' => 'DB: ' . $conn->connect_error]);
    exit;
}

$ids_sql = implode(',', array_merge([$principal], $dups));
$r = $conn->query("SELECT id_cliente, doc_identidad, telefono, email, COALESCE(estado,'1') AS estado FROM clientes WHERE id_cliente IN ($ids_sql) FOR UPDATE");
$cl = [];
while ($r && $x = $r->fetch_assoc()) $cl[(int)$x['id_cliente']] = $x;
if (count($cl) !== count($dups) + 1) {
    echo json_encode(['ok' => false, 'mensaje' => 'Alguno de los clientes no existe']);
    exit;
}
require_once(__DIR__ . '/cliente_helper.php');
if (array_intersect(cliente_ids_fusionados($conn), array_merge([$principal], $dups))) {
    echo json_encode(['ok' => false, 'mensaje' => 'Alguno de los clientes ya fue fusionado. Recarga la página.']);
    exit;
}

$conn->begin_transaction();
try {
    $actual = $cl[$principal];
    foreach ($dups as $d) {
        $vids = []; $pids = [];
        $q = $conn->query("SELECT id_venta FROM ventas WHERE cliente = $d");
        while ($q && $x = $q->fetch_assoc()) $vids[] = (int)$x['id_venta'];
        $q = $conn->query("SELECT id_preventa FROM preventa WHERE cliente = $d");
        while ($q && $x = $q->fetch_assoc()) $pids[] = (int)$x['id_preventa'];

        if (!$conn->query("UPDATE ventas SET cliente = $principal WHERE cliente = $d")) throw new Exception($conn->error);
        if (!$conn->query("UPDATE preventa SET cliente = $principal WHERE cliente = $d")) throw new Exception($conn->error);

        // El principal hereda documento/teléfono/email solo si los tenía vacíos.
        $rellenado = [];
        foreach (['doc_identidad', 'telefono', 'email'] as $campo) {
            if (trim((string)$actual[$campo]) === '' && trim((string)$cl[$d][$campo]) !== '') {
                $actual[$campo] = $cl[$d][$campo];
                $rellenado[$campo] = $cl[$d][$campo];
                $v = $conn->real_escape_string($cl[$d][$campo]);
                if (!$conn->query("UPDATE clientes SET $campo = '$v' WHERE id_cliente = $principal")) throw new Exception($conn->error);
            }
        }

        $st = $conn->prepare("INSERT INTO cliente_fusiones (id_principal, id_duplicado, ventas_ids, preventa_ids, datos_rellenados, usuario, fecha) VALUES (?,?,?,?,?,?,NOW())");
        $j1 = json_encode($vids); $j2 = json_encode($pids); $j3 = json_encode($rellenado);
        $st->bind_param('iisssi', $principal, $d, $j1, $j2, $j3, $usuario);
        if (!$st->execute()) throw new Exception($conn->error);
    }
    $conn->commit();
    @unlink(sys_get_temp_dir() . '/renacer_dup_clientes.cache');
    echo json_encode(['ok' => true]);
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['ok' => false, 'mensaje' => 'No se pudo fusionar: ' . $e->getMessage()]);
}
$conn->close();
