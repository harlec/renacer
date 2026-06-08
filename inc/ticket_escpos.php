<?php
session_start();
if (empty($_SESSION['ingress'])) { http_response_code(403); exit; }

header('Content-Type: application/json');
ob_start();
ini_set('display_errors','0');

$data     = json_decode(file_get_contents('php://input'), true);
$items    = $data['items']    ?? [];
$grand    = floatval($data['grand']    ?? 0);
$vendedor = $data['vendedor'] ?? '';
$fecha    = $data['fecha']    ?? '';
$cliente  = $data['cliente']  ?? '';

// ── Número a letras ────────────────────────────────────────
require_once __DIR__ . '/vendor/autoload.php';
use Luecano\NumeroALetras\NumeroALetras;
$fmt    = new NumeroALetras();
$letras = $fmt->toInvoice($grand, 2) . ' SOLES';

// ── Comandos ESC/POS ───────────────────────────────────────
$ESC = "\x1B";
$GS  = "\x1D";
$LF  = "\x0A";

$init      = $ESC . "@";           // Inicializar
$cut       = $GS  . "V\x42\x03";  // Cortar papel (avance + corte parcial)
$center    = $ESC . "a\x01";
$left      = $ESC . "a\x00";
$bold_on   = $ESC . "E\x01";
$bold_off  = $ESC . "E\x00";
$dbl_on    = $GS  . "!\x11";      // Doble ancho y alto
$dbl_off   = $GS  . "!\x00";
$wide_on   = $GS  . "!\x10";      // Solo doble ancho
$wide_off  = $GS  . "!\x00";

// Separador de 32 caracteres (ancho típico 80mm)
$sep = str_repeat('-', 32) . $LF;

$out  = $init;
$out .= $center;

// Nombre del negocio grande
$out .= $dbl_on . $bold_on;
$out .= iconv('UTF-8','CP850//TRANSLIT','Distribuidora Renacer') . $LF;
$out .= $bold_off . $dbl_off;

// Cita bíblica
$out .= $ESC . "a\x01";
$out .= iconv('UTF-8','CP850//TRANSLIT','"Y aunque tu principio haya sido pequeno,') . $LF;
$out .= iconv('UTF-8','CP850//TRANSLIT','Tu postrer estado sera muy grande"') . $LF;
$out .= iconv('UTF-8','CP850//TRANSLIT','Job 8:7') . $LF;

$out .= $LF;
$out .= $bold_on . iconv('UTF-8','CP850//TRANSLIT','NOTA VENTA') . $bold_off . $LF;
$out .= $LF;

// Fecha y cliente
$out .= $left;
$out .= iconv('UTF-8','CP850//TRANSLIT','FECHA: ' . $fecha) . $LF;
if ($cliente) {
    $out .= iconv('UTF-8','CP850//TRANSLIT','CLIENTE: ' . $cliente) . $LF;
}

$out .= $sep;

// Cabecera tabla
$out .= $bold_on;
$out .= str_pad(iconv('UTF-8','CP850//TRANSLIT','DESCRIPCION'), 24) . str_pad('TOTAL', 8, ' ', STR_PAD_LEFT) . $LF;
$out .= $bold_off;
$out .= $sep;

// Items
foreach ($items as $ci) {
    $name  = iconv('UTF-8','CP850//TRANSLIT', mb_substr($ci['name'] ?? '', 0, 30));
    $total = 'S/' . number_format(floatval($ci['total'] ?? 0), 2, '.', ',');
    // Nombre en primera línea si es largo, total a la derecha
    if (mb_strlen($name) <= 24) {
        $out .= str_pad($name, 24) . str_pad($total, 8, ' ', STR_PAD_LEFT) . $LF;
    } else {
        $out .= mb_substr($name, 0, 32) . $LF;
        $out .= str_repeat(' ', 24) . str_pad($total, 8, ' ', STR_PAD_LEFT) . $LF;
    }
}

$out .= $sep;

// Total
$total_str = 'S/' . number_format($grand, 2, '.', ',');
$out .= $bold_on . $dbl_on;
$out .= $center;
$out .= iconv('UTF-8','CP850//TRANSLIT','TOTAL: ' . $total_str) . $LF;
$out .= $dbl_off . $bold_off;
$out .= $left;

$out .= $sep;
$out .= iconv('UTF-8','CP850//TRANSLIT','IMPORTE EN LETRAS:') . $LF;
$out .= iconv('UTF-8','CP850//TRANSLIT',mb_substr($letras, 0, 32)) . $LF;
$out .= iconv('UTF-8','CP850//TRANSLIT','VENDEDOR: ' . $vendedor) . $LF;
$out .= iconv('UTF-8','CP850//TRANSLIT','PERSONAL ENTREGA: ____________') . $LF;
$out .= $sep;

// Pie
$out .= $center;
$out .= iconv('UTF-8','CP850//TRANSLIT','DIOS TE BENDIGA') . $LF;
$out .= iconv('UTF-8','CP850//TRANSLIT','GRACIAS POR TU PREFERENCIA') . $LF;
$out .= iconv('UTF-8','CP850//TRANSLIT','Reclamos dentro de 13 dias.') . $LF;
$out .= $LF . $LF . $LF;
$out .= $cut;

// ── Guardar en archivo temporal y devolver token ───────────
$token = bin2hex(random_bytes(16));
$file  = sys_get_temp_dir() . '/renacer_ticket_' . $token . '.bin';
file_put_contents($file, $out);

ob_clean();
echo json_encode(['ok' => true, 'token' => $token]);
