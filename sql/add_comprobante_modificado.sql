-- ============================================================
-- Migración: enlaza cada nota de crédito con el comprobante que
-- corrige, para poder mostrar la referencia cruzada en pantalla.
-- Ejecutar una sola vez en la base de datos.
-- ============================================================
ALTER TABLE `comprobantes`
    ADD COLUMN `comprobante_modificado` INT UNSIGNED NULL
    COMMENT 'id_comprobante original que esta nota de crédito anula o corrige';
