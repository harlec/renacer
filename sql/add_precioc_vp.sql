-- Add purchase price column to variante_p table
ALTER TABLE variante_p
    ADD COLUMN precioc_vp DECIMAL(10,2) NOT NULL DEFAULT 0 AFTER precio_vp;
