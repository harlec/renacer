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

// Un comprobante puede cubrir varias ventas (unidas), por eso se agrupa por
// id_comprobante y se listan las ventas que cubre con GROUP_CONCAT.
$r = $conn->query("
    SELECT c.id_comprobante, c.tipo, c.serie, c.numero, c.nombre, c.total, c.url,
           GROUP_CONCAT(DISTINCT cv.venta ORDER BY cv.venta SEPARATOR ',') AS ventas
    FROM comprobantes c
    JOIN comprobante_ventas cv ON cv.comprobante = c.id_comprobante
    JOIN ventas v ON v.id_venta = cv.venta
    WHERE DATE(c.fecha) = CURDATE() AND c.state = '0' $where_user
    GROUP BY c.id_comprobante
    ORDER BY c.id_comprobante DESC
");

$data = [];
if ($r) {
    while ($row = $r->fetch_assoc()) {
        $data[] = [
            'tipo'    => $row['tipo'] === 'F' ? 'Factura' : 'Boleta',
            'serie'   => $row['serie'],
            'numero'  => $row['numero'],
            'cliente' => $row['nombre'] ?: '-',
            'ventas'  => $row['ventas'],
            'total'   => round((float)$row['total'], 2),
            // Convención de Nubefact: el link "enlace" + ".pdf" da la representación en PDF.
            'url_pdf' => $row['url'] ? rtrim($row['url'], '/') . '.pdf' : null,
        ];
    }
}

$conn->close();
echo json_encode(['ok' => true, 'data' => $data]);
