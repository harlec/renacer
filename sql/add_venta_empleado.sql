-- Permite asociar una venta a un empleado (consumo de abarrotes a crédito que se
-- descuenta de su próximo pago de planilla, en vez de cobrarse en caja) y enlazar el
-- movimiento de planilla correspondiente con la venta real que descontó el stock.

ALTER TABLE ventas ADD COLUMN id_empleado INT UNSIGNED NULL;
ALTER TABLE movimientos_empleado ADD COLUMN id_venta INT UNSIGNED NULL;
