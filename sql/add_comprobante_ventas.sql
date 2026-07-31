-- ============================================================
-- Migración: Permite unir varias ventas (notas de venta) en un
-- solo comprobante (boleta/factura).
-- Ejecutar una sola vez en la base de datos.
-- ============================================================

CREATE TABLE `comprobante_ventas` (
  `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `comprobante` INT UNSIGNED NOT NULL,
  `venta`       INT UNSIGNED NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_comprobante` (`comprobante`),
  KEY `idx_venta` (`venta`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- Backfill: los comprobantes ya emitidos (1 venta cada uno) quedan
-- representados igual en la tabla puente, para que las lecturas nuevas
-- (inc/get_ventas.php) funcionen también con comprobantes históricos.
INSERT INTO `comprobante_ventas` (`comprobante`, `venta`)
SELECT `id_comprobante`, `venta` FROM `comprobantes` WHERE `venta` IS NOT NULL AND `venta` <> 0;
