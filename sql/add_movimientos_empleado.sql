-- Adelantos y crédito de abarrotes entregados a un empleado en cualquier momento (no
-- depende de que ya exista una planilla generada). Cuando se genera un periodo de
-- planilla que cubre la fecha del movimiento, se aplica automáticamente como descuento
-- (igual que ya pasa con las tardanzas) y queda marcado como aplicado.

CREATE TABLE movimientos_empleado (
  id_movimiento INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  id_empleado INT UNSIGNED NOT NULL,
  tipo ENUM('adelanto','abarrotes') NOT NULL,
  fecha DATE NOT NULL,
  importe DECIMAL(10,2) NOT NULL,
  descripcion VARCHAR(120) NULL,
  usuario INT NULL,
  id_detalle_aplicado INT UNSIGNED NULL,
  fecha_creacion DATETIME DEFAULT CURRENT_TIMESTAMP
);
