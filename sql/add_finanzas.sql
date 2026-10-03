-- ============================================================
-- Migración: módulo Finanzas (Gastos + Balance por periodos).
-- Solo crea tablas nuevas; no modifica ventas, compras ni planillas.
-- Ejecutar una sola vez en la base de datos.
-- ============================================================
CREATE TABLE `gastos` (
  `id_gasto`    INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `fecha`       DATE NOT NULL,
  `tipo`        ENUM('fijo','diario','puntual') NOT NULL DEFAULT 'puntual',
  `categoria`   VARCHAR(60) NOT NULL COMMENT 'ej. Luz, Alquiler, Pasajes',
  `descripcion` VARCHAR(160) NULL,
  `monto`       DECIMAL(10,2) NOT NULL,
  `metodo`      VARCHAR(30) NOT NULL DEFAULT 'efectivo',
  `usuario`     INT NOT NULL,
  `estado`      CHAR(1) NOT NULL DEFAULT '1' COMMENT '1 activo, 2 anulado',
  `fecha_registro` DATETIME NOT NULL,
  PRIMARY KEY (`id_gasto`),
  KEY `idx_fecha` (`fecha`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `caja_periodos` (
  `id_cperiodo`  INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `nombre`       VARCHAR(60) NOT NULL,
  `fecha_inicio` DATE NOT NULL,
  `fecha_fin`    DATE NOT NULL,
  `caja_inicial` DECIMAL(12,2) NOT NULL DEFAULT 0,
  `usuario`      INT NOT NULL,
  `fecha_registro` DATETIME NOT NULL,
  PRIMARY KEY (`id_cperiodo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
