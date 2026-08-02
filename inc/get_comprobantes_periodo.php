<?php
ob_start();
ini_set('display_errors', '0');
error_reporting(0);
session_start();
ob_clean();
header('Content-Type: application/json');

if (!isset($_SESSION['id_usr'])) {
    echo json_encode(['ok' => false, 'data' => []]);
    exit;
}

$conn = new mysqli('localhost', 'admin_renacer', 'ikm169uhn', 'admin_renacer');
$conn->set_charset('utf8');

if ($conn->connect_error) {
    echo json_encode(['ok' => false, 'data' => []]);
    exit;
}

$id_usuario = intval($_SESSION['id_usr']);
$es_admin   = ($_SESSION['type'] == 'admin');
$where_user = $es_admin ? "" : "AND v.usuario = $id_usuario";

$mes  = isset($_GET['mes'])  ? max(1, min(12, intval($_GET['mes'])))  : intval(date('n'));
$anio = isset($_GET['anio']) ? intval($_GET['anio'])                  : intval(date('Y'));

$desde = sprintf('%04d-%02d-01', $anio, $mes);
$hasta = date('Y-m-d', strtotime($desde . ' +1 month'));

$tipos_label = ['B' => 'Boleta', 'F' => 'Factura', 'NB' => 'N.C. Boleta', 'FC' => 'N.C. Factura'];
$estados_label = ['0' => 'Emitido', '1' => 'Baja comunicada', '2' => 'Anulado'];

// Un comprobante puede cubrir varias ventas (unidas), por eso se agrupa por
// id_comprobante y se listan las ventas que cubre con GROUP_CONCAT. Se muestran
// todos los estados (incluye anulados/baja comunicada) — para eso está la columna Estado.
$r = $conn->query("
    SELECT c.id_comprobante, c.tipo, c.serie, c.numero, c.nombre, c.total, c.url, c.state, c.fecha,
           GROUP_CONCAT(DISTINCT cv.venta ORDER BY cv.venta SEPARATOR ',') AS ventas
    FROM comprobantes c
    JOIN comprobante_ventas cv ON cv.comprobante = c.id_comprobante
    JOIN ventas v ON v.id_venta = cv.venta
    WHERE c.fecha >= '$desde' AND c.fecha < '$hasta' $where_user
    GROUP BY c.id_comprobante
    ORDER BY c.id_comprobante DESC
");

$data = [];
if ($r) {
    while ($row = $r->fetch_assoc()) {
        $data[] = [
            'id_comprobante' => (int)$row['id_comprobante'],
            'tipo'           => $row['tipo'],
            'tipo_label'     => $tipos_label[$row['tipo']] ?? $row['tipo'],
            'serie'          => $row['serie'],
            'numero'         => $row['numero'],
            'cliente'        => $row['nombre'] ?: '-',
            'ventas'         => $row['ventas'],
            'total'          => round((float)$row['total'], 2),
            'fecha'          => $row['fecha'],
            'state'          => $row['state'],
            'estado_label'   => $estados_label[$row['state']] ?? $row['state'],
            // Convención de Nubefact: el link "enlace" + ".pdf" da la representación en PDF.
            'url_pdf'        => $row['url'] ? rtrim($row['url'], '/') . '.pdf' : null,
        ];
    }
}

// Resumen del período, desglosado por boleta/factura (igual que en "Facturar" pero por
// el período elegido en vez de "hoy"). Solo cuenta comprobantes activos (state=0) y
// tipo B/F — las notas de crédito son ajustes, no facturación nueva, no se suman aquí.
$resumen = ['boletas' => 0, 'facturas' => 0, 'total' => 0];
$rr = $conn->query("
    SELECT c.tipo, SUM(c.total) AS total
    FROM comprobantes c
    WHERE c.fecha >= '$desde' AND c.fecha < '$hasta' AND c.state = '0' AND c.tipo IN ('B','F')
      AND c.id_comprobante IN (
          SELECT DISTINCT cv.comprobante
          FROM comprobante_ventas cv
          JOIN ventas v ON v.id_venta = cv.venta
          WHERE 1=1 $where_user
      )
    GROUP BY c.tipo
");
if ($rr) {
    while ($row = $rr->fetch_assoc()) {
        $t = round((float)$row['total'], 2);
        if ($row['tipo'] === 'B') $resumen['boletas'] += $t;
        elseif ($row['tipo'] === 'F') $resumen['facturas'] += $t;
        $resumen['total'] += $t;
    }
    $resumen['total'] = round($resumen['total'], 2);
}

$conn->close();
echo json_encode(['ok' => true, 'data' => $data, 'resumen' => $resumen, 'mes' => $mes, 'anio' => $anio]);
