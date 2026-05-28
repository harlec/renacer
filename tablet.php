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

// ── Obtener todos los variantes-producto con joins ──────────
$vp_raw = Sdba::table('variante_p');
$vp_raw->left_join('variante_vp', 'variantes', 'id_variante');
$vp_raw->left_join('producto_vp', 'productos',  'id_producto');
$vp_raw->left_join('categoria',   'categorias', 'id_categoria');
$vp_raw->left_join('unidad_prod', 'unidades',   'id_unidad');
$all_vp = $vp_raw->get();

// ── Agrupar por pestaña → categoría → productos ─────────────
$tabs_data = [];
foreach ($TABLET_TABS as $tab_key => $tab_cfg) {
    $tabs_data[$tab_key] = [
        'config'     => $tab_cfg,
        'categories' => [],   // id_categoria => ['name'=>..., 'items'=>[...]]
    ];
    foreach ($tab_cfg['category_ids'] as $cat_id) {
        $tabs_data[$tab_key]['categories'][$cat_id] = [
            'name'  => '',
            'items' => [],
        ];
    }
}

foreach ($all_vp as $row) {
    if (empty($row['nom_prod'])) continue;
    $cat_id = (int)$row['id_categoria'];

    foreach ($tabs_data as $tab_key => &$tab) {
        if (in_array($cat_id, $tab['config']['category_ids'])) {
            $tab['categories'][$cat_id]['name'] = $row['nom_cat'] ?? ('Cat '.$cat_id);

            // Detectar si la unidad es por peso
            $unit_name  = $row['nombre'] ?? '';
            $by_weight  = in_array($unit_name, TABLET_UNIT_BY_WEIGHT);

            $tab['categories'][$cat_id]['items'][] = [
                'id'        => (int)$row['id_vp'],
                'prod_name' => $row['nom_prod'],
                'variant'   => $row['variante'],
                'qty_per'   => (float)$row['cantidad_vp'],
                'price'     => (float)$row['precio_vp'],
                'unit'      => $unit_name,
                'by_weight' => $by_weight,
            ];
            break;
        }
    }
    unset($tab);
}

// Convertir a JSON para JS
$js_data = json_encode($tabs_data, JSON_UNESCAPED_UNICODE);

$store_name = TABLET_STORE_NAME;
$user_name  = htmlspecialchars($_SESSION['nombres'] ?? $_SESSION['usuario']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
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
  border-bottom:2px solid var(--border);
  padding:10px 16px;
  display:flex;
  align-items:center;
  justify-content:space-between;
  flex-shrink:0;
  gap:10px;
}
.hdr-brand{
  font-family:'Barlow Condensed',sans-serif;
  font-size:20px;
  font-weight:900;
  letter-spacing:2px;
  text-transform:uppercase;
  white-space:nowrap;
}
.hdr-brand span{color:var(--accent)}
.hdr-mid{
  display:flex;
  gap:8px;
  align-items:center;
  flex-wrap:wrap;
}
#clock{
  font-family:'Barlow Condensed',sans-serif;
  font-size:22px;
  font-weight:700;
}
#today{font-size:12px;color:var(--muted)}
.hdr-user{font-size:13px;color:var(--muted);text-align:right}
.hdr-user strong{color:var(--text)}
.btn-salir{
  background:none;
  border:1px solid #444;
  border-radius:6px;
  padding:4px 10px;
  color:var(--muted);
  font-size:12px;
  cursor:pointer;
}
.btn-salir:hover{color:var(--red);border-color:var(--red)}

/* ── Tabs ─────────────────────────────────────────────────── */
.tabs-row{
  display:flex;
  padding:10px 16px 0;
  gap:4px;
  flex-shrink:0;
}
.tab-btn{
  flex:1;
  padding:12px 8px;
  border:none;
  cursor:pointer;
  font-family:'Barlow Condensed',sans-serif;
  font-size:18px;
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
.tab-icon{margin-right:6px;font-size:20px}

/* ── Main area ────────────────────────────────────────────── */
.main{
  display:flex;
  flex:1;
  overflow:hidden;
  background:var(--surface);
  margin:0 16px;
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
  gap:6px;
  padding:12px 12px 0;
  flex-shrink:0;
  overflow-x:auto;
}
.cat-strip::-webkit-scrollbar{height:3px}
.cat-strip::-webkit-scrollbar-thumb{background:var(--border)}
.cat-pill{
  padding:6px 14px;
  border:2px solid var(--border);
  border-radius:20px;
  font-family:'Barlow Condensed',sans-serif;
  font-size:14px;
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
  padding:12px;
}
.prod-grid-wrap::-webkit-scrollbar{width:4px}
.prod-grid-wrap::-webkit-scrollbar-thumb{background:var(--border);border-radius:4px}

.prod-grid{
  display:grid;
  grid-template-columns:repeat(4,1fr);
  gap:8px;
}
@media(max-width:700px){
  .prod-grid{grid-template-columns:repeat(3,1fr)}
}

.prod-btn{
  background:var(--surface2);
  border:2px solid var(--border);
  border-radius:var(--r);
  padding:12px 6px;
  cursor:pointer;
  text-align:center;
  transition:all .15s;
  display:flex;
  flex-direction:column;
  align-items:center;
  gap:4px;
}
.prod-btn:active,.prod-btn.selected{
  border-color:var(--accent);
  background:var(--accent-bg,rgba(245,166,35,.12));
  transform:scale(.96);
}
.pb-name{
  font-family:'Barlow Condensed',sans-serif;
  font-size:15px;
  font-weight:700;
  color:var(--text);
  line-height:1.2;
}
.pb-variant{
  font-size:11px;
  color:var(--muted);
}
.pb-price{
  font-family:'Barlow Condensed',sans-serif;
  font-size:20px;
  font-weight:900;
  color:var(--accent);
}
.pb-price small{font-size:11px;font-weight:400;color:var(--muted)}

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
  padding:12px 0 0 12px;
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
    font-family:'Courier New',monospace;
    font-size:13px;
    padding:10mm 8mm;
  }
  .pa-hdr{text-align:center;margin-bottom:8px}
  .pa-hdr h2{font-size:16px;font-weight:900;text-transform:uppercase;letter-spacing:2px}
  .pa-hdr p{font-size:11px;color:#444}
  .pa-div{border:none;border-top:1px dashed #888;margin:8px 0}
  .pa-tbl{width:100%;border-collapse:collapse;font-size:12px}
  .pa-tbl td{padding:3px 2px}
  .pa-tot{display:flex;justify-content:space-between;font-size:15px;font-weight:900;margin-top:8px}
  .pa-foot{text-align:center;font-size:11px;color:#777;margin-top:10px}
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
      <h2 id="tc-store"><?= htmlspecialchars($store_name) ?></h2>
      <p id="tc-addr"><?= htmlspecialchars(TABLET_STORE_ADDRESS) ?></p>
      <p id="tc-date"></p>
    </div>
    <hr class="tc-div">
    <table class="tc-tbl" id="tc-tbl"></table>
    <hr class="tc-div">
    <div class="tc-tot">
      <span>TOTAL</span>
      <span id="tc-total">S/0.00</span>
    </div>
    <div class="tc-foot">¡Gracias por su compra!</div>
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
const TABS_DATA = <?= $js_data ?>;

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
  const cats = TABS_DATA[tabKey].categories;
  const ids   = Object.keys(cats);

  if(!ids.length){
    renderProducts([]);
    return;
  }

  ids.forEach((catId,i)=>{
    const cat = cats[catId];
    const pill = document.createElement('button');
    pill.className = 'cat-pill' + (i===0?' active':'');
    pill.dataset.catid = catId;
    pill.textContent = cat.name || ('Cat '+catId);
    pill.onclick = ()=>selectCat(tabKey, catId);
    strip.appendChild(pill);
  });

  // Seleccionar primera categoría
  selectCat(tabKey, ids[0]);
}

// ── Seleccionar categoría ──────────────────────────────────
function selectCat(tabKey, catId){
  currentCatId = catId;
  selectedProduct = null;
  numBuf = '0';
  updateNumDisplay();

  document.querySelectorAll('.cat-pill').forEach(p=>{
    p.classList.toggle('active', p.dataset.catid==catId);
  });

  const items = TABS_DATA[tabKey].categories[catId]?.items || [];
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
  numBuf = '0';

  document.querySelectorAll('.prod-btn').forEach(b=>b.classList.remove('selected'));
  btn.classList.add('selected');

  document.getElementById('nd-lbl').textContent  = item.by_weight ? 'Peso (kg)' : 'Cantidad';
  document.getElementById('nd-unit').textContent = item.by_weight ? 'kg' : (item.unit||'unid.');
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
    const sub = (val*selectedProduct.price).toFixed(2);
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
    uid   : Date.now(),
    name  : label,
    qty   : qty,
    price : selectedProduct.price,
    total : +(qty * selectedProduct.price).toFixed(2),
    unit  : selectedProduct.by_weight ? selectedProduct.unit : 'unid.',
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
    now.toLocaleString('es-PE',{day:'numeric',month:'long',year:'numeric',hour:'2-digit',minute:'2-digit'});

  let rows='', grand=0;
  cart.forEach(ci=>{
    grand+=ci.total;
    rows+=`<tr>
      <td class="tc-n">${escHtml(ci.name)}</td>
      <td class="tc-q">${ci.qty}</td>
      <td class="tc-p">S/${ci.total.toFixed(2)}</td>
    </tr>`;
  });
  document.getElementById('tc-tbl').innerHTML = rows;
  document.getElementById('tc-total').textContent = 'S/'+grand.toFixed(2);
}

function closeTicket(){
  document.getElementById('modal-ticket').classList.remove('show');
}

// ── Imprimir ───────────────────────────────────────────────
function printTicket(){
  // Preparar área de impresión
  const now    = new Date();
  const fecha  = now.toLocaleString('es-PE',{day:'numeric',month:'long',year:'numeric',hour:'2-digit',minute:'2-digit'});
  const store  = <?= json_encode($store_name) ?>;
  const addr   = <?= json_encode(TABLET_STORE_ADDRESS) ?>;
  const phone  = <?= json_encode(TABLET_STORE_PHONE) ?>;

  let rows='', grand=0;
  cart.forEach(ci=>{
    grand+=ci.total;
    rows+=`<tr>
      <td class="pa-n">${escHtml(ci.name)}</td>
      <td class="pa-q" style="text-align:center">${ci.qty}</td>
      <td class="pa-p" style="text-align:right">S/${ci.total.toFixed(2)}</td>
    </tr>`;
  });

  document.getElementById('print-area').innerHTML = `
    <div class="pa-hdr">
      <h2>${escHtml(store)}</h2>
      ${addr?`<p>${escHtml(addr)}</p>`:''}
      ${phone?`<p>Tel: ${escHtml(phone)}</p>`:''}
      <p>${escHtml(fecha)}</p>
    </div>
    <div class="pa-div"></div>
    <table class="pa-tbl">
      <thead>
        <tr>
          <td><strong>Producto</strong></td>
          <td style="text-align:center"><strong>Cant.</strong></td>
          <td style="text-align:right"><strong>Total</strong></td>
        </tr>
      </thead>
      <tbody>${rows}</tbody>
    </table>
    <div class="pa-div"></div>
    <div class="pa-tot"><span>TOTAL</span><span>S/${grand.toFixed(2)}</span></div>
    <div class="pa-foot">¡Gracias por su compra!</div>
  `;

  document.getElementById('print-area').style.display = 'block';
  window.print();
  document.getElementById('print-area').style.display = 'none';
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
