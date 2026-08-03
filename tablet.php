<?php
// ── Sesión & control ────────────────────────────────────────
session_start();
if (empty($_SESSION['ingress'])) {
    header("Location: index.html");
    exit;
}

// ── Configuración del POS desde base de datos ───────────────
require_once 'inc/sdba/sdba.php';
require_once 'inc/tablet_config.php'; // solo para constantes TABLET_STORE_*

$conn = new mysqli('localhost', 'admin_renacer', 'ikm169uhn', 'admin_renacer');
$conn->set_charset('utf8');

// Cargar tabs y grupos desde la BD
$db_tabs = [];
$rt = $conn->query("SELECT * FROM tablet_tabs ORDER BY sort_order,id");
while ($tab = $rt->fetch_assoc()) {
    $tid = $tab['id'];
    $groups_db = [];
    $rg = $conn->query("SELECT * FROM tablet_groups WHERE tab_id=$tid ORDER BY sort_order,id");
    while ($g = $rg->fetch_assoc()) {
        $gid = $g['id'];
        $cats = []; $prods_all = []; $prods_specific = [];
        $rc = $conn->query("SELECT category_id FROM tablet_group_categories WHERE group_id=$gid");
        while ($c = $rc->fetch_assoc()) $cats[] = (int)$c['category_id'];
        $rp = $conn->query("SELECT id, product_id, all_variants FROM tablet_group_products WHERE group_id=$gid");
        while ($p = $rp->fetch_assoc()) {
            if ($p['all_variants']) {
                $prods_all[] = (int)$p['product_id'];
            } else {
                $vts = [];
                $rv = $conn->query("SELECT variant_type_id FROM tablet_group_product_variants WHERE group_product_id=".(int)$p['id']);
                while ($v = $rv->fetch_assoc()) $vts[] = (int)$v['variant_type_id'];
                $prods_specific[(int)$p['product_id']] = $vts;
            }
        }
        $groups_db[] = ['id'=>$gid,'label'=>$g['label'],'cats'=>$cats,'prods_all'=>$prods_all,'prods_specific'=>$prods_specific];
    }
    $db_tabs[] = ['tab'=>$tab,'groups'=>$groups_db];
}
$conn->close();

// Obtener todas las variantes-producto con joins
$vp_raw = Sdba::table('variante_p');
$vp_raw->left_join('variante_vp', 'variantes', 'id_variante');
$vp_raw->left_join('producto_vp', 'productos',  'id_producto');
$all_vp = $vp_raw->get();

// Construir tabs_data para JS
$tabs_data = [];
foreach ($db_tabs as $ti => $tab_entry) {
    $tab = $tab_entry['tab'];
    $tab_key = 'tab_' . $tab['id'];
    $groups = [];
    foreach ($tab_entry['groups'] as $gi => $g) {
        $groups[$gi] = ['name' => $g['label'], 'items' => []];
    }
    $tabs_data[$tab_key] = [
        'config' => [
            'label'        => $tab['label'],
            'icon'         => $tab['icon'],
            'color_accent' => $tab['color_accent'],
            'color_bg'     => $tab['color_bg'],
            'by_weight'    => (bool)$tab['by_weight'],
        ],
        'groups' => $groups,
    ];
}

foreach ($all_vp as $row) {
    if (empty($row['nom_prod'])) continue;
    $cat_id  = (int)($row['categoria'] ?? $row['id_categoria'] ?? 0);
    $prod_id = (int)($row['id_producto'] ?? 0);
    $var_id  = (int)($row['id_variante'] ?? $row['variante_vp'] ?? 0);

    foreach ($db_tabs as $tab_entry) {
        $tab     = $tab_entry['tab'];
        $tab_key = 'tab_' . $tab['id'];
        $by_weight = (bool)$tab['by_weight'];

        foreach ($tab_entry['groups'] as $gi => $g) {
            $match = false;

            // Categoría
            if (in_array($cat_id, $g['cats'])) { $match = true; }

            // Producto sin filtro de variante (todas sus variantes)
            if (!$match && in_array($prod_id, $g['prods_all'])) { $match = true; }

            // Producto con variantes específicas
            if (!$match && isset($g['prods_specific'][$prod_id])) {
                if (in_array($var_id, $g['prods_specific'][$prod_id])) { $match = true; }
            }

            if ($match) {
                $tabs_data[$tab_key]['groups'][$gi]['items'][] = [
                    'id'        => (int)$row['id_vp'],
                    'prod_id'   => $prod_id,
                    'prod_name' => $row['nom_prod'],
                    'variant'   => $row['variante'],
                    'qty_per'   => (float)$row['cantidad_vp'],
                    'price'     => (float)$row['precio_vp'],
                    'by_weight' => $by_weight,
                ];
                break 2;
            }
        }
    }
}

// Convertir a JSON para JS
$js_data = json_encode($tabs_data, JSON_UNESCAPED_UNICODE);

$store_name = TABLET_STORE_NAME;
$user_name  = htmlspecialchars($_SESSION['nombres'] ?? $_SESSION['usuario']);
$user_id    = (int)($_SESSION['id_usr'] ?? 0);
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
<meta name="mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="theme-color" content="#111111">
<link rel="manifest" href="/manifest.json">
<title>POS Tablet – <?= $store_name ?></title>
<style>
/* ── Fuentes ──────────────────────────────────────────────── */
@import url('https://fonts.googleapis.com/css2?family=Barlow+Condensed:wght@400;600;700;900&family=Barlow:wght@400;500;600&display=swap');
@import url('https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css');

/* ── Variables ────────────────────────────────────────────── */
:root {
  --bg:          #1a1a1a;
  --surface:     #242424;
  --surface2:    #2e2e2e;
  --border:      #3a3a3a;
  --green:       #27ae60;
  --red:         #e74c3c;
  --text:        #f0f0f0;
  --muted:       #888;
  --r:           10px;
  --accent:      #f5a623;   /* sobreescrito por JS */
}

/* ── Reset ────────────────────────────────────────────────── */
*{box-sizing:border-box;margin:0;padding:0;-webkit-tap-highlight-color:transparent}

/* ── Layout global ────────────────────────────────────────── */
html,body{height:100%;overflow:hidden}
body{
  font-family:'Barlow',sans-serif;
  background:var(--bg);
  color:var(--text);
  display:flex;
  flex-direction:column;
}

/* ── Header ───────────────────────────────────────────────── */
.hdr{
  background:#111;
  border-bottom:1px solid var(--border);
  padding:6px 14px;
  display:flex;
  align-items:center;
  justify-content:space-between;
  flex-shrink:0;
  gap:10px;
}
.hdr-brand{
  font-family:'Barlow Condensed',sans-serif;
  font-size:17px;
  font-weight:900;
  letter-spacing:2px;
  text-transform:uppercase;
  white-space:nowrap;
}
.hdr-brand span{color:var(--accent)}
.hdr-mid{
  display:flex;
  gap:6px;
  align-items:center;
  flex-wrap:wrap;
}
#clock{
  font-family:'Barlow Condensed',sans-serif;
  font-size:18px;
  font-weight:700;
}
#today{font-size:11px;color:var(--muted)}
.hdr-user{font-size:12px;color:var(--muted);text-align:right}
.hdr-user strong{color:var(--text)}
.btn-salir{
  background:none;
  border:1px solid #444;
  border-radius:6px;
  padding:3px 8px;
  color:var(--muted);
  font-size:11px;
  cursor:pointer;
}
.btn-salir:hover{color:var(--red);border-color:var(--red)}

/* ── Tabs ─────────────────────────────────────────────────── */
.tabs-row{
  display:flex;
  padding:8px 14px 0;
  gap:4px;
  flex-shrink:0;
}
.tab-btn{
  flex:1;
  padding:10px 8px;
  border:none;
  cursor:pointer;
  font-family:'Barlow Condensed',sans-serif;
  font-size:17px;
  font-weight:700;
  letter-spacing:1px;
  text-transform:uppercase;
  border-radius:var(--r) var(--r) 0 0;
  background:var(--surface2);
  color:var(--muted);
  border-bottom:3px solid transparent;
  transition:all .2s;
}
.tab-btn.active{
  background:var(--surface);
  color:var(--accent);
  border-bottom-color:var(--accent);
}
.tab-icon{margin-right:6px;font-size:18px}

/* ── Main area ────────────────────────────────────────────── */
.main{
  display:flex;
  flex:1;
  overflow:hidden;
  background:var(--surface);
  margin:0 14px;
  border-radius:0 0 var(--r) var(--r);
}

/* ── Left: products ───────────────────────────────────────── */
.left-panel{
  flex:1.4;
  display:flex;
  flex-direction:column;
  border-right:1px solid var(--border);
  overflow:hidden;
}

/* Category selector tabs (horizontal strip) */
.cat-strip{
  display:flex;
  gap:8px;
  padding:12px 14px 10px;
  flex-shrink:0;
  overflow-x:auto;
}
.cat-strip::-webkit-scrollbar{height:3px}
.cat-strip::-webkit-scrollbar-thumb{background:var(--border)}
.cat-pill{
  padding:8px 20px;
  border:2px solid var(--border);
  border-radius:24px;
  font-family:'Barlow Condensed',sans-serif;
  font-size:15px;
  font-weight:700;
  letter-spacing:1px;
  text-transform:uppercase;
  background:var(--surface2);
  color:var(--muted);
  cursor:pointer;
  white-space:nowrap;
  transition:all .15s;
}
.cat-pill.active{
  border-color:var(--accent);
  color:var(--accent);
  background:var(--accent-bg,rgba(245,166,35,.12));
}

/* Product grid */
.prod-grid-wrap{
  flex:1;
  overflow-y:auto;
  padding:4px 14px 14px;
}
.prod-grid-wrap::-webkit-scrollbar{width:4px}
.prod-grid-wrap::-webkit-scrollbar-thumb{background:var(--border);border-radius:4px}

.prod-grid{
  display:grid;
  grid-template-columns:repeat(3,1fr);
  gap:10px;
}
@media(max-width:600px){
  .prod-grid{grid-template-columns:repeat(2,1fr)}
}

.prod-btn{
  background:var(--surface2);
  border:2px solid var(--border);
  border-radius:12px;
  padding:16px 10px 14px;
  cursor:pointer;
  text-align:center;
  transition:border-color .15s, background .15s, transform .1s;
  display:flex;
  flex-direction:column;
  align-items:center;
  gap:0;
  min-height:90px;
  justify-content:space-between;
  position:relative;
}
.prod-btn:active{transform:scale(.95)}
.prod-btn.selected{
  border-color:var(--accent);
  border-width:3px;
  background:var(--accent-bg,rgba(245,166,35,.12));
}
.prod-btn.selected .pb-price{
  color:#fff;
  background:var(--accent);
  border-radius:6px;
  padding:2px 10px;
}
.pb-name{
  font-family:'Barlow Condensed',sans-serif;
  font-size:16px;
  font-weight:700;
  color:var(--text);
  line-height:1.2;
  text-transform:uppercase;
  letter-spacing:.5px;
}
.pb-variant{
  font-size:11px;
  color:var(--muted);
  margin:3px 0 8px;
  line-height:1.3;
}
.pb-price{
  font-family:'Barlow Condensed',sans-serif;
  font-size:22px;
  font-weight:900;
  color:var(--accent);
  line-height:1;
  border-radius:6px;
  padding:2px 8px;
  transition:all .15s;
}
.pb-price small{font-size:12px;font-weight:400;color:inherit;opacity:.8}

.no-products{
  grid-column:1/-1;
  text-align:center;
  padding:30px;
  color:var(--muted);
  font-size:14px;
}

/* ── Right: numpad + cart ─────────────────────────────────── */
.right-panel{
  flex:1;
  display:flex;
  flex-direction:column;
  padding:12px 12px 0 12px;
  gap:8px;
  overflow:hidden;
}

/* Numpad */
.numpad-area{
  background:var(--surface2);
  border-radius:var(--r);
  border:1px solid var(--border);
  padding:10px;
  flex-shrink:0;
}
.nd-display{
  background:#111;
  border-radius:8px;
  padding:8px 12px;
  margin-bottom:8px;
  display:flex;
  align-items:center;
  justify-content:space-between;
}
.nd-left{display:flex;flex-direction:column;gap:2px}
.nd-lbl{font-size:11px;color:var(--muted);text-transform:uppercase;letter-spacing:1px}
.nd-val-row{display:flex;align-items:baseline;gap:4px}
.nd-val{
  font-family:'Barlow Condensed',sans-serif;
  font-size:30px;
  font-weight:900;
  color:#fff;
}
.nd-unit{font-size:12px;color:var(--muted)}
.nd-sub{
  font-family:'Barlow Condensed',sans-serif;
  font-size:20px;
  font-weight:700;
  color:var(--accent);
  text-align:right;
}
.nd-sublbl{font-size:10px;color:var(--muted);text-align:right}

.numpad-grid{
  display:grid;
  grid-template-columns:repeat(3,1fr);
  gap:6px;
}
.nk{
  background:var(--surface);
  border:1px solid var(--border);
  border-radius:8px;
  padding:14px 4px;
  font-family:'Barlow Condensed',sans-serif;
  font-size:22px;
  font-weight:700;
  color:var(--text);
  cursor:pointer;
  text-align:center;
  transition:all .1s;
  user-select:none;
}
.nk:active{transform:scale(.92);background:#333}
.nk-del{color:var(--red);font-size:18px}
.nk-dot{color:var(--muted)}
.nk-add{
  background:var(--green);
  border-color:var(--green);
  color:#fff;
  font-size:16px;
  grid-column:1/-1;
}
.nk-add:active{background:#1e8449}
.nk-add:disabled,.nk-add[disabled]{background:#444;border-color:#444;color:#666;cursor:not-allowed}

/* Cart */
.cart-area{
  flex:1;
  display:flex;
  flex-direction:column;
  background:var(--surface2);
  border-radius:var(--r);
  border:1px solid var(--border);
  overflow:hidden;
}
.cart-hdr{
  padding:8px 12px;
  border-bottom:1px solid var(--border);
  display:flex;
  align-items:center;
  justify-content:space-between;
  flex-shrink:0;
}
.cart-hdr-lbl{
  font-family:'Barlow Condensed',sans-serif;
  font-size:14px;
  font-weight:700;
  letter-spacing:2px;
  text-transform:uppercase;
  color:var(--muted);
}
.cart-clear{
  background:none;
  border:1px solid #555;
  border-radius:5px;
  padding:3px 8px;
  font-size:11px;
  color:var(--red);
  cursor:pointer;
}
.cart-list{
  flex:1;
  overflow-y:auto;
  padding:6px;
}
.cart-list::-webkit-scrollbar{width:3px}
.cart-list::-webkit-scrollbar-thumb{background:var(--border)}
.cart-empty{
  text-align:center;
  padding:20px;
  color:var(--muted);
  font-size:13px;
}
.ci{
  display:flex;
  align-items:center;
  padding:6px 4px;
  border-bottom:1px solid var(--border);
  gap:4px;
}
.ci:last-child{border:none}
.ci-name{flex:1;font-size:12px;color:var(--text);line-height:1.3}
.ci-price{font-family:'Barlow Condensed',sans-serif;font-size:12px;color:var(--muted);width:48px;text-align:center}
.ci-total{font-family:'Barlow Condensed',sans-serif;font-size:15px;font-weight:700;color:var(--text);width:56px;text-align:right}
.ci-del{background:none;border:none;color:var(--red);font-size:16px;cursor:pointer;padding:0 4px;line-height:1}

/* Cart footer */
.cart-footer{
  border-top:2px solid var(--border);
  padding:8px 10px;
  flex-shrink:0;
}
.total-row{
  display:flex;
  justify-content:space-between;
  align-items:center;
  margin-bottom:8px;
}
.total-lbl{
  font-family:'Barlow Condensed',sans-serif;
  font-size:13px;
  letter-spacing:2px;
  text-transform:uppercase;
  color:var(--muted);
}
.total-amount{
  font-family:'Barlow Condensed',sans-serif;
  font-size:30px;
  font-weight:900;
  color:#fff;
}
.total-amount small{font-size:14px;color:var(--muted);margin-right:3px}
.pay-method-row{display:none;gap:6px;margin-bottom:4px}
.pay-method-row.show{display:flex}
.pay-input-box{
  flex:1;
  display:flex;
  align-items:center;
  gap:4px;
  background:var(--surface);
  border:2px solid var(--border);
  border-radius:var(--r);
  padding:4px 6px;
}
.pay-input-box:focus-within{border-color:var(--accent)}
.pay-quick-btn{
  background:none;
  border:none;
  font-size:20px;
  line-height:1;
  padding:4px;
  cursor:pointer;
  flex-shrink:0;
}
.pay-input{
  flex:1;
  min-width:0;
  background:none;
  border:none;
  color:var(--text);
  font-family:'Barlow Condensed',sans-serif;
  font-size:16px;
  font-weight:700;
  text-align:right;
  padding:4px 2px;
}
.pay-input:focus{outline:none}
.pay-status{
  display:none;
  font-size:12px;
  font-weight:700;
  text-align:right;
  margin-bottom:8px;
  letter-spacing:.5px;
}
.pay-status.falta{color:var(--red)}
.pay-status.ok{color:var(--green)}
.venta-guardada{
  display:none;
  align-items:center;
  justify-content:space-between;
  gap:8px;
  background:rgba(39,174,96,.15);
  border:2px solid var(--green);
  border-radius:var(--r);
  padding:8px 10px;
  margin-bottom:8px;
  font-size:13px;
  font-weight:700;
  color:var(--green);
}
.venta-guardada.show{display:flex}
.venta-guardada button{
  background:var(--green);
  border:none;
  border-radius:8px;
  padding:8px 12px;
  color:#fff;
  font-family:'Barlow Condensed',sans-serif;
  font-size:14px;
  font-weight:700;
  cursor:pointer;
  flex-shrink:0;
}
.action-row{display:flex;gap:6px}
.btn-save{
  flex:1;
  padding:13px 6px;
  background:#7d3c98;
  border:none;
  border-radius:var(--r);
  font-family:'Barlow Condensed',sans-serif;
  font-size:18px;
  font-weight:900;
  letter-spacing:1px;
  text-transform:uppercase;
  color:#fff;
  cursor:pointer;
  transition:all .15s;
}
.btn-save:active{transform:scale(.98);background:#6c3483}
.btn-save:disabled{background:#444;color:#666;cursor:not-allowed}
.btn-pay{
  flex:1;
  padding:13px 6px;
  background:var(--green);
  border:none;
  border-radius:var(--r);
  font-family:'Barlow Condensed',sans-serif;
  font-size:18px;
  font-weight:900;
  letter-spacing:1px;
  text-transform:uppercase;
  color:#fff;
  cursor:pointer;
  transition:all .15s;
}
.btn-pay:active{transform:scale(.98);background:#1e8449}
.btn-pay:disabled{background:#444;color:#666;cursor:not-allowed}
.btn-print-cart{
  padding:13px 12px;
  background:#1a6090;
  border:none;
  border-radius:var(--r);
  font-family:'Barlow Condensed',sans-serif;
  font-size:18px;
  font-weight:900;
  color:#fff;
  cursor:pointer;
  transition:all .15s;
}
.btn-print-cart:active{background:#145075}
.btn-print-cart:disabled{background:#444;color:#666;cursor:not-allowed}

/* ── Modal confirm / ticket ───────────────────────────────── */
.modal-ov{
  display:none;
  position:fixed;
  inset:0;
  background:rgba(0,0,0,.78);
  z-index:200;
  align-items:center;
  justify-content:center;
}
.modal-ov.show{display:flex}
.ticket-card{
  background:#fff;
  color:#111;
  width:330px;
  max-height:90vh;
  overflow-y:auto;
  border-radius:8px;
  padding:22px 18px;
  font-family:'Courier New',monospace;
  position:relative;
}
.tc-hdr{text-align:center;margin-bottom:14px}
.tc-hdr h2{font-size:17px;font-weight:900;text-transform:uppercase;letter-spacing:2px}
.tc-hdr p{font-size:11px;color:#555;margin-top:3px}
.tc-div{border:none;border-top:1px dashed #bbb;margin:10px 0}
.tc-tbl{font-size:12px;width:100%;border-collapse:collapse}
.tc-tbl td{padding:3px 2px}
.tc-tbl .tc-n{width:52%}
.tc-tbl .tc-q{width:18%;text-align:center}
.tc-tbl .tc-p{width:30%;text-align:right}
.tc-tot{display:flex;justify-content:space-between;font-size:15px;font-weight:900;margin-top:8px}
.tc-foot{text-align:center;font-size:11px;color:#777;margin-top:12px}
.tc-actions{display:flex;gap:8px;margin-top:14px}
.tc-actions button{
  flex:1;padding:11px;border:none;border-radius:8px;
  font-family:'Barlow Condensed',sans-serif;
  font-size:16px;font-weight:700;cursor:pointer;letter-spacing:1px;
}
.btn-imprimir{background:#111;color:#fff}
.btn-nueva{background:#27ae60;color:#fff}
.btn-cerrar{background:#eee;color:#333}

/* ── Toast ────────────────────────────────────────────────── */
.toast{
  position:fixed;
  bottom:16px;
  left:50%;
  transform:translateX(-50%) translateY(60px);
  background:var(--green);
  color:#fff;
  padding:10px 22px;
  border-radius:30px;
  font-family:'Barlow Condensed',sans-serif;
  font-size:16px;
  font-weight:700;
  letter-spacing:1px;
  transition:transform .3s;
  z-index:300;
  pointer-events:none;
}
.toast.err{background:var(--red)}
.toast.show{transform:translateX(-50%) translateY(0)}

/* ── Print styles ─────────────────────────────────────────── */
@media print{
  body>*:not(#print-area){ display:none !important }
  #print-area{
    display:block !important;
    position:fixed;
    inset:0;
    background:#fff;
    color:#000;
    font-family:Helvetica,Sans-Serif;
    font-size:9px;
    padding:0.4cm;
  }
  #print-area img.logo-ticket{ width:230px; display:block; margin:0 auto; }
  #print-area .pa-vers{ font-size:8px; text-align:center; margin:2px 0; }
  #print-area .pa-vers2{ font-size:8px; text-align:right; margin:-8px 0 4px; }
  #print-area h5{ text-align:center; font-size:11px; font-weight:bold; margin:4px 0; }
  #print-area h6{ font-size:9px; margin:2px 0; }
  .pa-div{ border:none; border-top:1px solid #000; margin:6px 0; }
  .pa-tbl{ width:100%; border-collapse:collapse; font-size:9px; }
  .pa-tbl thead th{ font-size:9px; font-weight:bold; }
  .pa-tbl tbody td{ font-size:9px; }
  .pa-foot{ text-align:center; font-size:9px; color:#444; margin-top:8px; }
}
</style>
</head>
<body>

<!-- ── PRINT AREA (solo visible al imprimir) ─────────────── -->
<div id="print-area" style="display:none"></div>

<!-- ── HEADER ────────────────────────────────────────────── -->
<div class="hdr">
  <div class="hdr-brand"><?= htmlspecialchars($store_name) ?></div>
  <div class="hdr-mid">
    <div id="clock">--:--</div>
    <div id="today"></div>
  </div>
  <div class="hdr-user">
    Hola, <strong><?= $user_name ?></strong><br>
    <button class="btn-salir" onclick="if(confirm('¿Cerrar sesión?'))location.href='salir.php'">Salir</button>
  </div>
</div>

<!-- ── TABS ───────────────────────────────────────────────── -->
<div class="tabs-row" id="tabs-row"></div>

<!-- ── MAIN ───────────────────────────────────────────────── -->
<div class="main">

  <!-- Productos -->
  <div class="left-panel">
    <div class="cat-strip" id="cat-strip"></div>
    <div class="prod-grid-wrap">
      <div class="prod-grid" id="prod-grid"></div>
    </div>
  </div>

  <!-- Numpad + Carrito -->
  <div class="right-panel">

    <!-- Numpad -->
    <div class="numpad-area">
      <div class="nd-display">
        <div class="nd-left">
          <div class="nd-lbl" id="nd-lbl">Cantidad</div>
          <div class="nd-val-row">
            <div class="nd-val" id="nd-val">0</div>
            <div class="nd-unit" id="nd-unit">unid.</div>
          </div>
        </div>
        <div>
          <div class="nd-sublbl">Subtotal</div>
          <div class="nd-sub" id="nd-sub">S/0.00</div>
        </div>
      </div>
      <div class="numpad-grid">
        <button class="nk" onclick="nk('7')">7</button>
        <button class="nk" onclick="nk('8')">8</button>
        <button class="nk" onclick="nk('9')">9</button>
        <button class="nk" onclick="nk('4')">4</button>
        <button class="nk" onclick="nk('5')">5</button>
        <button class="nk" onclick="nk('6')">6</button>
        <button class="nk" onclick="nk('1')">1</button>
        <button class="nk" onclick="nk('2')">2</button>
        <button class="nk" onclick="nk('3')">3</button>
        <button class="nk nk-del" onclick="nk('del')">⌫</button>
        <button class="nk" onclick="nk('0')">0</button>
        <button class="nk nk-dot" onclick="nk('.')">.</button>
        <button class="nk nk-add" id="btn-add" onclick="addToCart()">➕ Agregar</button>
      </div>
    </div>

    <!-- Carrito -->
    <div class="cart-area">
      <div class="cart-hdr">
        <span class="cart-hdr-lbl">🛒 Pedido</span>
        <button class="cart-clear" onclick="clearCart()">Limpiar</button>
      </div>
      <div class="cart-list" id="cart-list">
        <div class="cart-empty">Sin productos aún</div>
      </div>
      <div class="cart-footer">
        <div class="total-row">
          <span class="total-lbl">Total</span>
          <span class="total-amount"><small>S/</small><span id="total-val">0.00</span></span>
        </div>
        <div class="pay-method-row" id="pay-method-row">
          <div class="pay-input-box">
            <button type="button" class="pay-quick-btn" onclick="pagoSoloUnMetodo('efectivo')" title="Todo en efectivo">💵</button>
            <input type="number" inputmode="decimal" class="pay-input" id="pm-monto-efectivo" placeholder="0.00" step="0.01" min="0" oninput="actualizarPagoUI()">
          </div>
          <div class="pay-input-box">
            <button type="button" class="pay-quick-btn" onclick="pagoSoloUnMetodo('tarjeta')" title="Todo con tarjeta">💳</button>
            <input type="number" inputmode="decimal" class="pay-input" id="pm-monto-tarjeta" placeholder="0.00" step="0.01" min="0" oninput="actualizarPagoUI()">
          </div>
        </div>
        <div class="pay-status" id="pay-status"></div>
        <div class="venta-guardada" id="venta-guardada">
          <span id="vg-texto"></span>
          <button type="button" onclick="reimprimirUltimoTicket()">🖨️ Reimprimir</button>
        </div>
        <div class="action-row">
          <button class="btn-save" id="btn-save" onclick="saveAndPrint()" disabled>💾 Guardar</button>
          <!--<button class="btn-pay" id="btn-pay" onclick="openTicket()" disabled>🧾 Cobrar</button>
          <button class="btn-print-cart" id="btn-print" onclick="printTicket()" disabled title="Imprimir ticket">🖨️</button>-->
        </div>
      </div>
    </div>

  </div><!-- /right-panel -->
</div><!-- /main -->

<!-- ── MODAL TICKET ───────────────────────────────────────── -->
<div class="modal-ov" id="modal-ticket">
  <div class="ticket-card">
    <div class="tc-hdr">
      <img src="assets/img/logo_avasa.png" style="width:180px;display:block;margin:0 auto 4px">
      <p style="font-size:10px;color:#444;margin:0">&ldquo;Y aunque tu principio haya sido pequeño,<br>Tu postrer estado será muy grande&rdquo; Job 8:7</p>
      <h2 style="margin:6px 0 2px">NOTA VENTA</h2>
      <p id="tc-date" style="margin:0"></p>
    </div>
    <hr class="tc-div">
    <table class="tc-tbl" id="tc-tbl"></table>
    <hr class="tc-div">
    <div class="tc-tot">
      <span>TOTAL: S/</span>
      <span id="tc-total">0.00</span>
    </div>
    <div style="font-size:10px;margin-top:6px"><b>IMPORTE EN LETRAS: </b><span id="tc-letras"></span></div>
    <div style="font-size:10px;margin-top:4px"><b>VENDEDOR: </b><?= $user_name ?></div>
    <div style="font-size:10px;margin-top:4px"><b>PERSONAL ENTREGA: </b>__________________________</div>
    <div class="tc-foot">DIOS TE BENDIGA<br>GRACIAS POR TU PREFERENCIA<br><span style="font-size:9px">Todo reclamo deberá realizarse dentro de los 13 días posteriores.</span></div>
    <div class="tc-actions">
      <button class="btn-imprimir" onclick="printTicket()">🖨️ Imprimir</button>
      <button class="btn-nueva" onclick="closeTicket();clearCart()">✓ Nueva venta</button>
      <button class="btn-cerrar" onclick="closeTicket()">✕ Cerrar</button>
    </div>
  </div>
</div>

<div class="toast" id="toast"></div>

<!-- Overlay imprimir con RawBT -->
<div id="rawbt-overlay" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.75);
     z-index:500;align-items:center;justify-content:center;flex-direction:column;gap:16px">
  <a id="rawbt-link" href="#" style="
     display:block;background:#27ae60;color:#fff;font-family:'Barlow Condensed',sans-serif;
     font-size:28px;font-weight:900;letter-spacing:1px;text-transform:uppercase;
     padding:24px 48px;border-radius:16px;text-decoration:none;text-align:center;
     box-shadow:0 4px 24px rgba(0,0,0,.4)">
    🖨️ TOCA AQUÍ PARA IMPRIMIR
  </a>
  <button onclick="document.getElementById('rawbt-overlay').style.display='none'"
    style="background:none;border:1px solid #666;color:#ccc;padding:8px 20px;
           border-radius:8px;font-size:14px;cursor:pointer">
    Cancelar
  </button>
</div>

<!-- ── JAVASCRIPT ─────────────────────────────────────────── -->
<script>
// ── Datos del servidor ─────────────────────────────────────
const TABS_DATA   = <?= $js_data ?>;
const USER_ID          = <?= $user_id ?>;
const USER_CLIENT      = {10: 1, 11: 4580};      // usuario → id_cliente
const USER_CLIENT_NAME = {10: 'Cliente General', 11: 'Cliente Huevos'}; // usuario → nombre para el ticket

// ── Estado ─────────────────────────────────────────────────
let currentTab      = null;
let currentCatId    = null;
let selectedProduct = null;
let numBuf          = '0';
let cart            = [];

// ── ¿La pestaña actual requiere método de pago? ─────────────
function isHuevosTab(tabKey){
  const cfg = tabKey && TABS_DATA[tabKey] ? TABS_DATA[tabKey].config : null;
  return !!cfg && String(cfg.label||'').trim().toLowerCase() === 'huevos';
}

// ── Reloj ──────────────────────────────────────────────────
(function clockTick(){
  const now = new Date();
  document.getElementById('clock').textContent =
    now.toLocaleTimeString('es-PE',{hour:'2-digit',minute:'2-digit'});
  document.getElementById('today').textContent =
    now.toLocaleDateString('es-PE',{weekday:'short',day:'numeric',month:'short'});
  setTimeout(clockTick,10000);
})();

// ── Construir tabs ─────────────────────────────────────────
function buildTabs(){
  const row   = document.getElementById('tabs-row');
  const keys  = Object.keys(TABS_DATA);
  keys.forEach((key,i)=>{
    const cfg = TABS_DATA[key].config;
    const btn = document.createElement('button');
    btn.className = 'tab-btn' + (i===0?' active':'');
    btn.dataset.tab = key;
    btn.dataset.accent = cfg.color_accent;
    btn.dataset.accentbg = cfg.color_bg;
    const iconMap = {'fas fa-egg':'🥚','fas fa-utensils':'🍴','fas fa-box':'📦'};
    const iconHtml = iconMap[cfg.icon] || cfg.icon || '';
    btn.innerHTML = `<span class="tab-icon">${iconHtml}</span>${cfg.label}`;
    btn.onclick = ()=>switchTab(key);
    row.appendChild(btn);
  });
  // Activar primera pestaña
  if(keys.length) switchTab(keys[0]);
}

// ── Cambiar pestaña ────────────────────────────────────────
function switchTab(key){
  currentTab      = key;
  selectedProduct = null;
  numBuf          = '0';
  limpiarPago();

  // Colores dinámicos
  const cfg = TABS_DATA[key].config;
  document.documentElement.style.setProperty('--accent', cfg.color_accent);
  document.documentElement.style.setProperty('--accent-bg', cfg.color_bg);

  // Tabs UI
  document.querySelectorAll('.tab-btn').forEach(b=>{
    b.classList.toggle('active', b.dataset.tab===key);
    b.style.color       = b.dataset.tab===key ? b.dataset.accent : '';
    b.style.borderColor = b.dataset.tab===key ? b.dataset.accent : '';
  });

  // Categorías
  buildCatStrip(key);
  updateNumDisplay();
  actualizarPagoUI();
}

// ── Método de pago (solo pestaña Huevos) — permite pago mixto ─
// (parte efectivo + parte tarjeta, o solo uno de los dos)
function montoEfectivo(){ return parseFloat(document.getElementById('pm-monto-efectivo').value)||0; }
function montoTarjeta(){ return parseFloat(document.getElementById('pm-monto-tarjeta').value)||0; }
function totalCarrito(){ return cart.reduce((s,i)=>s+i.total,0); }

// Redondeo comercial: en efectivo se permite cobrar hasta 9 céntimos de más
// (no hay monedas de 1-5 céntimos para dar el vuelto exacto), igual que en caja_pagos.php.
function pagoListoHuevos(){
  if(!isHuevosTab(currentTab)) return true;
  const efectivo = montoEfectivo(), tarjeta = montoTarjeta();
  if(efectivo<=0 && tarjeta<=0) return false;
  const diferencia = +((efectivo+tarjeta) - totalCarrito()).toFixed(2);
  if(diferencia < 0) return false;
  if(diferencia > 0.01 && (efectivo<=0 || diferencia>0.09)) return false;
  return true;
}

function limpiarPago(){
  document.getElementById('pm-monto-efectivo').value = '';
  document.getElementById('pm-monto-tarjeta').value = '';
  actualizarPagoUI();
}

function pagoSoloUnMetodo(m){
  const total = totalCarrito();
  document.getElementById('pm-monto-efectivo').value = m==='efectivo' ? total.toFixed(2) : '';
  document.getElementById('pm-monto-tarjeta').value  = m==='tarjeta'  ? total.toFixed(2) : '';
  actualizarPagoUI();
}

function actualizarPagoUI(){
  const esHuevos = isHuevosTab(currentTab);
  document.getElementById('pay-method-row').classList.toggle('show', esHuevos);

  const status = document.getElementById('pay-status');
  if(esHuevos){
    const suma = montoEfectivo() + montoTarjeta();
    const diferencia = +(totalCarrito() - suma).toFixed(2);
    status.style.display = suma>0 ? 'block' : 'none';
    if(diferencia > 0){
      status.textContent = 'Falta S/' + diferencia.toFixed(2);
      status.className = 'pay-status falta';
    } else if(diferencia < -0.09){
      status.textContent = 'Sobra S/' + Math.abs(diferencia).toFixed(2);
      status.className = 'pay-status falta';
    } else {
      status.textContent = '✓ Pago completo';
      status.className = 'pay-status ok';
    }
  } else {
    status.style.display = 'none';
  }

  setTotalButtons(totalCarrito());
}

// ── Barra de categorías ────────────────────────────────────
function buildCatStrip(tabKey){
  const strip = document.getElementById('cat-strip');
  strip.innerHTML = '';
  const groups = TABS_DATA[tabKey].groups;
  const idxs   = Object.keys(groups);

  if(!idxs.length){
    renderProducts([]);
    return;
  }

  idxs.forEach((gi,i)=>{
    const grp = groups[gi];
    const pill = document.createElement('button');
    pill.className = 'cat-pill' + (i===0?' active':'');
    pill.dataset.gi = gi;
    pill.textContent = grp.name;
    pill.onclick = ()=>selectGroup(tabKey, gi);
    strip.appendChild(pill);
  });

  selectGroup(tabKey, idxs[0]);
}

// ── Seleccionar categoría ──────────────────────────────────
function selectGroup(tabKey, gi){
  selectedProduct = null;
  numBuf = '0';
  updateNumDisplay();

  document.querySelectorAll('.cat-pill').forEach(p=>{
    p.classList.toggle('active', p.dataset.gi==gi);
  });

  const items = TABS_DATA[tabKey].groups[gi]?.items || [];
  renderProducts(items);
}

// ── Renderizar grid de productos ───────────────────────────
function renderProducts(items){
  const grid = document.getElementById('prod-grid');
  grid.innerHTML = '';

  if(!items.length){
    grid.innerHTML = '<div class="no-products">Sin productos en esta categoría</div>';
    return;
  }

  items.forEach(item=>{
    const btn = document.createElement('button');
    btn.className = 'prod-btn';
    btn.dataset.id = item.id;

    const label = item.by_weight
      ? `${item.variant} · ${item.unit}`
      : (item.qty_per > 1 ? `${item.variant} (${item.qty_per})` : item.variant);

    btn.innerHTML = `
      <span class="pb-name">${escHtml(item.prod_name)}</span>
      <span class="pb-variant">${escHtml(label)}</span>
      <span class="pb-price">S/<b>${item.price.toFixed(2)}</b></span>
    `;
    btn.onclick = ()=>selectProduct(item, btn);
    grid.appendChild(btn);
  });
}

// ── Seleccionar producto ───────────────────────────────────
function selectProduct(item, btn){
  selectedProduct = item;

  // precio por unidad base: igual que venta.php → precio_vp / cantidad_vp
  // Sin redondear aquí: si se redondea antes de multiplicar por la cantidad, ese
  // resto de céntimo se amplifica con cantidades grandes (ej. 180 unidades) y el
  // total termina en el céntimo equivocado (ej. 73.01 en vez de 73.00). Se redondea
  // una sola vez, al final, cuando se calcula el total de la línea.
  selectedProduct.unit_price = item.qty_per > 0
    ? item.price / item.qty_per
    : item.price;

  // pre-llenar con qty_per para edición rápida (ej: 0.5 para ½kg)
  numBuf = String(item.qty_per > 0 ? item.qty_per : 1);

  document.querySelectorAll('.prod-btn').forEach(b=>b.classList.remove('selected'));
  btn.classList.add('selected');

  document.getElementById('nd-lbl').textContent  = item.by_weight ? 'Peso (kg)' : 'Cantidad';
  document.getElementById('nd-unit').textContent = item.by_weight ? 'kg' : 'unid.';
  updateNumDisplay();
}

// ── Numpad ─────────────────────────────────────────────────
function nk(key){
  if(key==='del'){
    numBuf = numBuf.length>1 ? numBuf.slice(0,-1) : '0';
  } else if(key==='.'){
    if(!numBuf.includes('.')) numBuf += '.';
  } else {
    if(numBuf==='0') numBuf = key;
    else numBuf += key;
  }
  if(numBuf.length>7) numBuf = numBuf.slice(0,7);
  updateNumDisplay();
}

function updateNumDisplay(){
  const val = parseFloat(numBuf)||0;
  document.getElementById('nd-val').textContent = numBuf;
  if(selectedProduct){
    // subtotal = cantidad_ingresada × (precio_vp / cantidad_vp)  — igual que venta.php
    const sub = (val * selectedProduct.unit_price).toFixed(2);
    document.getElementById('nd-sub').textContent = 'S/'+sub;
  } else {
    document.getElementById('nd-sub').textContent = 'S/0.00';
  }
}

// ── Agregar al carrito ─────────────────────────────────────
function addToCart(){
  if(!selectedProduct){ toast('Selecciona un producto','err'); return; }
  const qty = parseFloat(numBuf)||0;
  if(qty<=0){ toast('Ingresa una cantidad válida','err'); return; }

  const label = selectedProduct.by_weight
    ? `${selectedProduct.prod_name} – ${selectedProduct.variant} ${qty}${selectedProduct.unit}`
    : `${selectedProduct.prod_name} – ${selectedProduct.variant} ×${qty}`;

  cart.push({
    uid     : Date.now(),
    vp_id   : selectedProduct.id,
    prod_id : selectedProduct.prod_id,
    name    : label,
    qty     : qty,
    price   : selectedProduct.unit_price,
    total   : +(qty * selectedProduct.unit_price).toFixed(2),
    unit    : selectedProduct.by_weight ? selectedProduct.unit : 'unid.',
  });

  numBuf = '0';
  updateNumDisplay();
  renderCart();
  toast('Agregado ✓');
  ocultarVentaGuardada(); // están armando un pedido nuevo, ya no aplica reimprimir el anterior
}

// ── Renderizar carrito ─────────────────────────────────────
function renderCart(){
  const list = document.getElementById('cart-list');
  if(!cart.length){
    list.innerHTML = '<div class="cart-empty">Sin productos aún</div>';
    setTotalButtons(0);
    return;
  }
  let html='', grand=0;
  cart.forEach(ci=>{
    grand += ci.total;
    html += `<div class="ci">
      <div class="ci-name">${escHtml(ci.name)}</div>
      <div class="ci-price">S/${ci.price.toFixed(2)}</div>
      <div class="ci-total">S/${ci.total.toFixed(2)}</div>
      <button class="ci-del" onclick="removeItem(${ci.uid})">✕</button>
    </div>`;
  });
  list.innerHTML = html;
  setTotalButtons(grand);
}

function setTotalButtons(grand){
  document.getElementById('total-val').textContent = grand.toFixed(2);
  const hasItems  = grand>0;
  const pagoListo = pagoListoHuevos();
  document.getElementById('btn-save').disabled  = !hasItems || !pagoListo;
  // btn-pay / btn-print están comentados en el HTML (sin usar) — no se referencian aquí.
}

function removeItem(uid){
  cart = cart.filter(i=>i.uid!==uid);
  renderCart();
}

function clearCart(){
  if(!cart.length) return;
  if(!confirm('¿Limpiar el pedido?')) return;
  cart=[];
  limpiarPago();
  renderCart();
}

// ── Ticket modal ───────────────────────────────────────────
function openTicket(){
  if(!cart.length) return;
  fillTicketModal();
  document.getElementById('modal-ticket').classList.add('show');
}

function fillTicketModal(){
  const now = new Date();
  document.getElementById('tc-date').textContent =
    'FECHA: '+now.toLocaleString('es-PE',{day:'numeric',month:'long',year:'numeric',hour:'2-digit',minute:'2-digit'});

  let rows='', grand=0;
  cart.forEach(ci=>{
    grand+=ci.total;
    rows+=`<tr>
      <td class="tc-n" style="font-weight:bold;font-size:11px">${escHtml(ci.name)}</td>
      <td class="tc-p" style="text-align:right;font-weight:bold">S/${ci.total.toFixed(2)}</td>
    </tr>`;
  });
  // Cabecera
  rows = `<tr><th style="text-align:left;font-size:10px">[CANT.][UNID] DESCRIPCIÓN</th><th style="text-align:right;font-size:10px">TOTAL</th></tr>` + rows;
  document.getElementById('tc-tbl').innerHTML = rows;
  document.getElementById('tc-total').textContent = grand.toFixed(2);
  document.getElementById('tc-letras').textContent = numeroALetras(grand);
}

function closeTicket(){
  document.getElementById('modal-ticket').classList.remove('show');
}

// ── Número a letras (español) ──────────────────────────────
function numeroALetras(num){
  const UNIDADES=['','UN','DOS','TRES','CUATRO','CINCO','SEIS','SIETE','OCHO','NUEVE'];
  const DECENAS=['','DIEZ','VEINTE','TREINTA','CUARENTA','CINCUENTA','SESENTA','SETENTA','OCHENTA','NOVENTA'];
  const ESPECIALES=['','ONCE','DOCE','TRECE','CATORCE','QUINCE','DIECISÉIS','DIECISIETE','DIECIOCHO','DIECINUEVE'];
  const CENTENAS=['','CIENTO','DOSCIENTOS','TRESCIENTOS','CUATROCIENTOS','QUINIENTOS','SEISCIENTOS','SETECIENTOS','OCHOCIENTOS','NOVECIENTOS'];
  function grupo(n){
    let s='';
    const c=Math.floor(n/100), d=Math.floor((n%100)/10), u=n%10;
    if(c>0) s+=(c===1&&d===0&&u===0?'CIEN':CENTENAS[c])+' ';
    if(d===1&&u>0) s+=ESPECIALES[u]+' ';
    else{ if(d>0) s+=DECENAS[d]+' '; if(u>0) s+=UNIDADES[u]+' '; }
    return s.trim();
  }
  const entero=Math.floor(num);
  const cents=Math.round((num-entero)*100);
  let s='';
  const miles=Math.floor(entero/1000), resto=entero%1000;
  if(miles>0) s+=(miles===1?'MIL':grupo(miles)+' MIL')+' ';
  if(resto>0) s+=grupo(resto);
  if(!s) s='CERO';
  return s.trim()+' CON '+String(cents).padStart(2,'0')+'/100 SOLES';
}

// ── Imprimir ───────────────────────────────────────────────
// Guarda el último ticket construido para poder reimprimirlo sin depender del
// carrito actual (que ya se vació tras guardar) — así un fallo al imprimir no
// obliga a re-ingresar y re-guardar el pedido para intentarlo de nuevo.
let lastTicketText = null;

function printTicket(){
  if(!cart.length) return;
  lastTicketText = buildTicketText(cart);
  showPrintButton(null, lastTicketText);
}

function reimprimirUltimoTicket(){
  if(!lastTicketText){ toast('No hay ningún ticket guardado para reimprimir','err'); return; }
  showPrintButton(null, lastTicketText);
}

// Banner persistente tras guardar — se queda a la vista (a diferencia del toast, que
// desaparece solo en 2s) hasta que se empieza un pedido nuevo, para que el botón
// "Reimprimir" siga a mano si el ticket no salió a la primera.
function mostrarVentaGuardada(ventaId, total){
  const el = document.getElementById('venta-guardada');
  document.getElementById('vg-texto').textContent = '✓ Venta v-' + ventaId + ' guardada — S/' + total.toFixed(2);
  el.classList.add('show');
}

function ocultarVentaGuardada(){
  document.getElementById('venta-guardada').classList.remove('show');
}

function buildTicketText(items){
  const now   = new Date();
  const fecha = now.toLocaleString('es-PE',{day:'numeric',month:'long',year:'numeric',hour:'2-digit',minute:'2-digit'});
  const vendedor = <?= json_encode($user_name) ?>;
  const grand = items.reduce((s,i)=>s+i.total, 0);
  const clienteNombre = USER_CLIENT_NAME[USER_ID] ?? '';

  // Comandos ESC/POS codificados como %XX
  // Chrome preserva %XX en opaque paths; RawBT los decodifica igual que %20→espacio
  const I  = '%1B%40';          // Init impresora
  const CA = '%1B%61%01';     // Centro
  const LA = '%1B%61%00';     // Izquierda
  const BO = '%1B%45%01';     // Negrita ON
  const BF = '%1B%45%00';     // Negrita OFF
  const DO = '%1D%21%11';     // Doble ancho + alto
  const DF = '%1D%21%00';     // Tamaño normal
  const LF = '%0A';           // Salto de línea
  const CT = '%1D%56%42%03';  // Corte parcial de papel

  const W   = 42;
  const SEP = '-'.repeat(W) + LF;

  // Quita acentos y chars no-ASCII (chrome los codificaría)
  const asc  = (s) => String(s).normalize('NFD').replace(/[̀-ͯ]/g,'')
                       .replace(/[–—]/g,'-').replace(/×/g,'x').replace(/[^\x20-\x7E]/g,'');
  const pad  = (s, n) => asc(s).substring(0, n).padEnd(n);
  const rpad = (s, n) => asc(s).substring(0, n).padStart(n);

  let t = I;

  // ── Header grande centrado ──
  t += CA + DO + BO;
  t += 'DISTRIBUIDORA RENACER' + LF;
  t += BF + DF;
  t += '"Y aunque tu principio haya sido' + LF;
  t += 'pequeno, tu postrer estado sera' + LF;
  t += 'muy grande" Job 8:7' + LF + LF;
  t += BO + 'NOTA VENTA' + BF + LF + LF;

  // ── Datos ──
  t += LA;
  t += 'FECHA: '   + asc(fecha)          + LF;
  if(clienteNombre) t += 'CLIENTE: ' + asc(clienteNombre) + LF;
  t += SEP;

  // ── Cabecera tabla ──
  t += BO + pad('DESCRIPCION', W - 9) + rpad('TOTAL', 9) + BF + LF;
  t += SEP;

  // ── Items ──
  items.forEach(ci => {
    const tot  = 'S/' + ci.total.toFixed(2);
    const name = asc(ci.name.replace(/\s*[–—]\s*/g,' ').replace(/×/g,'x')).trim();
    // pad() trunca el nombre para que el precio siempre quepa en la misma línea
    t += pad(name, W - tot.length) + tot + LF;
  });

  t += SEP;

  // ── Total grande centrado ──
  t += CA + DO + BO + 'TOTAL: S/' + grand.toFixed(2) + BF + DF + LF;
  t += LA + SEP;

  t += 'IMPORTE EN LETRAS:' + LF;
  t += asc(numeroALetras(grand)).substring(0, W) + LF;
  t += 'VENDEDOR: '        + asc(vendedor)         + LF;
  t += 'PERSONAL ENTREGA: _____________'            + LF;
  t += SEP;

  // ── Pie centrado ──
  t += CA;
  t += 'DIOS TE BENDIGA'            + LF;
  t += 'GRACIAS POR TU PREFERENCIA' + LF;
  t += 'Reclamos dentro de 13 dias.' + LF;
  t += LF + LF + LF;

  // ── Corte de papel ──
  t += CT;

  return t;
}

// ── Botón imprimir con RawBT ───────────────────────────────
let _rawbtHideTO;
function showPrintButton(url, text) {
  const ov = document.getElementById('rawbt-overlay');
  const lk = document.getElementById('rawbt-link');
  lk.href = text ? 'rawbt:' + text : 'rawbt:' + url;
  ov.style.display = 'flex';
  // 20s alcanzaba muy poco — si el cajero se distrae un momento, el aviso
  // desaparece solo y parece que "no pasó nada", llevando a re-guardar el pedido
  // completo solo para volver a ver el botón de imprimir. El banner "Reimprimir"
  // que queda abajo del carrito es la forma normal de reintentar sin duplicar la venta.
  clearTimeout(_rawbtHideTO);
  _rawbtHideTO = setTimeout(()=>{ ov.style.display='none'; }, 90000);
}

// ── Toast ──────────────────────────────────────────────────
let _toastTO;
function toast(msg, type='ok'){
  const el = document.getElementById('toast');
  el.textContent = msg;
  el.className   = 'toast'+(type==='err'?' err':'');
  el.classList.add('show');
  clearTimeout(_toastTO);
  _toastTO = setTimeout(()=>el.classList.remove('show'),2000);
}

// ── Guardar venta y imprimir ───────────────────────────────
function saveAndPrint(){
  if(!cart.length){ toast('El pedido está vacío','err'); return; }
  if(isHuevosTab(currentTab) && !pagoListoHuevos()){ toast('El pago no cuadra con el total','err'); return; }

  const btn = document.getElementById('btn-save');
  btn.disabled = true;
  btn.textContent = '⏳ Guardando…';

  const now   = new Date();
  const fecha = now.toISOString().slice(0,10); // YYYY-MM-DD

  const id_cliente = USER_CLIENT[USER_ID] ?? null;

  const body = new URLSearchParams();
  body.append('fecha',   fecha);
  body.append('total1',  cart.reduce((s,i)=>s+i.total,0).toFixed(2));
  if(id_cliente) body.append('id_cliente', id_cliente);
  if(isHuevosTab(currentTab)){
    if(montoEfectivo()>0){ body.append('metodo_pago[]', 'efectivo'); body.append('monto_pago[]', montoEfectivo().toFixed(2)); }
    if(montoTarjeta()>0){ body.append('metodo_pago[]', 'tarjeta');  body.append('monto_pago[]', montoTarjeta().toFixed(2)); }
  }

  cart.forEach(ci=>{
    body.append('id_pro[]',     ci.prod_id);
    body.append('id_vp[]',      ci.vp_id);
    body.append('cantidad[]',   ci.qty);
    body.append('precio[]',     ci.price.toFixed(2));
    body.append('total_pre[]',  ci.total.toFixed(2));
  });

  fetch('/inc/registrar_venta_tablet.php', {
    method:'POST',
    body: body,
  })
  .then(r=>r.json())
  .then(data=>{
    if(data.respuesta){
      const grand = cart.reduce((s,i)=>s+i.total,0);
      printTicket();
      cart=[];
      limpiarPago();
      renderCart();
      toast('Venta guardada ✓');
      mostrarVentaGuardada(data.venta_id, grand);
    } else {
      toast(data.mensaje||'Error al guardar','err');
    }
  })
  .catch(()=>toast('Error de conexión','err'))
  .finally(()=>{
    btn.textContent = '💾 Guardar';
    btn.disabled = !cart.length;
  });
}

// ── Util ───────────────────────────────────────────────────
function escHtml(s){
  return String(s)
    .replace(/&/g,'&amp;')
    .replace(/</g,'&lt;')
    .replace(/>/g,'&gt;')
    .replace(/"/g,'&quot;');
}

// ── Iniciar ────────────────────────────────────────────────
buildTabs();
</script>
</body>
</html>
