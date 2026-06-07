<?php
/**
 * Ejecutar UNA VEZ para crear las tablas y migrar tablet_config.php a la BD.
 * Acceder desde el navegador: /inc/tablet_db_migrate.php
 */
session_start();
if (empty($_SESSION['ingress']) || $_SESSION['type'] !== 'admin') {
    die('No autorizado');
}

$conn = new mysqli('localhost', 'admin_renacer', 'ikm169uhn', 'admin_renacer');
$conn->set_charset('utf8');

$conn->query("
CREATE TABLE IF NOT EXISTS tablet_tabs (
  id           INT AUTO_INCREMENT PRIMARY KEY,
  tab_key      VARCHAR(50) NOT NULL UNIQUE,
  label        VARCHAR(100) NOT NULL,
  icon         VARCHAR(20)  DEFAULT '📦',
  color_accent VARCHAR(30)  DEFAULT '#f5a623',
  color_bg     VARCHAR(80)  DEFAULT 'rgba(245,166,35,.12)',
  by_weight    TINYINT(1)   DEFAULT 0,
  sort_order   INT          DEFAULT 0
)");

$conn->query("
CREATE TABLE IF NOT EXISTS tablet_groups (
  id         INT AUTO_INCREMENT PRIMARY KEY,
  tab_id     INT NOT NULL,
  label      VARCHAR(100) NOT NULL,
  sort_order INT DEFAULT 0,
  INDEX idx_tab (tab_id)
)");

$conn->query("
CREATE TABLE IF NOT EXISTS tablet_group_categories (
  id          INT AUTO_INCREMENT PRIMARY KEY,
  group_id    INT NOT NULL,
  category_id INT NOT NULL,
  UNIQUE KEY uc_gc (group_id, category_id),
  INDEX idx_grp (group_id)
)");

$conn->query("
CREATE TABLE IF NOT EXISTS tablet_group_products (
  id           INT AUTO_INCREMENT PRIMARY KEY,
  group_id     INT NOT NULL,
  product_id   INT NOT NULL,
  all_variants TINYINT(1) DEFAULT 1,
  UNIQUE KEY uc_gp (group_id, product_id),
  INDEX idx_grp (group_id)
)");

$conn->query("
CREATE TABLE IF NOT EXISTS tablet_group_product_variants (
  id               INT AUTO_INCREMENT PRIMARY KEY,
  group_product_id INT NOT NULL,
  variant_type_id  INT NOT NULL,
  UNIQUE KEY uc_gpv (group_product_id, variant_type_id),
  INDEX idx_gp (group_product_id)
)");

// ── Migrar desde tablet_config.php ─────────────────────────
require_once __DIR__ . '/tablet_config.php';

$migrated = 0;
foreach ($TABLET_TABS as $tab_key => $tab_cfg) {
    // Verificar si ya existe
    $r = $conn->query("SELECT id FROM tablet_tabs WHERE tab_key='".
        $conn->real_escape_string($tab_key)."' LIMIT 1");
    if ($r && $r->num_rows > 0) continue;

    $label    = $conn->real_escape_string($tab_cfg['label']);
    $icon     = $conn->real_escape_string($tab_cfg['icon']);
    $accent   = $conn->real_escape_string($tab_cfg['color_accent']);
    $bg       = $conn->real_escape_string($tab_cfg['color_bg']);
    $bw       = $tab_cfg['by_weight'] ? 1 : 0;
    $sort     = $migrated;

    $conn->query("INSERT INTO tablet_tabs (tab_key,label,icon,color_accent,color_bg,by_weight,sort_order)
                  VALUES ('$tab_key','$label','$icon','$accent','$bg',$bw,$sort)");
    $tab_id = $conn->insert_id;

    foreach ($tab_cfg['groups'] as $gi => $grp) {
        $glabel = $conn->real_escape_string($grp['label']);
        $conn->query("INSERT INTO tablet_groups (tab_id,label,sort_order) VALUES ($tab_id,'$glabel',$gi)");
        $group_id = $conn->insert_id;

        // Categorías
        foreach (($grp['category_ids'] ?? []) as $cat_id) {
            if ($cat_id > 0)
                $conn->query("INSERT IGNORE INTO tablet_group_categories (group_id,category_id) VALUES ($group_id,$cat_id)");
        }

        $has_var_filter = !empty($grp['variant_ids']);
        $var_ids = $grp['variant_ids'] ?? [];

        // Productos
        foreach (($grp['product_ids'] ?? []) as $prod_id) {
            if ($prod_id <= 0) continue;
            // Si hay variant_ids → all_variants=0, guardamos las variantes específicas
            $all_v = $has_var_filter ? 0 : 1;
            $conn->query("INSERT IGNORE INTO tablet_group_products (group_id,product_id,all_variants)
                          VALUES ($group_id,$prod_id,$all_v)");
            $gp_id = $conn->insert_id;

            if ($has_var_filter && $gp_id > 0) {
                foreach ($var_ids as $vt_id) {
                    $conn->query("INSERT IGNORE INTO tablet_group_product_variants (group_product_id,variant_type_id)
                                  VALUES ($gp_id,$vt_id)");
                }
            }
        }
    }
    $migrated++;
}

$conn->close();
echo "<h2 style='font-family:sans-serif;color:green'>✓ Migración completada ($migrated pestañas migradas)</h2>
<p style='font-family:sans-serif'>Puedes borrar este archivo una vez ejecutado.<br>
<a href='/tablet_config_admin.php'>→ Ir a Configuración Tablet</a></p>";
