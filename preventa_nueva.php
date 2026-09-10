<?php
session_start();
require_once 'inc/preventa_control.php';
preventa_requerir_html();

$cliente_nombre = htmlspecialchars($_SESSION['pv_cliente_nombre']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
<title>Hacer pedido</title>
<style>
@import url('https://fonts.googleapis.com/css2?family=Barlow+Condensed:wght@700;900&family=Barlow:wght@400;500;600&display=swap');
:root{
  --bg:#1a1a1a; --surface:#242424; --surface2:#2e2e2e; --border:#3a3a3a;
  --green:#27ae60; --red:#e74c3c; --text:#f0f0f0; --muted:#888; --accent:#f5a623;
}
*{box-sizing:border-box;margin:0;padding:0;-webkit-tap-highlight-color:transparent}
html,body{height:100%}
body{
  font-family:'Barlow',sans-serif;
  background:var(--bg);
  color:var(--text);
  display:flex;
  flex-direction:column;
}
.hdr{
  background:#111;
  border-bottom:1px solid var(--border);
  padding:12px 16px;
  display:flex;
  align-items:center;
  justify-content:space-between;
}
.hdr-title{
  font-family:'Barlow Condensed',sans-serif;
  font-size:18px;
  font-weight:900;
  text-transform:uppercase;
  letter-spacing:1px;
}
.hdr-user{font-size:12px;color:var(--muted);text-align:right}
.hdr-user strong{color:var(--text)}
.btn-salir{
  background:none;border:1px solid #444;border-radius:6px;
  padding:3px 8px;color:var(--muted);font-size:11px;cursor:pointer;
}
.main{flex:1;overflow-y:auto;padding:14px 14px 0}
.search-box{
  display:flex;
  gap:6px;
  margin-bottom:12px;
}
#q{
  flex:1;
  padding:12px 14px;
  border-radius:10px;
  border:2px solid var(--border);
  background:var(--surface2);
  color:#fff;
  font-size:15px;
}
#q:focus{outline:none;border-color:var(--accent)}
.resultados{
  display:flex;
  flex-direction:column;
  gap:6px;
  margin-bottom:16px;
}
.res-item{
  display:flex;
  align-items:center;
  justify-content:space-between;
  gap:10px;
  background:var(--surface2);
  border:1px solid var(--border);
  border-radius:10px;
  padding:10px 12px;
}
.res-nombre{font-size:14px;flex:1}
.res-add{
  background:var(--green);
  border:none;
  color:#fff;
  border-radius:8px;
  padding:8px 14px;
  font-weight:700;
  cursor:pointer;
}
.sin-resultados{color:var(--muted);font-size:13px;padding:8px 2px}
.cart-hdr{
  font-family:'Barlow Condensed',sans-serif;
  font-size:14px;
  font-weight:700;
  letter-spacing:2px;
  text-transform:uppercase;
  color:var(--muted);
  margin:6px 0 8px;
}
.cart-list{display:flex;flex-direction:column;gap:6px;margin-bottom:100px}
.cart-empty{color:var(--muted);font-size:13px;padding:10px 2px}
.ci{
  display:flex;
  align-items:center;
  gap:8px;
  background:var(--surface2);
  border:1px solid var(--border);
  border-radius:10px;
  padding:8px 10px;
}
.ci-name{flex:1;font-size:14px}
.ci-qty-ctrl{display:flex;align-items:center;gap:6px}
.ci-qty-ctrl button{
  width:30px;height:30px;
  border-radius:8px;
  border:1px solid var(--border);
  background:var(--surface);
  color:var(--text);
  font-size:16px;
  cursor:pointer;
}
.ci-qty{width:36px;text-align:center;font-weight:700}
.ci-del{background:none;border:none;color:var(--red);font-size:18px;cursor:pointer;padding:0 4px}
.footer{
  position:fixed;
  left:0;right:0;bottom:0;
  background:#111;
  border-top:1px solid var(--border);
  padding:12px 14px;
  display:flex;
  align-items:center;
  gap:10px;
}
.footer-total{flex:1;font-family:'Barlow Condensed',sans-serif;font-size:14px;color:var(--muted)}
.footer-total strong{color:#fff;font-size:18px}
.btn-enviar{
  background:var(--accent);
  border:none;
  color:#111;
  border-radius:10px;
  padding:13px 22px;
  font-family:'Barlow Condensed',sans-serif;
  font-size:16px;
  font-weight:900;
  text-transform:uppercase;
  cursor:pointer;
}
.btn-enviar:disabled{background:#444;color:#777;cursor:not-allowed}
.overlay{
  display:none;
  position:fixed;inset:0;
  background:rgba(0,0,0,.8);
  align-items:center;justify-content:center;
  z-index:100;
  padding:20px;
}
.overlay.show{display:flex}
.ok-card{
  background:var(--surface);
  border-radius:14px;
  padding:28px 24px;
  text-align:center;
  max-width:320px;
}
.ok-card h2{font-family:'Barlow Condensed',sans-serif;font-size:22px;margin-bottom:8px}
.ok-card p{color:var(--muted);font-size:14px;margin-bottom:20px}
.ok-card button{
  background:var(--green);border:none;color:#fff;border-radius:10px;
  padding:12px 20px;font-weight:700;cursor:pointer;width:100%;
}
.toast{
  position:fixed;bottom:80px;left:50%;
  transform:translateX(-50%) translateY(60px);
  background:var(--green);color:#fff;padding:10px 22px;border-radius:30px;
  font-size:14px;font-weight:700;transition:transform .3s;z-index:200;pointer-events:none;
}
.toast.err{background:var(--red)}
.toast.show{transform:translateX(-50%) translateY(0)}
</style>
</head>
<body>

<div class="hdr">
  <div class="hdr-title">Hacer pedido</div>
  <div class="hdr-user">
    Hola, <strong><?= $cliente_nombre ?></strong><br>
    <button class="btn-salir" onclick="location.href='preventa_salir.php'">Salir</button>
  </div>
</div>

<div class="main">
  <div class="search-box">
    <input type="text" id="q" placeholder="Buscar producto..." autocomplete="off">
  </div>
  <div class="resultados" id="resultados"></div>

  <div class="cart-hdr">Tu pedido</div>
  <div class="cart-list" id="cart-list">
    <div class="cart-empty">Aún no agregaste productos</div>
  </div>
</div>

<div class="footer">
  <div class="footer-total"><strong id="cart-count">0</strong> producto(s)</div>
  <button class="btn-enviar" id="btn-enviar" disabled onclick="enviarPedido()">Enviar pedido</button>
</div>

<div class="overlay" id="overlay-ok">
  <div class="ok-card">
    <h2>✓ Pedido enviado</h2>
    <p>Recibimos tu pedido. Te avisaremos cuando esté listo.</p>
    <button onclick="location.reload()">Hacer otro pedido</button>
  </div>
</div>

<div class="toast" id="toast"></div>

<script>
let cart = []; // {id, nombre, cantidad}

const qInput = document.getElementById('q');
let searchTO;
qInput.addEventListener('input', () => {
  clearTimeout(searchTO);
  const val = qInput.value.trim();
  if (val.length < 2) { renderResultados([]); return; }
  searchTO = setTimeout(() => buscar(val), 250);
});

function buscar(q) {
  fetch('inc/buscar_producto_preventa.php?q=' + encodeURIComponent(q))
    .then(r => r.json())
    .then(data => {
      if (!data.ok) { toast(data.mensaje || 'Sesión expirada', 'err'); setTimeout(() => location.href = 'preventa_login.php', 1500); return; }
      renderResultados(data.productos);
    })
    .catch(() => toast('Error de conexión', 'err'));
}

function renderResultados(productos) {
  const cont = document.getElementById('resultados');
  if (!productos.length) {
    cont.innerHTML = qInput.value.trim().length >= 2 ? '<div class="sin-resultados">Sin resultados</div>' : '';
    return;
  }
  cont.innerHTML = productos.map(p => `
    <div class="res-item">
      <div class="res-nombre">${escHtml(p.nom_prod)}</div>
      <button class="res-add" onclick="agregar(${p.id_producto}, '${escAttr(p.nom_prod)}')">Agregar</button>
    </div>
  `).join('');
}

function agregar(id, nombre) {
  const existente = cart.find(i => i.id === id);
  if (existente) existente.cantidad += 1;
  else cart.push({ id, nombre, cantidad: 1 });
  renderCart();
  toast('Agregado ✓');
}

function cambiarCantidad(id, delta) {
  const item = cart.find(i => i.id === id);
  if (!item) return;
  item.cantidad += delta;
  if (item.cantidad <= 0) cart = cart.filter(i => i.id !== id);
  renderCart();
}

function quitar(id) {
  cart = cart.filter(i => i.id !== id);
  renderCart();
}

function renderCart() {
  const list = document.getElementById('cart-list');
  if (!cart.length) {
    list.innerHTML = '<div class="cart-empty">Aún no agregaste productos</div>';
  } else {
    list.innerHTML = cart.map(i => `
      <div class="ci">
        <div class="ci-name">${escHtml(i.nombre)}</div>
        <div class="ci-qty-ctrl">
          <button onclick="cambiarCantidad(${i.id}, -1)">−</button>
          <div class="ci-qty">${i.cantidad}</div>
          <button onclick="cambiarCantidad(${i.id}, 1)">+</button>
        </div>
        <button class="ci-del" onclick="quitar(${i.id})">✕</button>
      </div>
    `).join('');
  }
  document.getElementById('cart-count').textContent = cart.reduce((s, i) => s + i.cantidad, 0);
  document.getElementById('btn-enviar').disabled = !cart.length;
}

function enviarPedido() {
  if (!cart.length) return;
  const btn = document.getElementById('btn-enviar');
  btn.disabled = true;
  btn.textContent = 'Enviando...';

  const body = new URLSearchParams();
  cart.forEach(i => {
    body.append('producto_id[]', i.id);
    body.append('cantidad[]', i.cantidad);
  });

  fetch('inc/registrar_preventa.php', { method: 'POST', body })
    .then(r => r.json())
    .then(data => {
      if (data.ok) {
        document.getElementById('overlay-ok').classList.add('show');
      } else {
        toast(data.mensaje || 'No se pudo enviar el pedido', 'err');
        if (/sesión|sesion/i.test(data.mensaje || '')) setTimeout(() => location.href = 'preventa_login.php', 1500);
      }
    })
    .catch(() => toast('Error de conexión', 'err'))
    .finally(() => {
      btn.textContent = 'Enviar pedido';
      btn.disabled = !cart.length;
    });
}

function escHtml(s) {
  return String(s).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
}
function escAttr(s) {
  return escHtml(s).replace(/'/g, '&#39;');
}

let _toastTO;
function toast(msg, type = 'ok') {
  const el = document.getElementById('toast');
  el.textContent = msg;
  el.className = 'toast' + (type === 'err' ? ' err' : '');
  el.classList.add('show');
  clearTimeout(_toastTO);
  _toastTO = setTimeout(() => el.classList.remove('show'), 2000);
}
</script>
</body>
</html>
