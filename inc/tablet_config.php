<?php
/**
 * CONFIGURACIÓN DEL POS TABLET
 * ============================================================
 * Consulta tus IDs en:
 *   - Categorías : Productos → Categorías  (columna "id_categoria")
 *   - Productos  : Productos → Listar      (columna "id_producto")
 *
 * Cada pestaña tiene 'groups' (pills/filtros que aparecen arriba del grid).
 * Cada grupo puede filtrar con:
 *   'category_ids' => incluye TODOS los productos de esas categorías
 *   'product_ids'  => incluye solo esos productos específicos (id_producto)
 * Puedes combinar ambos en el mismo grupo, o usar solo uno de los dos.
 *
 * 'by_weight' => true   → el numpad acepta decimales (ventas por kg)
 * 'by_weight' => false  → solo enteros (ventas por unidad)
 * ============================================================
 */

$TABLET_TABS = [

    // ── PESTAÑA 1: HUEVOS ──────────────────────────────────
    'huevos' => [
        'label'        => 'Huevos',
        'icon'         => '🥚',
        'color_accent' => '#f5a623',
        'color_bg'     => 'rgba(245,166,35,.12)',
        'by_weight'    => false,   // por unidad
        'groups' => [
            [
                'label'        => 'Celda',
                'category_ids' => [22],    // <-- ID categoría "Celda"
                'product_ids'  => [],      // dejar [] si usas category_ids
            ],
            [
                'label'        => 'Botellas',
                'category_ids' => [23],    // <-- ID categoría "Caja"
                'product_ids'  => [845,846,847,848,871],
            ],
            // Agrega más grupos si necesitas más tipos de huevos:
            // [
            //     'label'        => 'Paquete',
            //     'category_ids' => [],
            //     'product_ids'  => [5, 8, 12],  // solo estos productos específicos
            // ],
        ],
    ],

    // ── PESTAÑA 2: EMBUTIDOS ───────────────────────────────
    'embutidos' => [
        'label'        => 'Embutidos',
        'icon'         => '🥩',
        'color_accent' => '#c0392b',
        'color_bg'     => 'rgba(192,57,43,.12)',
        'by_weight'    => true,    // por kg
        'groups' => [
            [
                'label'        => 'Jamones',
                'category_ids' => [10],    // <-- ID categoría de jamones/embutidos
                'product_ids'  => [],
            ],
            [
                'label'        => 'Salchichas',
                'category_ids' => [29],    // <-- otra categoría
                'product_ids'  => [],
            ],
            // Ejemplo: grupo con productos específicos mezclados
            // [
            //     'label'        => 'Especiales',
            //     'category_ids' => [],
            //     'product_ids'  => [3, 7, 15],
            // ],
        ],
    ],

];

/**
 * Nombre del negocio que aparece en el ticket impreso
 */
define('TABLET_STORE_NAME', 'Distribuidora Renacer');
define('TABLET_STORE_ADDRESS', ''); // opcional: dirección en el ticket
define('TABLET_STORE_PHONE', '');   // opcional: teléfono en el ticket
