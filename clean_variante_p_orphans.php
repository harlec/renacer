<?php
// Limpia variantes en `variante_p` que no tengan producto asociado o con `cantidad_vp` inválida.
// Uso CLI: php clean_variante_p_orphans.php [--delete]
// Uso web: /clean_variante_p_orphans.php  (añadir ?delete=1 para borrar)

require_once __DIR__ . '/inc/sdba/sdba.php';

$table = Sdba::table('variante_p');
$rows = $table->get();

$orphan_ids = [];
$invalid_ids = [];
foreach ($rows as $r) {
    $id_vp = isset($r['id_vp']) ? $r['id_vp'] : (isset($r['id']) ? $r['id'] : null);
    $id_producto = isset($r['id_producto']) ? $r['id_producto'] : null;
    $cantidad = isset($r['cantidad_vp']) ? $r['cantidad_vp'] : null;

    // Comprobar existencia del producto
    $exists = false;
    if ($id_producto !== null) {
        $p = Sdba::table('productos');
        $p->where('id_producto', $id_producto);
        $p_row = $p->get_one();
        $exists = (bool)$p_row;
    }

    if (!$exists) {
        $orphan_ids[] = $id_vp;
        continue;
    }

    // Comprobar cantidad válida (numérica y no vacía)
    if ($cantidad === null || $cantidad === '' || !is_numeric($cantidad)) {
        $invalid_ids[] = $id_vp;
    }
}

$to_delete = array_unique(array_merge($orphan_ids, $invalid_ids));

if (php_sapi_name() === 'cli') {
    echo "Variantes sin producto: " . count($orphan_ids) . PHP_EOL;
    echo "Variantes con cantidad inválida: " . count($invalid_ids) . PHP_EOL;
    echo "Total a considerar: " . count($to_delete) . PHP_EOL;
    if (in_array('--delete', $argv) && count($to_delete) > 0) {
        foreach ($to_delete as $id) {
            $t = Sdba::table('variante_p');
            $t->where('id_vp', $id);
            $t->delete();
            echo "Eliminado id_vp={$id}\n";
        }
        echo "Eliminación completada.\n";
    } else {
        foreach ($to_delete as $id) {
            echo "id_vp={$id}\n";
        }
        echo "Ejecuta: php clean_variante_p_orphans.php --delete  para borrar estas filas\n";
    }
    exit(0);
}

$delete_requested = isset($_GET['delete']) && $_GET['delete'] == '1';
$deleted = 0;
if ($delete_requested && count($to_delete) > 0) {
    foreach ($to_delete as $id) {
        $t = Sdba::table('variante_p');
        $t->where('id_vp', $id);
        $t->delete();
        $deleted++;
    }
}

?>
<!doctype html>
<html lang="es">
<head><meta charset="utf-8"><title>Limpiar variante_p</title>
<style>table{border-collapse:collapse;width:100%}td,th{border:1px solid #ccc;padding:6px;text-align:left}</style>
</head>
<body>
  <h2>Resumen</h2>
  <p>Variantes sin producto: <?php echo count($orphan_ids); ?></p>
  <p>Variantes con cantidad inválida: <?php echo count($invalid_ids); ?></p>
  <p>Total detectado: <?php echo count($to_delete); ?></p>
  <?php if ($delete_requested): ?>
    <p style="color:green">Se eliminaron <?php echo $deleted; ?> filas.</p>
  <?php else: ?>
    <?php if (count($to_delete) > 0): ?>
      <p>Para eliminar ejecuta este enlace (requiere confirmación): <a href="?delete=1">Eliminar filas detectadas</a></p>
    <?php else: ?>
      <p>No se detectaron filas a eliminar.</p>
    <?php endif; ?>
  <?php endif; ?>
  <?php if (count($to_delete) > 0): ?>
    <h3>IDs detectados</h3>
    <table>
      <thead><tr><th>id_vp</th></tr></thead>
      <tbody>
      <?php foreach ($to_delete as $id): ?>
        <tr><td><?php echo htmlspecialchars($id); ?></td></tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  <?php endif; ?>
</body>
</html>
