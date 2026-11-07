<?php
// Cálculo del balance de caja para un rango de fechas ($p['fecha_inicio'] .. $p['fecha_fin']).
// Requiere inc/medios_pago.php cargado antes. Lo usan balance.php (por periodo) y balance_diario.php (un día).
function calcular_balance($conn, $p) {
	$ini = $p['fecha_inicio'];
	$fin = $p['fecha_fin'];
	$mov = [];

	// DEBE (entra a caja): cobros de ventas. Se excluyen ventas anuladas (estado 2).
	$r = $conn->query("
		SELECT vp.fecha, vp.monto, vp.metodo, vp.venta
		FROM venta_pagos vp INNER JOIN ventas v ON v.id_venta = vp.venta
		WHERE v.estado != '2' AND vp.metodo != 'planilla' AND DATE(vp.fecha) BETWEEN '$ini' AND '$fin'");
	while ($r && $x = $r->fetch_assoc()) {
		$mov[] = ['fecha' => $x['fecha'], 'grupo' => 'Ventas', 'medio' => $x['metodo'], 'detalle' => 'Cobro venta #' . $x['venta'], 'debe' => (float)$x['monto'], 'haber' => 0];
	}

	// HABER (sale de caja): pagos a proveedores.
	$r = $conn->query("
		SELECT cp.fecha, cp.monto, cp.metodo, pr.proveedor
		FROM compra_pagos cp
		INNER JOIN compras c ON c.id_compra = cp.compra
		LEFT JOIN proveedores pr ON pr.id_proveedor = c.proveedor
		WHERE c.estado != '2' AND DATE(cp.fecha) BETWEEN '$ini' AND '$fin'");
	while ($r && $x = $r->fetch_assoc()) {
		$mov[] = ['fecha' => $x['fecha'], 'grupo' => 'Proveedores', 'medio' => $x['metodo'], 'detalle' => 'Pago a ' . ($x['proveedor'] ?: 'proveedor'), 'debe' => 0, 'haber' => (float)$x['monto']];
	}

	// HABER: gastos (fijos, diarios, puntuales).
	$r = $conn->query("SELECT fecha, tipo, categoria, descripcion, monto, metodo FROM gastos WHERE estado = '1' AND fecha BETWEEN '$ini' AND '$fin'");
	while ($r && $x = $r->fetch_assoc()) {
		$mov[] = ['fecha' => $x['fecha'] . ' 12:00:00', 'grupo' => 'Gastos', 'medio' => $x['metodo'], 'detalle' => ucfirst($x['tipo']) . ': ' . $x['categoria'] . ($x['descripcion'] ? ' - ' . $x['descripcion'] : ''), 'debe' => 0, 'haber' => (float)$x['monto']];
	}

	// HABER: planillas (neto a pagar = sueldo del periodo - descuentos), en la fecha fin de la quincena.
	$r = $conn->query("
		SELECT pp.fecha_inicio, pp.fecha_fin,
		       COALESCE(SUM(pd.sueldo_periodo), 0) + COALESCE((SELECT SUM(i.importe) FROM planilla_ingresos i INNER JOIN planilla_detalle pd3 ON pd3.id_detalle = i.id_detalle WHERE pd3.id_periodo = pp.id_periodo), 0) AS sueldos,
		       COALESCE((SELECT SUM(d.importe) FROM planilla_descuentos d INNER JOIN planilla_detalle pd2 ON pd2.id_detalle = d.id_detalle WHERE pd2.id_periodo = pp.id_periodo), 0) AS descuentos
		FROM planilla_periodos pp LEFT JOIN planilla_detalle pd ON pd.id_periodo = pp.id_periodo
		WHERE pp.fecha_fin BETWEEN '$ini' AND '$fin'
		GROUP BY pp.id_periodo");
	while ($r && $x = $r->fetch_assoc()) {
		$neto = round((float)$x['sueldos'] - (float)$x['descuentos'], 2);
		if ($neto > 0) {
			$mov[] = ['fecha' => $x['fecha_fin'] . ' 23:00:00', 'grupo' => 'Planillas', 'medio' => 'efectivo', 'detalle' => 'Planilla ' . date('d/m', strtotime($x['fecha_inicio'])) . ' al ' . date('d/m', strtotime($x['fecha_fin'])), 'debe' => 0, 'haber' => $neto];
		}
	}

	global $MEDIOS_PAGO;
	foreach ($mov as &$mm) { if (!isset($MEDIOS_PAGO[$mm['medio']])) $mm['medio'] = 'otro'; }
	unset($mm);

	usort($mov, function($a, $b) { return strcmp($a['fecha'], $b['fecha']); });

	$debe = $haber = 0;
	$resumen = [];
	foreach ($mov as $m) {
		$debe += $m['debe'];
		$haber += $m['haber'];
		if (!isset($resumen[$m['grupo']])) $resumen[$m['grupo']] = 0;
		$resumen[$m['grupo']] += $m['debe'] - $m['haber'];
	}
	$inicial = (float)$p['caja_inicial'];

	// Saldo por medio: inicial (por medio) + debe - haber
	$medios = [];
	foreach ($MEDIOS_PAGO as $k => $v) $medios[$k] = ['ini' => 0, 'debe' => 0, 'haber' => 0];
	$medios['otro'] = ['ini' => 0, 'debe' => 0, 'haber' => 0];
	$rs = $conn->query("SELECT metodo, monto FROM caja_periodo_saldos WHERE id_cperiodo = " . (int)$p['id_cperiodo']);
	$suma_ini = 0;
	while ($rs && $x = $rs->fetch_assoc()) { if (isset($medios[$x['metodo']])) { $medios[$x['metodo']]['ini'] += (float)$x['monto']; $suma_ini += (float)$x['monto']; } }
	// periodos creados antes del desglose: toda la caja inicial cuenta como efectivo
	if ($suma_ini == 0 && $inicial != 0) $medios['efectivo']['ini'] = $inicial;
	foreach ($mov as $m) { $medios[$m['medio']]['debe'] += $m['debe']; $medios[$m['medio']]['haber'] += $m['haber']; }

	return ['medios' => $medios, 'mov' => $mov, 'debe' => round($debe, 2), 'haber' => round($haber, 2), 'resumen' => $resumen,
	        'inicial' => $inicial, 'saldo_final' => round($inicial + $debe - $haber, 2)];
}
