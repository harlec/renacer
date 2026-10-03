<?php
// Deshace una fusión: devuelve al duplicado sus ventas/preventas y lo reactiva.
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

$id = intval($_POST['id'] ?? 0);
$conn = new mysqli('localhost', 'admin_renacer', 'ikm169uhn', 'admin_renacer');
$conn->set_charset('utf8');
if ($id <= 0 || $conn->connect_error) {
    echo json_encode(['ok' => false, 'mensaje' => 'Solicitud inválida']);
    exit;
}

$conn->begin_transaction();
try {
    $r = $conn->query("SELECT * FROM cliente_fusiones WHERE id_fusion = $id FOR UPDATE");
    $f = $r ? $r->fetch_assoc() : null;
    if (!$f || $f['deshecha']) throw new Exception('La fusión no existe o ya fue deshecha');
    $p = (int)$f['id_principal']; $d = (int)$f['id_duplicado'];

    $vids = array_map('intval', json_decode($f['ventas_ids'], true) ?: []);
    $pids = array_map('intval', json_decode($f['preventa_ids'], true) ?: []);
    // Solo se devuelven las que siguen en el principal (si alguien las reasignó después, se respetan).
    if ($vids) $conn->query("UPDATE ventas SET cliente = $d WHERE cliente = $p AND id_venta IN (" . implode(',', $vids) . ")");
    if ($pids) $conn->query("UPDATE preventa SET cliente = $d WHERE cliente = $p AND id_preventa IN (" . implode(',', $pids) . ")");

    // Datos que el principal heredó: se quitan solo si siguen siendo los mismos valores.
    foreach ((json_decode($f['datos_rellenados'], true) ?: []) as $campo => $valor) {
        if (!in_array($campo, ['doc_identidad', 'telefono', 'email'], true)) continue;
        $v = $conn->real_escape_string($valor);
        $conn->query("UPDATE clientes SET $campo = '' WHERE id_cliente = $p AND $campo = '$v'");
    }

    $conn->query("UPDATE cliente_fusiones SET deshecha = 1 WHERE id_fusion = $id");
    $conn->commit();
    @unlink(sys_get_temp_dir() . '/renacer_dup_clientes.cache');
    echo json_encode(['ok' => true]);
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['ok' => false, 'mensaje' => $e->getMessage()]);
}
$conn->close();
