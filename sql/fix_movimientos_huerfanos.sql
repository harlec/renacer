-- Reparación puntual: movimientos_empleado que quedaron marcados como "aplicados" a un
-- id_detalle que ya no existe (porque el periodo de planilla al que apuntaban se borró
-- antes de que inc/borrar_planilla_periodo.php tuviera el fix que los revierte a pendiente).
-- Ejecutar una sola vez para dejarlos "pendientes" de nuevo, listos para la próxima planilla.

-- 1) Si alguno de esos movimientos venía de una venta (abarrotes) y se le había
--    registrado un pago 'planilla' que ya no corresponde a nada real, se revierte:
DELETE vp FROM venta_pagos vp
INNER JOIN movimientos_empleado m ON m.id_venta = vp.venta
WHERE vp.metodo = 'planilla'
  AND m.id_detalle_aplicado IS NOT NULL
  AND m.id_detalle_aplicado NOT IN (SELECT id_detalle FROM planilla_detalle);

-- 2) Los movimientos huérfanos vuelven a quedar pendientes:
UPDATE movimientos_empleado
SET id_detalle_aplicado = NULL
WHERE id_detalle_aplicado IS NOT NULL
  AND id_detalle_aplicado NOT IN (SELECT id_detalle FROM planilla_detalle);
