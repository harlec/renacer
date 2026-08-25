-- Permite descontar un adelanto o consumo de abarrotes en varias cuotas (una por cada
-- planilla siguiente que se genere) en vez de descontarlo completo en la primera
-- planilla que cubra su fecha. Cada movimiento se divide en N cuotas (iguales por
-- defecto, editables al crear el movimiento) y cada planilla que se genera consume la
-- siguiente cuota pendiente del movimiento, sin importar el rango exacto de fechas de
-- esa planilla (las cuotas siguientes no tienen fecha propia: se aplican en orden a
-- medida que se van generando planillas).

ALTER TABLE movimientos_empleado
    ADD COLUMN partes INT UNSIGNED NOT NULL DEFAULT 1
        COMMENT 'Cantidad de cuotas en las que se descuenta este movimiento';

CREATE TABLE movimiento_cuotas (
  id_cuota INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  id_movimiento INT UNSIGNED NOT NULL,
  numero_cuota INT UNSIGNED NOT NULL,
  monto DECIMAL(10,2) NOT NULL,
  id_detalle_aplicado INT UNSIGNED NULL,
  KEY idx_movimiento (id_movimiento)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- Backfill: todo movimiento ya existente pasa a ser una única cuota (numero_cuota=1),
-- conservando si ya estaba aplicado o pendiente.
INSERT INTO movimiento_cuotas (id_movimiento, numero_cuota, monto, id_detalle_aplicado)
SELECT id_movimiento, 1, importe, id_detalle_aplicado FROM movimientos_empleado;

-- El estado de aplicación ahora vive por cuota (movimiento_cuotas.id_detalle_aplicado),
-- no por movimiento.
ALTER TABLE movimientos_empleado DROP COLUMN id_detalle_aplicado;
