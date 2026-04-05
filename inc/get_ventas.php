<?php
session_start();
header('Content-Type: application/json');

include('sdba/sdba.php');

$id_usuario = $_SESSION['id_usr'];
$es_admin   = ($_SESSION['type'] == 'admin');

// Parámetros de DataTables server-side
$draw      = intval($_GET['draw'] ?? 1);
$start     = intval($_GET['start'] ?? 0);
$length    = intval($_GET['length'] ?? 25);
$search    = trim($_GET['search']['value'] ?? '');
$order_col = intval($_GET['order'][0]['column'] ?? 3);
$order_dir = strtoupper($_GET['order'][0]['dir'] ?? 'DESC') === 'ASC' ? 'ASC' : 'DESC';

// Mapeo índice columna → campo SQL ordenable
$col_map = [
    0 => 'v.id_venta',
    1 => 'v.tipo',
    2 => 'v.forma',
    3 => 'v.fecha',
    4 => 'monto',
];
$order_by = $col_map[$order_col] ?? 'v.id_venta';

// Sanitizar búsqueda
$search_safe = preg_replace('/[^a-zA-Z0-9\s\-\.\/]/', '', $search);

// Filtro por usuario
$where_user = $es_admin ? "" : "AND v.usuario = '$id_usuario'";

// Filtro por búsqueda
$where_search = '';
if (!empty($search_safe)) {
    $where_search = "AND (v.id_venta LIKE '%{$search_safe}%' OR v.fecha LIKE '%{$search_safe}%')";
}

// Total sin filtro
$total = Sdba::db()->query(
    "SELECT COUNT(*) as c FROM ventas v WHERE v.estado != '2' $where_user"
)->row()['c'];

// Total con filtro de búsqueda
$filtered = Sdba::db()->query(
    "SELECT COUNT(*) as c FROM ventas v WHERE v.estado != '2' $where_user $where_search"
)->row()['c'];

// Query principal — 1 sola query con JOINs en lugar de N+1
$sql = "
    SELECT
        v.id_venta, v.tipo, v.forma, v.fecha, v.estado,
        COALESCE(SUM(dv.total), 0) as monto,
        MAX(c.tipo)   as comp_tipo,
        MAX(c.numero) as comp_numero,
        MAX(c.url)    as comp_url
    FROM ventas v
    LEFT JOIN detalle_ventas dv ON dv.venta = v.id_venta
    LEFT JOIN comprobantes   c  ON c.venta  = v.id_venta
    WHERE v.estado != '2' $where_user $where_search
    GROUP BY v.id_venta
    ORDER BY $order_by $order_dir
    LIMIT $length OFFSET $start
";

$rows = Sdba::db()->query($sql)->result();

$data = [];
foreach ($rows as $row) {

    switch ($row['tipo']) {
        case '1': $tipo = 'Contado'; break;
        case '2': $tipo = 'Crédito'; break;
        default:  $tipo = '-';
    }

    switch ($row['forma']) {
        case '1': $forma = 'Efectivo';    break;
        case '2': $forma = 'Tar. Débito'; break;
        case '3': $forma = 'Tar. Crédito'; break;
        default:  $forma = '-';
    }

    $comprobante = '';
    if ($row['estado'] == '1' && !empty($row['comp_url'])) {
        $comprobante = '<a href="' . htmlspecialchars($row['comp_url']) . '" target="_blank">'
                     . htmlspecialchars($row['comp_tipo'] . $row['comp_numero']) . '</a>';
    }

    $boton_editar = '';
    if ($row['estado'] == '0') {
        $boton_editar = ' <a title="Editar venta" class="btn btn-warning btn-sm" href="editar_venta.php?id=' . $row['id_venta'] . '"><i class="fas fa-edit"></i></a>';
    }

    $opciones = '<a title="Ver venta" class="btn btn-primary btn-sm" href="ver_venta.php?id=' . $row['id_venta'] . '"><i class="fas fa-eye"></i></a>'
              . $boton_editar
              . ' <button class="btn-custom btn-borrar" value="' . $row['id_venta'] . '" title="borrar"><img src="/assets/img/trash.png" /></button>';

    $data[] = [
        'v-' . $row['id_venta'],
        $tipo,
        $forma,
        $row['fecha'],
        number_format((float)$row['monto'], 2),
        $comprobante,
        $opciones,
    ];
}

echo json_encode([
    'draw'            => $draw,
    'recordsTotal'    => intval($total),
    'recordsFiltered' => intval($filtered),
    'data'            => $data,
]);
