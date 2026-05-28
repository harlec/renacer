-- ============================================================
-- Migración: Agrega modo tablet a tabla usuarios
-- Ejecutar una sola vez en la base de datos.
-- ============================================================
ALTER TABLE `usuarios`
    ADD COLUMN `tablet_mode` TINYINT(1) NOT NULL DEFAULT 0
    COMMENT '1 = redirigir a tablet.php al iniciar sesión';
