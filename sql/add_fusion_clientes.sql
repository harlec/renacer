-- ============================================================
-- Migración: fusión de clientes duplicados.
-- Solo crea una tabla nueva (historial/auditoría de fusiones, necesaria para
-- poder deshacer). Un cliente fusionado queda con clientes.estado = '2'.
-- Ejecutar una sola vez en la base de datos.
-- ============================================================
CREATE TABLE `cliente_fusiones` (
  `id_fusion`      INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_principal`   INT UNSIGNED NOT NULL,
  `id_duplicado`   INT UNSIGNED NOT NULL,
  `ventas_ids`     MEDIUMTEXT NULL COMMENT 'ids de ventas movidas (JSON)',
  `preventa_ids`   MEDIUMTEXT NULL COMMENT 'ids de preventas movidas (JSON)',
  `datos_rellenados` TEXT NULL COMMENT 'doc/telefono/email que el principal heredó del duplicado (JSON)',
  `usuario`        INT NOT NULL,
  `fecha`          DATETIME NOT NULL,
  `deshecha`       TINYINT(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id_fusion`),
  KEY `idx_dup` (`id_duplicado`),
  KEY `idx_principal` (`id_principal`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
