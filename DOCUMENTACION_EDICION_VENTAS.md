# Funcionalidad de Edición de Ventas

## Descripción
Se ha implementado una funcionalidad completa para editar ventas existentes en el sistema, permitiendo modificar productos, cantidades, precios, y agregar o quitar items de una venta.

## Archivos Creados/Modificados

### Nuevos Archivos:
1. **`editar_venta.php`** - Interfaz principal para editar ventas
2. **`inc/procesar_edicion_venta.php`** - Lógica de procesamiento de ediciones
3. **`inc/logs_auditoria.php`** - Sistema de logs de auditoría
4. **`inc/ver_detalle_log.php`** - Vista de detalles de logs
5. **`logs_auditoria.php`** - Panel de administración de logs
6. **`sql/crear_tabla_logs.sql`** - Script SQL para tabla de auditoría

### Archivos Modificados:
1. **`ventas.php`** - Agregado botón de editar para ventas sin comprobante

## Funcionalidades Implementadas

### 1. Edición de Ventas
- **Restricciones de Seguridad:**
  - Solo se pueden editar ventas con `estado = '0'` (sin comprobante generado)
  - Solo el usuario que creó la venta o administradores pueden editarla
  - Validaciones de stock en tiempo real

- **Operaciones Permitidas:**
  - Modificar fecha de venta
  - Cambiar cliente
  - Modificar tipo (Contado/Crédito) y forma de pago
  - Agregar nuevos productos
  - Modificar cantidades de productos existentes
  - Cambiar precios de productos
  - Eliminar productos de la venta

### 2. Manejo Automático de Stock
- **Devolución de Stock:** Cuando se eliminan productos o se reducen cantidades
- **Descuento de Stock:** Cuando se agregan productos o se aumentan cantidades
- **Historial Completo:** Todos los movimientos se registran en la tabla `stock`
- **Motivos Específicos:** 
  - `V-{id_venta}-EDIT` para nuevos egresos por edición
  - `EV-{id_venta}-EDIT` para devoluciones por edición

### 3. Sistema de Auditoría
- **Tabla `log_ediciones`:** Registra todas las modificaciones
- **Información Registrada:**
  - Tabla y registro afectado
  - Usuario que realizó la modificación
  - Fecha y hora exacta
  - IP del usuario
  - Datos anteriores y nuevos (formato JSON)
  - Observaciones del cambio

### 4. Panel de Logs (Solo Administradores)
- Vista completa de todas las ediciones
- Filtros y búsqueda avanzada
- Detalles completos de cada modificación
- Comparación de datos anteriores vs nuevos

## Flujo de Operación

### Al Editar una Venta:
1. **Validaciones Iniciales**
   - Verificar permisos del usuario
   - Confirmar que la venta se puede editar
   - Validar datos recibidos

2. **Procesamiento de Stock**
   - Revertir stock de productos eliminados/reducidos
   - Validar stock disponible para productos nuevos/aumentados
   - Actualizar registros en tabla `stock`
   - Actualizar `stockp` en tabla `productos`

3. **Actualización de Datos**
   - Eliminar detalle anterior de la venta
   - Insertar nuevo detalle con productos actualizados
   - Actualizar datos generales de la venta

4. **Auditoría**
   - Registrar cambios en tabla de logs
   - Guardar datos anteriores y nuevos para comparación

## Seguridad Implementada

### Control de Acceso:
- Verificación de sesión activa
- Control de permisos por tipo de usuario
- Validación de propiedad de la venta

### Validaciones de Negocio:
- Stock insuficiente → Error y rollback
- Venta con comprobante → No editable
- Datos inválidos → Rechazo de operación

### Auditoría Completa:
- Registro de todas las acciones
- Trazabilidad completa de cambios
- IP y timestamp de cada operación

## Instalación

### 1. Crear Tabla de Logs (Opcional - se crea automáticamente)
```sql
-- Ejecutar el archivo sql/crear_tabla_logs.sql
-- O acceder a inc/logs_auditoria.php para creación automática
```

### 2. Verificar Permisos
- Los usuarios normales pueden editar solo sus propias ventas
- Los administradores pueden editar cualquier venta
- Los operadores no tienen acceso a edición

### 3. Probar Funcionalidad
1. Ir a "Listar ventas"
2. Buscar una venta con estado "Sin comprobante" 
3. Hacer clic en el botón amarillo "Editar" (ícono lápiz)
4. Realizar modificaciones
5. Verificar cambios en "Ver venta"
6. Administradores: Revisar logs en `logs_auditoria.php`

## Consideraciones de Producción

### Rendimiento:
- Las operaciones son atómicas y eficientes
- Se minimiza el número de consultas SQL
- Índices apropiados en tabla de logs

### Backup y Recuperación:
- Todos los cambios quedan registrados en logs
- Posibilidad de recuperar estados anteriores
- Historial completo de stock mantenido

### Monitoreo:
- Logs detallados para debugging
- Captura de errores y excepciones
- Auditoría completa para compliance

## Notas Técnicas

### Compatibilidad:
- Compatible con el sistema SDBA existente
- No modifica estructura de tablas principales
- Mantiene compatibilidad con reportes existentes

### Extensibilidad:
- Fácil agregar nuevas validaciones
- Sistema de logs reutilizable para otras funciones
- Arquitectura modular y mantenible

---

**Importante:** Esta funcionalidad está diseñada para sistemas en producción con altos estándares de seguridad y auditoría. Todos los cambios son reversibles y trazables.