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
 *   'category_ids' => todas las variantes de productos en esas categorías
 *   'product_ids'  => todas las presentaciones de esos productos (id_producto)
 *   'variant_ids'  => id_variante de la tabla variantes (5kg, saco, etc.)
 *
 * Combinaciones:
 *   solo product_ids              → todas las presentaciones de esos productos
 *   solo variant_ids              → esa presentación en cualquier producto
 *   product_ids + variant_ids     → INTERSECCIÓN: solo esas presentaciones de esos productos
 *                                   Ej: arroces [373,378] en presentación 5kg y saco [15,19]
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
                'category_ids' => [],    // <-- ID categoría "Caja"
                'product_ids'  => [845,846,847,848],
                'variant_ids'  => [1],
            ],
            [
                'label'        => 'Arroz',
                'category_ids' => [],
                'product_ids'  => [373, 378, 371, 370, 376, 377, 374, 380, 379],
                'variant_ids'  => [15,19,35,36,37,56],
            ],
            [
                'label'        => 'Aceites',
                'category_ids' => [],
                'product_ids'  => [82, 84, 85, 86],
                'variant_ids'  => [7],
            ],
        ],
    ],

    // ── PESTAÑA 2: EMBUTIDOS ───────────────────────────────
    'embutidos' => [
        'label'        => 'Embutidos',
        'icon'         => '🥩',
        'color_accent' => '#c0392b',
        'color_bg'     => 'rgba(192,57,43,.12)',
        'by_weight'    => false,
        'groups' => [
            [
                'label'        => 'Quesos',
                'category_ids' => [],
                'product_ids'  => [910, 909, 951, 936, 925, 928, 937],
            ],
            [
                'label'        => 'Salchicha',
                'category_ids' => [],
                'product_ids'  => [248, 911, 912, 152, 153, 154, 156, 158, 238, 239, 240, 913],
            ],
            [
                'label'        => 'Jamones',
                'category_ids' => [],
                'product_ids'  => [242, 249, 144, 145, 146, 147, 159, 1052, 150, 151, 233, 234, 157, 237, 243, 235, 241],
            ],
            [
                'label'        => 'Varios',
                'category_ids' => [],
                'product_ids'  => [208, 502, 503, 214, 215, 1057, 216, 202, 200, 196, 198, 199, 195],
            ],
            [
                'label'        => 'Galletas',
                'category_ids' => [],
                'product_ids'  => [176, 177, 180, 181, 182, 183, 184, 185, 186, 187, 188, 914],
            ],
            [
                'label'        => 'Salsas',
                'category_ids' => [],
                'product_ids'  => [134, 135, 136, 137, 138, 139, 218, 219, 220, 222, 223, 224, 225, 226, 229, 232, 927, 231],
            ],
            [
                'label'        => 'Postres',
                'category_ids' => [],
                'product_ids'  => [1020, 496, 497, 498, 499, 500, 501, 504, 505, 506, 507, 508, 509, 510, 511, 512, 513, 514],
            ],
            [
                'label'        => 'Mantequilla',
                'category_ids' => [],
                'product_ids'  => [160, 250, 252, 253, 256, 257, 260, 262],
            ],
            [
                'label'        => 'Cocoas',
                'category_ids' => [],
                'product_ids'  => [482, 483, 484, 485],
            ],
            [
                'label'        => 'Café',
                'category_ids' => [],
                'product_ids'  => [450, 451, 459, 959, 452, 960, 464],
            ],
        ],
    ],

];

/**
 * Nombre del negocio que aparece en el ticket impreso
 */
define('TABLET_STORE_NAME', 'Distribuidora Renacer');
define('TABLET_STORE_ADDRESS', ''); // opcional: dirección en el ticket
define('TABLET_STORE_PHONE', '');   // opcional: teléfono en el ticket
