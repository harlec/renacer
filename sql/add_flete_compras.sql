-- ============================================================
-- Migración: flete en compras. El flete se registra en la compra (por compra
-- completa o por bulto), se prorratea entre los items para obtener el costo
-- real, y genera automáticamente un gasto (categoría "Flete") vinculado a la
-- compra. compras.total NO incluye el flete (es lo que se debe al proveedor).
-- Ejecutar una sola vez en la base de datos.
-- ============================================================
ALTER TABLE `compras`
    ADD COLUMN `flete_modo`   ENUM('ninguno','total','bulto') NOT NULL DEFAULT 'ninguno',
    ADD COLUMN `flete_tarifa` DECIMAL(10,2) NOT NULL DEFAULT 0 COMMENT 'monto ingresado: total de la compra o precio por bulto',
    ADD COLUMN `flete_bultos` INT NOT NULL DEFAULT 0,
    ADD COLUMN `flete_total`  DECIMAL(10,2) NOT NULL DEFAULT 0 COMMENT 'flete total de la compra (en soles)';

ALTER TABLE `detalle_compras`
    ADD COLUMN `flete` DECIMAL(10,2) NOT NULL DEFAULT 0 COMMENT 'parte del flete prorrateada a este item';

ALTER TABLE `gastos`
    ADD COLUMN `compra` INT UNSIGNED NULL COMMENT 'compra a la que pertenece (flete)',
    ADD KEY `idx_compra` (`compra`);
