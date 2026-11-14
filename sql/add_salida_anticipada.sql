-- ============================================================
-- Migración: nuevo tipo de descuento "salida_anticipada" (salir antes de la
-- hora programada; se descuenta el tiempo real, x1). Ejecutar una sola vez (después de add_saldo_anterior_planilla.sql).
-- ============================================================
ALTER TABLE `planilla_descuentos`
    MODIFY COLUMN `tipo` ENUM('tardanza','abarrotes','adelanto','falta','prestamo','afp','saldo_anterior','salida_anticipada') NOT NULL;
