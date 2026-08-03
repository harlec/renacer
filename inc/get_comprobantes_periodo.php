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
// LEFT JOIN a "co" trae los datos del comprobante ORIGINAL cuando esta fila es una nota de
// crédito (comprobante_modificado apunta a él). Las dos subconsultas hacen el camino inverso:
// si esta fila es un comprobante original, buscan la nota de crédito que lo referencia (si
// existe), para poder mostrar la referencia cruzada en ambos sentidos en la tabla.
$r = $conn->query("
    SELECT c.id_comprobante, c.tipo, c.serie, c.numero, c.nombre, c.total, c.url, c.state, c.fecha,
           c.comprobante_modificado,
           MAX(co.serie) AS orig_serie, MAX(co.numero) AS orig_numero,
           MAX((SELECT nc.id_comprobante FROM comprobantes nc WHERE nc.comprobante_modificado = c.id_comprobante ORDER BY nc.id_comprobante DESC LIMIT 1)) AS nc_id,
           MAX((SELECT CONCAT(nc.serie,'-',nc.numero) FROM comprobantes nc WHERE nc.comprobante_modificado = c.id_comprobante ORDER BY nc.id_comprobante DESC LIMIT 1)) AS nc_serie_numero,
           GROUP_CONCAT(DISTINCT cv.venta ORDER BY cv.venta SEPARATOR ',') AS ventas
    FROM comprobantes c
    JOIN comprobante_ventas cv ON cv.comprobante = c.id_comprobante
    JOIN ventas v ON v.id_venta = cv.venta
    LEFT JOIN comprobantes co ON co.id_comprobante = c.comprobante_modificado
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
            // Si esta fila ES una nota de crédito: a qué comprobante corrige.
            'aplica_a'       => ($row['orig_serie'] && $row['orig_numero']) ? $row['orig_serie'] . '-' . $row['orig_numero'] : null,
            // Si esta fila es el comprobante ORIGINAL: qué nota de crédito la corrigió.
            'anulado_por_id' => $row['nc_id'] ? (int)$row['nc_id'] : null,
            'anulado_por'    => $row['nc_serie_numero'] ?: null,
        ];
    }
}

// Resumen del período (igual que en "Facturar" pero por el período elegido en vez de "hoy").
$resumen = ['boletas' => 0, 'facturas' => 0, 'notas_credito' => 0, 'anuladas' => 0, 'total' => 0];

// Boletas y facturas activas (state=0) — la base del total facturado.
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
    }
}

// Notas de crédito activas (state=0) del período: son un ajuste, no facturación nueva,
// así que no se suman a boletas/facturas — se restan del total facturado.
$rr = $conn->query("
    SELECT SUM(c.total) AS total
    FROM comprobantes c
    WHERE c.fecha >= '$desde' AND c.fecha < '$hasta' AND c.state = '0' AND c.tipo IN ('NB','FC')
      AND c.id_comprobante IN (
          SELECT DISTINCT cv.comprobante
          FROM comprobante_ventas cv
          JOIN ventas v ON v.id_venta = cv.venta
          WHERE 1=1 $where_user
      )
");
if ($rr) {
    $row = $rr->fetch_assoc();
    $resumen['notas_credito'] = round((float)($row['total'] ?? 0), 2);
}

// Boletas/facturas anuladas o con baja comunicada (state 1 ó 2): ya no son ingresos reales,
// así que también se restan del total facturado.
$rr = $conn->query("
    SELECT SUM(c.total) AS total
    FROM comprobantes c
    WHERE c.fecha >= '$desde' AND c.fecha < '$hasta' AND c.state IN ('1','2') AND c.tipo IN ('B','F')
      AND c.id_comprobante IN (
          SELECT DISTINCT cv.comprobante
          FROM comprobante_ventas cv
          JOIN ventas v ON v.id_venta = cv.venta
          WHERE 1=1 $where_user
      )
");
if ($rr) {
    $row = $rr->fetch_assoc();
    $resumen['anuladas'] = round((float)($row['total'] ?? 0), 2);
}

$resumen['boletas'] = round($resumen['boletas'], 2);
$resumen['facturas'] = round($resumen['facturas'], 2);
$resumen['total'] = round($resumen['boletas'] + $resumen['facturas'] - $resumen['notas_credito'] - $resumen['anuladas'], 2);

$conn->close();
echo json_encode(['ok' => true, 'data' => $data, 'resumen' => $resumen, 'mes' => $mes, 'anio' => $anio]);
