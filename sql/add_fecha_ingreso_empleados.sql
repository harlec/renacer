-- Fecha de ingreso del colaborador, para mostrarla en la boleta de pago.
-- Ejecutar una sola vez en la base de datos.
ALTER TABLE `empleados`
    ADD COLUMN `fecha_ingreso` DATE NULL
        COMMENT 'Fecha en que el colaborador ingresó a trabajar';
