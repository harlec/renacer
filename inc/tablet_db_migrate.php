<?php
session_start();
if (empty($_SESSION['ingress']) || $_SESSION['type'] !== 'admin') die('No autorizado');

$conn = new mysqli('localhost', 'admin_renacer', 'ikm169uhn', 'admin_renacer');
if ($conn->connect_error) die('Error DB: ' . $conn->connect_error);
$conn->set_charset('utf8mb4');
$conn->query("SET NAMES utf8mb4");

echo "<style>body{font-family:sans-serif;padding:20px} .ok{color:green} .err{color:red} .info{color:#555}</style>";
echo "<h2>Migración Tablet Config</h2>";

// Verificar que las tablas existen
$tablas = ['tablet_tabs','tablet_groups','tablet_group_categories','tablet_group_products','tablet_group_product_variants'];
foreach ($tablas as $t) {
    $r = $conn->query("SHOW TABLES LIKE '$t'");
    if ($r->num_rows === 0) {
        echo "<p class='err'>✗ Tabla <b>$t</b> NO existe — ejecuta primero el SQL en phpMyAdmin</p>";
        exit;
    }
    echo "<p class='ok'>✓ Tabla <b>$t</b> existe</p>";
}

// Limpiar tablas para migración fresca
$conn->query("DELETE FROM tablet_group_product_variants");
$conn->query("DELETE FROM tablet_group_products");
$conn->query("DELETE FROM tablet_group_categories");
$conn->query("DELETE FROM tablet_groups");
$conn->query("DELETE FROM tablet_tabs");
$conn->query("ALTER TABLE tablet_tabs AUTO_INCREMENT = 1");
$conn->query("ALTER TABLE tablet_groups AUTO_INCREMENT = 1");
echo "<p class='info'>Tablas limpiadas para migración fresca</p>";

require_once __DIR__ . '/tablet_config.php';

$iconMap = [
    '🥚' => 'fas fa-egg',
    '🥩' => 'fas fa-utensils',
    '🍞' => 'fas fa-bread-slice',
    '☕' => 'fas fa-coffee',
    '🧀' => 'fas fa-cheese',
];

foreach ($TABLET_TABS as $tab_key => $tab_cfg) {
    $label  = $conn->real_escape_string($tab_cfg['label']);
    $raw    = $tab_cfg['icon'] ?? '';
    $icon   = $conn->real_escape_string($iconMap[$raw] ?? 'fas fa-box');
    $accent = $conn->real_escape_string($tab_cfg['color_accent']);
    $bg     = $conn->real_escape_string($tab_cfg['color_bg']);
    $bw     = $tab_cfg['by_weight'] ? 1 : 0;
    $key    = $conn->real_escape_string($tab_key);

    $ok = $conn->query("INSERT INTO tablet_tabs (tab_key,label,icon,color_accent,color_bg,by_weight,sort_order)
                        VALUES ('$key','$label','$icon','$accent','$bg',$bw,0)");
    if (!$ok) { echo "<p class='err'>✗ Error insertando pestaña $label: ".$conn->error."</p>"; continue; }
    $tab_id = $conn->insert_id;
    echo "<p class='ok'>✓ Pestaña <b>$label</b> (id=$tab_id)</p>";

    foreach ($tab_cfg['groups'] as $gi => $grp) {
        $glabel = $conn->real_escape_string($grp['label']);
        $ok2 = $conn->query("INSERT INTO tablet_groups (tab_id,label,sort_order) VALUES ($tab_id,'$glabel',$gi)");
        if (!$ok2) { echo "<p class='err'>  ✗ Error grupo $glabel: ".$conn->error."</p>"; continue; }
        $gid = $conn->insert_id;

        // Categorías
        foreach (($grp['category_ids'] ?? []) as $cid) {
            if ($cid > 0) $conn->query("INSERT IGNORE INTO tablet_group_categories (group_id,category_id) VALUES ($gid,$cid)");
        }

        $has_var = !empty($grp['variant_ids']);
        $var_ids = $grp['variant_ids'] ?? [];

        // Productos
        foreach (($grp['product_ids'] ?? []) as $pid) {
            if ($pid <= 0) continue;
            $all_v = $has_var ? 0 : 1;
            $conn->query("INSERT IGNORE INTO tablet_group_products (group_id,product_id,all_variants) VALUES ($gid,$pid,$all_v)");
            $gp_id = $conn->insert_id;
            if ($has_var && $gp_id > 0) {
                foreach ($var_ids as $vt) {
                    $conn->query("INSERT IGNORE INTO tablet_group_product_variants (group_product_id,variant_type_id) VALUES ($gp_id,$vt)");
                }
            }
        }
        echo "<p class='info' style='margin-left:20px'>→ Grupo <b>$glabel</b>: ".count($grp['category_ids'] ?? [])." cats, ".count($grp['product_ids'] ?? [])." prods</p>";
    }
}

$conn->close();
echo "<br><h3 class='ok'>✓ Migración completada</h3>";
echo "<p><a href='/tablet_config_admin.php'>→ Ir a Configuración Tablet</a></p>";
