<?php
ob_start();
ini_set('display_errors', '0');
error_reporting(0);
session_start();
if (empty($_SESSION['ingress']) || $_SESSION['type'] !== 'admin') {
    ob_clean(); http_response_code(403); echo json_encode(['ok'=>false,'msg'=>'No autorizado']); exit;
}
header('Content-Type: application/json');

$conn = new mysqli('localhost', 'admin_renacer', 'ikm169uhn', 'admin_renacer');
if ($conn->connect_error) {
    ob_clean(); echo json_encode(['ok'=>false,'msg'=>'DB: '.$conn->connect_error]); exit;
}
$conn->set_charset('utf8');

$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch ($action) {

// ── Cargar config completa ─────────────────────────────────
case 'get_config':
    $tabs = [];
    $rt = $conn->query("SELECT * FROM tablet_tabs ORDER BY sort_order,id");
    if (!$rt) { ob_clean(); echo json_encode(['ok'=>false,'msg'=>'tabla tablet_tabs: '.$conn->error]); exit; }
    while ($tab = $rt->fetch_assoc()) {
        $tab_id = (int)$tab['id'];
        $groups = [];
        $rg = $conn->query("SELECT * FROM tablet_groups WHERE tab_id=$tab_id ORDER BY sort_order,id");
        while ($g = $rg->fetch_assoc()) {
            $gid = (int)$g['id'];
            $cats = [];
            $rc = $conn->query("SELECT gc.category_id, c.nom_cat FROM tablet_group_categories gc
                LEFT JOIN categorias c ON c.id_categoria=gc.category_id WHERE gc.group_id=$gid");
            if ($rc) while ($c = $rc->fetch_assoc()) $cats[] = $c;
            $prods = [];
            $rp = $conn->query("SELECT gp.id as gp_id, gp.product_id, gp.all_variants, p.nom_prod
                FROM tablet_group_products gp
                LEFT JOIN productos p ON p.id_producto=gp.product_id WHERE gp.group_id=$gid");
            if ($rp) while ($p = $rp->fetch_assoc()) {
                $variants = [];
                if (!$p['all_variants']) {
                    $rv = $conn->query("SELECT gpv.variant_type_id, v.variante FROM tablet_group_product_variants gpv
                        LEFT JOIN variantes v ON v.id_variante=gpv.variant_type_id WHERE gpv.group_product_id=".(int)$p['gp_id']);
                    if ($rv) while ($v = $rv->fetch_assoc()) $variants[] = $v;
                }
                $p['variants'] = $variants;
                $prods[] = $p;
            }
            $g['categories'] = $cats;
            $g['products']   = $prods;
            $groups[] = $g;
        }
        $tab['groups'] = $groups;
        $tabs[] = $tab;
    }
    ob_clean();
    echo json_encode(['ok'=>true,'tabs'=>$tabs]);
    break;

// ── Buscar productos ───────────────────────────────────────
case 'search_products':
    $q = '%'.$conn->real_escape_string($_GET['q'] ?? '').'%';
    $r = $conn->query("SELECT id_producto, nom_prod FROM productos WHERE nom_prod LIKE '$q' ORDER BY nom_prod LIMIT 20");
    $rows = [];
    while ($row = $r->fetch_assoc()) $rows[] = $row;
    echo json_encode(['ok'=>true,'products'=>$rows]);
    break;

// ── Variantes de un producto ───────────────────────────────
case 'get_product_variants':
    $pid = (int)($_GET['product_id'] ?? 0);
    $r = $conn->query("SELECT DISTINCT v.id_variante, v.variante, vp.cantidad_vp
        FROM variante_p vp
        JOIN variantes v ON v.id_variante=vp.variante_vp
        WHERE vp.producto_vp=$pid ORDER BY vp.cantidad_vp");
    $rows = [];
    if ($r) while ($row = $r->fetch_assoc()) $rows[] = $row;
    ob_clean();
    echo json_encode(['ok'=>true,'variants'=>$rows]);
    break;

// ── Listar categorías ──────────────────────────────────────
case 'get_categories':
    $r = $conn->query("SELECT id_categoria, nom_cat FROM categorias ORDER BY nom_cat");
    $rows = [];
    while ($row = $r->fetch_assoc()) $rows[] = $row;
    echo json_encode(['ok'=>true,'categories'=>$rows]);
    break;

// ── Agregar grupo ──────────────────────────────────────────
case 'add_group':
    $tab_id = (int)($_POST['tab_id'] ?? 0);
    $label  = $conn->real_escape_string($_POST['label'] ?? '');
    $r = $conn->query("SELECT COALESCE(MAX(sort_order),0)+1 AS n FROM tablet_groups WHERE tab_id=$tab_id");
    $sort = $r->fetch_assoc()['n'];
    $conn->query("INSERT INTO tablet_groups (tab_id,label,sort_order) VALUES ($tab_id,'$label',$sort)");
    echo json_encode(['ok'=>true,'id'=>$conn->insert_id]);
    break;

// ── Renombrar grupo ────────────────────────────────────────
case 'rename_group':
    $gid   = (int)($_POST['group_id'] ?? 0);
    $label = $conn->real_escape_string($_POST['label'] ?? '');
    $conn->query("UPDATE tablet_groups SET label='$label' WHERE id=$gid");
    echo json_encode(['ok'=>true]);
    break;

// ── Eliminar grupo ─────────────────────────────────────────
case 'delete_group':
    $gid = (int)($_POST['group_id'] ?? 0);
    // Borrar variantes de productos del grupo
    $conn->query("DELETE gpv FROM tablet_group_product_variants gpv
                  JOIN tablet_group_products gp ON gp.id=gpv.group_product_id WHERE gp.group_id=$gid");
    $conn->query("DELETE FROM tablet_group_products    WHERE group_id=$gid");
    $conn->query("DELETE FROM tablet_group_categories  WHERE group_id=$gid");
    $conn->query("DELETE FROM tablet_groups            WHERE id=$gid");
    echo json_encode(['ok'=>true]);
    break;

// ── Agregar categoría al grupo ─────────────────────────────
case 'add_category':
    $gid = (int)($_POST['group_id'] ?? 0);
    $cid = (int)($_POST['category_id'] ?? 0);
    $conn->query("INSERT IGNORE INTO tablet_group_categories (group_id,category_id) VALUES ($gid,$cid)");
    echo json_encode(['ok'=>true]);
    break;

// ── Quitar categoría del grupo ─────────────────────────────
case 'remove_category':
    $gid = (int)($_POST['group_id'] ?? 0);
    $cid = (int)($_POST['category_id'] ?? 0);
    $conn->query("DELETE FROM tablet_group_categories WHERE group_id=$gid AND category_id=$cid");
    echo json_encode(['ok'=>true]);
    break;

// ── Agregar producto al grupo ──────────────────────────────
case 'add_product':
    $gid     = (int)($_POST['group_id']   ?? 0);
    $pid     = (int)($_POST['product_id'] ?? 0);
    $all_v   = (int)($_POST['all_variants'] ?? 1);
    $var_ids = json_decode($_POST['variant_ids'] ?? '[]', true);
    $conn->query("INSERT IGNORE INTO tablet_group_products (group_id,product_id,all_variants) VALUES ($gid,$pid,$all_v)");
    $gp_id = $conn->insert_id;
    if ($gp_id > 0 && !$all_v) {
        foreach ($var_ids as $vt_id) {
            $vt_id = (int)$vt_id;
            $conn->query("INSERT IGNORE INTO tablet_group_product_variants (group_product_id,variant_type_id) VALUES ($gp_id,$vt_id)");
        }
    }
    echo json_encode(['ok'=>true,'gp_id'=>$gp_id]);
    break;

// ── Quitar producto del grupo ──────────────────────────────
case 'remove_product':
    $gp_id = (int)($_POST['gp_id'] ?? 0);
    $conn->query("DELETE FROM tablet_group_product_variants WHERE group_product_id=$gp_id");
    $conn->query("DELETE FROM tablet_group_products WHERE id=$gp_id");
    echo json_encode(['ok'=>true]);
    break;

default:
    ob_clean();
    echo json_encode(['ok'=>false,'msg'=>'Accion no reconocida: '.$action]);
}
$conn->close();
