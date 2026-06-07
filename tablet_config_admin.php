<?php
include('inc/control.php');
if ($_SESSION['type'] !== 'admin') { header("Location: dashboard.php"); exit; }
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Configuración Tablet – Renacer</title>
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" type="text/css" href="/assets/css/bootstrap.min.css">
    <link rel="stylesheet" type="text/css" href="/assets/css/custom.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
<style>
.tab-pill {
    display:inline-block; padding:8px 18px; border-radius:20px; cursor:pointer;
    font-weight:700; font-size:14px; margin:4px; border:2px solid rgba(255,255,255,.4);
    transition:.2s; color:#fff;
}
.tab-pill.active { border-color:#fff; box-shadow:0 2px 8px rgba(0,0,0,.3); }
.chip {
    display:inline-flex; align-items:center; background:#e8f4fd; border:1px solid #b8d9f0;
    border-radius:12px; padding:3px 10px; margin:3px; font-size:13px;
}
.chip .remove { margin-left:6px; color:#c0392b; cursor:pointer; font-weight:700; }
.prod-row { display:flex; align-items:center; padding:7px 0; border-bottom:1px solid #eee; }
.prod-row:last-child { border:none; }
.prod-name { flex:1; font-size:13px; }
.section-lbl { font-size:11px; font-weight:700; text-transform:uppercase;
    color:#999; letter-spacing:1px; margin:12px 0 5px; }
.badge-all  { background:#27ae60; color:#fff; }
.badge-spec { background:transparent !important; color:#fff !important; }

/* Forzar estilos claros en los paneles de grupos */
#tab-panel-area .panel-default { background:#fff; }
#tab-panel-area .panel-default > .panel-heading {
    background:transparent;
    color:#333 !important;
    border-color:#ddd !important;
}
#tab-panel-area .panel-default > .panel-heading:hover {
    background:#e8e8e8 !important;
}
#tab-panel-area .panel-body {
    background:#fff !important;
    color:#333 !important;
}
#tab-panel-area .prod-name { color:#333 !important; }
#tab-panel-area .text-muted { color:#888 !important; }
#tab-panel-area .section-lbl { color:#999 !important; }
</style>
</head>
<body class="mobile dashboard">
<div class="">
    <nav class="navbar navbar-inverse navbar-fixed-top">
        <div class="">
            <div class="navbar-header">
                <button type="button" class="navbar-toggle collapsed" data-toggle="collapse"
                    data-target="#navbar" aria-expanded="false" aria-controls="navbar">
                    <span class="sr-only">Toggle navigation</span>
                    <span class="icon-bar"></span><span class="icon-bar"></span><span class="icon-bar"></span>
                </button>
                <a class="navbar-brand" href="#"><img class="img-responsive logo" src="/assets/img/harlec-sistema.png"></a>
            </div>
            <?php menu('8'); ?>
        </div>
    </nav>

    <div class="container-fluid" style="margin-top:70px;max-width:1100px">
        <div class="page-header">
            <h3>
                <img src="/assets/img/config_tablet.svg" style="width:28px;vertical-align:middle;margin-right:8px">
                Configuración Tablet POS
            </h3>
        </div>

        <div id="alert-area"></div>

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

            <!-- Editor grupos -->
            <div class="col-md-9">
                <div class="panel panel-default">
                    <div class="panel-heading" id="groups-title">
                        <b>Selecciona una pestaña</b>
                    </div>
                    <div class="panel-body" id="tab-panel-area" style="min-height:300px">
                        <p class="text-muted text-center" style="padding-top:40px">
                            ← Elige una pestaña para ver sus grupos
                        </p>
                    </div>
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
                <h4 class="modal-title" id="modal-prod-title">Agregar Producto al Grupo</h4>
            </div>
            <div class="modal-body">
                <div class="input-group" style="margin-bottom:10px">
                    <input type="text" id="prod-search" class="form-control" placeholder="Buscar producto por nombre...">
                    <span class="input-group-btn">
                        <button class="btn btn-default" onclick="searchProducts()">
                            <span class="glyphicon glyphicon-search"></span>
                        </button>
                    </span>
                </div>
                <div id="search-results" style="max-height:160px;overflow-y:auto;border:1px solid #ddd;border-radius:4px;display:none"></div>
                <div id="selected-product-area" style="display:none;margin-top:12px">
                    <div class="alert alert-info" style="padding:8px 12px;margin-bottom:8px">
                        Producto: <b id="sel-prod-name"></b>
                    </div>
                    <p class="text-muted" style="font-size:12px;margin-bottom:4px">
                        Presentaciones disponibles — marca las que quieres incluir:
                    </p>
                    <div style="margin-bottom:6px">
                        <label style="font-size:12px;cursor:pointer">
                            <input type="checkbox" id="chk-all-variants" checked onchange="toggleAllVariants()">
                            <b>Seleccionar todas</b>
                        </label>
                    </div>
                    <div id="variants-list" style="padding-left:10px;max-height:160px;overflow-y:auto;border:1px solid #ddd;border-radius:4px;padding:8px">
                        <p class="text-muted" style="font-size:12px;margin:0">Cargando presentaciones...</p>
                    </div>
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
                <select id="cat-select" class="form-control"></select>
            </div>
            <div class="modal-footer">
                <button class="btn btn-default" data-dismiss="modal">Cancelar</button>
                <button class="btn btn-success" onclick="confirmAddCategory()">Agregar</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Nuevo / Renombrar Grupo -->
<div class="modal fade" id="modalGroup" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title" id="modal-group-title">Nuevo Grupo</h4>
            </div>
            <div class="modal-body">
                <input type="text" id="group-name-input" class="form-control" placeholder="Nombre del grupo">
                <input type="hidden" id="edit-group-id">
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
let config = [], activeTabId = null, activeGroupId = null, selProduct = null;

// ── Carga inicial ──────────────────────────────────────────
function loadConfig() {
    $.get(API + '?action=get_config')
     .done(function(d) {
        if (!d.ok) { showAlert('Error: ' + (d.msg || 'Verifica que ejecutaste la migración'), 'danger'); return; }
        config = d.tabs;
        renderTabList();
        if (config.length) selectTab(config[0].id);
     })
     .fail(function(xhr) {
        showAlert('Error del servidor (' + xhr.status + '): ' + xhr.responseText.substring(0,200), 'danger');
     });
}

function renderIcon(icon) {
    if (!icon) return '📦';
    // FA class → glyphicon fallback ya que CDN no carga
    if (icon === 'fas fa-egg')      return '🥚';
    if (icon === 'fas fa-utensils') return '🍴';
    if (icon.startsWith('fa'))      return '📦';
    return icon;
}
function renderTabList() {
    let html = '';
    config.forEach(t => {
        const active = t.id === activeTabId ? 'active' : '';
        html += `<div class="tab-pill ${active}" style="background:${t.color_accent}"
                      onclick="selectTab(${t.id})">${renderIcon(t.icon)} ${t.label}</div>`;
    });
    $('#tabs-list').html(html || '<p class="text-muted">Sin pestañas</p>');
}

function selectTab(tabId) {
    activeTabId = tabId;
    const tab = config.find(t => t.id == tabId);
    if (!tab) return;
    renderTabList();
    $('#groups-title').html(`<b style="font-size:16px">${renderIcon(tab.icon)} ${tab.label}</b>
        <button class="btn btn-xs btn-success pull-right" onclick="openNewGroup()">
            <span class="glyphicon glyphicon-plus"></span> Nuevo grupo
        </button>`);
    renderGroups(tab);
}

// ── Render grupos ──────────────────────────────────────────
function renderGroups(tab) {
    if (!tab.groups.length) {
        $('#tab-panel-area').html('<p class="text-muted text-center" style="padding-top:40px">Sin grupos aún.</p>');
        return;
    }
    const accent = tab.color_accent || '#555';
    let html = '<div class="panel-group" id="accordion">';
    tab.groups.forEach((g, idx) => {
        html += `
        <div class="panel panel-default" style="border-color:${accent}30">
            <div class="panel-heading" data-toggle="collapse" data-target="#gc-${g.id}"
                 style="cursor:pointer;background:${accent};border-color:${accent}">
                <div style="display:flex;align-items:center;gap:6px">
                    <span style="flex:1;font-weight:700;color:#fff;font-size:14px">${g.label}</span>
                    <button class="btn btn-xs" style="background:rgba(255,255,255,.25);color:#fff;border:none"
                        onclick="event.stopPropagation();openRenameGroup(${g.id},'${esc(g.label)}')">
                        ✏️
                    </button>
                    <button class="btn btn-xs" style="background:rgba(255,0,0,.35);color:#fff;border:none"
                        onclick="event.stopPropagation();deleteGroup(${g.id})">
                        🗑️
                    </button>
                </div>
            </div>
            <div id="gc-${g.id}" class="panel-collapse collapse ${idx===0?'in':''}">
                <div class="panel-body" style="background:#fff">${renderGroupBody(g)}</div>
            </div>
        </div>`;
    });
    html += '</div>';
    $('#tab-panel-area').html(html);
}

function renderGroupBody(g) {
    const catChips = g.categories.length
        ? g.categories.map(c => `<span class="chip">${c.nom_cat || 'Cat #'+c.category_id}
            <span class="remove" onclick="removeCategory(${g.id},${c.category_id})">×</span></span>`).join('')
        : '<span class="text-muted" style="font-size:12px">Sin categorías</span>';

    const prodRows = g.products.length
        ? g.products.map(p => {
            const badge = p.all_variants
                ? `<span class="badge badge-all">Todas</span>`
                : `<span class="badge badge-spec">${p.variants.map(v=>v.variante).join(', ')}</span>`;
            return `<div class="prod-row">
                <span class="text-muted" style="font-size:11px;min-width:36px">#${p.product_id}</span>
                <span class="prod-name" style="margin-left:6px">${p.nom_prod || 'Prod #'+p.product_id}</span>
                <span style="margin:0 8px">${badge}</span>
                <button class="btn btn-xs btn-default" style="margin-right:3px"
                    onclick="openEditProduct(${p.gp_id},${g.id},${p.product_id},'${esc(p.nom_prod)}',${p.all_variants},[${p.variants.map(v=>v.variant_type_id).join(',')}])">
                    <span class="glyphicon glyphicon-pencil"></span>
                </button>
                <button class="btn btn-xs btn-danger" onclick="removeProduct(${p.gp_id})">
                    <span class="glyphicon glyphicon-remove"></span>
                </button>
            </div>`;
          }).join('')
        : '<p class="text-muted" style="font-size:12px;margin:0">Sin productos</p>';

    return `
        <div class="section-lbl">Categorías</div>
        ${catChips}
        <br>
        <button class="btn btn-xs btn-default" style="margin-top:6px" onclick="openAddCategory(${g.id})">
            <span class="glyphicon glyphicon-plus"></span> Agregar categoría
        </button>
        <div class="section-lbl" style="margin-top:14px">Productos</div>
        ${prodRows}
        <button class="btn btn-xs btn-default" style="margin-top:6px" onclick="openAddProduct(${g.id})">
            <span class="glyphicon glyphicon-plus"></span> Agregar producto
        </button>`;
}

// ── Grupos ─────────────────────────────────────────────────
function openNewGroup() {
    $('#modal-group-title').text('Nuevo Grupo');
    $('#group-name-input').val('');
    $('#edit-group-id').val('');
    $('#modalGroup').modal('show');
}
function openRenameGroup(gid, label) {
    $('#modal-group-title').text('Renombrar Grupo');
    $('#group-name-input').val(label);
    $('#edit-group-id').val(gid);
    $('#modalGroup').modal('show');
}
function confirmSaveGroup() {
    const name = $('#group-name-input').val().trim();
    const gid  = $('#edit-group-id').val();
    if (!name) return;
    const action = gid ? 'rename_group' : 'add_group';
    const data   = gid ? {group_id:gid, label:name} : {tab_id:activeTabId, label:name};
    $.post(API + '?action=' + action, data, d => { if (d.ok) { $('#modalGroup').modal('hide'); loadConfig(); } });
}
function deleteGroup(gid) {
    if (!confirm('¿Eliminar este grupo y todos sus productos?')) return;
    $.post(API + '?action=delete_group', {group_id:gid}, d => { if (d.ok) loadConfig(); });
}

// ── Categorías ─────────────────────────────────────────────
function openAddCategory(gid) {
    activeGroupId = gid;
    $.get(API + '?action=get_categories', d => {
        $('#cat-select').html(d.categories.map(c =>
            `<option value="${c.id_categoria}">${c.nom_cat}</option>`).join(''));
        $('#modalCategory').modal('show');
    });
}
function confirmAddCategory() {
    $.post(API + '?action=add_category', {group_id:activeGroupId, category_id:$('#cat-select').val()},
        d => { if (d.ok) { $('#modalCategory').modal('hide'); loadConfig(); } });
}
function removeCategory(gid, cid) {
    $.post(API + '?action=remove_category', {group_id:gid, category_id:cid}, d => { if (d.ok) loadConfig(); });
}

// ── Productos ──────────────────────────────────────────────
let editingGpId = null;

function openAddProduct(gid) {
    activeGroupId = gid; selProduct = null; editingGpId = null;
    $('#modal-prod-title').text('Agregar Producto al Grupo');
    $('#btn-add-prod').text('Agregar').prop('disabled', true);
    $('#prod-search').val('').closest('.input-group').show();
    $('#search-results').hide().html('');
    $('#selected-product-area').hide();
    $('#chk-all-variants').prop('checked', true);
    $('#variants-list').html('');
    $('#modalProduct').modal('show');
}

function openEditProduct(gpId, gid, pid, name, allVariants, currentVarIds) {
    editingGpId = gpId; activeGroupId = gid; selProduct = {id:pid, name:name};
    $('#modal-prod-title').text('Editar Presentaciones');
    $('#btn-add-prod').text('Guardar cambios').prop('disabled', false);
    $('#prod-search').closest('.input-group').hide();
    $('#search-results').hide().html('');
    $('#sel-prod-name').text('#' + pid + ' ' + name);
    $('#selected-product-area').show();
    $('#variants-list').html('<p class="text-muted" style="font-size:12px;margin:0">Cargando...</p>');
    $('#modalProduct').modal('show');

    $.get(API + '?action=get_product_variants&product_id=' + pid)
     .done(d => {
        if (!d.ok || !d.variants || !d.variants.length) {
            $('#variants-list').html('<p class="text-muted" style="font-size:12px;margin:0">Sin presentaciones registradas</p>');
            $('#chk-all-variants').prop('checked', true);
            return;
        }
        const html = d.variants.map(v => {
            const chk = allVariants || currentVarIds.includes(parseInt(v.id_variante));
            return `<div class="checkbox" style="margin:2px 0">
                <label><input type="checkbox" class="vt-check" value="${v.id_variante}" ${chk?'checked':''}>
                <b>${v.variante}</b> <small class="text-muted">${v.cantidad_vp} unid.</small></label>
            </div>`;
        }).join('');
        $('#variants-list').html(html);
        $('#chk-all-variants').prop('checked', allVariants == 1);
     });
}
function searchProducts() {
    const q = $('#prod-search').val().trim();
    if (q.length < 2) return;
    $.get(API + '?action=search_products&q=' + encodeURIComponent(q), d => {
        const html = d.products.map(p =>
            `<a class="list-group-item" style="cursor:pointer;padding:7px 12px;font-size:13px"
                onclick="selectProduct(${p.id_producto},'${esc(p.nom_prod)}')">
                <span class="text-muted" style="font-size:11px;margin-right:6px">#${p.id_producto}</span>${p.nom_prod}
            </a>`
        ).join('');
        $('#search-results').html(html || '<div style="padding:8px;color:#888">Sin resultados</div>').show();
    });
}
function selectProduct(pid, name) {
    selProduct = {id:pid, name:name};
    $('#search-results').hide();
    $('#sel-prod-name').text(name);
    $('#selected-product-area').show();
    $('#chk-all-variants').prop('checked', true);
    $('#variants-list').html('<p class="text-muted" style="font-size:12px;margin:0">Cargando...</p>');
    $('#btn-add-prod').prop('disabled', false);

    $.get(API + '?action=get_product_variants&product_id=' + pid)
     .done(d => {
        if (!d.ok || !d.variants || !d.variants.length) {
            $('#variants-list').html('<p class="text-muted" style="font-size:12px;margin:0">Sin presentaciones registradas</p>');
            return;
        }
        const html = d.variants.map(v =>
            `<div class="checkbox" style="margin:2px 0">
                <label><input type="checkbox" class="vt-check" value="${v.id_variante}" checked>
                <b>${v.variante}</b> <small class="text-muted">${v.cantidad_vp} unid.</small></label>
            </div>`).join('');
        $('#variants-list').html(html);
     })
     .fail(() => {
        $('#variants-list').html('<p class="text-danger" style="font-size:12px;margin:0">Error cargando presentaciones</p>');
     });
}
function toggleAllVariants() {
    const checked = $('#chk-all-variants').is(':checked');
    $('.vt-check').prop('checked', checked);
}
function confirmAddProduct() {
    if (!selProduct) return;
    const total   = $('.vt-check').length;
    const checked = $('.vt-check:checked').length;
    const allV    = (total === 0 || total === checked) ? 1 : 0;
    const varIds  = [];
    if (!allV) {
        $('.vt-check:checked').each(function() { varIds.push($(this).val()); });
        if (!varIds.length) { alert('Selecciona al menos una presentación'); return; }
    }

    if (editingGpId) {
        // Editar: eliminar y re-insertar
        $.post(API + '?action=remove_product', {gp_id: editingGpId}, d => {
            if (!d.ok) { alert('Error al actualizar'); return; }
            $.post(API + '?action=add_product', {
                group_id: activeGroupId, product_id: selProduct.id,
                all_variants: allV, variant_ids: JSON.stringify(varIds)
            }, d2 => {
                if (d2.ok) { $('#modalProduct').modal('hide'); loadConfig(); }
                else alert('Error: ' + (d2.msg || 'no guardado'));
            });
        });
    } else {
        $.post(API + '?action=add_product', {
            group_id: activeGroupId, product_id: selProduct.id,
            all_variants: allV, variant_ids: JSON.stringify(varIds)
        }, d => {
            if (d.ok) { $('#modalProduct').modal('hide'); loadConfig(); }
            else alert('Error: ' + (d.msg || 'no guardado'));
        });
    }
}
function removeProduct(gpId) {
    $.post(API + '?action=remove_product', {gp_id:gpId}, d => { if (d.ok) loadConfig(); });
}

$('#prod-search').on('keyup', e => { if (e.key==='Enter') searchProducts(); });

function showAlert(msg, type) {
    $('#alert-area').html(`<div class="alert alert-${type} alert-dismissible">
        <button type="button" class="close" data-dismiss="alert">&times;</button>${msg}</div>`);
}
function esc(s) { return String(s).replace(/'/g,"\\'"); }

loadConfig();
</script>
</body>
</html>
