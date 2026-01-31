<?php
// Script para detectar filas en 'variante_p' donde 'cantidad_vp' no es numérico o está vacío.
// Uso CLI: php check_cantidad_vp.php
// Uso web: abrir /check_cantidad_vp.php en el navegador

require_once __DIR__ . '/inc/sdba/sdba.php';

$table = Sdba::table('variante_p');
$rows = $table->get();

$bad = [];
foreach ($rows as $r) {
    $val = isset($r['cantidad_vp']) ? $r['cantidad_vp'] : null;
    // Considerar vacíos o valores no numéricos
    if ($val === null || $val === '' || !is_numeric($val)) {
        $bad[] = $r;
    }
}

if (php_sapi_name() === 'cli') {
    echo "Filas encontradas: " . count($bad) . PHP_EOL;
    foreach ($bad as $b) {
        $id = isset($b['id_vp']) ? $b['id_vp'] : (isset($b['id']) ? $b['id'] : 'N/A');
        $id_producto = isset($b['id_producto']) ? $b['id_producto'] : 'N/A';
        $valor = var_export($b['cantidad_vp'] ?? null, true);
        echo "id_vp: {$id} | id_producto: {$id_producto} | cantidad_vp: {$valor}\n";
    }
    exit(0);
}

// Salida para navegador
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>Verificar cantidad_vp</title>
  <style>table{border-collapse:collapse;width:100%}td,th{border:1px solid #ccc;padding:6px;text-align:left}</style>
</head>
<body>
  <h2>Filas con `cantidad_vp` no numérico o vacío: <?php echo count($bad); ?></h2>
  <table>
    <thead><tr><th>id_vp</th><th>id_producto</th><th>cantidad_vp</th><th>fila completa (JSON)</th></tr></thead>
    <tbody>
    <?php foreach ($bad as $b):
        $id = isset($b['id_vp']) ? $b['id_vp'] : (isset($b['id']) ? $b['id'] : 'N/A');
        $id_producto = isset($b['id_producto']) ? $b['id_producto'] : 'N/A';
        $valor = htmlspecialchars((string)($b['cantidad_vp'] ?? 'NULL'));
        $json = htmlspecialchars(json_encode($b, JSON_UNESCAPED_UNICODE));
    ?>
      <tr>
        <td><?php echo $id; ?></td>
        <td><?php echo $id_producto; ?></td>
        <td><?php echo $valor; ?></td>
        <td><?php echo $json; ?></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</body>
</html>
