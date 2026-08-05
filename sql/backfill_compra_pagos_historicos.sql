-- ============================================================
-- Backfill: las compras registradas ANTES de add_credito_compras.sql
-- no tienen ninguna fila en compra_pagos (esa tabla no existía), así
-- que cuentas_x_pagar.php las muestra a todas como saldo pendiente
-- por el total completo, aunque en la realidad ya fueron pagadas
-- (el sistema viejo no distinguía contado/crédito).
--
-- Esto marca como "pagadas por el total" a todas las compras que
-- todavía no tengan ningún pago registrado, sin tocar las que ya
-- se crearon con el flujo nuevo (esas ya insertan su propio pago
-- si son "contado", o se dejan pendientes a propósito si son "crédito").
--
-- Ejecutar una sola vez, después de add_credito_compras.sql.
-- ============================================================
INSERT INTO compra_pagos (compra, monto, metodo, usuario, fecha)
SELECT c.id_compra, c.total, 'efectivo', c.usuario, NOW()
FROM compras c
WHERE c.estado != '2'
  AND c.total > 0
  AND NOT EXISTS (SELECT 1 FROM compra_pagos cp WHERE cp.compra = c.id_compra);
