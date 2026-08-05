-- ============================================================
-- Migración: permite marcar una compra como "al contado" o "a
-- crédito" (con fecha de compromiso de pago), y registrar abonos
-- parciales contra ella hasta cubrir el total. Espejo de lo que
-- ya existe para ventas (ventas.fecha_compromiso_pago + venta_pagos).
-- Ejecutar una sola vez en la base de datos.
-- ============================================================
ALTER TABLE `compras`
    ADD COLUMN `forma_pago` ENUM('contado','credito') NOT NULL DEFAULT 'contado'
        COMMENT 'contado: se registra pagada de una vez al ingresar la compra. credito: queda pendiente en Cuentas x pagar',
    ADD COLUMN `fecha_compromiso_pago` DATE NULL
        COMMENT 'Solo aplica si forma_pago = credito: fecha en la que se compromete el pago al proveedor';

CREATE TABLE `compra_pagos` (
  `id_pago`   INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `compra`    INT UNSIGNED NOT NULL,
  `monto`     DECIMAL(10,2) NOT NULL,
  `metodo`    VARCHAR(30) NOT NULL DEFAULT 'efectivo',
  `usuario`   INT NOT NULL,
  `fecha`     DATETIME NOT NULL,
  PRIMARY KEY (`id_pago`),
  KEY `idx_compra` (`compra`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
