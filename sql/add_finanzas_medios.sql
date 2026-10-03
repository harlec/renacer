-- Caja inicial desglosada por medio de pago (efectivo, yape, bbva...) de cada periodo.
-- Ejecutar una sola vez, después de add_finanzas.sql.
CREATE TABLE `caja_periodo_saldos` (
  `id_cperiodo` INT UNSIGNED NOT NULL,
  `metodo`      VARCHAR(30) NOT NULL,
  `monto`       DECIMAL(12,2) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id_cperiodo`,`metodo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
