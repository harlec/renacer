-- ============================================================
-- Reset de comprobantes de prueba (Nubefact DEMO) antes de pasar
-- a producción. Ejecutar UNA VEZ, manualmente, en la base de datos.
--
-- ADVERTENCIA: operación destructiva e irreversible. Haz un backup
-- (mysqldump o export desde phpMyAdmin) de al menos las tablas
-- `comprobantes`, `comprobante_ventas` y `ventas` antes de correrlo.
--
-- OJO: esto borra TODAS las filas de `comprobantes`. Si en esta base
-- ya existían comprobantes reales (de antes de las pruebas, contra
-- producción), este script también los borraría. Si no estás 100%
-- seguro de que todo lo que hay hoy en `comprobantes` es de las
-- pruebas DEMO, revisa la tabla primero (por ejemplo filtrando por
-- fecha) antes de correr el DELETE sin condición.
-- ============================================================

-- 1) Libera las ventas que quedaron marcadas como facturadas (estado=1)
--    por los comprobantes de prueba, para que puedan facturarse de
--    verdad en producción.
UPDATE `ventas` v
JOIN `comprobante_ventas` cv ON cv.venta = v.id_venta
SET v.estado = '0'
WHERE v.estado = '1';

-- 2) Borra el vínculo comprobante-ventas (tabla puente de "unir varias
--    notas de venta").
DELETE FROM `comprobante_ventas`;

-- 3) Borra los comprobantes de prueba.
DELETE FROM `comprobantes`;

-- 4) Opcional: limpia los contadores viejos (ya no los usa el sistema,
--    el correlativo ahora se verifica en vivo contra Nubefact, pero no
--    está de más dejarlos en 0).
UPDATE `configuracion` SET `valor` = '0' WHERE `parametro` IN ('boleta', 'factura');
