-- Recalcula horas_trabajadas en asistencias ya guardadas donde el colaborador llegó antes
-- de su horario programado: antes se le daba crédito por ese tiempo extra; ahora, igual que
-- en inc/registrar_asistencia.php, el cálculo parte de la hora programada, no de la hora
-- real de llegada temprana (la hora real queda intacta en hora_entrada_real, solo cambia
-- el valor calculado horas_trabajadas). No toca minutos_tardanza (que ya solo contaba
-- llegadas tarde) ni ningún descuento ya generado en planillas — horas_trabajadas es solo
-- informativo. Ejecutar una sola vez.

UPDATE asistencias
SET horas_trabajadas = ROUND(
        GREATEST(TIME_TO_SEC(TIMEDIFF(hora_salida_real, hora_entrada_prog)), 0) / 3600
    , 2)
WHERE hora_entrada_real IS NOT NULL
  AND hora_salida_real IS NOT NULL
  AND hora_entrada_prog IS NOT NULL
  AND hora_entrada_real < hora_entrada_prog;
