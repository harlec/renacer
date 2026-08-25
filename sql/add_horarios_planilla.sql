-- Horarios por tipo de día (override por empleado) y catálogo de plantillas de periodo de planilla.
-- hora_ingreso/hora_salida (ya existentes en empleados) pasan a ser el override de Lunes-Viernes.

ALTER TABLE empleados
  ADD COLUMN hora_ingreso_sab TIME NULL,
  ADD COLUMN hora_salida_sab  TIME NULL,
  ADD COLUMN hora_ingreso_dom TIME NULL,
  ADD COLUMN hora_salida_dom  TIME NULL;

CREATE TABLE planilla_periodo_plantillas (
  id_plantilla INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(60) NOT NULL,
  dia_inicio TINYINT UNSIGNED NOT NULL,
  dia_fin_tipo ENUM('fijo','fin_mes') NOT NULL DEFAULT 'fijo',
  dia_fin TINYINT UNSIGNED NULL,
  estado ENUM('1','0') NOT NULL DEFAULT '1',
  fecha_creacion DATETIME DEFAULT CURRENT_TIMESTAMP
);
