<?php
include('inc/control.php');
include('inc/sdba/sdba.php');

$pendientes = Sdba::db()->query("
    SELECT v.id_venta, v.fecha, v.total, c.cliente AS nombre_cliente,
           COALESCE(vp.pagado, 0) AS pagado
    FROM ventas v
    LEFT JOIN clientes c ON c.id_cliente = v.cliente
    LEFT JOIN (
        SELECT venta, SUM(monto) AS pagado FROM venta_pagos GROUP BY venta
    ) vp ON vp.venta = v.id_venta
    WHERE v.estado != '2'
      AND DATE(v.fecha) = CURDATE()
      AND COALESCE(vp.pagado, 0) < v.total
    ORDER BY v.fecha ASC
")->result();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Caja - Registrar Pagos</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" type="text/css" href="/assets/css/bootstrap.min.css">
    <link rel="stylesheet" type="text/css" href="/assets/css/custom.css">
    <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.7.2/css/all.css" integrity="sha384-fnmOCqbTlWIlj8LyTjo7mOUStjsKC4pOpQbqyi7RrhN7udi9RwhKkMHpvLbHG9Sr" crossorigin="anonymous">
<style>
    :root { --c-navy: #1e3a4c; --c-orange: #ff5023; }
    body { background:#f4f4f4; font-family: -apple-system, Segoe UI, Roboto, sans-serif; }
    .caja-wrap { max-width: 480px; margin: 0 auto; padding: 16px; }
    .caja-titulo { font-size: 20px; font-weight: 700; color: var(--c-navy); margin: 8px 0 16px; text-align:center; }

    .cola-item {
        background:#fff; border-radius:12px; padding:14px 16px; margin-bottom:10px;
        display:flex; justify-content:space-between; align-items:center;
        box-shadow:0 1px 3px rgba(0,0,0,.08); cursor:pointer; border:2px solid transparent;
    }
    .cola-item:active { border-color: var(--c-orange); }
    .cola-item .ci-num { font-weight:700; color:var(--c-navy); }
    .cola-item .ci-cliente { font-size:12px; color:#888; text-transform:uppercase; }
    .cola-item .ci-hora { font-size:12px; color:#aaa; }
    .cola-item .ci-monto { font-size:20px; font-weight:700; color:var(--c-orange); }
    .cola-item .ci-saldo { font-size:11px; color:#c0392b; }
    .vacio { text-align:center; color:#aaa; padding:40px 0; }

    .overlay {
        display:none; position:fixed; inset:0; background:rgba(0,0,0,.5); z-index:100;
        align-items:flex-end; justify-content:center;
    }
    .overlay.abierto { display:flex; }
    .panel-pago {
        background:#fff; border-radius:16px 16px 0 0; width:100%; max-width:480px;
        padding:20px; max-height:92vh; overflow-y:auto;
    }
    .panel-pago .pp-head { text-align:center; margin-bottom:14px; }
    .panel-pago .pp-num { font-weight:700; color:var(--c-navy); font-size:16px; }
    .panel-pago .pp-total { font-size:32px; font-weight:800; color:var(--c-navy); }
    .panel-pago .pp-saldo { font-size:14px; color:#c0392b; font-weight:600; margin-top:2px; }

    .metodos { display:grid; grid-template-columns:1fr 1fr; gap:10px; margin:16px 0; }
    .metodo-btn {
        padding:18px 8px; border-radius:12px; border:2px solid #eee; background:#fafafa;
        font-size:15px; font-weight:700; color:#555; text-align:center;
    }
    .metodo-btn.activo { border-color: var(--c-orange); background:#fff3ee; color:var(--c-orange); }
    .metodo-btn i { display:block; font-size:22px; margin-bottom:4px; }

    .campo-monto { margin:14px 0; }
    .campo-monto label { font-size:12px; color:#888; font-weight:600; }
    .campo-monto input {
        width:100%; font-size:26px; font-weight:700; padding:10px 12px; border-radius:10px;
        border:2px solid #ddd; text-align:center; color:var(--c-navy);
    }
    .vuelto-linea { text-align:center; font-size:15px; font-weight:700; color:#27ae60; margin-top:6px; }

    .lineas-agregadas { margin:14px 0; }
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
<div class="caja-wrap">
    <div class="caja-titulo"><i class="fas fa-cash-register"></i> Pagos pendientes hoy</div>

    <div id="cola">
        <?php if (count($pendientes) === 0): ?>
            <div class="vacio">No hay ventas pendientes de pago</div>
        <?php else: ?>
            <?php foreach ($pendientes as $v):
                $saldo = round((float)$v['total'] - (float)$v['pagado'], 2); ?>
            <div class="cola-item" data-venta="<?= (int)$v['id_venta'] ?>" data-total="<?= number_format($v['total'], 2, '.', '') ?>" data-saldo="<?= number_format($saldo, 2, '.', '') ?>">
                <div>
                    <div class="ci-num">v-<?= (int)$v['id_venta'] ?></div>
                    <div class="ci-cliente"><?= htmlspecialchars($v['nombre_cliente'] ?: 'Sin cliente', ENT_QUOTES, 'UTF-8') ?></div>
                    <div class="ci-hora"><?= date('H:i', strtotime($v['fecha'])) ?></div>
                </div>
                <div style="text-align:right">
                    <div class="ci-monto">S/ <?= number_format($v['total'], 2) ?></div>
                    <?php if ($v['pagado'] > 0): ?>
                        <div class="ci-saldo">Saldo: S/ <?= number_format($saldo, 2) ?></div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
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

        <div class="metodos">
            <div class="metodo-btn" data-metodo="efectivo"><i class="fas fa-money-bill-wave"></i>Efectivo</div>
            <div class="metodo-btn" data-metodo="yape"><i class="fas fa-mobile-alt"></i>Yape</div>
            <div class="metodo-btn" data-metodo="plin"><i class="fas fa-mobile-alt"></i>Plin</div>
            <div class="metodo-btn" data-metodo="tarjeta"><i class="fas fa-credit-card"></i>Tarjeta</div>
        </div>

        <div id="editorLinea" style="display:none">
            <div class="campo-monto">
                <label id="labelMonto">Monto</label>
                <input type="number" id="inputMonto" inputmode="decimal" step="0.01">
            </div>
            <div id="bloqueEfectivo" style="display:none">
                <div class="campo-monto">
                    <label>Recibido</label>
                    <input type="number" id="inputRecibido" inputmode="decimal" step="0.01">
                </div>
                <div class="vuelto-linea" id="vueltoLinea">Vuelto: S/ 0.00</div>
            </div>
            <button class="btn-cobrar" id="btnAgregarLinea">Agregar</button>
        </div>

        <div class="lineas-agregadas" id="lineasAgregadas"></div>

        <button class="btn-cobrar" id="btnConfirmar" disabled>Cobrar</button>
    </div>
</div>

<script>
(function () {
    const overlay = document.getElementById('overlay');
    const ppNum = document.getElementById('ppNum');
    const ppSaldo = document.getElementById('ppSaldo');
    const metodoBtns = document.querySelectorAll('.metodo-btn');
    const editorLinea = document.getElementById('editorLinea');
    const inputMonto = document.getElementById('inputMonto');
    const bloqueEfectivo = document.getElementById('bloqueEfectivo');
    const inputRecibido = document.getElementById('inputRecibido');
    const vueltoLinea = document.getElementById('vueltoLinea');
    const lineasAgregadas = document.getElementById('lineasAgregadas');
    const btnAgregarLinea = document.getElementById('btnAgregarLinea');
    const btnConfirmar = document.getElementById('btnConfirmar');
    const labelMonto = document.getElementById('labelMonto');

    let ventaActual = null;
    let totalVenta = 0;
    let saldoRestante = 0;
    let lineas = [];
    let metodoSeleccionado = null;

    function money(n) { return 'S/ ' + n.toFixed(2); }

    function abrirPanel(item) {
        ventaActual = item.dataset.venta;
        totalVenta = parseFloat(item.dataset.total);
        saldoRestante = parseFloat(item.dataset.saldo);
        lineas = [];
        metodoSeleccionado = null;
        ppNum.textContent = 'v-' + ventaActual;
        renderSaldo();
        lineasAgregadas.innerHTML = '';
        editorLinea.style.display = 'none';
        metodoBtns.forEach(b => b.classList.remove('activo'));
        btnConfirmar.disabled = true;
        overlay.classList.add('abierto');
    }

    function cerrarPanel() {
        overlay.classList.remove('abierto');
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
            calcularVuelto();
            editorLinea.style.display = 'block';
            btnAgregarLinea.textContent = 'Cobrar ' + money(saldoRestante);
        });
    });

    function calcularVuelto() {
        const monto = parseFloat(inputMonto.value) || 0;
        const recibido = parseFloat(inputRecibido.value) || 0;
        const vuelto = Math.max(0, recibido - monto);
        vueltoLinea.textContent = 'Vuelto: ' + money(vuelto);
    }
    inputRecibido.addEventListener('input', calcularVuelto);
    inputMonto.addEventListener('input', function () {
        const monto = parseFloat(inputMonto.value) || 0;
        if (Math.abs(monto - saldoRestante) < 0.005) {
            btnAgregarLinea.textContent = 'Cobrar ' + money(monto);
        } else {
            btnAgregarLinea.textContent = 'Agregar línea';
        }
        if (metodoSeleccionado === 'efectivo') {
            inputRecibido.value = inputMonto.value;
            calcularVuelto();
        }
    });

    btnAgregarLinea.addEventListener('click', function () {
        const monto = Math.round((parseFloat(inputMonto.value) || 0) * 100) / 100;
        if (monto <= 0 || monto > saldoRestante + 0.005) {
            alert('Monto inválido');
            return;
        }
        lineas.push({ metodo: metodoSeleccionado, monto: monto });
        const div = document.createElement('div');
        div.className = 'linea';
        div.innerHTML = '<span>' + metodoSeleccionado[0].toUpperCase() + metodoSeleccionado.slice(1) + ': ' + money(monto) + '</span><span class="quitar" data-idx="' + (lineas.length - 1) + '">&times;</span>';
        lineasAgregadas.appendChild(div);

        saldoRestante = Math.round((saldoRestante - monto) * 100) / 100;
        renderSaldo();

        editorLinea.style.display = 'none';
        metodoBtns.forEach(b => b.classList.remove('activo'));
        metodoSeleccionado = null;

        btnConfirmar.disabled = saldoRestante > 0.005;
        btnConfirmar.textContent = saldoRestante > 0.005 ? 'Falta ' + money(saldoRestante) : 'Confirmar pago';
    });

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

    function renderLineas() {
        lineasAgregadas.innerHTML = '';
        lineas.forEach((l, idx) => {
            const div = document.createElement('div');
            div.className = 'linea';
            div.innerHTML = '<span>' + l.metodo[0].toUpperCase() + l.metodo.slice(1) + ': ' + money(l.monto) + '</span><span class="quitar" data-idx="' + idx + '">&times;</span>';
            lineasAgregadas.appendChild(div);
        });
    }

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
                const item = document.querySelector('.cola-item[data-venta="' + ventaActual + '"]');
                if (item) item.remove();
                if (!document.querySelector('.cola-item')) {
                    document.getElementById('cola').innerHTML = '<div class="vacio">No hay ventas pendientes de pago</div>';
                }
                cerrarPanel();
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

    document.getElementById('cola').addEventListener('click', function (e) {
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
