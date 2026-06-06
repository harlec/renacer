<?php
// ── Sesión & control ────────────────────────────────────────
session_start();
if (empty($_SESSION['ingress'])) {
    header("Location: index.html");
    exit;
}

// ── Configuración del POS ───────────────────────────────────
require_once 'inc/sdba/sdba.php';
require_once 'inc/tablet_config.php';

// ── Obtener todos los variantes-producto (solo joins válidos) ──
// categorias está en productos.id_categoria, no en variante_p,
// así que filtramos por id_categoria/id_producto en PHP.
$vp_raw = Sdba::table('variante_p');
$vp_raw->left_join('variante_vp', 'variantes', 'id_variante');
$vp_raw->left_join('producto_vp', 'productos',  'id_producto');
$all_vp = $vp_raw->get();

// ── Agrupar por pestaña → grupo → productos ──────────────────
// Cada pestaña tiene 'groups', y cada grupo puede filtrar por
// category_ids (id_categoria) y/o product_ids (id_producto).
$tabs_data = [];
foreach ($TABLET_TABS as $tab_key => $tab_cfg) {
    $groups = [];
    foreach ($tab_cfg['groups'] as $gi => $grp) {
        $groups[$gi] = [
            'name'  => $grp['label'],
            'items' => [],
        ];
    }
    $tabs_data[$tab_key] = [
        'config' => [
            'label'        => $tab_cfg['label'],
            'icon'         => $tab_cfg['icon'],
            'color_accent' => $tab_cfg['color_accent'],
            'color_bg'     => $tab_cfg['color_bg'],
            'by_weight'    => !empty($tab_cfg['by_weight']),
        ],
        'groups' => $groups,
    ];
}

foreach ($all_vp as $row) {
    if (empty($row['nom_prod'])) continue;
    // El join de productos trae la FK como 'categoria' (no 'id_categoria')
    $cat_id  = (int)($row['categoria'] ?? $row['id_categoria'] ?? 0);
    $prod_id = (int)($row['id_producto']  ?? 0);

    foreach ($TABLET_TABS as $tab_key => $tab_cfg) {
        $by_weight = !empty($tab_cfg['by_weight']);
        foreach ($tab_cfg['groups'] as $gi => $grp) {
            $var_id = (int)($row['id_variante'] ?? $row['variante_vp'] ?? 0);

            $cat_match  = !empty($grp['category_ids']) && in_array($cat_id,  $grp['category_ids']);
            $prod_match = !empty($grp['product_ids'])  && in_array($prod_id, $grp['product_ids']);
            $var_match  = !empty($grp['variant_ids'])  && in_array($var_id,  $grp['variant_ids']);

            $has_prod = !empty($grp['product_ids']);
            $has_var  = !empty($grp['variant_ids']);

            $match = $cat_match
                  || ($has_prod && !$has_var && $prod_match)
                  || (!$has_prod && $has_var  && $var_match)
                  || ($has_prod && $has_var   && $prod_match && $var_match);

            if ($match) {
                $tabs_data[$tab_key]['groups'][$gi]['items'][] = [
                    'id'        => (int)$row['id_vp'],
                    'prod_id'   => (int)$row['id_producto'],
                    'prod_name' => $row['nom_prod'],
                    'variant'   => $row['variante'],
                    'qty_per'   => (float)$row['cantidad_vp'],
                    'price'     => (float)$row['precio_vp'],
                    'by_weight' => $by_weight,
                ];
                break 2; // un producto va a un solo grupo
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
        <div class="action-row">
          <button class="btn-save" id="btn-save" onclick="saveAndPrint()" disabled>💾 Guardar</button>
          <button class="btn-pay" id="btn-pay" onclick="openTicket()" disabled>🧾 Cobrar</button>
          <button class="btn-print-cart" id="btn-print" onclick="printTicket()" disabled title="Imprimir ticket">🖨️</button>
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
    btn.innerHTML = `<span class="tab-icon">${cfg.icon}</span>${cfg.label}`;
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
  selectedProduct.unit_price = item.qty_per > 0
    ? +(item.price / item.qty_per).toFixed(4)
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
  const hasItems = grand>0;
  document.getElementById('btn-save').disabled  = !hasItems;
  document.getElementById('btn-pay').disabled   = !hasItems;
  document.getElementById('btn-print').disabled = !hasItems;
}

function removeItem(uid){
  cart = cart.filter(i=>i.uid!==uid);
  renderCart();
}

function clearCart(){
  if(!cart.length) return;
  if(!confirm('¿Limpiar el pedido?')) return;
  cart=[];
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
function printTicket(){
  if(!cart.length) return;

  const now      = new Date();
  const fecha    = now.toLocaleString('es-PE',{day:'numeric',month:'long',year:'numeric',hour:'2-digit',minute:'2-digit'});
  const vendedor = <?= json_encode($user_name) ?>;
  const addr     = <?= json_encode(TABLET_STORE_ADDRESS) ?>;
  const phone    = <?= json_encode(TABLET_STORE_PHONE) ?>;
  const grand    = cart.reduce((s,i)=>s+i.total, 0);

  const clienteNombre = USER_CLIENT_NAME[USER_ID] ?? '';

  const payload = {
    items    : cart.map(ci=>({name: ci.name, total: ci.total})),
    grand    : grand,
    vendedor : vendedor,
    fecha    : fecha,
    cliente  : clienteNombre,
    addr     : addr,
    phone    : phone,
  };

  fetch('/inc/ticket_pdf_tablet.php', {
    method  : 'POST',
    headers : {'Content-Type':'application/json'},
    body    : JSON.stringify(payload),
  })
  .then(r=>{
    if(!r.ok) throw new Error('Error generando PDF');
    return r.json();
  })
  .then(data=>{
    if(!data.url) throw new Error('Sin URL de ticket');
    // Abrir RawBT con la URL del PDF — RawBT lo descarga e imprime por Bluetooth
    const a = document.createElement('a');
    a.href = 'rawbt:' + data.url;
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
  })
  .catch(()=>toast('Error al generar el ticket','err'));
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
      printTicket();
      cart=[];
      renderCart();
      toast('Venta guardada ✓');
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
