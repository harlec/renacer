-- ============================================================
-- Migración: permite dejar una venta pendiente "a crédito" con
-- una fecha en la que el cliente se compromete a pagar, para que
-- deje de aparecer en la cola normal de cobro y se liste aparte
-- en la pestaña "Crédito" de caja_pagos.php.
-- Ejecutar una sola vez en la base de datos.
-- ============================================================
ALTER TABLE `ventas`
    ADD COLUMN `fecha_compromiso_pago` DATE NULL
    COMMENT 'Si no es NULL, la venta está "a crédito": el cliente pagará después, en esta fecha aproximada';
