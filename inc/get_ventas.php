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
$order_col = intval($_GET['order'][0]['column'] ?? 4);
$order_dir = strtoupper($_GET['order'][0]['dir'] ?? 'DESC') === 'ASC' ? 'ASC' : 'DESC';

$col_map = [
    1 => 'v.id_venta',
    4 => 'v.fecha',
    5 => 'monto',
];
$order_by = $col_map[$order_col] ?? 'v.id_venta';

// Sanitizar búsqueda
$search_safe = $conn->real_escape_string($search);

// Filtros base
$where_user   = $es_admin ? "" : "AND v.usuario = $id_usuario";
$where_search = !empty($search_safe)
    ? "AND (v.id_venta LIKE '%{$search_safe}%' OR v.fecha LIKE '%{$search_safe}%')"
    : "";

// Total sin filtro
$r     = $conn->query("SELECT COUNT(*) as c FROM ventas v WHERE v.estado != '2' $where_user");
$total = $r ? $r->fetch_assoc()['c'] : 0;

// Total con búsqueda
$r        = $conn->query("SELECT COUNT(*) as c FROM ventas v WHERE v.estado != '2' $where_user $where_search");
$filtered = $r ? $r->fetch_assoc()['c'] : 0;

// Query principal
$sql = "
    SELECT
        v.id_venta, v.fecha, v.estado,
        COALESCE(SUM(dv.total), 0) AS monto,
        MAX(c.tipo)   AS comp_tipo,
        MAX(c.numero) AS comp_numero,
        MAX(c.url)    AS comp_url
    FROM ventas v
    LEFT JOIN detalle_ventas dv ON dv.venta = v.id_venta
    LEFT JOIN comprobantes   c  ON c.venta  = v.id_venta
    WHERE v.estado != '2' $where_user $where_search
    GROUP BY v.id_venta
    ORDER BY $order_by $order_dir
    LIMIT $length OFFSET $start
";

$result = $conn->query($sql);

if (!$result) {
    echo json_encode(['draw' => $draw, 'recordsTotal' => 0, 'recordsFiltered' => 0, 'data' => [], 'error' => 'Query: ' . $conn->error]);
    exit;
}

$data = [];

while ($row = $result->fetch_assoc()) {

    $tipo  = '-';
    $forma = '-';

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

$conn->close();

echo json_encode([
    'draw'            => $draw,
    'recordsTotal'    => intval($total),
    'recordsFiltered' => intval($filtered),
    'data'            => $data,
]);
