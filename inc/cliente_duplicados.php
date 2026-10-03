<?php
// Detección de posibles clientes duplicados por parecido de nombre y/o mismo documento.
// Sugiere, no decide: la persona confirma cuáles fusionar.
//
// Reglas (conservadoras, para no mezclar personas distintas):
//  - Un nombre se considera igual a otro si TODAS sus palabras aparecen en el otro
//    (MARIA LOPEZ ~ MARIA LOPEZ PEÑA, o "SOLANO ROMERO AYDE" ~ "AYDE SOLANO").
//  - MARIA LOPEZ y MARIA PEREZ NO coinciden: el apellido distinto los separa.
//  - Palabras comunes (MARIA, GARCIA...) solo coinciden exactas; la tolerancia de una
//    letra (HAYDE ~ AYDE) solo vale para palabras raras.
//  - Un nombre de una sola palabra solo se une a otros si esa palabra es rara (AYDE sí, MARIA no).
//  - Dos grupos solo se unen si TODOS sus integrantes son compatibles entre sí.

function cd_upper($s) { return function_exists('mb_strtoupper') ? mb_strtoupper($s, 'UTF-8') : strtoupper($s); }

function cd_normalizar($s)
{
    $s = cd_upper(trim((string)$s));
    $s = strtr($s, ['Á' => 'A', 'É' => 'E', 'Í' => 'I', 'Ó' => 'O', 'Ú' => 'U', 'Ü' => 'U', 'Ñ' => 'N']);
    $s = preg_replace('/[^A-Z0-9 ]+/', ' ', $s);
    $tokens = preg_split('/\s+/', $s, -1, PREG_SPLIT_NO_EMPTY);
    return array_values(array_unique(array_diff($tokens, ['DE', 'DEL', 'LA', 'LAS', 'LOS', 'Y', 'E'])));
}

const CD_RARA = 5;       // una palabra es "rara" si aparece en <= 5 clientes
const CD_RARA_SOLA = 6;  // para unir un nombre de una sola palabra con uno más largo

function cd_tok_igual($a, $b, $df)
{
    if ($a === $b) return true;
    if (($df[$a] ?? 99) > CD_RARA || ($df[$b] ?? 99) > CD_RARA) return false; // común: solo exacta
    $la = strlen($a); $lb = strlen($b);
    if ($la < 4 || $lb < 4) return false;
    $d = levenshtein($a, $b);
    return $d <= 1 || ($la >= 7 && $lb >= 7 && $d <= 2);
}

function cd_compatibles($A, $B, $df)
{
    if ($A['doc'] !== '' && $A['doc'] === $B['doc']) return true;
    $t1 = $A['tok']; $t2 = $B['tok'];
    if (count($t1) > count($t2)) { $x = $t1; $t1 = $t2; $t2 = $x; }
    foreach ($t1 as $a) {
        $ok = false;
        foreach ($t2 as $b) { if (cd_tok_igual($a, $b, $df)) { $ok = true; break; } }
        if (!$ok) return false;
    }
    // Una sola palabra contra un nombre más largo: solo si esa palabra es rara.
    if (count($t1) === 1 && count($t2) > 1 && ($df[$t1[0]] ?? 99) > CD_RARA_SOLA) {
        return false;
    }
    return true;
}

function detectar_clientes_duplicados(mysqli $conn, array $excluir_ids = [])
{
    $excluir_ids = array_flip($excluir_ids);
    $genericos = ['TABLET', 'FACTURA MANUAL', 'CLIENTES VARIOS', 'CLIENTE VARIOS', 'VARIOS', 'PUBLICO GENERAL', 'CLIENTE GENERAL'];

    $ventas = [];
    $r = $conn->query("SELECT cliente, COUNT(*) AS n, MAX(fecha) AS ultima FROM ventas WHERE estado != '2' GROUP BY cliente");
    while ($r && $x = $r->fetch_assoc()) $ventas[(int)$x['cliente']] = ['n' => (int)$x['n'], 'ultima' => $x['ultima']];

    $cl = [];
    $r = $conn->query("SELECT id_cliente, cliente, doc_identidad, telefono, email FROM clientes");
    while ($r && $x = $r->fetch_assoc()) {
        $id = (int)$x['id_cliente'];
        if (isset($excluir_ids[$id])) continue;
        $nombre = trim($x['cliente']);
        if ($nombre === '' || in_array(cd_upper($nombre), $genericos, true)) continue;
        $tok = cd_normalizar($nombre);
        if (!$tok) continue;
        $doc = preg_replace('/\D+/', '', (string)$x['doc_identidad']);
        $cl[$id] = ['id_cliente' => $id, 'cliente' => $nombre, 'doc_identidad' => $x['doc_identidad'],
                    'telefono' => $x['telefono'], 'email' => $x['email'],
                    'tok' => $tok, 'doc' => strlen($doc) >= 6 ? $doc : '',
                    'ventas' => $ventas[$id]['n'] ?? 0, 'ultima' => $ventas[$id]['ultima'] ?? null];
    }

    // Frecuencia de cada palabra (en cuántos clientes aparece).
    $df = [];
    foreach ($cl as $c) foreach ($c['tok'] as $t) $df[$t] = ($df[$t] ?? 0) + 1;

    // Índices: por palabra exacta; y, solo para palabras raras, por variante con una letra menos
    // (permite hallar HAYDE ~ AYDE sin comparar todo contra todo).
    $idx = []; $var = []; $docs = []; $nombres = [];
    foreach ($cl as $id => $c) {
        foreach ($c['tok'] as $t) {
            $idx[$t][] = $id;
            $len = strlen($t);
            if ($df[$t] <= CD_RARA && $len >= 4 && $len <= 14) {
                for ($i = 0; $i < $len; $i++) $var[substr($t, 0, $i) . substr($t, $i + 1)][] = $id;
                $var[$t][] = $id;
            }
        }
        if ($c['doc'] !== '') $docs[$c['doc']][] = $id;
        $o = $c['tok']; sort($o); $nombres[implode(' ', $o)][] = $id;
    }

    // Pares candidatos: para cada cliente se parte de su palabra MÁS RARA (todo nombre que lo
    // contenga debe tener esa palabra), así cada búsqueda es chica.
    $pares = [];
    $agregar = function ($a, $b) use (&$pares, $cl, $df) {
        if ($a === $b) return;
        $k = $a < $b ? "$a-$b" : "$b-$a";
        if (isset($pares[$k])) return;
        if (cd_compatibles($cl[$a], $cl[$b], $df)) $pares[$k] = [$a, $b];
    };
    foreach ($docs as $ids) { if (count($ids) > 1 && count($ids) <= 50) foreach ($ids as $i => $a) foreach (array_slice($ids, $i + 1) as $b) $agregar($a, $b); }
    foreach ($nombres as $ids) { if (count($ids) > 1 && count($ids) <= 50) foreach ($ids as $i => $a) foreach (array_slice($ids, $i + 1) as $b) $agregar($a, $b); }
    foreach ($cl as $a => $c) {
        $rara = null;
        foreach ($c['tok'] as $t) { if (strlen($t) >= 3 && ($rara === null || $df[$t] < $df[$rara])) $rara = $t; }
        if ($rara === null) continue;
        $cand = $idx[$rara] ?? [];
        if ($df[$rara] <= CD_RARA && strlen($rara) >= 4) {
            $len = strlen($rara);
            $cand = array_merge($cand, $var[$rara] ?? []);
            for ($i = 0; $i < $len; $i++) $cand = array_merge($cand, $var[substr($rara, 0, $i) . substr($rara, $i + 1)] ?? []);
            $cand = array_unique($cand);
        }
        if (count($cand) > 400) continue; // nombre hecho solo de palabras muy comunes
        foreach ($cand as $b) $agregar($a, $b);
    }

    // Se unen primero los pares con más ventas. Dos grupos solo se juntan si TODOS sus
    // integrantes son compatibles entre sí (evita encadenar personas distintas).
    uasort($pares, function ($x, $y) use ($cl) {
        return ($cl[$y[0]]['ventas'] + $cl[$y[1]]['ventas']) - ($cl[$x[0]]['ventas'] + $cl[$x[1]]['ventas']);
    });
    $grupo = [];     // id cliente -> id de grupo
    $miembros = [];  // id de grupo -> [ids]
    $nuevo = 0;
    foreach ($pares as [$a, $b]) {
        $ga = $grupo[$a] ?? null; $gb = $grupo[$b] ?? null;
        if ($ga !== null && $ga === $gb) continue;
        $ma = $ga !== null ? $miembros[$ga] : [$a];
        $mb = $gb !== null ? $miembros[$gb] : [$b];
        if (count($ma) + count($mb) > 15) continue;
        $ok = true;
        foreach ($ma as $x) { foreach ($mb as $y) { if (!cd_compatibles($cl[$x], $cl[$y], $df)) { $ok = false; break 2; } } }
        if (!$ok) continue;
        $dest = $ga ?? ($gb ?? $nuevo++);
        $todos = array_merge($ma, $mb);
        if ($ga !== null && $gb !== null) unset($miembros[$gb]);
        $miembros[$dest] = $todos;
        foreach ($todos as $m) $grupo[$m] = $dest;
    }

    $out = [];
    foreach ($miembros as $ids) {
        if (count($ids) < 2) continue;
        $g = [];
        foreach ($ids as $i) $g[] = $cl[$i];
        usort($g, function ($a, $b) {
            if ($a['ventas'] !== $b['ventas']) return $b['ventas'] - $a['ventas'];
            return strlen($b['cliente']) - strlen($a['cliente']);
        });
        $out[] = $g;
    }
    usort($out, function ($a, $b) { return array_sum(array_column($b, 'ventas')) - array_sum(array_column($a, 'ventas')); });
    return $out;
}
