<?php
session_start();
if (empty($_SESSION['ingress'])) { http_response_code(403); exit; }

require_once __DIR__ . '/dompdf/autoload.inc.php';
require_once __DIR__ . '/vendor/autoload.php';
use Luecano\NumeroALetras\NumeroALetras;
use Dompdf\Dompdf;

$data     = json_decode(file_get_contents('php://input'), true);
$items    = $data['items']    ?? [];
$grand    = floatval($data['grand']    ?? 0);
$vendedor = htmlspecialchars($data['vendedor'] ?? '', ENT_QUOTES);
$fecha    = htmlspecialchars($data['fecha']    ?? '', ENT_QUOTES);
$addr     = htmlspecialchars($data['addr']     ?? '', ENT_QUOTES);
$phone    = htmlspecialchars($data['phone']    ?? '', ENT_QUOTES);

$formatter = new NumeroALetras();
$letras    = $formatter->toInvoice($grand, 2) . ' SOLES';

$rows = '';
foreach ($items as $ci) {
    $name  = htmlspecialchars($ci['name']  ?? '', ENT_QUOTES);
    $total = number_format(floatval($ci['total'] ?? 0), 2, '.', ',');
    $rows .= "<tr>
        <td style=\"font-weight:bold;font-size:9px\">$name</td>
        <td style=\"text-align:right;font-weight:bold\">$total</td>
    </tr>";
}

$total_fmt = number_format($grand, 2, '.', ',');

$logo_path = realpath(__DIR__ . '/../assets/img/logo_avasa.png');
$logo_b64  = base64_encode(file_get_contents($logo_path));
$logo_src  = 'data:image/png;base64,' . $logo_b64;

$addr_row  = $addr  ? "<h6>$addr</h6>"        : '';
$phone_row = $phone ? "<h6>Tel: $phone</h6>"  : '';

$html = <<<HTML
<style>
    body { font-family: Helvetica, Sans-Serif; }
    thead th { font-size: 9px; font-weight: bold; }
    tbody td { font-size: 9px; }
    @page { margin-left: 0.4cm; margin-right: 0.4cm; margin-top: 0.4cm; }
</style>
<img style="width:230px; display:block; margin:0 auto;" src="$logo_src">
<h6 style="margin-top:0px;">&ldquo;Y aunque tu principio haya sido peque&ntilde;o,
Tu postrer estado ser&aacute; muy grande&rdquo;</h6>
<h6 style="text-align:right; margin-top:-20px;">Job 8: 7</h6>
<h5 style="text-align:center;"><b>NOTA VENTA</b></h5>
<h6>FECHA: $fecha</h6>
$addr_row
$phone_row
<hr>
<table>
    <thead>
        <tr>
            <th>[CANT.][UNID] DESCRIPCI&Oacute;N</th>
            <th style="text-align:right">TOTAL</th>
        </tr>
    </thead>
    <tbody>
        $rows
        <tr>
            <td style="text-align:right"><h4>TOTAL: S/</h4></td>
            <td style="padding-left:8px; text-align:right; font-size:12px;"><h4>$total_fmt</h4></td>
        </tr>
        <tr><td colspan="2"><b>IMPORTE EN LETRAS: </b>$letras</td></tr>
        <tr><td colspan="2"><b>VENDEDOR: </b>$vendedor</td></tr>
        <tr><td colspan="2"><b>PERSONAL ENTREGA: </b>__________________________</td></tr>
    </tbody>
</table>
<h6 style="text-align:center;">DIOS TE BENDIGA<br>
GRACIAS POR TU PREFERENCIA<br>
Todo reclamo deber&aacute; realizarse dentro de los
13 d&iacute;as posteriores a la emisi&oacute;n de la boleta.</h6>
HTML;

$dompdf = new DOMPDF();
$dompdf->load_html($html);
$dompdf->set_paper([0, 0, 200, 2000]);
$dompdf->render();
$dompdf->stream('ticket.pdf', ['Attachment' => 0]);
