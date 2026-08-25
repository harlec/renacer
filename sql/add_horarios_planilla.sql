-- Horarios por tipo de día (override por empleado).
-- hora_ingreso/hora_salida (ya existentes en empleados) pasan a ser el override de Lunes-Viernes.

ALTER TABLE empleados
  ADD COLUMN hora_ingreso_sab TIME NULL,
  ADD COLUMN hora_salida_sab  TIME NULL,
  ADD COLUMN hora_ingreso_dom TIME NULL,
  ADD COLUMN hora_salida_dom  TIME NULL;

-- Nota: una versión anterior de esta migración creaba `planilla_periodo_plantillas`
-- (catálogo de plantillas de periodo con día fijo del mes). Se descartó: la quincena
-- real es rotativa cada 15 días exactos (ej. 31-14, luego 15-29), no atada a un día
-- fijo del calendario, así que un catálogo de plantillas por día de mes no aplicaba.
-- "Nueva planilla" ahora solo sugiere fecha_inicio = fin del periodo anterior + 1 día,
-- editable a mano. Si esa tabla ya se creó en tu base, no hace falta usarla ni borrarla.
