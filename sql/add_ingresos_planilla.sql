-- ============================================================
-- Migración: ingresos extra en planilla (feriado trabajado y asignación familiar).
--  - asistencias.feriado: se marca en Asistencia que el colaborador trabajó un feriado;
--    al generar la planilla se paga un día adicional (doble pago por ese día).
--  - empleados.asignacion_familiar: si está activa, cada planilla suma el 10% de la
--    remuneración mínima vital (config planilla_rmv), prorrateado por los días del periodo.
--  - planilla_ingresos: lo que se suma al sueldo (espejo de planilla_descuentos).
-- Ejecutar una sola vez en la base de datos.
-- ============================================================
ALTER TABLE `asistencias`
    ADD COLUMN `feriado` TINYINT(1) NOT NULL DEFAULT 0
        COMMENT '1 = trabajó en feriado (se paga doble ese día)';

ALTER TABLE `empleados`
    ADD COLUMN `asignacion_familiar` ENUM('0','1') NOT NULL DEFAULT '0'
        COMMENT 'Si el colaborador recibe asignación familiar (10% de la RMV)';

CREATE TABLE `planilla_ingresos` (
  `id_ingreso`   INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_detalle`   INT UNSIGNED NOT NULL,
  `tipo`         ENUM('feriado','asignacion_familiar') NOT NULL,
  `fecha`        DATE NOT NULL,
  `importe`      DECIMAL(10,2) NOT NULL,
  `descripcion`  VARCHAR(120) NULL,
  `usuario`      INT NULL,
  PRIMARY KEY (`id_ingreso`),
  KEY `idx_detalle` (`id_detalle`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
