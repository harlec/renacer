# Guía de Prueba - Edición de Ventas

## ¿Qué se corrigió?

🔧 **Problema del "Ingresa un valor válido"**: Fixed!
- Cambié campos `number` por campos `text` con patrones de validación
- Eliminé restricciones `step` que causaban problemas con decimales
- Mejoré el manejo de valores con coma decimal

📊 **Validaciones mejoradas**:
- Conversión automática de comas a puntos
- Validación en tiempo real
- Campos con errores se marcan en rojo
- Mejor manejo de números decimales

🔍 **Debug habilitado temporalmente**:
- Los datos se envían a `debug_editar_venta.php` primero
- Se pueden ver todos los datos en consola del navegador
- Mensajes de error más específicos

## 🚀 Pasos para Probar:

### 1. Probar la Interfaz
1. Ve a **"Listar ventas"**
2. Busca una venta sin comprobante (estado=0)
3. Clic en botón amarillo **"Editar"** (icono lápiz)
4. **Modifica los valores**:
   - Cambia cantidad de 13 a otro número (ej: 10)
   - Cambia precio de 0.875 a otro (ej: 1.000)
   - El total debe actualizarse automáticamente

### 2. Probar Guardado (Modo Debug)
1. Completa tutti los campos obligatorios
2. Clic en **"Guardar Cambios"**
3. Abre la **consola del navegador** (F12)
4. Verifica que se muestra:
   - "Datos que se enviarán: {objeto con datos}"
   - "Respuesta del servidor: {respuesta exitosa}"

### 3. Si Todo Sale Bien
Una vez que confirmes que los datos se envían correctamente, te cambio la URL de vuelta al procesador real:

**En línea 404 de `editar_venta.php`**, cambiar:
```javascript
url: "/debug_editar_venta.php", // Temporalmente cambiado para debug
```

Por:
```javascript
url: "/inc/procesar_edicion_venta.php", // Procesador real
```

## 🔍 Cómo Ver el Debug:

### En el navegador:
1. F12 → Pestaña "Console"
2. Verás todos los datos que se envían
3. Cualquier error aparecerá detallado

### En debug_editar_venta.php:
Ve directamente a: `tu-dominio.com/debug_editar_venta.php`
- Si funciona, verás mensaje "No se recibieron datos POST"

## ❗ Posibles Problemas y Soluciones:

### Si sigue sin funcionar:
1. **Revisa la consola** del navegador por errores JavaScript
2. **Verifica permisos** de archivos PHP
3. **Confirma la sesión** - asegúrate de estar logueado
4. **Prueba con otro navegador** (Chrome/Firefox)

### Mensajes de Error Comunes:
- **"Faltan datos obligatorios"**: Llenar todos los campos requeridos
- **"No tienes permisos"**: Usar cuenta admin o dueño de la venta
- **"No se puede editar"**: Solo ventas sin comprobante (estado=0)

## 📞 Siguiente Paso:

Prueba ahora y me dices:
1. ✅ ¿Se carga la página sin errores?
2. ✅ ¿Los campos funcionan sin "Ingresa un valor válido"?
3. ✅ ¿Se puede hacer clic en "Guardar Cambios"?
4. ❓ ¿Qué mensaje aparece después de hacer clic?
5. ❓ ¿Qué se ve en la consola del navegador?

**Con esta información podremos terminar de ajustar lo que falte.** 🎯