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
    .cola-item .ci-num { font-weight:700; color:var(--c-navy); }
    .cola-item .ci-cliente { font-size:12px; color:#888; text-transform:uppercase; }
    .cola-item .ci-hora { font-size:12px; color:#aaa; }
    .cola-item .ci-monto { font-size:20px; font-weight:700; color:var(--c-orange); }
    .cola-item .ci-saldo { font-size:11px; color:#c0392b; }
    .vacio { text-align:center; color:#aaa; padding:60px 0; grid-column:1/-1; font-size:15px; }

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
    <div class="caja-titulo">
        Pagos pendientes hoy
        <span class="contador" id="contador">0</span>
    </div>

    <div class="cola-grid" id="cola">
        <div class="vacio">Cargando...</div>
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
                    <div class="metodo-btn" data-metodo="tarjeta"><i class="fas fa-credit-card"></i>Tarjeta</div>
                </div>

                <div id="editorLinea" style="display:none">
                    <div class="campo-monto">
                        <label id="labelMonto">Monto</label>
                        <input type="text" id="inputMonto" inputmode="none">
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

    let ventaActual = null;
    let saldoRestante = 0;
    let lineas = [];
    let metodoSeleccionado = null;
    let campoActivo = inputMonto;
    let panelAbierto = false;

    function money(n) { return 'S/ ' + n.toFixed(2); }

    // ── Cola en tiempo real ──────────────────────────────
    let idsConocidos = null; // null = primera carga, no resaltar nada todavía

    function renderCola(items) {
        contador.textContent = items.length;
        if (items.length === 0) {
            cola.innerHTML = '<div class="vacio">No hay ventas pendientes de pago</div>';
            idsConocidos = new Set();
            return;
        }
        cola.innerHTML = items.map(function (v) {
            const esNueva = idsConocidos !== null && !idsConocidos.has(v.id_venta);
            const saldoHtml = v.saldo < v.total
                ? '<div class="ci-saldo">Saldo: ' + money(v.saldo) + '</div>' : '';
            return '<div class="cola-item' + (esNueva ? ' nueva' : '') + '" data-venta="' + v.id_venta + '" data-total="' + v.total.toFixed(2) + '" data-saldo="' + v.saldo.toFixed(2) + '">' +
                '<div>' +
                    '<div class="ci-num">v-' + v.id_venta + '</div>' +
                    '<div class="ci-cliente">' + v.cliente + '</div>' +
                    '<div class="ci-hora">' + v.hora + '</div>' +
                '</div>' +
                '<div style="text-align:right">' +
                    '<div class="ci-monto">' + money(v.total) + '</div>' +
                    saldoHtml +
                '</div>' +
            '</div>';
        }).join('');
        idsConocidos = new Set(items.map(v => v.id_venta));
    }

    function cargarCola() {
        fetch('inc/get_pagos_pendientes.php')
            .then(r => r.json())
            .then(res => {
                if (!res.ok) return;
                renderCola(res.data);
                // Si la venta que tengo abierta en el panel ya no está pendiente, la cerró alguien más
                if (panelAbierto && ventaActual && !res.data.some(v => String(v.id_venta) === String(ventaActual))) {
                    alert('Esta venta ya fue cobrada.');
                    cerrarPanel();
                }
            })
            .catch(() => {});
    }
    cargarCola();
    setInterval(cargarCola, 5000);

    // ── Panel de pago ────────────────────────────────────
    function abrirPanel(item) {
        ventaActual = item.dataset.venta;
        saldoRestante = parseFloat(item.dataset.saldo);
        lineas = [];
        metodoSeleccionado = null;
        ppNum.textContent = 'v-' + ventaActual;
        renderSaldo();
        lineasAgregadas.innerHTML = '';
        editorLinea.style.display = 'none';
        editorTeclado.style.display = 'none';
        metodoBtns.forEach(b => b.classList.remove('activo'));
        btnConfirmar.disabled = true;
        btnConfirmar.textContent = 'Cobrar';
        overlay.classList.add('abierto');
        panelAbierto = true;
    }

    function cerrarPanel() {
        overlay.classList.remove('abierto');
        panelAbierto = false;
        ventaActual = null;
    }

    function renderSaldo() {
        ppSaldo.textContent = money(saldoRestante);
    }

    metodoBtns.forEach(btn => {
        btn.addEventListener('click', function () {
            metodoBtns.forEach(b => b.classList.remove('activo'));
            btn.classList.add('activo');
            metodoSeleccionado = btn.dataset.metodo;
            labelMonto.textContent = 'Monto en ' + btn.textContent.trim();
            inputMonto.value = saldoRestante.toFixed(2);
            bloqueEfectivo.style.display = metodoSeleccionado === 'efectivo' ? 'block' : 'none';
            inputRecibido.value = saldoRestante.toFixed(2);
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
        if (Math.abs(monto - saldoRestante) < 0.005) {
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
        if (monto <= 0 || monto > saldoRestante + 0.005) {
            alert('Monto inválido');
            return;
        }
        lineas.push({ metodo: metodoSeleccionado, monto: monto });
        renderLineas();

        saldoRestante = Math.round((saldoRestante - monto) * 100) / 100;
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
            body: 'venta=' + encodeURIComponent(ventaActual) + '&pagos=' + encodeURIComponent(JSON.stringify(lineas))
        })
        .then(r => r.json())
        .then(data => {
            if (data.ok) {
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
        if (item) abrirPanel(item);
    });

    document.getElementById('btnCerrar').addEventListener('click', cerrarPanel);
    overlay.addEventListener('click', function (e) {
        if (e.target === overlay) cerrarPanel();
    });
})();
</script>
</body>
</html>
