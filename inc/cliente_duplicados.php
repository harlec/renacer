<?php
// Detección de posibles clientes duplicados por parecido de nombre y/o mismo documento.
// Sugiere, no decide: la persona confirma cuáles fusionar.

function cd_normalizar($s)
{
    $s = mb_strtoupper(trim((string)$s), 'UTF-8');
    $s = strtr($s, ['Á' => 'A', 'É' => 'E', 'Í' => 'I', 'Ó' => 'O', 'Ú' => 'U', 'Ü' => 'U', 'Ñ' => 'N']);
    $s = preg_replace('/[^A-Z0-9 ]+/', ' ', $s);
    $tokens = preg_split('/\s+/', $s, -1, PREG_SPLIT_NO_EMPTY);
    $particulas = ['DE', 'DEL', 'LA', 'LAS', 'LOS', 'Y', 'E'];
    return array_values(array_unique(array_diff($tokens, $particulas)));
}

function cd_token_igual($a, $b)
{
    if ($a === $b) return true;
    return strlen($a) >= 4 && strlen($b) >= 4 && levenshtein($a, $b) <= 1; // HAYDE ~ AYDE
}

// Todos los tokens del conjunto menor deben aparecer (o casi) en el mayor.
function cd_contenido($t1, $t2)
{
    if (count($t1) > count($t2)) { $tmp = $t1; $t1 = $t2; $t2 = $tmp; }
    foreach ($t1 as $a) {
        $ok = false;
        foreach ($t2 as $b) { if (cd_token_igual($a, $b)) { $ok = true; break; } }
        if (!$ok) return false;
    }
    return true;
}

function cd_find(&$p, $x) { while ($p[$x] !== $x) { $p[$x] = $p[$p[$x]]; $x = $p[$x]; } return $x; }

function detectar_clientes_duplicados(mysqli $conn)
{
    $excluir = ['TABLET', 'FACTURA MANUAL', 'CLIENTES VARIOS', 'CLIENTE VARIOS', 'VARIOS', 'PUBLICO GENERAL', 'CLIENTE GENERAL'];

    $ventas = [];
    $r = $conn->query("SELECT cliente, COUNT(*) AS n, MAX(fecha) AS ultima FROM ventas WHERE estado != '2' GROUP BY cliente");
    while ($r && $x = $r->fetch_assoc()) $ventas[(int)$x['cliente']] = ['n' => (int)$x['n'], 'ultima' => $x['ultima']];

    $cl = [];
    $r = $conn->query("SELECT id_cliente, cliente, doc_identidad, telefono, email FROM clientes WHERE COALESCE(estado,'1') != '2'");
    while ($r && $x = $r->fetch_assoc()) {
        $id = (int)$x['id_cliente'];
        $nombre = trim($x['cliente']);
        if ($nombre === '' || in_array(mb_strtoupper($nombre, 'UTF-8'), $excluir, true)) continue;
        $tok = cd_normalizar($nombre);
        if (!$tok) continue;
        $doc = preg_replace('/\D+/', '', (string)$x['doc_identidad']);
        $cl[$id] = $x + ['tok' => $tok, 'doc' => strlen($doc) >= 6 ? $doc : '',
                         'ventas' => $ventas[$id]['n'] ?? 0, 'ultima' => $ventas[$id]['ultima'] ?? null];
    }

    // Bloques para no comparar todos contra todos: comparten token exacto, o 3 primeras / 3 últimas letras.
    $bloques = [];
    foreach ($cl as $id => $c) {
        foreach ($c['tok'] as $t) {
            if (strlen($t) < 3) continue;
            foreach (['E:' . $t, 'P:' . substr($t, 0, 3), 'S:' . substr($t, -3)] as $k) $bloques[$k][] = $id;
        }
        if ($c['doc'] !== '') $bloques['D:' . $c['doc']][] = $id;
    }

    $pares = [];
    foreach ($bloques as $ids) {
        $n = count($ids);
        if ($n < 2 || $n > 400) continue; // bloque demasiado genérico
        for ($i = 0; $i < $n; $i++) for ($j = $i + 1; $j < $n; $j++) {
            $a = $ids[$i]; $b = $ids[$j];
            $pares[min($a, $b) . '-' . max($a, $b)] = [min($a, $b), max($a, $b)];
        }
    }

    $p = array_combine(array_keys($cl), array_keys($cl));
    $union = function ($a, $b) use (&$p) { $ra = cd_find($p, $a); $rb = cd_find($p, $b); if ($ra !== $rb) $p[$rb] = $ra; };
    $pendientes = []; // (nombre de una palabra) vs (nombre de varias): se asignan después
    foreach ($pares as [$a, $b]) {
        $A = $cl[$a]; $B = $cl[$b];
        $mismoDoc = $A['doc'] !== '' && $A['doc'] === $B['doc'];
        if ($mismoDoc) { $union($a, $b); continue; }
        if (!cd_contenido($A['tok'], $B['tok'])) continue;
        $na = count($A['tok']); $nb = count($B['tok']);
        if ($na >= 2 && $nb >= 2) { $union($a, $b); }
        elseif ($na === 1 && $nb === 1) { $union($a, $b); }
        else { $pendientes[] = $na === 1 ? [$a, $b] : [$b, $a]; } // [una palabra, varias]
    }

    $tam = [];
    foreach ($cl as $id => $c) { $tam[cd_find($p, $id)][] = $id; }
    // Una palabra suelta (p. ej. "AYDE") se une al grupo de varias palabras con más ventas que coincida.
    $mejor = [];
    foreach ($pendientes as [$uno, $multi]) {
        $raizMulti = cd_find($p, $multi);
        $score = array_sum(array_map(function ($i) use ($cl) { return $cl[$i]['ventas']; }, $tam[$raizMulti]));
        if (!isset($mejor[$uno]) || $score > $mejor[$uno][1]) $mejor[$uno] = [$multi, $score];
    }
    foreach ($mejor as $uno => [$multi]) {
        if (count($cl[$uno]['tok']) === 1 && count($tam[cd_find($p, $uno)]) === 1) $union($uno, $multi); // solo si estaba suelta
    }

    $grupos = [];
    foreach ($cl as $id => $c) $grupos[cd_find($p, $id)][] = $c;
    $out = [];
    foreach ($grupos as $g) {
        if (count($g) < 2) continue;
        usort($g, function ($a, $b) {
            if ($a['ventas'] !== $b['ventas']) return $b['ventas'] - $a['ventas'];
            return strlen($b['cliente']) - strlen($a['cliente']);
        });
        $out[] = $g;
    }
    usort($out, function ($a, $b) { return count($b) - count($a); });
    return $out;
}
