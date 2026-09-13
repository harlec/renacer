-- Agrega trazabilidad de origen a las preventas creadas por interpretación de IA.
-- Ejecutar manualmente en la BD antes de usar pedido_ia.php.

ALTER TABLE preventa
  ADD COLUMN origen ENUM('manual','ia') NOT NULL DEFAULT 'manual' AFTER estado,
  ADD COLUMN texto_ia TEXT NULL AFTER origen;
