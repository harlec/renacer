<?php
// Si un cliente fue fusionado en otro (estado = '2'), devuelve el id del cliente
// principal; así, si alguien vuelve a escribir el nombre del duplicado, la venta
// va al cliente correcto en vez de recrear el duplicado.
// Si la tabla cliente_fusiones aún no existe, devuelve el mismo id sin fallar.
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
