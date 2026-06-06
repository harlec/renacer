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
$cliente  = htmlspecialchars($data['cliente']  ?? '', ENT_QUOTES);
$addr     = htmlspecialchars($data['addr']     ?? '', ENT_QUOTES);
$phone    = htmlspecialchars($data['phone']    ?? '', ENT_QUOTES);

$formatter = new NumeroALetras();
$letras    = $formatter->toInvoice($grand, 2) . ' SOLES';

$mostrar_de_venta = '';
foreach ($items as $ci) {
    $name  = htmlspecialchars($ci['name']  ?? '', ENT_QUOTES);
    $total = number_format(floatval($ci['total'] ?? 0), 2, '.', ',');
    $mostrar_de_venta .= '<tr>
        <td style="font-weight:bold; font-size:11px;"> ' . $name . '</td>
        <td style="text-align: right; font-weight:bold;">' . $total . '</td>
    </tr>';
}

$tot_fmt  = number_format($grand, 2, '.', ',');

$logo_path = realpath(__DIR__ . '/../assets/img/logo_avasa.png');
$logo_b64  = base64_encode(file_get_contents($logo_path));
$logo_src  = 'data:image/png;base64,' . $logo_b64;

$addr_row  = $addr  ? "<h6>$addr</h6>"       : '';
$phone_row = $phone ? "<h6>Tel: $phone</h6>" : '';

ob_start();
?>
<style>
    body {
        font-family: Helvetica, Sans-Serif;
    }
    thead th {
        font-size: 9px;
        font-weight: bold;
    }
    tbody td {
        font-size: 9px;
    }
    @page {
        margin-left: 0.4cm;
        margin-right: 0.4cm;
        margin-top: 0.4cm;
    }
</style>
<img style="width:230px; text-align:center;" src="<?= $logo_src ?>">
<h6 style="margin-top:0px;">"Y aunque tu principio haya sido pequeño,
Tu postrer estado será muy grande"</h6>
<h6 style="text-align:right; margin-top:-20px;">Job 8: 7</h6>
<h5 style="text-align:center;"><b>NOTA VENTA</b></h5>
<h6>FECHA: <?= $fecha ?><br>CLIENTE: <?= $cliente ?>
</h6>
<?= $addr_row ?>
<?= $phone_row ?>
<hr>
<table>
    <thead>
        <tr>
            <th>[CANT.][UNID] DESCRIPCIÓN</th>
            <th style="text-align: right;">TOTAL</th>
        </tr>
    </thead>
    <tbody>
        <?= $mostrar_de_venta ?>
        <tr>
            <td style="text-align: right;" class="text-right"><h4>TOTAL: S/</h4></td>
            <td style="padding-left:8px; text-align: right; font-size:12px;"><h4><?= $tot_fmt ?></h4></td>
        </tr>
        <tr>
            <td colspan="2"><B>IMPORTE EN LETRAS: </B><?= $letras ?></td>
        </tr>
        <tr>
            <td colspan="2"><B>VENDEDOR: </B><?= $vendedor ?></td>
        </tr>
        <tr>
            <td colspan="2"><B>PERSONAL ENTREGA: __________________________</td>
        </tr>
    </tbody>
</table>
<h6 style="text-align:center;">DIOS TE BENDIGA<br>
    GRACIAS POR TU PREFERENCIA<br>
    Todo reclamo deberá realizarse dentro de los
    13 días posteriores a la emisión de la boleta.</h6>
<?php
$html = ob_get_clean();

$dompdf = new DOMPDF();
$dompdf->load_html($html);
$dompdf->set_paper(array(0, 0, 200, 2000));
$dompdf->render();
$pdf = $dompdf->output();

// ── Guardar en /temp/ y devolver URL para RawBT ────────────
$temp_dir = realpath(__DIR__ . '/../temp');

// Limpiar tickets anteriores de esta sesión
$sess_id = session_id();
foreach (glob("$temp_dir/ticket_{$sess_id}_*.pdf") as $old) {
    @unlink($old);
}

$filename  = "ticket_{$sess_id}_" . time() . ".pdf";
$filepath  = "$temp_dir/$filename";
file_put_contents($filepath, $pdf);

$protocol  = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host      = $_SERVER['HTTP_HOST'];
$ticket_url = "$protocol://$host/temp/$filename";

header('Content-Type: application/json');
echo json_encode(['url' => $ticket_url]);
