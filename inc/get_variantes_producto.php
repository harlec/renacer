<?php
include('sdba/sdba.php');

$producto = intval($_GET['producto'] ?? 0);
$result = [];

if ($producto > 0) {
    $vp = Sdba::table('variante_p');
    $vp->where('producto_vp', $producto);
    $vp->where('state_vp', '1');
    $vp->left_join('variante_vp', 'variantes', 'id_variante');
    $lista = $vp->get();
    foreach ($lista as $v) {
        $result[] = [
            'id_vp'       => $v['id_vp'],
            'variante'    => $v['variante'],
            'cantidad_vp' => floatval($v['cantidad_vp']),
            'precioc_vp'  => floatval($v['precioc_vp'])
        ];
    }
}

header('Content-Type: application/json');
echo json_encode($result);
