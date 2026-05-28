<?php
/**
 * CONFIGURACIÓN DEL POS TABLET
 * ============================================================
 * Edita este archivo para definir qué categorías del sistema
 * aparecen en cada pestaña del POS tablet.
 *
 * Los IDs de categoría los encuentras en: Productos → Categorías
 * ============================================================
 */

$TABLET_TABS = [

    // ── PESTAÑA 1: HUEVOS ──────────────────────────────────
    'huevos' => [
        'label'       => 'Huevos',
        'icon'        => '🥚',
        'color_accent'=> '#f5a623',            // amarillo/naranja
        'color_bg'    => 'rgba(245,166,35,.12)',
        'category_ids'=> [1, 2],               // <-- pon aquí los IDs de categorías de huevos
    ],

    // ── PESTAÑA 2: EMBUTIDOS ───────────────────────────────
    'embutidos' => [
        'label'       => 'Embutidos',
        'icon'        => '🥩',
        'color_accent'=> '#c0392b',            // rojo
        'color_bg'    => 'rgba(192,57,43,.12)',
        'category_ids'=> [3, 4, 5],            // <-- pon aquí los IDs de categorías de embutidos
    ],

];

/**
 * Nombre del negocio que aparece en el ticket impreso
 */
define('TABLET_STORE_NAME', 'Distribuidora Renacer');
define('TABLET_STORE_ADDRESS', ''); // opcional
define('TABLET_STORE_PHONE', '');   // opcional

/**
 * Unidades que se venden por peso (el numpad acepta decimales)
 * Escribe exactamente como aparece en la columna "nombre" de la tabla unidades.
 */
define('TABLET_UNIT_BY_WEIGHT', ['kg', 'KG', 'kilo', 'Kg']);
