-- Tabla para auditoría de ediciones de ventas
CREATE TABLE IF NOT EXISTS `log_ediciones` (
  `id_log` int(11) NOT NULL AUTO_INCREMENT,
  `tabla_afectada` varchar(50) NOT NULL,
  `id_registro` int(11) NOT NULL,
  `accion` varchar(20) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `fecha_edicion` datetime NOT NULL,
  `datos_anteriores` text,
  `datos_nuevos` text,
  `ip_usuario` varchar(45),
  `observaciones` text,
  PRIMARY KEY (`id_log`),
  KEY `idx_tabla_registro` (`tabla_afectada`, `id_registro`),
  KEY `idx_usuario` (`usuario_id`),
  KEY `idx_fecha` (`fecha_edicion`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- Comentarios para documentar el uso
-- tabla_afectada: 'ventas', 'detalle_ventas', etc.
-- accion: 'EDIT', 'DELETE', 'INSERT'
-- datos_anteriores: JSON con los datos antes de la edición
-- datos_nuevos: JSON con los datos después de la edición