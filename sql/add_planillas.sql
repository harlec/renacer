-- ============================================================
-- Migración: horario y sueldo por empleado + módulo de Planillas
-- (asistencia diaria con cálculo de tardanzas, y generación de
-- planillas quincenales con descuentos por fecha+importe, espejo
-- del patrón compras/compra_pagos usado en Cuentas x pagar).
-- Ejecutar una sola vez en la base de datos.
-- ============================================================
ALTER TABLE `empleados`
    ADD COLUMN `cargo` VARCHAR(60) NULL
        COMMENT 'Puesto/ocupación, ej. BOLETEADORA',
    ADD COLUMN `hora_ingreso` TIME NULL
        COMMENT 'Horario programado de entrada',
    ADD COLUMN `hora_salida` TIME NULL
        COMMENT 'Horario programado de salida',
    ADD COLUMN `sueldo_mensual` DECIMAL(10,2) NOT NULL DEFAULT 0
        COMMENT 'Sueldo mensual base; el pago es quincenal (cada 15 días)';

CREATE TABLE `asistencias` (
  `id_asistencia`      INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_empleado`        INT UNSIGNED NOT NULL,
  `fecha`              DATE NOT NULL,
  `hora_entrada_prog`  TIME NULL,
  `hora_entrada_real`  TIME NULL,
  `hora_salida_prog`   TIME NULL,
  `hora_salida_real`   TIME NULL,
  `minutos_tardanza`   INT NOT NULL DEFAULT 0,
  `horas_trabajadas`   DECIMAL(5,2) NULL,
  `observacion`        VARCHAR(20) NULL COMMENT 'PUNTUAL, RETARDADO o FALTO',
  `usuario`            INT NULL,
  PRIMARY KEY (`id_asistencia`),
  UNIQUE KEY `uq_emp_fecha` (`id_empleado`,`fecha`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `planilla_periodos` (
  `id_periodo`      INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `fecha_inicio`    DATE NOT NULL,
  `fecha_fin`       DATE NOT NULL,
  `dias`            INT UNSIGNED NOT NULL COMMENT 'DATEDIFF(fecha_fin,fecha_inicio)+1',
  `estado`          ENUM('abierto','cerrado') NOT NULL DEFAULT 'abierto',
  `fecha_creacion`  DATETIME NOT NULL,
  PRIMARY KEY (`id_periodo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `planilla_detalle` (
  `id_detalle`      INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_periodo`      INT UNSIGNED NOT NULL,
  `id_empleado`     INT UNSIGNED NOT NULL,
  `sueldo_mensual`  DECIMAL(10,2) NOT NULL COMMENT 'snapshot al generar la planilla',
  `calculo_diario`  DECIMAL(10,2) NOT NULL COMMENT 'sueldo_mensual / 15',
  `sueldo_periodo`  DECIMAL(10,2) NOT NULL COMMENT 'calculo_diario * dias del periodo',
  PRIMARY KEY (`id_detalle`),
  UNIQUE KEY `uq_periodo_emp` (`id_periodo`,`id_empleado`),
  KEY `idx_periodo` (`id_periodo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `planilla_descuentos` (
  `id_descuento`  INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_detalle`    INT UNSIGNED NOT NULL,
  `tipo`          ENUM('tardanza','abarrotes','adelanto','falta','prestamo') NOT NULL,
  `fecha`         DATE NOT NULL,
  `importe`       DECIMAL(10,2) NOT NULL,
  `descripcion`   VARCHAR(120) NULL,
  `usuario`       INT NULL,
  PRIMARY KEY (`id_descuento`),
  KEY `idx_detalle` (`id_detalle`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
