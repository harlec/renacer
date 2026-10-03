<?php
// Un cliente "fusionado" es un duplicado que ya se unió a otro cliente (principal).
// La fuente de verdad es la tabla cliente_fusiones (no clientes.estado). Si esa tabla
// aún no existe (migración sin correr), todo sigue funcionando como si no hubiera fusiones.

function cliente_conn_helper()
{
    $c = new mysqli('localhost', 'admin_renacer', 'ikm169uhn', 'admin_renacer');
    $c->set_charset('utf8');
    return $c;
}

// Ids de clientes que ya fueron fusionados en otro (y no se deshicieron).
function cliente_ids_fusionados(?mysqli $conn = null)
{
    static $cache = null;
    if ($cache !== null && $conn === null) return $cache;
    $propia = false;
    if ($conn === null) { $conn = cliente_conn_helper(); $propia = true; }
    $ids = [];
    try {
        $r = $conn->query("SELECT DISTINCT id_duplicado FROM cliente_fusiones WHERE deshecha = 0");
        while ($r && $x = $r->fetch_assoc()) $ids[] = (int)$x['id_duplicado'];
    } catch (Throwable $e) {
        $ids = [];
    }
    if ($propia) { $conn->close(); $cache = $ids; }
    return $ids;
}

// Si el cliente fue fusionado en otro, devuelve el id del principal; si no, el mismo id.
// Así, si alguien vuelve a escribir el nombre del duplicado, la venta va al cliente
// correcto en vez de recrear el duplicado.
function cliente_resolver_fusion(mysqli $conn, $id)
{
    $id = (int)$id;
    for ($i = 0; $i < 5; $i++) {
        try {
            $r = $conn->query("SELECT id_principal FROM cliente_fusiones WHERE id_duplicado = $id AND deshecha = 0 ORDER BY id_fusion DESC LIMIT 1");
            $row = $r ? $r->fetch_assoc() : null;
        } catch (Throwable $e) {
            return $id; // tabla aún no creada
        }
        if (!$row) break;
        $id = (int)$row['id_principal'];
    }
    return $id;
}
