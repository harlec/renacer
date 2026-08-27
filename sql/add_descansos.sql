-- ============================================================
-- Migración: días de descanso programados por empleado (2 o 3 al mes,
-- alternando). Un descanso programado bloquea la fila de ese día en
-- Asistencia y nunca cuenta como falta, para que no afecte el pago.
-- Ejecutar una sola vez en la base de datos.
-- ============================================================
CREATE TABLE `empleado_descansos` (
  `id_descanso`     INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_empleado`     INT UNSIGNED NOT NULL,
  `fecha`           DATE NOT NULL,
  `usuario`         INT NULL,
  `fecha_registro`  DATETIME NOT NULL,
  PRIMARY KEY (`id_descanso`),
  UNIQUE KEY `uq_emp_fecha` (`id_empleado`,`fecha`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
