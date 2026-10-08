# Renacer

Sistema de gestión y punto de venta (POS) de **Distribuidora Renacer**. Aplicación web en PHP + MySQL, con una versión de POS para tablet instalable como PWA.

## Stack

- **Backend:** PHP (páginas en la raíz, lógica y endpoints en `inc/`), MySQL, capa de acceso `inc/sdba/sdba.php`.
- **Frontend:** Bootstrap 3.3.7, jQuery 3.3.1, DataTables 1.10, Select2, SweetAlert2, jQuery UI, Chart.js 4 (dashboard), Font Awesome y Phosphor Icons.
- **Estilos:** `assets/css/sistema.css` (base, ~3.800 líneas), `assets/css/custom.css` (ajustes por pantalla), `assets/css/bootstrap.min.css`.
- **Facturación electrónica:** Nubefact (boletas, facturas, notas de crédito/débito). PDF con dompdf, tickets ESC/POS y RawBT.
- **IA:** pedidos interpretados por IA (`pedido_ia.php`, `inc/ia_helper.php`).
- **Migraciones SQL:** carpeta `sql/`.

## Módulos

| Módulo | Páginas principales |
|---|---|
| Escritorio | `dashboard.php` (ventas del día/mes, top productos y clientes, stock bajo, predicción de quiebre, gráfica 7 días) |
| Ventas | `venta.php`, `ventas.php`, `editar_venta.php`, `notas_venta.php`, `comprobantes.php` |
| Preventas | `preventas.php`, `preventa_nueva.php`, `procesar_preventa.php`, `pedido_ia.php` |
| Caja | `caja_pagos.php`, `cuentas_x_pagar.php` |
| Compras | `compra.php`, `compras.php`, `proveedores.php` |
| Productos | `ver_productos.php`, `agregar_producto.php`, `variantes.php`, `categorias.php`, `marcas.php`, `alias_productos.php` |
| Clientes | `ver_clientes.php`, `clientes_duplicados.php` (fusión y deshacer) |
| Gastos y balance | `gastos.php`, `balance.php` |
| RR. HH. | `ver_empleados.php`, `asistencia.php`, `descansos.php`, `planillas.php`, `configuracion_planillas.php` |
| Reportes | `reportes.php`, `reporte_ventas.php`, `reporte_evolucion.php`, `reporte_kardex.php`, `reporte_prediccion_stock.php`, `reporte_huevos.php`, entre otros |
| Configuración | `tablet_config_admin.php`, `configuracion_facturacion.php`, `alias_productos.php` |
| Auditoría | `logs_auditoria.php` |
| POS tablet | `tablet.php` (PWA, `manifest.json`, pantalla completa horizontal, fondo oscuro `#111`) |

Usuarios con rol `admin` ven todo el menú; el resto ve una versión reducida (ver `menu()` en `inc/control.php`).

## Estructura de la interfaz actual

- **Navbar superior** (`menu($n)` en `inc/control.php`): logo, íconos con etiqueta (Escritorio, Usuarios, Clientes, Productos, Ventas, Caja, Compras, Preventas, Gastos, Reportes, Config.) y saludo con botón de salir. El número indica la opción activa.
- **Submenú de pestañas** (`.submenu .subtop-tabs`) debajo del navbar en cada módulo (ej. Ventas: Registrar venta / Listar ventas / Facturar / Comprobantes).
- **Cuerpo** (`.kbg` > `.cuerpo`) con título `.titulo`, formularios y tablas DataTables.
- Los íconos del menú son imágenes sueltas en `assets/img/` (PNG/SVG), con estilos inconsistentes entre sí.
- Cada página incluye su propio `<head>` y estilos en línea; hay mucho CSS duplicado por pantalla.

## Puntos débiles de diseño (a mejorar)

1. Aspecto de plantilla antigua (Bootstrap 3): colores apagados (`#1d2939`, `#636e7b`, naranja `#ff9a34` en cabeceras), tipografía Lato con fallbacks del sistema.
2. Íconos del menú heterogéneos (PNG de distintos estilos, mezcla de SVG, Font Awesome y Phosphor).
3. Tablas DataTables densas y sin jerarquía visual; botones y estados (anulado, crédito, pagado, pendiente) sin un sistema de color coherente.
4. Formularios largos sin agrupación clara (venta, compra, producto, planillas).
5. Dashboard, reportes y pantallas nuevas (evolución de ventas, predicción de stock) tienen un estilo distinto al resto del sistema.
6. Uso en móvil limitado; la tablet (`tablet.php`) tiene su propio diseño oscuro y no comparte tokens con el sistema de escritorio.

## Objetivos del rediseño

- Un sistema de diseño único (colores, tipografía, espaciado, radios, sombras) definido como variables CSS y reutilizado en todas las pantallas, incluida la tablet.
- Navegación más clara y ligera, con íconos consistentes (una sola librería, preferible Phosphor, que ya se usa en el dashboard).
- Tablas más legibles: cabeceras sobrias, filas con hover, badges de estado, acciones agrupadas.
- Formularios en tarjetas con secciones y jerarquía clara; botón de acción principal evidente.
- Dashboard tipo tarjetas KPI + gráficas con paleta coherente.
- Responsive real (móvil y tablet), sin romper el POS de tablet.
- **Restricciones:** mantener PHP/Bootstrap 3 y los ids/clases que usa el JavaScript (`#items`, `#frmfactura`, `.cuerpo`, `.kbg`, etc.); no cambiar lógica de negocio ni endpoints; no romper la impresión de tickets ni PDFs.

## Pantallas prioritarias para el rediseño

1. `venta.php` (registro de venta, la más usada).
2. `dashboard.php`.
3. `ventas.php` / `comprobantes.php` (listados con filtros).
4. `tablet.php` (POS).
5. `reportes.php` y `reporte_evolucion.php`.
6. `ver_productos.php` / `ver_clientes.php`.

## Documentación adicional

- [DOCUMENTACION_EDICION_VENTAS.md](DOCUMENTACION_EDICION_VENTAS.md): edición de ventas y auditoría.
- [GUIA_PRUEBA_EDICION.md](GUIA_PRUEBA_EDICION.md): guía de pruebas de la edición de ventas.

## Notas

- Las credenciales de base de datos no deben documentarse aquí. Nota: `tablet.php` las tiene escritas directamente en el código; conviene moverlas a un archivo de configuración fuera del repositorio.
