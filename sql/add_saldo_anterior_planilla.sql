-- ============================================================
-- Migración: arrastre del saldo negativo de una planilla a la siguiente.
-- Si en una quincena los descuentos superan lo ganado (total a pagar negativo),
-- la planilla siguiente que se genere descuenta automáticamente ese faltante
-- (tipo 'saldo_anterior'). Ejecutar una sola vez, ANTES de generar la próxima planilla.
-- ============================================================
ALTER TABLE `planilla_descuentos`
    MODIFY COLUMN `tipo` ENUM('tardanza','abarrotes','adelanto','falta','prestamo','afp','saldo_anterior') NOT NULL;

-- ============================================================
-- RETROACTIVO (opcional): solo hace falta si la planilla siguiente a una con total
-- negativo YA fue generada antes de este cambio. Si todavía no la generaste, sáltate
-- esto: se arrastra solo al generarla.
-- Aplica a la planilla más reciente, solo si sigue 'abierta', y es idempotente
-- (no duplica si ya tiene saldo_anterior). Primero ejecuta la vista previa (A) y,
-- si los montos son correctos, el INSERT (B).
-- ============================================================

-- (A) Vista previa
SELECT e.nombres, e.apellidos, prev.fecha_inicio AS prev_inicio, prev.fecha_fin AS prev_fin,
       ROUND(-prev.neto, 2) AS saldo_a_arrastrar, cur.id_detalle AS detalle_actual
FROM (SELECT id_periodo, fecha_inicio FROM planilla_periodos ORDER BY fecha_fin DESC LIMIT 1) ult
INNER JOIN planilla_periodos pc ON pc.id_periodo = ult.id_periodo AND pc.estado = 'abierto'
INNER JOIN planilla_detalle cur ON cur.id_periodo = pc.id_periodo
INNER JOIN empleados e ON e.id_empleado = cur.id_empleado
INNER JOIN (
    SELECT pd.id_empleado, pp.fecha_inicio, pp.fecha_fin,
           pd.sueldo_periodo
             + (SELECT COALESCE(SUM(pi.importe),0) FROM planilla_ingresos pi WHERE pi.id_detalle = pd.id_detalle)
             - (SELECT COALESCE(SUM(px.importe),0) FROM planilla_descuentos px WHERE px.id_detalle = pd.id_detalle) AS neto,
           pp.id_periodo
    FROM planilla_detalle pd
    INNER JOIN planilla_periodos pp ON pp.id_periodo = pd.id_periodo
) prev ON prev.id_empleado = cur.id_empleado
      AND prev.id_periodo = (SELECT p2.id_periodo FROM planilla_periodos p2
                              WHERE p2.fecha_fin < pc.fecha_inicio ORDER BY p2.fecha_fin DESC LIMIT 1)
WHERE prev.neto < -0.005
  AND NOT EXISTS (SELECT 1 FROM planilla_descuentos x WHERE x.id_detalle = cur.id_detalle AND x.tipo = 'saldo_anterior');

-- (B) Aplicar
INSERT INTO planilla_descuentos (id_detalle, tipo, fecha, importe, descripcion)
SELECT cur.id_detalle, 'saldo_anterior', prev.fecha_fin, ROUND(-prev.neto, 2),
       CONCAT('Saldo negativo planilla ', DATE_FORMAT(prev.fecha_inicio, '%d/%m'), ' - ', DATE_FORMAT(prev.fecha_fin, '%d/%m'))
FROM (SELECT id_periodo, fecha_inicio FROM planilla_periodos ORDER BY fecha_fin DESC LIMIT 1) ult
INNER JOIN planilla_periodos pc ON pc.id_periodo = ult.id_periodo AND pc.estado = 'abierto'
INNER JOIN planilla_detalle cur ON cur.id_periodo = pc.id_periodo
INNER JOIN (
    SELECT pd.id_empleado, pp.fecha_inicio, pp.fecha_fin,
           pd.sueldo_periodo
             + (SELECT COALESCE(SUM(pi.importe),0) FROM planilla_ingresos pi WHERE pi.id_detalle = pd.id_detalle)
             - (SELECT COALESCE(SUM(px.importe),0) FROM planilla_descuentos px WHERE px.id_detalle = pd.id_detalle) AS neto,
           pp.id_periodo
    FROM planilla_detalle pd
    INNER JOIN planilla_periodos pp ON pp.id_periodo = pd.id_periodo
) prev ON prev.id_empleado = cur.id_empleado
      AND prev.id_periodo = (SELECT p2.id_periodo FROM planilla_periodos p2
                              WHERE p2.fecha_fin < pc.fecha_inicio ORDER BY p2.fecha_fin DESC LIMIT 1)
WHERE prev.neto < -0.005
  AND NOT EXISTS (SELECT 1 FROM planilla_descuentos x WHERE x.id_detalle = cur.id_detalle AND x.tipo = 'saldo_anterior');
