<?php
ob_start();
ini_set('display_errors', '0');
error_reporting(0);
session_start();
ob_clean();
header('Content-Type: application/json');

if (!isset($_SESSION['id_usr'])) {
    echo json_encode(['draw' => 1, 'recordsTotal' => 0, 'recordsFiltered' => 0, 'data' => [], 'error' => 'Sin sesion']);
    exit;
}

$conn = new mysqli('localhost', 'admin_renacer', 'ikm169uhn', 'admin_renacer');
$conn->set_charset('utf8');

if ($conn->connect_error) {
    echo json_encode(['draw' => 1, 'recordsTotal' => 0, 'recordsFiltered' => 0, 'data' => [], 'error' => 'DB: ' . $conn->connect_error]);
    exit;
}

$id_usuario = intval($_SESSION['id_usr']);
$es_admin   = ($_SESSION['type'] == 'admin');

// Parámetros DataTables
$draw      = intval($_GET['draw'] ?? 1);
$start     = intval($_GET['start'] ?? 0);
$length    = intval($_GET['length'] ?? 25);
$search    = trim($_GET['search']['value'] ?? '');
$order_col = intval($_GET['order'][0]['column'] ?? 1);
$order_dir = strtoupper($_GET['order'][0]['dir'] ?? 'ASC') === 'ASC' ? 'ASC' : 'DESC';

$col_map = [
    1 => 'v.id_venta',
    4 => 'v.fecha',
    5 => 'monto',
];
$order_by = ($col_map[$order_col] ?? 'v.id_venta') . ' ' . $order_dir;

// Sanitizar búsqueda — quitar prefijo "v-" si lo escriben
$search_clean = preg_replace('/^v-/i', '', $search);
$search_safe  = $conn->real_escape_string($search_clean);

// Filtros base
$where_user   = $es_admin ? "" : "AND v.usuario = $id_usuario";
$where_search = !empty($search_safe)
    ? "AND (v.id_venta LIKE '%{$search_safe}%' OR v.fecha LIKE '%{$search_safe}%')"
    : "";

// Total sin filtro (las anuladas también se listan, marcadas — no se ocultan)
$r     = $conn->query("SELECT COUNT(*) as c FROM ventas v WHERE 1=1 $where_user");
$total = $r ? $r->fetch_assoc()['c'] : 0;

// Total con búsqueda
$r        = $conn->query("SELECT COUNT(*) as c FROM ventas v WHERE 1=1 $where_user $where_search");
$filtered = $r ? $r->fetch_assoc()['c'] : 0;

// Query principal
$sql = "
    SELECT
        v.id_venta, v.fecha, v.estado, v.total,
        COALESCE(SUM(dv.total), 0) AS monto,
        MAX(c.tipo)   AS comp_tipo,
        MAX(c.numero) AS comp_numero,
        MAX(c.url)    AS comp_url,
        u.nombres     AS nombre_usuario,
        cl.cliente    AS nombre_cliente,
        MAX(vp.pagos_raw) AS pagos_raw,
        MAX(vp.pagado)    AS pagado
    FROM ventas v
    LEFT JOIN detalle_ventas   dv ON dv.venta = v.id_venta
    LEFT JOIN comprobante_ventas cv ON cv.venta = v.id_venta
    LEFT JOIN comprobantes     c  ON c.id_comprobante = cv.comprobante
    LEFT JOIN usuarios         u  ON u.id_usuario = v.usuario
    LEFT JOIN clientes         cl ON cl.id_cliente = v.cliente
    LEFT JOIN (
        SELECT venta, GROUP_CONCAT(CONCAT(metodo, ':', monto) SEPARATOR '|') AS pagos_raw, SUM(monto) AS pagado
        FROM venta_pagos GROUP BY venta
    ) vp ON vp.venta = v.id_venta
    WHERE 1=1 $where_user $where_search
    GROUP BY v.id_venta
    ORDER BY $order_by
    LIMIT $length OFFSET $start
";

$result = $conn->query($sql);

if (!$result) {
    echo json_encode(['draw' => $draw, 'recordsTotal' => 0, 'recordsFiltered' => 0, 'data' => [], 'error' => 'Query: ' . $conn->error]);
    exit;
}

$data = [];

while ($row = $result->fetch_assoc()) {

    $cliente_venta = htmlspecialchars($row['nombre_cliente'] ?: 'Sin cliente');

    $metodos_label = ['efectivo' => 'Efectivo', 'yape' => 'Yape', 'plin' => 'Plin', 'bbva' => 'BBVA', 'yape_susan' => 'Yape Susan', 'tarjeta' => 'Tarjeta'];
    if (!empty($row['pagos_raw'])) {
        $partes = [];
        foreach (explode('|', $row['pagos_raw']) as $linea) {
            [$metodo, $monto] = explode(':', $linea);
            $partes[] = ($metodos_label[$metodo] ?? $metodo) . ' S/' . number_format((float)$monto, 2);
        }
        $forma = implode(' + ', $partes);
        if ((float)$row['pagado'] < (float)$row['total'] - 0.01) {
            $forma .= ' (parcial)';
        }
    } else {
        $forma = 'Pendiente';
    }

    $comprobante = '';
    if ($row['estado'] == '1' && !empty($row['comp_url'])) {
        $comprobante = '<a href="' . htmlspecialchars($row['comp_url']) . '" target="_blank">'
                     . htmlspecialchars($row['comp_tipo'] . $row['comp_numero']) . '</a>';
    }

    $boton_editar = '';
    $boton_facturar = '';
    $boton_borrar = '';
    if ($row['estado'] == '0') {
        $boton_editar = ' <a title="Editar venta" class="btn btn-warning btn-sm" href="editar_venta.php?id=' . $row['id_venta'] . '"><i class="fas fa-edit"></i></a>';
        $boton_facturar = ' <a title="Factura electrónica" class="btn btn-success btn-sm" href="factura.php?ids=' . $row['id_venta'] . '"><i class="fas fa-file-invoice-dollar"></i></a>'
                         . ' <a title="Boleta electrónica" class="btn btn-danger btn-sm" href="boleta.php?ids=' . $row['id_venta'] . '"><i class="fab fa-bitcoin"></i></a>';
        $boton_borrar = ' <button class="btn-custom btn-borrar" value="' . $row['id_venta'] . '" title="Anular venta"><img src="/assets/img/trash.png" /></button>';
    }

    $opciones = '<a title="Ver venta" class="btn btn-primary btn-sm" href="ver_venta.php?id=' . $row['id_venta'] . '"><i class="fas fa-eye"></i></a>'
              . $boton_editar
              . $boton_facturar
              . $boton_borrar;

    $venta_label = 'v-' . $row['id_venta'];
    if ($row['estado'] == '2') {
        $venta_label .= ' <span style="background:#f8d7da;color:#a71d2a;font-size:10px;font-weight:700;padding:2px 6px;border-radius:8px;text-transform:uppercase">Anulada</span>';
    }

    $data[] = [
        $venta_label,
        $row['nombre_usuario'] ?: '-',
        $cliente_venta,
        $forma,
        $row['fecha'],
        number_format((float)$row['monto'], 2),
        $comprobante,
        $opciones,
    ];
}

$conn->close();

echo json_encode([
    'draw'            => $draw,
    'recordsTotal'    => intval($total),
    'recordsFiltered' => intval($filtered),
    'data'            => $data,
]);
