-- Marca una compra como "deuda anterior" (saldo previo a usar el sistema, sin
-- productos ni movimiento de stock detrás). Ejecutar manualmente en la BD.

ALTER TABLE compras ADD COLUMN deuda_anterior CHAR(1) NOT NULL DEFAULT '0' AFTER forma_pago;
