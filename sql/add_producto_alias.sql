-- Alias/sinónimos por producto para "Tomar pedido con IA": apodos regionales que no se parecen
-- en nada al nombre real del producto (ej. "casillero de huevo", "cubeta de huevo" -> el huevo)
-- y que por eso ninguna comparación de texto puede detectar de forma confiable por sí sola.
-- Administrable desde alias_productos.php. Ejecutar manualmente en la BD antes de usarlo.

CREATE TABLE IF NOT EXISTS producto_alias (
  id_alias INT AUTO_INCREMENT PRIMARY KEY,
  alias VARCHAR(191) NOT NULL,
  id_producto INT NOT NULL,
  fecha_creacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_producto_alias_producto (id_producto),
  CONSTRAINT fk_producto_alias_producto FOREIGN KEY (id_producto) REFERENCES productos(id_producto)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
