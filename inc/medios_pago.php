<?php
// Medios de pago fijos del sistema (los mismos que usa Caja para cobrar ventas).
// Se usan para registrar cómo se pagan gastos, compras y abonos a proveedores,
// y para llevar el saldo por medio en el Balance.
$MEDIOS_PAGO = [
    'efectivo'   => 'Efectivo',
    'yape'       => 'Yape',
    'plin'       => 'Plin',
    'bbva'       => 'BBVA',
    'yape_susan' => 'Yape Susan',
    'tarjeta'    => 'Tarjeta',
    'transferencia' => 'Transferencia',
];

// Valores antiguos de compra_pagos (antes de tener esta lista). Siguen siendo
// válidos al leer datos viejos, pero ya no se ofrecen al registrar.
$MEDIOS_LEGACY = ['deposito' => 'Depósito', 'cheque' => 'Cheque', 'otro' => 'Otro'];

function medio_label($k) {
    global $MEDIOS_PAGO, $MEDIOS_LEGACY;
    return $MEDIOS_PAGO[$k] ?? $MEDIOS_LEGACY[$k] ?? ucfirst($k);
}

function medios_options($sel = 'efectivo') {
    global $MEDIOS_PAGO;
    $h = '';
    foreach ($MEDIOS_PAGO as $k => $v) $h .= '<option value="' . $k . '"' . ($k === $sel ? ' selected' : '') . '>' . $v . '</option>';
    return $h;
}
