<?php
// Fuente de datos para el select2 de "Factura simple" en notas_venta.php.
// Devuelve presentaciones de producto (variante_p) que calzan con el término buscado,
// en el formato {results:[{id,text,...}]} que espera select2.
ob_start();
ini_set('display_errors', '0');
error_reporting(0);
session_start();
ob_clean();
header('Content-Type: application/json');

if (!isset($_SESSION['id_usr'])) {
    echo json_encode(['results' => []]);
    exit;
}

$conn = new mysqli('localhost', 'admin_renacer', 'ikm169uhn', 'admin_renacer');
$conn->set_charset('utf8');

if ($conn->connect_error) {
    echo json_encode(['results' => []]);
    exit;
}

$term = trim($_GET['term'] ?? '');
$termino_safe = $conn->real_escape_string($term);

$r = $conn->query("
    SELECT vp.id_vp, vp.precio_vp, vp.cantidad_vp,
           p.id_producto, p.nom_prod,
           v.variante,
           u.codigo AS unidad_codigo, u.nombre AS unidad_nombre
    FROM variante_p vp
    LEFT JOIN productos p ON p.id_producto = vp.producto_vp
    LEFT JOIN variantes v ON v.id_variante = vp.variante_vp
    LEFT JOIN unidades  u ON u.id_unidad = p.unidad_prod
    WHERE p.nom_prod LIKE '%$termino_safe%'
    ORDER BY p.nom_prod
    LIMIT 30
");

if (!$r) {
    error_log('buscar_producto_vp.php: ' . $conn->error);
}

$results = [];
if ($r) {
    while ($row = $r->fetch_assoc()) {
        $cantidad_vp = (float)$row['cantidad_vp'];
        $precio_unit = $cantidad_vp > 0 ? round((float)$row['precio_vp'] / $cantidad_vp, 4) : (float)$row['precio_vp'];

        $variante = trim((string)$row['variante']);
        $texto = $row['nom_prod'] . ($variante && $variante !== '0000-00-00' ? ' – ' . $variante : '');

        $results[] = [
            'id'            => (int)$row['id_vp'],
            'text'          => $texto,
            'prod_id'       => (int)$row['id_producto'],
            'precio'        => $precio_unit,
            'unidad_codigo' => $row['unidad_codigo'] ?: 'NIU',
        ];
    }
}

$conn->close();
echo json_encode(['results' => $results]);
