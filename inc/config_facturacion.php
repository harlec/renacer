<?php

function get_config($parametro, $default = '') {
    $row = Sdba::table('configuracion');
    $row->where('parametro', $parametro);
    $valor = $row->get_single('valor');
    return $valor !== null ? $valor : $default;
}

function set_config($parametro, $valor) {
    $chk = Sdba::table('configuracion');
    $chk->where('parametro', $parametro);
    $existe = $chk->get_single('valor');
    if ($existe === null) {
        Sdba::table('configuracion')->insert(array('parametro' => $parametro, 'valor' => $valor));
    } else {
        $upd = Sdba::table('configuracion');
        $upd->where('parametro', $parametro);
        $upd->update(array('valor' => $valor));
    }
}
