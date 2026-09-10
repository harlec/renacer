-- Tablas del módulo de preventas (captura móvil por el cliente + asignación
-- de variante y procesamiento por un admin). Ejecutar manualmente en la BD.

CREATE TABLE preventa (
  id_preventa INT AUTO_INCREMENT PRIMARY KEY,
  cliente INT NOT NULL,
  fecha DATETIME NOT NULL,
  estado ENUM('0','1','2') NOT NULL DEFAULT '0', -- 0 pendiente, 1 procesada, 2 anulada
  venta_generada INT NULL, -- id_venta resultante al procesar
  KEY idx_preventa_estado (estado),
  KEY idx_preventa_cliente (cliente)
);

CREATE TABLE detalle_preventa (
  id_detalle INT AUTO_INCREMENT PRIMARY KEY,
  preventa INT NOT NULL,
  producto INT NOT NULL,
  cantidad DECIMAL(10,3) NOT NULL,
  id_vp INT NULL,        -- variante asignada por el admin al procesar
  precio DECIMAL(10,2) NULL,
  estado ENUM('0','1') NOT NULL DEFAULT '0',
  KEY idx_detalle_preventa (preventa)
);
