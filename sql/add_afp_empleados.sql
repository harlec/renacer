-- ============================================================
-- Migración: descuento de AFP por empleado. Se marca al registrar/editar
-- al colaborador (AFP sí/no) junto con un monto fijo de descuento mensual;
-- si está activado, cada planilla que se genere aplica automáticamente la
-- mitad de ese monto como descuento (una mitad por quincena).
-- Ejecutar una sola vez en la base de datos.
-- ============================================================
ALTER TABLE `empleados`
    ADD COLUMN `afp` ENUM('0','1') NOT NULL DEFAULT '0'
        COMMENT 'Si el colaborador tiene descuento de AFP',
    ADD COLUMN `afp_monto_mensual` DECIMAL(10,2) NOT NULL DEFAULT 0
        COMMENT 'Monto mensual de descuento AFP; se aplica la mitad en cada quincena';

ALTER TABLE `planilla_descuentos`
    MODIFY COLUMN `tipo` ENUM('tardanza','abarrotes','adelanto','falta','prestamo','afp') NOT NULL;
