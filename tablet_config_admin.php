<?php
include('inc/control.php');
if ($_SESSION['type'] !== 'admin') header("Location: dashboard.php");
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Configuración Tablet – Renacer</title>
<link rel="stylesheet" href="assets/css/bootstrap.min.css">
<style>
body { background:#f4f6f9; }
.page-header { margin-top:10px; }
.tab-pill {
  display:inline-block; padding:8px 18px; border-radius:20px; cursor:pointer;
  font-weight:700; font-size:14px; margin:4px; border:2px solid transparent;
  transition:.2s;
}
.tab-pill.active { border-color:#fff; box-shadow:0 2px 8px rgba(0,0,0,.3); }
.group-panel { margin-bottom:10px; }
.group-panel .panel-heading { cursor:pointer; }
.chip {
  display:inline-flex; align-items:center; background:#e8f4fd; border:1px solid #b8d9f0;
  border-radius:12px; padding:3px 10px; margin:3px; font-size:13px;
}
.chip .remove { margin-left:6px; color:#c0392b; cursor:pointer; font-weight:700; line-height:1; }
.prod-row { display:flex; align-items:center; padding:6px 0; border-bottom:1px solid #eee; }
.prod-row:last-child { border:none; }
.prod-name { flex:1; font-size:13px; }
.prod-variants { font-size:12px; color:#888; margin:0 10px; }
.badge-all { background:#27ae60; color:#fff; }
.badge-specific { background:#e67e22; color:#fff; }
.section-title { font-size:11px; font-weight:700; text-transform:uppercase;
  color:#888; letter-spacing:1px; margin:10px 0 6px; }
#tab-panel-area { min-height:400px; }
.panel-body-inner { padding:10px 15px; }
</style>
</head>
<body>
<?php include('inc/control.php'); menu(''); ?>

<div class="container-fluid" style="max-width:1100px;margin-top:70px">
  <div class="page-header">
    <h3><img src="assets/img/config_tablet.svg" style="width:32px;vertical-align:middle;margin-right:8px">
      Configuración Tablet POS</h3>
  </div>

  <div class="row">
    <!-- Pestañas -->
    <div class="col-md-3">
      <div class="panel panel-default">
        <div class="panel-heading"><b>Pestañas</b></div>
        <div class="panel-body" id="tabs-list" style="padding:10px">
          <p class="text-muted text-center"><small>Cargando...</small></p>
        </div>
      </div>
    </div>

    <!-- Editor de grupos -->
    <div class="col-md-9">
      <div class="panel panel-default">
        <div class="panel-heading" id="groups-title"><b>Selecciona una pestaña</b></div>
        <div class="panel-body" id="tab-panel-area">
          <p class="text-muted text-center" style="margin-top:40px">← Elige una pestaña para ver sus grupos</p>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Modal: Agregar Producto -->
<div class="modal fade" id="modalProduct" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal">&times;</button>
        <h4 class="modal-title">Agregar Producto al Grupo</h4>
      </div>
      <div class="modal-body">
        <div class="input-group" style="margin-bottom:10px">
          <input type="text" id="prod-search" class="form-control" placeholder="Buscar producto...">
          <span class="input-group-btn">
            <button class="btn btn-default" onclick="searchProducts()">Buscar</button>
          </span>
        </div>
        <div id="search-results" style="max-height:180px;overflow-y:auto;border:1px solid #ddd;border-radius:4px;display:none"></div>

        <div id="selected-product-area" style="display:none;margin-top:12px">
          <div class="alert alert-info" style="padding:8px 12px;margin-bottom:10px">
            <b id="sel-prod-name"></b>
          </div>
          <div class="checkbox" style="margin:0 0 8px">
            <label><input type="checkbox" id="chk-all-variants" checked onchange="toggleVariants()">
              <b>Todas las presentaciones</b>
            </label>
          </div>
          <div id="variants-list" style="display:none;padding-left:10px"></div>
        </div>
      </div>
      <div class="modal-footer">
        <button class="btn btn-default" data-dismiss="modal">Cancelar</button>
        <button class="btn btn-success" id="btn-add-prod" onclick="confirmAddProduct()" disabled>
          <span class="glyphicon glyphicon-plus"></span> Agregar
        </button>
      </div>
    </div>
  </div>
</div>

<!-- Modal: Agregar Categoría -->
<div class="modal fade" id="modalCategory" tabindex="-1">
  <div class="modal-dialog modal-sm">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal">&times;</button>
        <h4 class="modal-title">Agregar Categoría</h4>
      </div>
      <div class="modal-body">
        <select id="cat-select" class="form-control" style="width:100%">
          <option value="">Cargando...</option>
        </select>
      </div>
      <div class="modal-footer">
        <button class="btn btn-default" data-dismiss="modal">Cancelar</button>
        <button class="btn btn-success" onclick="confirmAddCategory()">Agregar</button>
      </div>
    </div>
  </div>
</div>

<!-- Modal: Nuevo Grupo -->
<div class="modal fade" id="modalNewGroup" tabindex="-1">
  <div class="modal-dialog modal-sm">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal">&times;</button>
        <h4 class="modal-title" id="modal-group-title">Nuevo Grupo</h4>
      </div>
      <div class="modal-body">
        <input type="text" id="new-group-name" class="form-control" placeholder="Nombre del grupo">
        <input type="hidden" id="edit-group-id" value="">
      </div>
      <div class="modal-footer">
        <button class="btn btn-default" data-dismiss="modal">Cancelar</button>
        <button class="btn btn-primary" onclick="confirmSaveGroup()">Guardar</button>
      </div>
    </div>
  </div>
</div>

<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.3.1/jquery.min.js"></script>
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js"></script>
<script>
const API = '/inc/tablet_config_api.php';
let config      = [];
let activeTabId = null;
let activeGroupId = null;
let selProduct  = null;

// ── Cargar config ──────────────────────────────────────────
function loadConfig() {
  $.get(API + '?action=get_config', function(d) {
    if (!d.ok) return;
    config = d.tabs;
    renderTabList();
    if (config.length) selectTab(config[0].id);
  });
}

function renderTabList() {
  let html = '';
  config.forEach(tab => {
    const active = tab.id === activeTabId ? 'active' : '';
    html += `<div class="tab-pill ${active}" style="background:${tab.color_accent};color:#fff"
               onclick="selectTab(${tab.id})">
               ${tab.icon} ${tab.label}
             </div>`;
  });
  $('#tabs-list').html(html || '<p class="text-muted">Sin pestañas</p>');
}

function selectTab(tabId) {
  activeTabId = tabId;
  const tab = config.find(t => t.id == tabId);
  if (!tab) return;
  renderTabList();
  $('#groups-title').html(`<b>${tab.icon} ${tab.label}</b>
    <button class="btn btn-xs btn-success pull-right" onclick="openNewGroup()">
      <span class="glyphicon glyphicon-plus"></span> Nuevo grupo
    </button>`);
  renderGroups(tab);
}

// ── Render grupos ──────────────────────────────────────────
function renderGroups(tab) {
  if (!tab.groups.length) {
    $('#tab-panel-area').html('<p class="text-muted text-center" style="margin-top:40px">Sin grupos. Crea el primero.</p>');
    return;
  }
  let html = '<div class="panel-group" id="accordion">';
  tab.groups.forEach((g, idx) => {
    html += `
    <div class="panel panel-default group-panel" id="group-panel-${g.id}">
      <div class="panel-heading" data-toggle="collapse" data-target="#gc-${g.id}">
        <div style="display:flex;align-items:center">
          <span class="panel-title" style="flex:1;font-weight:700">${g.label}</span>
          <button class="btn btn-xs btn-default" onclick="event.stopPropagation();openRenameGroup(${g.id},'${esc(g.label)}')">
            <span class="glyphicon glyphicon-pencil"></span>
          </button>
          <button class="btn btn-xs btn-danger" style="margin-left:4px" onclick="event.stopPropagation();deleteGroup(${g.id})">
            <span class="glyphicon glyphicon-trash"></span>
          </button>
        </div>
      </div>
      <div id="gc-${g.id}" class="panel-collapse collapse ${idx===0?'in':''}">
        <div class="panel-body-inner">
          ${renderGroupBody(g)}
        </div>
      </div>
    </div>`;
  });
  html += '</div>';
  $('#tab-panel-area').html(html);
}

function renderGroupBody(g) {
  // Categorías
  let catChips = g.categories.map(c =>
    `<span class="chip">${c.nom_cat || 'Cat #'+c.category_id}
       <span class="remove" onclick="removeCategory(${g.id},${c.category_id})">×</span>
     </span>`).join('');

  // Productos
  let prodRows = g.products.map(p => {
    const varBadge = p.all_variants
      ? `<span class="badge badge-all">Todas</span>`
      : `<span class="badge badge-specific">${p.variants.map(v=>v.variante).join(', ')}</span>`;
    return `<div class="prod-row">
      <span class="prod-name"><b>${p.nom_prod || 'Prod #'+p.product_id}</b></span>
      <span class="prod-variants">${varBadge}</span>
      <button class="btn btn-xs btn-danger" onclick="removeProduct(${p.gp_id})">
        <span class="glyphicon glyphicon-remove"></span>
      </button>
    </div>`;
  }).join('');

  return `
    <div class="section-title">Categorías</div>
    <div id="cats-${g.id}">
      ${catChips || '<span class="text-muted" style="font-size:12px">Sin categorías</span>'}
    </div>
    <button class="btn btn-xs btn-default" style="margin-top:6px" onclick="openAddCategory(${g.id})">
      <span class="glyphicon glyphicon-plus"></span> Categoría
    </button>

    <div class="section-title" style="margin-top:14px">Productos</div>
    <div id="prods-${g.id}">
      ${prodRows || '<p class="text-muted" style="font-size:12px">Sin productos</p>'}
    </div>
    <button class="btn btn-xs btn-default" style="margin-top:6px" onclick="openAddProduct(${g.id})">
      <span class="glyphicon glyphicon-plus"></span> Producto
    </button>`;
}

// ── Grupos CRUD ────────────────────────────────────────────
function openNewGroup() {
  $('#modal-group-title').text('Nuevo Grupo');
  $('#new-group-name').val('');
  $('#edit-group-id').val('');
  $('#modalNewGroup').modal('show');
}

function openRenameGroup(gid, label) {
  $('#modal-group-title').text('Renombrar Grupo');
  $('#new-group-name').val(label);
  $('#edit-group-id').val(gid);
  $('#modalNewGroup').modal('show');
}

function confirmSaveGroup() {
  const name = $('#new-group-name').val().trim();
  const gid  = $('#edit-group-id').val();
  if (!name) return;
  if (gid) {
    $.post(API + '?action=rename_group', {group_id:gid, label:name}, function(d) {
      if (d.ok) { $('#modalNewGroup').modal('hide'); loadConfig(); }
    });
  } else {
    $.post(API + '?action=add_group', {tab_id:activeTabId, label:name}, function(d) {
      if (d.ok) { $('#modalNewGroup').modal('hide'); loadConfig(); }
    });
  }
}

function deleteGroup(gid) {
  if (!confirm('¿Eliminar este grupo y todos sus productos?')) return;
  $.post(API + '?action=delete_group', {group_id:gid}, function(d) {
    if (d.ok) loadConfig();
  });
}

// ── Categorías ─────────────────────────────────────────────
function openAddCategory(gid) {
  activeGroupId = gid;
  $.get(API + '?action=get_categories', function(d) {
    let opts = d.categories.map(c =>
      `<option value="${c.id_categoria}">${c.nom_cat}</option>`).join('');
    $('#cat-select').html(opts);
    $('#modalCategory').modal('show');
  });
}

function confirmAddCategory() {
  const cid = $('#cat-select').val();
  $.post(API + '?action=add_category', {group_id:activeGroupId, category_id:cid}, function(d) {
    if (d.ok) { $('#modalCategory').modal('hide'); loadConfig(); }
  });
}

function removeCategory(gid, cid) {
  $.post(API + '?action=remove_category', {group_id:gid, category_id:cid}, function(d) {
    if (d.ok) loadConfig();
  });
}

// ── Productos ──────────────────────────────────────────────
function openAddProduct(gid) {
  activeGroupId = gid;
  selProduct = null;
  $('#prod-search').val('');
  $('#search-results').hide().html('');
  $('#selected-product-area').hide();
  $('#chk-all-variants').prop('checked', true);
  $('#variants-list').hide().html('');
  $('#btn-add-prod').prop('disabled', true);
  $('#modalProduct').modal('show');
}

function searchProducts() {
  const q = $('#prod-search').val().trim();
  if (q.length < 2) return;
  $.get(API + '?action=search_products&q=' + encodeURIComponent(q), function(d) {
    let html = d.products.map(p =>
      `<div class="list-group-item" style="cursor:pointer;padding:8px 12px;font-size:13px"
            onclick="selectProduct(${p.id_producto},'${esc(p.nom_prod)}')">
         ${p.nom_prod}
       </div>`).join('');
    $('#search-results').html(html || '<div style="padding:8px;color:#888">Sin resultados</div>').show();
  });
}

function selectProduct(pid, name) {
  selProduct = {id: pid, name: name};
  $('#search-results').hide();
  $('#sel-prod-name').text(name);
  $('#selected-product-area').show();
  $('#chk-all-variants').prop('checked', true);
  $('#variants-list').hide().html('');
  $('#btn-add-prod').prop('disabled', false);

  // Cargar variantes disponibles
  $.get(API + '?action=get_product_variants&product_id=' + pid, function(d) {
    if (!d.variants.length) return;
    let html = d.variants.map(v =>
      `<div class="checkbox" style="margin:2px 0">
         <label><input type="checkbox" class="vt-check" value="${v.id_variante}"> ${v.variante} (${v.cantidad_vp})</label>
       </div>`).join('');
    $('#variants-list').html(html);
  });
}

function toggleVariants() {
  const all = $('#chk-all-variants').is(':checked');
  $('#variants-list').toggle(!all);
}

function confirmAddProduct() {
  if (!selProduct) return;
  const allV    = $('#chk-all-variants').is(':checked') ? 1 : 0;
  const varIds  = [];
  if (!allV) {
    $('.vt-check:checked').each(function() { varIds.push($(this).val()); });
    if (!varIds.length) { alert('Selecciona al menos una presentación'); return; }
  }
  $.post(API + '?action=add_product', {
    group_id    : activeGroupId,
    product_id  : selProduct.id,
    all_variants: allV,
    variant_ids : JSON.stringify(varIds),
  }, function(d) {
    if (d.ok) { $('#modalProduct').modal('hide'); loadConfig(); }
  });
}

function removeProduct(gpId) {
  $.post(API + '?action=remove_product', {gp_id: gpId}, function(d) {
    if (d.ok) loadConfig();
  });
}

// ── Enter en búsqueda ──────────────────────────────────────
$('#prod-search').on('keyup', function(e) {
  if (e.key === 'Enter') searchProducts();
});

function esc(s) { return String(s).replace(/'/g, "\\'"); }

// ── Init ───────────────────────────────────────────────────
loadConfig();
</script>
</body>
</html>
