<?php
include('inc/control.php');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Caja - Registrar Pagos</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" type="text/css" href="/assets/css/bootstrap.min.css">
    <link rel="stylesheet" type="text/css" href="/assets/css/custom.css">
<style>
    :root { --c-navy: #1e3a4c; --c-orange: #ff5023; }
    body { background:#f4f4f4; font-family: -apple-system, Segoe UI, Roboto, sans-serif; }
    .caja-wrap { max-width: 1600px; margin: 0 auto; padding: 20px 24px 60px; }
    .caja-titulo { font-size: 22px; font-weight: 700; color: var(--c-navy); margin: 8px 0 18px; display:flex; align-items:center; gap:10px; }
    .caja-titulo .contador { font-size:14px; font-weight:600; color:#fff; background:var(--c-orange); border-radius:20px; padding:3px 12px; }

    .resumen-row { display:grid; grid-template-columns: repeat(7, 1fr); gap:14px; margin-bottom:24px; }
    .resumen-card { background:#fff; border-radius:12px; padding:16px 18px; box-shadow:0 1px 3px rgba(0,0,0,.08); }
    .resumen-card .rc-label { font-size:12px; color:#888; font-weight:600; text-transform:uppercase; display:flex; align-items:center; gap:6px; }
    .resumen-card .rc-valor { font-size:24px; font-weight:800; color:var(--c-navy); margin-top:6px; }
    .resumen-card.total { background:var(--c-navy); }
    .resumen-card.total .rc-label { color:#bcd; }
    .resumen-card.total .rc-valor { color:#fff; }
    @media (max-width: 900px) { .resumen-row { grid-template-columns: repeat(2, 1fr); } }

    .cola-grid { display:grid; grid-template-columns: repeat(auto-fill, minmax(240px, 1fr)); gap: 14px; }

    .cola-item {
        background:#fff; border-radius:12px; padding:14px 16px;
        display:flex; justify-content:space-between; align-items:center;
        box-shadow:0 1px 3px rgba(0,0,0,.08); cursor:pointer; border:2px solid transparent;
    }
    .cola-item:hover, .cola-item:active { border-color: var(--c-orange); }
    .cola-item.nueva { animation: destelloNueva 1.8s ease-out 2; border-color: var(--c-orange); }
    @keyframes destelloNueva {
        0%   { background:#fff3ee; }
        100% { background:#fff; }
    }
    .cola-item.seleccionable { border-color:#ddd; }
    .cola-item.seleccionada { border-color: var(--c-orange); background:#fff3ee; }
    .cola-item .ci-check {
        display:none; width:22px; height:22px; border-radius:50%; border:2px solid #ccc;
        margin-right:10px; flex-shrink:0; align-items:center; justify-content:center; color:#fff;
    }
    .cola-item.seleccionable .ci-check { display:flex; }
    .cola-item.seleccionada .ci-check { background:var(--c-orange); border-color:var(--c-orange); }

    .btn-seleccionar {
        margin-left:auto; font-size:13px; font-weight:700; color:var(--c-navy); background:#fff;
        border:2px solid #eee; border-radius:20px; padding:7px 16px;
    }
    .btn-seleccionar.activo { background:var(--c-navy); color:#fff; border-color:var(--c-navy); }

    .barra-seleccion {
        display:none; position:fixed; bottom:0; left:0; right:0; background:var(--c-navy); color:#fff;
        padding:16px 24px; align-items:center; justify-content:space-between; z-index:90;
        box-shadow:0 -2px 10px rgba(0,0,0,.15);
    }
    .barra-seleccion.abierta { display:flex; }
    .btn-barra { border:none; border-radius:10px; padding:10px 18px; font-weight:700; font-size:14px; margin-left:10px; }
    .btn-barra.cancelar { background:transparent; color:#bcd; }
    .btn-barra.cobrar { background:var(--c-orange); color:#fff; }
    .btn-barra.cobrar:disabled { background:#4a5f6d; color:#8ba; }
    .cola-item .ci-num { font-weight:700; color:var(--c-navy); }
    .cola-item .ci-cliente { font-size:12px; color:#888; text-transform:uppercase; }
    .cola-item .ci-hora { font-size:12px; color:#aaa; }
    .cola-item .ci-monto { font-size:20px; font-weight:700; color:var(--c-orange); }
    .cola-item .ci-saldo { font-size:11px; color:#c0392b; }
    .vacio { text-align:center; color:#aaa; padding:60px 0; grid-column:1/-1; font-size:15px; }

    .tabs-caja { display:flex; gap:8px; margin-bottom:18px; }
    .tab-caja { border:none; background:#fff; color:#888; font-weight:700; font-size:14px; padding:10px 20px; border-radius:10px; box-shadow:0 1px 3px rgba(0,0,0,.08); }
    .tab-caja.activo { background:var(--c-navy); color:#fff; }

    .pagada-grid { display:grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 14px; }
    .pagada-item { background:#fff; border-radius:12px; padding:14px 16px; box-shadow:0 1px 3px rgba(0,0,0,.08); }
    .pagada-item .pi-head { display:flex; justify-content:space-between; align-items:center; margin-bottom:8px; }
    .pagada-item .pi-num { font-weight:700; color:var(--c-navy); }
    .pagada-item .pi-cliente { font-size:12px; color:#888; text-transform:uppercase; }
    .pagada-item .pi-total { font-size:16px; font-weight:700; color:var(--c-orange); }
    .pagada-item .pi-estado { font-size:10px; font-weight:700; text-transform:uppercase; padding:2px 8px; border-radius:10px; background:#eee; color:#777; }
    .linea-pago { display:flex; justify-content:space-between; align-items:center; background:#f7f7f7; border-radius:8px; padding:6px 10px; margin-top:6px; font-size:13px; }
    .linea-pago .lp-metodo { font-weight:600; color:var(--c-navy); }
    .linea-pago .lp-editar { color:#555; background:#fff; border:1px solid #ddd; border-radius:6px; padding:3px 8px; font-size:11px; font-weight:700; }
    .linea-pago .lp-editar:active { background:#eee; }
    .linea-pago select, .linea-pago input { font-size:12px; padding:2px 4px; border-radius:6px; border:1px solid #ddd; }
    .linea-pago .lp-guardar { font-size:11px; font-weight:700; color:#fff; background:var(--c-orange); border:none; border-radius:6px; padding:3px 8px; }

    .credito-grid { display:grid; grid-template-columns: repeat(auto-fill, minmax(260px, 1fr)); gap: 14px; }
    .credito-item { background:#fff; border-radius:12px; padding:14px 16px; box-shadow:0 1px 3px rgba(0,0,0,.08); border:2px solid transparent; }
    .credito-item.vencida { border-color:#c0392b; }
    .credito-item .ci-head { display:flex; justify-content:space-between; align-items:center; }
    .credito-item .ci-num { font-weight:700; color:var(--c-navy); }
    .credito-item .ci-cliente { font-size:12px; color:#888; text-transform:uppercase; }
    .credito-item .ci-monto { font-size:18px; font-weight:700; color:var(--c-orange); }
    .credito-item .ci-fecha { font-size:12px; font-weight:700; margin-top:8px; color:#555; }
    .credito-item .ci-fecha.vencida { color:#c0392b; }
    .credito-item .ci-acciones { display:flex; gap:8px; margin-top:10px; }
    .credito-item .ci-acciones button { flex:1; font-size:12px; font-weight:700; border-radius:8px; padding:7px 4px; border:none; }
    .credito-item .btn-cobrar-ahora { background:var(--c-orange); color:#fff; }
    .credito-item .btn-quitar-credito { background:#eee; color:#555; }

    .credito-link { text-align:center; margin-top:10px; }
    .credito-link a { font-size:13px; color:#888; text-decoration:underline; cursor:pointer; }
    .credito-form { display:none; margin-top:10px; background:#f7f7f7; border-radius:10px; padding:12px; }
    .credito-form label { font-size:12px; color:#888; font-weight:600; }
    .credito-form input[type="date"] { width:100%; padding:8px; border-radius:8px; border:1px solid #ddd; margin:6px 0 10px; }
    .credito-form button { width:100%; padding:10px; border-radius:8px; border:none; background:var(--c-navy); color:#fff; font-weight:700; }

    .overlay {
        display:none; position:fixed; inset:0; background:rgba(0,0,0,.5); z-index:100;
        align-items:center; justify-content:center;
    }
    .overlay.abierto { display:flex; }
    .panel-pago {
        background:#fff; border-radius:16px; width:100%; max-width:900px;
        padding:24px; max-height:92vh; overflow-y:auto; position:relative;
    }
    .panel-pago .pp-head { text-align:center; margin-bottom:16px; }
    .panel-pago .pp-num { font-weight:700; color:var(--c-navy); font-size:16px; }
    .panel-pago .pp-total { font-size:32px; font-weight:800; color:var(--c-navy); }
    .panel-pago .pp-saldo { font-size:14px; color:#c0392b; font-weight:600; margin-top:2px; }

    .pp-body { display:grid; grid-template-columns: 1.15fr 1fr; gap:28px; align-items:start; }
    @media (max-width: 720px) {
        .panel-pago { max-width:460px; }
        .pp-body { grid-template-columns: 1fr; }
    }

    .metodos { display:grid; grid-template-columns:1fr 1fr; gap:10px; margin:0 0 14px; }
    .metodo-btn {
        padding:14px 8px; border-radius:12px; border:2px solid #eee; background:#fafafa;
        font-size:15px; font-weight:700; color:#555; text-align:center; user-select:none;
    }
    .metodo-btn.activo { border-color: var(--c-orange); background:#fff3ee; color:var(--c-orange); }
    .metodo-btn i { display:block; font-size:20px; margin-bottom:4px; }

    .campo-monto { margin:10px 0; }
    .campo-monto label { font-size:12px; color:#888; font-weight:600; }
    .campo-monto input {
        width:100%; font-size:24px; font-weight:700; padding:8px 12px; border-radius:10px;
        border:2px solid #ddd; text-align:center; color:var(--c-navy);
    }
    .campo-monto input.activo-teclado { border-color: var(--c-orange); }
    .vuelto-linea { text-align:center; font-size:15px; font-weight:700; color:#27ae60; margin-top:4px; }
    .nota-redondeo { text-align:center; font-size:12px; color:#c0392b; font-weight:600; margin-top:4px; }

    .keypad { display:grid; grid-template-columns: repeat(3, 1fr); gap:10px; }
    .keypad button {
        padding:20px 0; font-size:22px; font-weight:700; border-radius:10px; border:1px solid #e5e5e5;
        background:#f7f7f7; color:var(--c-navy); user-select:none;
    }
    .keypad button:active { background:#eee; }
    .keypad button.tecla-back { color:#c0392b; }

    .lineas-agregadas { margin:10px 0; }
    .linea { display:flex; justify-content:space-between; align-items:center; background:#f7f7f7; border-radius:8px; padding:8px 12px; margin-bottom:6px; font-size:14px; }
    .linea .quitar { color:#c0392b; font-weight:700; padding:0 6px; }

    .btn-cobrar {
        width:100%; padding:16px; border-radius:12px; border:none; font-size:18px; font-weight:700;
        background: var(--c-orange); color:#fff; margin-top:10px;
    }
    .btn-cobrar:disabled { background:#ddd; color:#999; }
    .btn-cerrar { position:absolute; top:14px; right:18px; font-size:22px; color:#aaa; background:none; border:none; }
</style>
</head>
<body>
    <nav class="navbar navbar-inverse navbar-fixed-top">
        <div class="">
            <div class="navbar-header">
                <button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#navbar" aria-expanded="false" aria-controls="navbar">
                    <span class="sr-only">Toggle navigation</span>
                    <span class="icon-bar"></span>
                    <span class="icon-bar"></span>
                    <span class="icon-bar"></span>
                </button>
                <a class="navbar-brand" href="#"><img class="img-responsive logo" src="/assets/img/harlec-sistema.png"></a>
            </div>
            <?php menu('9'); ?>
        </div>
    </nav>

<div class="caja-wrap" style="margin-top:70px">
    <div class="resumen-row" id="resumenRow">
        <div class="resumen-card">
            <div class="rc-label"><i class="fas fa-money-bill-wave"></i> Efectivo</div>
            <div class="rc-valor" id="resEfectivo">S/ 0.00</div>
        </div>
        <div class="resumen-card">
            <div class="rc-label"><i class="fas fa-mobile-alt"></i> Yape</div>
            <div class="rc-valor" id="resYape">S/ 0.00</div>
        </div>
        <div class="resumen-card">
            <div class="rc-label"><i class="fas fa-mobile-alt"></i> Plin</div>
            <div class="rc-valor" id="resPlin">S/ 0.00</div>
        </div>
        <div class="resumen-card">
            <div class="rc-label"><i class="fas fa-university"></i> BBVA</div>
            <div class="rc-valor" id="resBbva">S/ 0.00</div>
        </div>
        <div class="resumen-card">
            <div class="rc-label"><i class="fas fa-mobile-alt"></i> Yape Susan</div>
            <div class="rc-valor" id="resYapeSusan">S/ 0.00</div>
        </div>
        <div class="resumen-card">
            <div class="rc-label"><i class="fas fa-credit-card"></i> Tarjeta</div>
            <div class="rc-valor" id="resTarjeta">S/ 0.00</div>
        </div>
        <div class="resumen-card total">
            <div class="rc-label"><i class="fas fa-cash-register"></i> Total cobrado hoy</div>
            <div class="rc-valor" id="resTotal">S/ 0.00</div>
        </div>
    </div>

    <div class="tabs-caja">
        <button class="tab-caja activo" id="tabPendientes">Pagos pendientes</button>
        <button class="tab-caja" id="tabPagadas">Ventas pagadas hoy</button>
        <button class="tab-caja" id="tabCredito">Crédito</button>
    </div>

    <div id="vistaPendientes">
        <div class="caja-titulo">
            Pagos pendientes hoy
            <span class="contador" id="contador">0</span>
            <button class="btn-seleccionar" id="btnModoSeleccion">Cobrar varias juntas</button>
        </div>

        <div class="cola-grid" id="cola">
            <div class="vacio">Cargando...</div>
        </div>
    </div>

    <div id="vistaPagadas" style="display:none">
        <div class="caja-titulo">
            Ventas pagadas hoy
            <span class="contador" id="contadorPagadas">0</span>
        </div>

        <div class="pagada-grid" id="pagadas">
            <div class="vacio">Cargando...</div>
        </div>
    </div>

    <div id="vistaCredito" style="display:none">
        <div class="caja-titulo">
            Ventas a crédito
            <span class="contador" id="contadorCredito">0</span>
        </div>

        <div class="credito-grid" id="credito">
            <div class="vacio">Cargando...</div>
        </div>
    </div>
</div>

<div class="barra-seleccion" id="barraSeleccion">
    <span id="barraTexto">0 ventas seleccionadas</span>
    <div>
        <button class="btn-barra cancelar" id="btnCancelarSeleccion">Cancelar</button>
        <button class="btn-barra cobrar" id="btnCobrarJuntas" disabled>Cobrar juntas</button>
    </div>
</div>

<div class="overlay" id="overlay">
    <div class="panel-pago">
        <button class="btn-cerrar" id="btnCerrar">&times;</button>
        <div class="pp-head">
            <div class="pp-num" id="ppNum"></div>
            <div class="pp-total" id="ppSaldo"></div>
            <div class="pp-saldo" id="ppSaldoLabel">Saldo pendiente</div>
        </div>

        <div class="pp-body">
            <div class="pp-left">
                <div class="metodos">
                    <div class="metodo-btn" data-metodo="efectivo"><i class="fas fa-money-bill-wave"></i>Efectivo</div>
                    <div class="metodo-btn" data-metodo="yape"><i class="fas fa-mobile-alt"></i>Yape</div>
                    <div class="metodo-btn" data-metodo="plin"><i class="fas fa-mobile-alt"></i>Plin</div>
                    <div class="metodo-btn" data-metodo="bbva"><i class="fas fa-university"></i>BBVA</div>
                    <div class="metodo-btn" data-metodo="yape_susan"><i class="fas fa-mobile-alt"></i>Yape Susan</div>
                    <div class="metodo-btn" data-metodo="tarjeta"><i class="fas fa-credit-card"></i>Tarjeta</div>
                </div>

                <div id="editorLinea" style="display:none">
                    <div class="campo-monto">
                        <label id="labelMonto">Monto</label>
                        <input type="text" id="inputMonto" inputmode="none">
                        <div class="nota-redondeo" id="notaRedondeo" style="display:none"></div>
                    </div>
                    <div id="bloqueEfectivo" style="display:none">
                        <div class="campo-monto">
                            <label>Recibido</label>
                            <input type="text" id="inputRecibido" inputmode="none">
                        </div>
                        <div class="vuelto-linea" id="vueltoLinea">Vuelto: S/ 0.00</div>
                    </div>
                </div>

                <div class="lineas-agregadas" id="lineasAgregadas"></div>

                <button class="btn-cobrar" id="btnConfirmar" disabled>Cobrar</button>

                <div class="credito-link" id="creditoLink">
                    <a id="abrirFormCredito">¿Paga después? Dejar a crédito</a>
                </div>
                <div class="credito-form" id="creditoForm">
                    <label>Fecha en que se compromete a pagar</label>
                    <input type="date" id="inputFechaCredito">
                    <button type="button" id="btnConfirmarCredito">Dejar a crédito</button>
                </div>
            </div>

            <div class="pp-right" id="editorTeclado" style="display:none">
                <div class="keypad" id="keypad">
                    <button data-key="1">1</button><button data-key="2">2</button><button data-key="3">3</button>
                    <button data-key="4">4</button><button data-key="5">5</button><button data-key="6">6</button>
                    <button data-key="7">7</button><button data-key="8">8</button><button data-key="9">9</button>
                    <button data-key=".">.</button><button data-key="0">0</button><button data-key="back" class="tecla-back">⌫</button>
                </div>
                <button class="btn-cobrar" id="btnAgregarLinea" style="margin-top:14px">Agregar</button>
            </div>
        </div>
    </div>
</div>

<script src="https://use.fontawesome.com/releases/v5.7.2/css/all.css"></script>
<script>
(function () {
    const overlay = document.getElementById('overlay');
    const cola = document.getElementById('cola');
    const contador = document.getElementById('contador');
    const ppNum = document.getElementById('ppNum');
    const ppSaldo = document.getElementById('ppSaldo');
    const metodoBtns = document.querySelectorAll('.metodo-btn');
    const editorLinea = document.getElementById('editorLinea');
    const editorTeclado = document.getElementById('editorTeclado');
    const inputMonto = document.getElementById('inputMonto');
    const bloqueEfectivo = document.getElementById('bloqueEfectivo');
    const inputRecibido = document.getElementById('inputRecibido');
    const vueltoLinea = document.getElementById('vueltoLinea');
    const lineasAgregadas = document.getElementById('lineasAgregadas');
    const btnAgregarLinea = document.getElementById('btnAgregarLinea');
    const btnConfirmar = document.getElementById('btnConfirmar');
    const labelMonto = document.getElementById('labelMonto');
    const creditoLink = document.getElementById('creditoLink');
    const creditoForm = document.getElementById('creditoForm');
    const abrirFormCredito = document.getElementById('abrirFormCredito');
    const inputFechaCredito = document.getElementById('inputFechaCredito');
    const btnConfirmarCredito = document.getElementById('btnConfirmarCredito');

    let ventasActuales = []; // ventas incluidas en el panel de pago abierto (1 o varias)
    let saldoRestante = 0;
    let lineas = [];
    let metodoSeleccionado = null;
    let campoActivo = inputMonto;
    let panelAbierto = false;

    let modoSeleccion = false;
    let seleccionados = new Set();
    let ultimosItems = [];

    function money(n) { return 'S/ ' + n.toFixed(2); }

    // ── Cola en tiempo real ──────────────────────────────
    let idsConocidos = null; // null = primera carga, no resaltar nada todavía

    function renderCola(items) {
        ultimosItems = items;
        contador.textContent = items.length;
        if (items.length === 0) {
            cola.innerHTML = '<div class="vacio">No hay ventas pendientes de pago</div>';
            idsConocidos = new Set();
            return;
        }
        cola.innerHTML = items.map(function (v) {
            const esNueva = idsConocidos !== null && !idsConocidos.has(v.id_venta);
            const seleccionable = modoSeleccion ? ' seleccionable' : '';
            const seleccionada = seleccionados.has(v.id_venta) ? ' seleccionada' : '';
            const saldoHtml = v.saldo < v.total
                ? '<div class="ci-saldo">Saldo: ' + money(v.saldo) + '</div>' : '';
            return '<div class="cola-item' + (esNueva ? ' nueva' : '') + seleccionable + seleccionada + '" data-venta="' + v.id_venta + '" data-total="' + v.total.toFixed(2) + '" data-saldo="' + v.saldo.toFixed(2) + '">' +
                '<div class="ci-check">&check;</div>' +
                '<div style="flex:1;display:flex;justify-content:space-between;align-items:center">' +
                '<div>' +
                    '<div class="ci-num">v-' + v.id_venta + '</div>' +
                    '<div class="ci-cliente">' + v.cliente + '</div>' +
                    '<div class="ci-hora">' + v.hora + '</div>' +
                '</div>' +
                '<div style="text-align:right">' +
                    '<div class="ci-monto">' + money(v.total) + '</div>' +
                    saldoHtml +
                '</div>' +
                '</div>' +
            '</div>';
        }).join('');
        idsConocidos = new Set(items.map(v => v.id_venta));
    }

    function renderResumen(r) {
        document.getElementById('resEfectivo').textContent = money(r.efectivo);
        document.getElementById('resYape').textContent = money(r.yape);
        document.getElementById('resPlin').textContent = money(r.plin);
        document.getElementById('resBbva').textContent = money(r.bbva);
        document.getElementById('resYapeSusan').textContent = money(r.yape_susan);
        document.getElementById('resTarjeta').textContent = money(r.tarjeta);
        document.getElementById('resTotal').textContent = money(r.total);
    }

    function cargarCola() {
        fetch('inc/get_pagos_pendientes.php')
            .then(r => r.json())
            .then(res => {
                if (!res.ok) return;
                renderCola(res.data);
                if (res.resumen) renderResumen(res.resumen);
                // Si alguna venta que tengo abierta en el panel ya no está pendiente, la cobró alguien más
                if (panelAbierto && ventasActuales.length > 0) {
                    const idsPendientes = new Set(res.data.map(v => v.id_venta));
                    const faltaAlguna = ventasActuales.some(id => !idsPendientes.has(id));
                    if (faltaAlguna) {
                        alert('Una de estas ventas ya fue cobrada.');
                        cerrarPanel();
                    }
                }
                // Sacar de la selección lo que ya no está pendiente
                if (seleccionados.size > 0) {
                    const idsPendientes = new Set(res.data.map(v => v.id_venta));
                    seleccionados.forEach(id => { if (!idsPendientes.has(id)) seleccionados.delete(id); });
                    actualizarBarraSeleccion();
                }
            })
            .catch(() => {});
    }
    cargarCola();
    setInterval(cargarCola, 5000);

    // ── Panel de pago ────────────────────────────────────
    // items: array de elementos .cola-item (1 = venta simple, 2+ = cobro combinado)
    function abrirPanel(items) {
        ventasActuales = items.map(el => parseInt(el.dataset.venta));
        saldoRestante = items.reduce((acc, el) => acc + parseFloat(el.dataset.saldo), 0);
        saldoRestante = Math.round(saldoRestante * 100) / 100;
        lineas = [];
        metodoSeleccionado = null;
        ppNum.textContent = items.length === 1
            ? 'v-' + ventasActuales[0]
            : items.length + ' ventas: ' + ventasActuales.map(id => 'v-' + id).join(', ');
        renderSaldo();
        lineasAgregadas.innerHTML = '';
        editorLinea.style.display = 'none';
        editorTeclado.style.display = 'none';
        metodoBtns.forEach(b => b.classList.remove('activo'));
        btnConfirmar.disabled = true;
        btnConfirmar.textContent = 'Cobrar';
        creditoForm.style.display = 'none';
        inputFechaCredito.value = '';
        creditoLink.style.display = items.length === 1 ? 'block' : 'none';
        overlay.classList.add('abierto');
        panelAbierto = true;
    }

    function cerrarPanel() {
        overlay.classList.remove('abierto');
        panelAbierto = false;
        ventasActuales = [];
    }

    function renderSaldo() {
        ppSaldo.textContent = money(saldoRestante);
    }

    // Redondeo comercial: en efectivo no se puede dar vuelto en centavos chicos,
    // así que el monto a cobrar sube a la décima de sol más cercana hacia arriba.
    function redondearArriba10(valor) { return Math.ceil(valor * 10 - 0.0001) / 10; }

    metodoBtns.forEach(btn => {
        btn.addEventListener('click', function () {
            metodoBtns.forEach(b => b.classList.remove('activo'));
            btn.classList.add('activo');
            metodoSeleccionado = btn.dataset.metodo;
            labelMonto.textContent = 'Monto en ' + btn.textContent.trim();

            let montoSugerido = saldoRestante;
            if (metodoSeleccionado === 'efectivo') {
                montoSugerido = Math.round(redondearArriba10(saldoRestante) * 100) / 100;
            }
            inputMonto.value = montoSugerido.toFixed(2);
            bloqueEfectivo.style.display = metodoSeleccionado === 'efectivo' ? 'block' : 'none';
            inputRecibido.value = montoSugerido.toFixed(2);

            const notaRedondeo = document.getElementById('notaRedondeo');
            if (metodoSeleccionado === 'efectivo' && montoSugerido - saldoRestante > 0.001) {
                notaRedondeo.textContent = 'Redondeado hacia arriba (antes ' + money(saldoRestante) + ')';
                notaRedondeo.style.display = 'block';
            } else {
                notaRedondeo.style.display = 'none';
            }

            // en efectivo el campo que casi siempre hay que tocar es "Recibido"
            marcarActivo(metodoSeleccionado === 'efectivo' ? inputRecibido : inputMonto);
            calcularVuelto();
            editorLinea.style.display = 'block';
            editorTeclado.style.display = 'block';
            actualizarLabelAgregar();
        });
    });

    function actualizarLabelAgregar() {
        const monto = parseFloat(inputMonto.value) || 0;
        if (monto >= saldoRestante - 0.005) {
            btnAgregarLinea.textContent = 'Cobrar ' + money(monto);
        } else {
            btnAgregarLinea.textContent = 'Agregar línea';
        }
    }

    function calcularVuelto() {
        const monto = parseFloat(inputMonto.value) || 0;
        const recibido = parseFloat(inputRecibido.value) || 0;
        const vuelto = Math.max(0, recibido - monto);
        vueltoLinea.textContent = 'Vuelto: ' + money(vuelto);
    }

    let reemplazarSiguiente = true; // el próximo dígito borra el valor precargado, en vez de pegarse al final

    function marcarActivo(input) {
        campoActivo = input;
        reemplazarSiguiente = true;
        inputMonto.classList.toggle('activo-teclado', input === inputMonto);
        inputRecibido.classList.toggle('activo-teclado', input === inputRecibido);
    }
    inputMonto.addEventListener('click', () => marcarActivo(inputMonto));
    inputRecibido.addEventListener('click', () => marcarActivo(inputRecibido));

    // Si escriben con teclado físico (numérico o normal) directo en el campo
    function alCambiarCampo(campo) {
        if (campo === inputMonto) {
            actualizarLabelAgregar();
        }
        calcularVuelto();
    }
    inputMonto.addEventListener('input', () => alCambiarCampo(inputMonto));
    inputRecibido.addEventListener('input', () => alCambiarCampo(inputRecibido));

    // ── Teclado numérico en pantalla ─────────────────────
    document.getElementById('keypad').addEventListener('click', function (e) {
        const btn = e.target.closest('button');
        if (!btn) return;
        const key = btn.dataset.key;
        let val = reemplazarSiguiente ? '' : campoActivo.value;
        reemplazarSiguiente = false;

        if (key === 'back') {
            val = val.slice(0, -1);
        } else if (key === '.') {
            if (!val.includes('.')) val += '.';
        } else {
            if (val.includes('.') && val.split('.')[1].length >= 2) return; // máx 2 decimales
            val += key;
        }
        campoActivo.value = val;
        alCambiarCampo(campoActivo);
    });

    btnAgregarLinea.addEventListener('click', function () {
        const monto = Math.round((parseFloat(inputMonto.value) || 0) * 100) / 100;
        const excesoPermitido = metodoSeleccionado === 'efectivo' ? 0.09 : 0; // margen de redondeo
        if (monto <= 0 || monto > saldoRestante + excesoPermitido + 0.005) {
            alert('Monto inválido');
            return;
        }
        lineas.push({ metodo: metodoSeleccionado, monto: monto });
        renderLineas();

        saldoRestante = Math.max(0, Math.round((saldoRestante - monto) * 100) / 100);
        renderSaldo();

        editorLinea.style.display = 'none';
        editorTeclado.style.display = 'none';
        metodoBtns.forEach(b => b.classList.remove('activo'));
        metodoSeleccionado = null;

        btnConfirmar.disabled = saldoRestante > 0.005;
        btnConfirmar.textContent = saldoRestante > 0.005 ? 'Falta ' + money(saldoRestante) : 'Confirmar pago';
    });

    function renderLineas() {
        lineasAgregadas.innerHTML = '';
        lineas.forEach((l, idx) => {
            const div = document.createElement('div');
            div.className = 'linea';
            div.innerHTML = '<span>' + l.metodo[0].toUpperCase() + l.metodo.slice(1) + ': ' + money(l.monto) + '</span><span class="quitar" data-idx="' + idx + '">&times;</span>';
            lineasAgregadas.appendChild(div);
        });
    }

    lineasAgregadas.addEventListener('click', function (e) {
        if (!e.target.classList.contains('quitar')) return;
        const idx = parseInt(e.target.dataset.idx);
        saldoRestante = Math.round((saldoRestante + lineas[idx].monto) * 100) / 100;
        lineas.splice(idx, 1);
        renderLineas();
        renderSaldo();
        btnConfirmar.disabled = saldoRestante > 0.005;
        btnConfirmar.textContent = saldoRestante > 0.005 ? 'Falta ' + money(saldoRestante) : 'Confirmar pago';
    });

    btnConfirmar.addEventListener('click', function () {
        if (lineas.length === 0) return;
        btnConfirmar.disabled = true;
        btnConfirmar.textContent = 'Guardando...';

        fetch('inc/registrar_pago.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'ventas=' + encodeURIComponent(JSON.stringify(ventasActuales)) + '&pagos=' + encodeURIComponent(JSON.stringify(lineas))
        })
        .then(r => r.json())
        .then(data => {
            if (data.ok) {
                salirModoSeleccion();
                cerrarPanel();
                cargarCola();
            } else {
                alert(data.mensaje || 'No se pudo registrar el pago');
                btnConfirmar.disabled = false;
                btnConfirmar.textContent = 'Confirmar pago';
            }
        })
        .catch(() => {
            alert('Error de conexión');
            btnConfirmar.disabled = false;
            btnConfirmar.textContent = 'Confirmar pago';
        });
    });

    cola.addEventListener('click', function (e) {
        const item = e.target.closest('.cola-item');
        if (!item) return;
        if (modoSeleccion) {
            const id = parseInt(item.dataset.venta);
            if (seleccionados.has(id)) seleccionados.delete(id);
            else seleccionados.add(id);
            item.classList.toggle('seleccionada');
            actualizarBarraSeleccion();
        } else {
            abrirPanel([item]);
        }
    });

    document.getElementById('btnCerrar').addEventListener('click', cerrarPanel);
    overlay.addEventListener('click', function (e) {
        if (e.target === overlay) cerrarPanel();
    });

    // ── Selección múltiple / cobro combinado ─────────────
    const btnModoSeleccion = document.getElementById('btnModoSeleccion');
    const barraSeleccion = document.getElementById('barraSeleccion');
    const barraTexto = document.getElementById('barraTexto');
    const btnCobrarJuntas = document.getElementById('btnCobrarJuntas');
    const btnCancelarSeleccion = document.getElementById('btnCancelarSeleccion');

    function actualizarBarraSeleccion() {
        const n = seleccionados.size;
        barraTexto.textContent = n + (n === 1 ? ' venta seleccionada' : ' ventas seleccionadas');
        let total = 0;
        ultimosItems.forEach(v => { if (seleccionados.has(v.id_venta)) total += v.saldo; });
        btnCobrarJuntas.textContent = n > 0 ? 'Cobrar juntas (' + money(total) + ')' : 'Cobrar juntas';
        btnCobrarJuntas.disabled = n < 2;
    }

    function salirModoSeleccion() {
        modoSeleccion = false;
        seleccionados = new Set();
        btnModoSeleccion.classList.remove('activo');
        btnModoSeleccion.textContent = 'Cobrar varias juntas';
        barraSeleccion.classList.remove('abierta');
        renderCola(ultimosItems);
    }

    btnModoSeleccion.addEventListener('click', function () {
        if (modoSeleccion) {
            salirModoSeleccion();
        } else {
            modoSeleccion = true;
            seleccionados = new Set();
            btnModoSeleccion.classList.add('activo');
            btnModoSeleccion.textContent = 'Cancelar selección';
            barraSeleccion.classList.add('abierta');
            actualizarBarraSeleccion();
            renderCola(ultimosItems);
        }
    });

    btnCancelarSeleccion.addEventListener('click', salirModoSeleccion);

    btnCobrarJuntas.addEventListener('click', function () {
        const items = Array.from(cola.querySelectorAll('.cola-item')).filter(el => seleccionados.has(parseInt(el.dataset.venta)));
        if (items.length < 2) return;
        abrirPanel(items);
    });

    // ── Tabs "Ventas pagadas" / "Crédito" ────────────────
    const tabPendientes = document.getElementById('tabPendientes');
    const tabPagadas = document.getElementById('tabPagadas');
    const tabCredito = document.getElementById('tabCredito');
    const vistaPendientes = document.getElementById('vistaPendientes');
    const vistaPagadas = document.getElementById('vistaPagadas');
    const vistaCredito = document.getElementById('vistaCredito');
    const pagadasGrid = document.getElementById('pagadas');
    const contadorPagadas = document.getElementById('contadorPagadas');
    const creditoGrid = document.getElementById('credito');
    const contadorCredito = document.getElementById('contadorCredito');

    const metodosLabel = { efectivo: 'Efectivo', yape: 'Yape', plin: 'Plin', bbva: 'BBVA', yape_susan: 'Yape Susan', tarjeta: 'Tarjeta' };
    const metodosOrden = ['efectivo', 'yape', 'plin', 'bbva', 'yape_susan', 'tarjeta'];

    function renderPagadas(items) {
        contadorPagadas.textContent = items.length;
        if (items.length === 0) {
            pagadasGrid.innerHTML = '<div class="vacio">No hay ventas pagadas hoy</div>';
            return;
        }
        pagadasGrid.innerHTML = items.map(function (v) {
            const lineas = v.pagos.map(function (p) {
                return '<div class="linea-pago" data-id-pago="' + p.id_pago + '" data-metodo="' + p.metodo + '" data-monto="' + p.monto.toFixed(2) + '">' +
                    '<span class="lp-metodo">' + (metodosLabel[p.metodo] || p.metodo) + ': ' + money(p.monto) + ' <small style="color:#aaa">(' + p.hora + ')</small></span>' +
                    '<button class="lp-editar" data-accion="editar" title="Editar pago"><i class="fas fa-pen"></i> Editar</button>' +
                '</div>';
            }).join('');
            return '<div class="pagada-item" data-venta="' + v.id_venta + '">' +
                '<div class="pi-head">' +
                    '<div><div class="pi-num">v-' + v.id_venta + '</div><div class="pi-cliente">' + v.cliente + '</div></div>' +
                    '<div style="text-align:right"><div class="pi-total">' + money(v.total) + '</div><span class="pi-estado">' + v.estado_label + '</span></div>' +
                '</div>' +
                lineas +
            '</div>';
        }).join('');
    }

    function cargarPagadas() {
        fetch('inc/get_pagos_realizados.php')
            .then(r => r.json())
            .then(res => { if (res.ok) renderPagadas(res.data); })
            .catch(() => {});
    }

    function mostrarTab(nombre) {
        vistaPendientes.style.display = nombre === 'pendientes' ? '' : 'none';
        vistaPagadas.style.display = nombre === 'pagadas' ? '' : 'none';
        vistaCredito.style.display = nombre === 'credito' ? '' : 'none';
        tabPendientes.classList.toggle('activo', nombre === 'pendientes');
        tabPagadas.classList.toggle('activo', nombre === 'pagadas');
        tabCredito.classList.toggle('activo', nombre === 'credito');
        if (nombre === 'pagadas') cargarPagadas();
        if (nombre === 'credito') cargarCredito();
    }
    tabPendientes.addEventListener('click', () => mostrarTab('pendientes'));
    tabPagadas.addEventListener('click', () => mostrarTab('pagadas'));
    tabCredito.addEventListener('click', () => mostrarTab('credito'));

    pagadasGrid.addEventListener('click', function (e) {
        const btnEditar = e.target.closest('[data-accion="editar"]');
        if (btnEditar) {
            const linea = btnEditar.closest('.linea-pago');
            const claveActual = linea.dataset.metodo;
            const montoActual = linea.dataset.monto;
            const opciones = metodosOrden.map(m => '<option value="' + m + '"' + (m === claveActual ? ' selected' : '') + '>' + metodosLabel[m] + '</option>').join('');
            linea.innerHTML = '<select class="ed-metodo">' + opciones + '</select>' +
                '<input type="number" step="0.01" min="0.01" class="ed-monto" value="' + montoActual + '" style="width:80px">' +
                '<button class="lp-guardar" data-accion="guardar">Guardar</button>';
            return;
        }
        const btnGuardar = e.target.closest('[data-accion="guardar"]');
        if (btnGuardar) {
            const linea = btnGuardar.closest('.linea-pago');
            const idPago = linea.dataset.idPago;
            const metodoNuevo = linea.querySelector('.ed-metodo').value;
            const montoNuevo = parseFloat(linea.querySelector('.ed-monto').value) || 0;
            if (montoNuevo <= 0) { alert('Monto inválido'); return; }
            btnGuardar.disabled = true;
            btnGuardar.textContent = '...';
            fetch('inc/editar_metodo_pago.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'id_pago=' + encodeURIComponent(idPago) + '&metodo=' + encodeURIComponent(metodoNuevo) + '&monto=' + encodeURIComponent(montoNuevo)
            })
            .then(r => r.json())
            .then(data => {
                if (data.ok) {
                    cargarPagadas();
                    cargarCola();
                } else {
                    alert(data.mensaje || 'No se pudo actualizar el pago');
                    btnGuardar.disabled = false;
                    btnGuardar.textContent = 'Guardar';
                }
            })
            .catch(() => {
                alert('Error de conexión');
                btnGuardar.disabled = false;
                btnGuardar.textContent = 'Guardar';
            });
        }
    });

    // ── Tab "Crédito" ─────────────────────────────────────
    function formatFecha(f) {
        if (!f) return '-';
        const p = f.substring(0, 10).split('-');
        return p.length === 3 ? (p[2] + '/' + p[1] + '/' + p[0]) : f;
    }

    function renderCredito(items) {
        contadorCredito.textContent = items.length;
        if (items.length === 0) {
            creditoGrid.innerHTML = '<div class="vacio">No hay ventas a crédito</div>';
            return;
        }
        creditoGrid.innerHTML = items.map(function (v) {
            return '<div class="credito-item' + (v.vencida ? ' vencida' : '') + '" data-venta="' + v.id_venta + '" data-total="' + v.total.toFixed(2) + '" data-saldo="' + v.saldo.toFixed(2) + '">' +
                '<div class="ci-head">' +
                    '<div><div class="ci-num">v-' + v.id_venta + '</div><div class="ci-cliente">' + v.cliente + '</div></div>' +
                    '<div class="ci-monto">' + money(v.saldo) + '</div>' +
                '</div>' +
                '<div class="ci-fecha' + (v.vencida ? ' vencida' : '') + '">' + (v.vencida ? 'Venció el ' : 'Paga el ') + formatFecha(v.fecha) + '</div>' +
                '<div class="ci-acciones">' +
                    '<button class="btn-cobrar-ahora" data-accion="cobrar-ahora">Cobrar ahora</button>' +
                    '<button class="btn-quitar-credito" data-accion="quitar-credito">Quitar de crédito</button>' +
                '</div>' +
            '</div>';
        }).join('');
    }

    function cargarCredito() {
        fetch('inc/get_ventas_credito.php')
            .then(r => r.json())
            .then(res => { if (res.ok) renderCredito(res.data); })
            .catch(() => {});
    }

    creditoGrid.addEventListener('click', function (e) {
        const item = e.target.closest('.credito-item');
        if (!item) return;

        if (e.target.closest('[data-accion="cobrar-ahora"]')) {
            abrirPanel([item]);
            return;
        }

        if (e.target.closest('[data-accion="quitar-credito"]')) {
            fetch('inc/marcar_credito.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'id_venta=' + encodeURIComponent(item.dataset.venta) + '&accion=quitar'
            })
            .then(r => r.json())
            .then(data => {
                if (data.ok) {
                    cargarCredito();
                    cargarCola();
                } else {
                    alert(data.mensaje || 'No se pudo quitar de crédito');
                }
            })
            .catch(() => alert('Error de conexión'));
        }
    });

    // ── "Dejar a crédito" desde el panel de pago (solo venta única) ──
    abrirFormCredito.addEventListener('click', function () {
        if (!inputFechaCredito.value) {
            const manana = new Date();
            manana.setDate(manana.getDate() + 1);
            inputFechaCredito.value = manana.toISOString().substring(0, 10);
        }
        creditoForm.style.display = creditoForm.style.display === 'block' ? 'none' : 'block';
    });

    btnConfirmarCredito.addEventListener('click', function () {
        if (!inputFechaCredito.value) {
            alert('Elige una fecha');
            return;
        }
        if (ventasActuales.length !== 1) return;
        btnConfirmarCredito.disabled = true;
        btnConfirmarCredito.textContent = 'Guardando...';
        fetch('inc/marcar_credito.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'id_venta=' + encodeURIComponent(ventasActuales[0]) + '&accion=marcar&fecha=' + encodeURIComponent(inputFechaCredito.value)
        })
        .then(r => r.json())
        .then(data => {
            if (data.ok) {
                cerrarPanel();
                cargarCola();
                cargarCredito();
            } else {
                alert(data.mensaje || 'No se pudo dejar a crédito');
            }
        })
        .catch(() => alert('Error de conexión'))
        .finally(() => {
            btnConfirmarCredito.disabled = false;
            btnConfirmarCredito.textContent = 'Dejar a crédito';
        });
    });
})();
</script>
</body>
</html>
