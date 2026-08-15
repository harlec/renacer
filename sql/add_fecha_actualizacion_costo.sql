-- ============================================================
-- Migración: permite saber cuándo se actualizó por última vez el
-- costo de un producto (precio_compra) o de una variante (precioc_vp),
-- para detectar costos desactualizados en el análisis de márgenes.
-- Ejecutar una sola vez en la base de datos.
-- ============================================================
ALTER TABLE `productos`
    ADD COLUMN `fecha_actualizacion_costo` DATE NULL
    COMMENT 'Última vez que cambió precio_compra; NULL si nunca se cargó un costo';

ALTER TABLE `variante_p`
    ADD COLUMN `fecha_actualizacion_costo` DATE NULL
    COMMENT 'Última vez que cambió precioc_vp; NULL si nunca se cargó un costo';
