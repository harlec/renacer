<?php
session_start();
if (!isset($_SESSION['id_usr'])) { echo '[]'; exit; }

$conn = new mysqli('localhost', 'admin_renacer', 'ikm169uhn', 'admin_renacer');
$conn->set_charset('utf8');

$term = '%' . $conn->real_escape_string($_GET['term'] ?? '') . '%';
// Se muestra cuántos pedidos tiene cada cliente porque hay nombres duplicados en la base
// (varios registros con el mismo nombre) y así se puede distinguir cuál es el correcto —
// el que realmente tiene historial de compras — en vez de adivinar por el nombre solo.
$r = $conn->query("
    SELECT c.id_cliente, c.cliente, COUNT(v.id_venta) AS total_pedidos
    FROM clientes c
    LEFT JOIN ventas v ON v.cliente = c.id_cliente AND v.estado != '2'
    WHERE c.cliente LIKE '$term'
    GROUP BY c.id_cliente, c.cliente
    ORDER BY total_pedidos DESC, c.cliente ASC
    LIMIT 10
");
$data = [];
while ($row = $r->fetch_assoc()) {
    $total = (int)$row['total_pedidos'];
    $etiqueta = $row['cliente'] . ' (' . ($total > 0 ? $total . ' pedidos' : 'sin pedidos') . ')';
    $data[] = ['id' => (int)$row['id_cliente'], 'label' => $etiqueta, 'value' => $row['cliente']];
}
$conn->close();
echo json_encode($data);
