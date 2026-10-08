# Handoff: Escritorio (dashboard.php) — rediseño

## Overview
Rediseño del Escritorio del sistema POS (Grupo Avasa / Renacer). Reemplaza el contenido de `dashboard.php`: KPIs compactos, gráfica de ventas 7 días por vendedor con detalle del día, balance del mes, medios de pago, cuentas por pagar, top productos, clientes (más consumen / recurrentes), clientes que dejaron de venir, quiebre previsto y stock bajo. También refresca el navbar (`menu($n)` en `inc/control.php`) con íconos Phosphor coherentes.

## About the Design Files
`Escritorio v2.dc.html` es una **referencia de diseño en HTML** (prototipo de aspecto y comportamiento), no código de producción. Ábrelo en un navegador (necesita `support.js` al lado). La tarea es **recrearlo en el stack existente**: PHP + MySQL, Bootstrap 3.3.7, jQuery 3.3.1, Chart.js 4, Phosphor Icons.

Restricciones del proyecto (del README del repo):
- Mantener PHP/Bootstrap 3 y los ids/clases que usa el JS (`.cuerpo`, `.kbg`, `#items`, `#frmfactura`, etc.).
- No cambiar lógica de negocio ni endpoints existentes; no romper tickets ni PDFs.
- Estilos nuevos como **variables CSS** en `assets/css/custom.css` (o un nuevo `assets/css/escritorio.css`), no inline. El prototipo usa estilos inline solo por cómo fue construido.

## Fidelity
**Alta fidelidad.** Colores, tipografía, espaciado, radios e interacciones son finales. Los **datos son de ejemplo** (salvo top productos/clientes/stock, tomados de una captura real) — todo debe salir de la BD.

## Layout general
- Fondo página `#f5f4f2`. Contenedor `main`: `max-width:1600px; margin:0 auto; padding:20px 24px 48px; gap:16px` vertical.
- Filas = `display:flex; flex-wrap:wrap; gap:16px`. Cada tarjeta usa `flex: <grow> 1 <basis>` para reacomodarse sola (en Bootstrap 3 se puede usar flex propio dentro de `.cuerpo`, no hace falta el grid de BS3).
- Tarjeta estándar: `background:#fff; border:1px solid #ebe7e2; border-radius:16px; padding:16px 20px; display:flex; flex-direction:column; gap:10–14px; min-width:0`. **Sin sombras.**
- Cabecera de tarjeta: chip de ícono 30×30, `border-radius:9px`, fondo tinte claro + ícono Phosphor duotone 17px del mismo tono; título 14px/600; a la derecha meta o link (12px).

Orden de filas:
1. Título: fecha “Jueves, 8 de octubre de 2026 · actualizado 11:00” (12px `#7a746e`) + h1 “Escritorio” 24px/600. A la derecha select “Mes”.
2. KPIs (4): dos grupos `flex:1 1 440px`, cada grupo grid 2 columnas `gap:12px` → 4 en fila en ancho, 2×2 en medio, nunca 3+1.
3. Ventas 7 días (`flex:999 1 560px`) + Detalle del día (`flex:1 1 300px`).
4. Balance del mes · Medios de pago · Cuentas por pagar (`flex:1 1 320px` c/u).
5. Top productos (`1 1 320px`) · Clientes (`1 1 360px`) · Clientes que dejaron de venir (`1 1 360px`).
6. `#inventario`: Quiebre previsto · Stock bajo (`1 1 440px` c/u).

## Componentes

### Navbar
- Blanco, alto 72px, `border-bottom:3px solid` naranja primario, sticky.
- Logo: “GRUPO” 12px + “AVASA” 24px/700, `#1f1d1b`.
- Ítems: columna ícono (28px) + etiqueta (13px), `padding:0 13px`, color texto `#6b665f`. Hover `background:#fbf3ef`.
- Activo: fondo naranja primario, texto e ícono blancos, ícono en peso `ph-fill`, label 600.
- Íconos (Phosphor duotone) y tono oklch(0.62 0.17 H):
  Escritorio `ph-house` 37 · Usuarios `ph-user-plus` 145 · Clientes `ph-users-three` 60 · Productos `ph-package` 75 · Ventas `ph-receipt` 230 · Caja `ph-cash-register` 150 · Compras `ph-shopping-cart` 170 · Preventas `ph-clipboard-text` 30 · Gastos `ph-wallet` 120 · Reportes `ph-chart-line-up` 250 · Config. `ph-gear` 210.
- Derecha: “Hola **HARS**” 17px + botón salir 36×36 radio 10, fondo `oklch(0.93 0.05 230)`, ícono `ph-bold ph-sign-out`.
- Overflow horizontal con scrollbar oculta. Usuarios no admin ven menú reducido (respetar `menu()`).

### KPIs
Tarjeta `padding:14px 16px; border-radius:14px`, fila: chip 36×36 (radio 10, ícono 20px) · bloque texto (label 12px `#7a746e`, valor IBM Plex Mono 600 `clamp(17px,1.7vw,21px)` `white-space:nowrap`, sub 11px `#9a948d`).
- Ventas hoy — `ph-coins`, tono 37. Valor + “N transacciones”. Mini barras 7 días a la derecha (5px ancho, gap 2, alto 30; hoy naranja, resto `#e6e1db`).
- Ventas del mes — `ph-calendar-check`, tono 145. Sub “2,771 trans. · día 8 de 31”.
- Ticket promedio — `ph-receipt`, tono 270. Sub “hoy S/ 49.30” (ventas hoy / transacciones hoy).
- Stock bajo — `ph-warning`, tono 25; valor color `#c2361a`; sub “24 sin stock”. Toda la tarjeta es link a `#inventario`, hover borde `#d9481c`.

### Ventas · últimos 7 días
- Barras apiladas por vendedor (recomendado: **Chart.js 4** `type:'bar'`, `stacked:true`, `borderRadius` arriba 5, `barPercentage` ~0.8, max ancho ~60px). Eje Y 0–30k con líneas punteadas `#ebe7e2`, base `#dcd8d3`. Sin línea de total.
- Etiqueta encima de cada barra: total en “k” (IBM Plex Mono 11px/600). Eje X “Vie 02/10”… 11.5px. Hoy en `#c2361a`.
- Día seleccionado: outline 2px `#1f1d1b` offset 2, etiqueta en 600. Selección por **hover** (por defecto: hoy).
- Leyenda: chips pill (`padding:4px 9px`, borde `#e4e0db`, 11.5px, cuadrado 8px color). Click = enfocar vendedor (demás segmentos opacidad 0.18; chip activo borde `#1f1d1b`); click otra vez = quitar.
- Total 7 días arriba a la derecha (mono 14px/600).
- Colores vendedor: `oklch(0.7 0.12 H)` con H: DAYANA 25, KIARA 65, SUSAN 215, HUEVOS 145, Robert 295, AREA EMBUTIDOS 260, Hugo Romero 340. Asignar hue por id de vendedor de forma estable.

### Detalle del día
- Label “Detalle del día” 12px + h2 15px/600 (“Hoy · jueves 08/10” o “Miércoles 07/10”).
- Total mono 24px/600 `#c2361a`.
- Lista de vendedores con venta > 0, orden desc: color · nombre · monto mono + % (`#9a948d`); barra 4px fondo `#f2efeb`, ancho = % del día.
- Se actualiza al hacer hover en la gráfica.

### Balance del mes
- Ícono `ph-scales` tono 145; link “Ver balance →” a `balance.php`.
- “Utilidad neta” + valor mono 24px/600 `oklch(0.5 0.13 145)` + pill “margen X%”.
- 4 barras (6px, radio 3): Ventas `oklch(0.65 0.14 145)`, Costo de ventas `oklch(0.8 0.06 75)`, Gastos `oklch(0.7 0.14 25)`, Utilidad neta `oklch(0.55 0.14 145)`; ancho relativo a Ventas.
- “Gastos por tipo”: chips `#f5f2ee`, nombre + monto (desde `gastos.php` agrupado por tipo).

### Medios de pago
- Ícono `ph-credit-card` tono 300. Dona 118px (Chart.js `doughnut`, cutout ~66%, o CSS `conic-gradient`) con centro “más usado / Efectivo / 43%”.
- Lista: color · método · % · monto (mono, `min-width:96px; nowrap`). Colores `oklch(0.7 0.13 H)`: Efectivo 145, Yape 300, Transferencia 230, Crédito 25, Plin 190, Tarjeta 75.
- Nota inferior (fondo `#f8f6f3`, radio 8): % de pagos digitales (Yape+Plin+Transferencia).
- Fuente: `caja_pagos.php` / pagos de ventas del mes agrupados por medio.

### Cuentas por pagar
- Ícono `ph-hand-coins` tono 75; “Ver todas →” a `cuentas_x_pagar.php`.
- Dos mini tarjetas: Pendiente (`#f8f6f3`) y Vencido · N (`#fbe4dc`, texto `#a3290f`), valores mono 17px/600.
- Lista ordenada por vencimiento: proveedor (ellipsis) · pill estado · monto.
  - Vencida → “Vencida hace N d”, `#fbe4dc`/`#a3290f`
  - ≤3 días → “En N días”, `#f6ecd6`/`#8a5a00`
  - resto → “Vence dd/mm”, `#f2efeb`/`#57524d`

### Top productos
- Ícono `ph-package` tono 75. Toggle segmentado “Monto / Cant.” (fondo `#f2efeb`, activo blanco + sombra mínima 0 1px 2px rgba(0,0,0,.08)).
- 6 filas: rank mono 11px `#9a948d` · nombre + barra 3px naranja (relativa al #1) · valor. “Ver los 20 →”.

### Clientes (tabs)
- Ícono `ph-users-three` tono 230. Toggle “Más consumen / Recurrentes”.
- Más consumen: rank · nombre + barra 3px `oklch(0.62 0.12 230)` · “N compras” · total.
- Recurrentes: nombre + “Última: hoy · ticket S/ X” · 8 cuadritos 10×10 radio 3 (semana con compra = `oklch(0.65 0.13 230)`, sin compra `#ebe7e2`) · visitas/mes (mono 600 `oklch(0.5 0.14 230)`).
- Recurrente = media de visitas/mes de los últimos 90 días; ordenar desc, top 6.

### Clientes que dejaron de venir
- Ícono `ph-user-minus` tono 25. Sub: “Compraban 4+ veces al mes y llevan más de 2 semanas sin volver”.
- Banda roja `#fbe4dc`: “Venta mensual en riesgo” + suma del gasto mensual promedio de estos clientes.
- Filas: nombre + “Última compra dd/mm · hace N días (rojo) · S/ X/mes” · 4 mini barras (visitas jul/ago/sep/oct: 2 primeras `oklch(0.75 0.06 230)`, recientes `oklch(0.8 0.1 60)`, 0 visitas `oklch(0.6 0.19 25)` con min-height 2px) · botón “Contactar” (`ph-whatsapp-logo`, fondo `oklch(0.94 0.06 150)`, texto `oklch(0.42 0.12 150)`) → `https://wa.me/51<telefono>`.
- Regla sugerida: promedio ≥4 visitas/mes en los 3 meses previos a los últimos 30 días **y** última compra hace >14 días. Excluir clientes genéricos (VARIOS, FACTURA MANUAL, HUEVOS - VARIOS). Ordenar por venta en riesgo desc, top 5.

### Quiebre previsto
- Ícono `ph-trend-down` tono 25; meta “852 por agotarse”. Banda “24 productos ya sin stock con ventas en los últimos 30 días”.
- 6 filas: producto · stock (mono) · pill “No alcanza hoy” (rojo) o “Se agota hoy” (ámbar). Reemplaza la etiqueta única “Urgente”. Link a `reporte_prediccion_stock.php`.

### Stock bajo
- Ícono `ph-warning` tono 75; meta “154 productos”. 7 filas: producto · badge stock (<0.5 rojo, resto ámbar; radio 4, mono 11.5px/600) · precio. Columna “Código” eliminada (venía vacía). Precio nulo → “—”.

## Interacciones
- Hover en barra → día seleccionado (detalle + outline).
- Click en chip de leyenda → enfocar/desenfocar vendedor.
- Toggles Monto/Cant. y Más consumen/Recurrentes → cambian datos sin recargar.
- Select de mes → recarga los datos del mes (GET `?mes=2026-10` o AJAX).
- Hover filas de tabla: `#faf8f6`. Links: `#d9481c`, hover `#a8350f`.
- Sin animaciones obligatorias.

## Estado / datos
Sugerido: un endpoint `inc/dashboard_data.php?mes=YYYY-MM` que devuelva JSON con:
`kpis, ventas7d[{fecha, total, porVendedor[{id,nombre,monto}]}], balance{ventas,costo,gastos,utilidad,gastosPorTipo[]}, mediosPago[{medio,monto}], cxp{pendiente,vencido,nVencidas,items[{proveedor,monto,vence}]}, topProductos[{nombre,cant,monto}], topClientes[], recurrentes[{nombre,visitasMes,ticket,ultima,semanas[8]}], perdidos[{nombre,telefono,ultima,dias,promMes,visitasPorMes[4]}], quiebre[], stockBajo[]`.
Estado en front: `diaSel` (default hoy), `vendedorFoco` (null), `ordenProductos` ('monto'|'cant'), `tabClientes` ('top'|'freq').

## Design tokens
```css
:root{
  --bg:#f5f4f2; --card:#fff; --border:#ebe7e2; --border-strong:#dcd8d3; --row-line:#f5f2ee; --track:#f2efeb; --soft:#f8f6f3;
  --ink:#1f1d1b; --ink-2:#57524d; --muted:#7a746e; --muted-2:#9a948d;
  --primary:oklch(0.64 0.19 37);   /* ≈ #e05a2b */
  --link:#d9481c; --link-hover:#a8350f;
  --danger-bg:#fbe4dc; --danger:#a3290f; --danger-strong:#c2361a;
  --warn-bg:#f6ecd6;   --warn:#8a5a00;
  --ok:oklch(0.55 0.14 145);
  --radius-card:16px; --radius-kpi:14px; --radius-chip:9px; --radius-pill:999px;
  --font:'IBM Plex Sans',system-ui,sans-serif; --mono:'IBM Plex Mono',monospace;
}
```
- Tintes de chips de ícono: `oklch(0.94 0.05 H)` fondo, `oklch(0.55–0.6 0.13–0.19 H)` ícono.
- Tipografía: h1 24/600 · título tarjeta 14/600 · cuerpo filas 12.5 · meta 11–12 · cabeceras de tabla 10.5–11 uppercase tracking .06em · cifras siempre IBM Plex Mono (tabulares).
- Espaciado: 4 / 6 / 8 / 10 / 12 / 14 / 16 / 20 / 24.
- Sombras: ninguna (solo el toggle activo: `0 1px 2px rgba(0,0,0,.08)`).
- Reemplaza Lato por IBM Plex Sans/Mono (Google Fonts).

## Assets
- Íconos: Phosphor Icons web (`https://unpkg.com/@phosphor-icons/web@2.1.1`), ya usado en el dashboard. Retirar los PNG sueltos de `assets/img/` del menú.
- Fuentes: Google Fonts IBM Plex Sans (400–700) e IBM Plex Mono (400–600).
- Sin imágenes.

## Files
- `Escritorio v2.dc.html` — prototipo (abrir en navegador junto a `support.js`). Datos de ejemplo en las constantes al inicio del `<script>` (RAW, PAGOS, CXP, RECUR, PERDIDOS…).
- `support.js` — runtime del prototipo, solo para visualizarlo; no se implementa.
