<?php
// Script de diagnóstico - BORRAR DESPUÉS DE USAR
session_start();
if (empty($_SESSION['ingress'])) { die('No autorizado'); }

require_once 'inc/sdba/sdba.php';
require_once 'inc/tablet_config.php';

// 1. Ver estructura de variante_p
$raw = Sdba::table('variante_p');
$raw->left_join('variante_vp', 'variantes', 'id_variante');
$raw->left_join('producto_vp', 'productos',  'id_producto');
$all = $raw->get();

echo "<h2>Total filas variante_p con joins: " . count($all) . "</h2>";

if (count($all) > 0) {
    echo "<h3>Claves disponibles (primera fila):</h3>";
    echo "<pre>" . implode(', ', array_keys($all[0])) . "</pre>";

    echo "<h3>Primeras 10 filas:</h3>";
    echo "<table border='1' cellpadding='4' style='font-size:12px'>";
    $keys = array_keys($all[0]);
    echo "<tr>";
    foreach ($keys as $k) echo "<th>$k</th>";
    echo "</tr>";
    foreach (array_slice($all, 0, 10) as $row) {
        echo "<tr>";
        foreach ($keys as $k) echo "<td>" . htmlspecialchars((string)($row[$k] ?? '')) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p style='color:red'>La tabla variante_p está vacía o los joins no retornan datos.</p>";

    // Verificar cada tabla
    echo "<h3>Conteos directos:</h3>";
    $vp = Sdba::table('variante_p');
    $vp_list = $vp->get();
    echo "variante_p: " . count($vp_list) . "<br>";

    $pr = Sdba::table('productos');
    $pr_list = $pr->get();
    echo "productos: " . count($pr_list) . "<br>";

    $vr = Sdba::table('variantes');
    $vr_list = $vr->get();
    echo "variantes: " . count($vr_list) . "<br>";
}

// 2. Ver categorías existentes
echo "<h3>Categorías en DB:</h3>";
$cats = Sdba::table('categorias');
$cat_list = $cats->get();
echo "<table border='1' cellpadding='4'>";
echo "<tr><th>id_categoria</th><th>nom_cat</th></tr>";
foreach ($cat_list as $c) {
    echo "<tr><td>{$c['id_categoria']}</td><td>{$c['nom_cat']}</td></tr>";
}
echo "</table>";

// 3. Ver productos con id_categoria
echo "<h3>Primeros 20 productos (id_producto, nom_prod, id_categoria):</h3>";
$prods = Sdba::table('productos');
$prod_list = $prods->get();
echo "<table border='1' cellpadding='4'>";
echo "<tr><th>id_producto</th><th>nom_prod</th><th>id_categoria</th></tr>";
foreach (array_slice($prod_list, 0, 20) as $p) {
    echo "<tr><td>{$p['id_producto']}</td><td>{$p['nom_prod']}</td><td>{$p['id_categoria']}</td></tr>";
}
echo "</table>";

echo "<h3>Config actual (category_ids buscados):</h3><pre>";
foreach ($TABLET_TABS as $tk => $tc) {
    echo "$tk:\n";
    foreach ($tc['groups'] as $g) {
        echo "  {$g['label']}: cat_ids=[" . implode(',', $g['category_ids']) . "] prod_ids=[" . implode(',', $g['product_ids']) . "]\n";
    }
}
echo "</pre>";
