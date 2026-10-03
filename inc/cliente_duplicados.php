<?php
// Detección de posibles clientes duplicados por parecido de nombre y/o mismo documento.
// Sugiere, no decide: la persona confirma cuáles fusionar.

function cd_upper($s) { return function_exists('mb_strtoupper') ? mb_strtoupper($s, 'UTF-8') : strtoupper($s); }

function cd_normalizar($s)
{
    $s = cd_upper(trim((string)$s));
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
        if ($nombre === '' || in_array(cd_upper($nombre), $excluir, true)) continue;
        $tok = cd_normalizar($nombre);
        if (!$tok) continue;
        $doc = preg_replace('/\D+/', '', (string)$x['doc_identidad']);
        $cl[$id] = $x + ['tok' => $tok, 'doc' => strlen($doc) >= 6 ? $doc : '',
                         'ventas' => $ventas[$id]['n'] ?? 0, 'ultima' => $ventas[$id]['ultima'] ?? null];
    }

    // Bloques para no comparar todos contra todos. Dos clientes se comparan solo si comparten
    // alguna clave: mismo documento, mismo conjunto de palabras, una palabra igual, o una palabra
    // que difiere en una sola letra (HAYDE ~ AYDE: se indexan las variantes con una letra menos).
    $bloques = [];
    foreach ($cl as $id => $c) {
        $orden = $c['tok']; sort($orden);
        $bloques['N:' . implode(' ', $orden)][] = $id;
        if ($c['doc'] !== '') $bloques['D:' . $c['doc']][] = $id;
        foreach ($c['tok'] as $t) {
            if (strlen($t) < 3) continue;
            $bloques['E:' . $t][] = $id;
            $len = strlen($t);
            if ($len >= 4 && $len <= 14) {
                for ($i = 0; $i < $len; $i++) $bloques['V:' . substr($t, 0, $i) . substr($t, $i + 1)][] = $id;
            }
        }
    }

    $p = array_combine(array_keys($cl), array_keys($cl));
    $union = function ($a, $b) use (&$p) { $ra = cd_find($p, $a); $rb = cd_find($p, $b); if ($ra !== $rb) $p[$rb] = $ra; };
    $mejor = []; // para cada nombre de una palabra: el cliente de varias palabras con más ventas que lo contiene

    foreach ($bloques as $k => $ids) {
        $n = count($ids);
        if ($n < 2 || ($n > 150 && $k[0] !== 'D' && $k[0] !== 'N')) continue; // palabra demasiado común
        if ($n > 800) continue;
        for ($i = 0; $i < $n; $i++) for ($j = $i + 1; $j < $n; $j++) {
            $a = $ids[$i]; $b = $ids[$j];
            if ($a === $b || cd_find($p, $a) === cd_find($p, $b)) continue;
            $A = $cl[$a]; $B = $cl[$b];
            if ($A['doc'] !== '' && $A['doc'] === $B['doc']) { $union($a, $b); continue; }
            if (!cd_contenido($A['tok'], $B['tok'])) continue;
            $na = count($A['tok']); $nb = count($B['tok']);
            if (($na >= 2 && $nb >= 2) || ($na === 1 && $nb === 1)) { $union($a, $b); continue; }
            [$uno, $multi] = $na === 1 ? [$a, $b] : [$b, $a];
            if (!isset($mejor[$uno]) || $cl[$multi]['ventas'] > $cl[$mejor[$uno]]['ventas']) $mejor[$uno] = $multi;
        }
    }

    // Una palabra suelta (p. ej. "AYDE") se une a la mejor coincidencia de varias palabras,
    // solo si no quedó ya agrupada con otros.
    $tam = [];
    foreach ($cl as $id => $c) { $r = cd_find($p, $id); $tam[$r] = ($tam[$r] ?? 0) + 1; }
    foreach ($mejor as $uno => $multi) {
        if ($tam[cd_find($p, $uno)] === 1) $union($uno, $multi);
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
