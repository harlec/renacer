<?php
include('inc/control.php');
if ($_SESSION['type'] === 'operador') {
    header("Location: dashboard.php");
    exit;
}

require_once __DIR__ . '/inc/sdba/sdba.php';
require_once __DIR__ . '/inc/config_facturacion.php';
require_once __DIR__ . '/inc/dompdf/autoload.inc.php';
use Dompdf\Dompdf;

$id_detalle = intval($_GET['id_detalle'] ?? 0);

$conn = new mysqli('localhost', 'admin_renacer', 'ikm169uhn', 'admin_renacer');
$conn->set_charset('utf8');

$r = $conn->query("
    SELECT pd.id_detalle, pd.id_periodo, pd.sueldo_periodo, pd.calculo_diario,
           e.nombres, e.apellidos, e.dni, e.direccion, e.cargo, e.sueldo_mensual,
           e.hora_ingreso, e.hora_salida, e.fecha_ingreso,
           pp.fecha_inicio, pp.fecha_fin, pp.dias
    FROM planilla_detalle pd
    INNER JOIN empleados e ON e.id_empleado = pd.id_empleado
    INNER JOIN planilla_periodos pp ON pp.id_periodo = pd.id_periodo
    WHERE pd.id_detalle = $id_detalle
");
$det = $r ? $r->fetch_assoc() : null;

if (!$det) {
    $conn->close();
    echo 'Registro no encontrado. <a href="planillas.php">Volver</a>';
    exit;
}

$rd = $conn->query("SELECT tipo, fecha, importe, descripcion FROM planilla_descuentos WHERE id_detalle = $id_detalle ORDER BY fecha, id_descuento");
$porTipo = ['tardanza' => [], 'abarrotes' => [], 'adelanto' => [], 'falta' => [], 'prestamo' => [], 'afp' => []];
while ($d = $rd->fetch_assoc()) {
    $porTipo[$d['tipo']][] = $d;
}
$conn->close();

function ia_boleta_suma(array $filas): float
{
    $t = 0.0;
    foreach ($filas as $f) {
        $t += (float) $f['importe'];
    }
    return $t;
}

$totalTardanzas  = ia_boleta_suma($porTipo['tardanza']);
$totalAbarrotes  = ia_boleta_suma($porTipo['abarrotes']);
$totalAdelantos  = ia_boleta_suma($porTipo['adelanto']);
$totalFaltas     = ia_boleta_suma($porTipo['falta']);
$totalPrestamos  = ia_boleta_suma($porTipo['prestamo']);
$totalAfp        = ia_boleta_suma($porTipo['afp']);
$totalDescuentos = $totalTardanzas + $totalAbarrotes + $totalAdelantos + $totalFaltas + $totalPrestamos + $totalAfp;

$sueldoPeriodo = round((float) $det['sueldo_periodo'], 2);
$totalPagar    = round($sueldoPeriodo - $totalDescuentos, 2);
$calculoDiario = round((float) $det['calculo_diario'], 2);

// Tarifa por hora/minuto de referencia (informativa): usa el horario lunes-viernes propio
// del colaborador, o el horario general de la empresa si no tiene uno definido.
$horaIngreso = ($det['hora_ingreso'] && $det['hora_ingreso'] !== '00:00:00') ? $det['hora_ingreso'] : get_config('planilla_horario_lv_ingreso');
$horaSalida  = ($det['hora_salida'] && $det['hora_salida'] !== '00:00:00') ? $det['hora_salida'] : get_config('planilla_horario_lv_salida');
$horasProg   = ($horaIngreso && $horaSalida) ? (strtotime($horaSalida) - strtotime($horaIngreso)) / 3600 : 0;
$calculoHora   = $horasProg > 0 ? round($calculoDiario / $horasProg, 2) : 0;
$calculoMinuto = $horasProg > 0 ? round($calculoHora / 60, 2) : 0;

$mesesEs = [1 => 'ENERO', 2 => 'FEBRERO', 3 => 'MARZO', 4 => 'ABRIL', 5 => 'MAYO', 6 => 'JUNIO', 7 => 'JULIO', 8 => 'AGOSTO', 9 => 'SEPTIEMBRE', 10 => 'OCTUBRE', 11 => 'NOVIEMBRE', 12 => 'DICIEMBRE'];
function ia_boleta_fecha_letras(string $fecha, array $meses): string
{
    $ts = strtotime($fecha);
    return $ts ? ((int) date('d', $ts) . ' DE ' . $meses[(int) date('n', $ts)]) : '';
}

$rangoFechas  = ia_boleta_fecha_letras($det['fecha_inicio'], $mesesEs) . ' AL ' . ia_boleta_fecha_letras($det['fecha_fin'], $mesesEs);
$fechaIngreso = (!empty($det['fecha_ingreso']) && $det['fecha_ingreso'] !== '0000-00-00') ? date('d/m/Y', strtotime($det['fecha_ingreso'])) : '';
$nombreCompleto = strtoupper(trim($det['nombres'] . ' ' . $det['apellidos']));

function ia_boleta_filas(array $filas, array $columnas): string
{
    $html = '';
    foreach ($filas as $f) {
        $html .= '<tr>';
        foreach ($columnas as $c) {
            if ($c === 'fecha') {
                $html .= '<td>' . date('d-M', strtotime($f['fecha'])) . '</td>';
            } elseif ($c === 'importe') {
                $html .= '<td style="text-align:right">' . number_format((float) $f['importe'], 2) . '</td>';
            } else {
                $html .= '<td>' . htmlspecialchars($f['descripcion'] ?? '') . '</td>';
            }
        }
        $html .= '</tr>';
    }
    return $html;
}

ob_start();
?>
<style>
    body { font-family: Helvetica, Arial, sans-serif; font-size: 10px; color:#000; }
    .titulo-principal { text-align:center; color:#e74c3c; font-size:20px; font-weight:bold; margin-bottom:12px; }
    table.datos-emp { width:100%; margin-bottom:10px; }
    table.datos-emp td { padding:2px 4px; font-size:10px; }
    table.datos-emp .label { width:180px; }
    table.tabla-resumen, table.tabla-desc { border-collapse: collapse; width:100%; }
    table.tabla-resumen th, table.tabla-resumen td,
    table.tabla-desc th, table.tabla-desc td { border:1px solid #999; padding:3px 4px; font-size:8.5px; }
    table.tabla-resumen th { background:#f0f0f0; text-align:center; }
    .grupo-remu { background:#e8e8e8; }
    .grupo-desc { color:#c0392b; }
    .total-pagar { color:#e74c3c; font-weight:bold; }
    .titulo-descuentos { text-align:center; color:#2e86c1; font-size:16px; font-weight:bold; margin:16px 0 8px; }
    td.detalle-cell { vertical-align:top; padding:0 3px; width:16.6%; }
    @page { margin: 1cm; }
</style>

<div class="titulo-principal">FAMILIA RENACER</div>

<table class="datos-emp">
    <tr><td class="label">NOMBRE Y APELLIDOS</td><td><?= htmlspecialchars($nombreCompleto) ?></td></tr>
    <tr><td class="label">DNI</td><td><?= htmlspecialchars($det['dni'] ?? '') ?></td></tr>
    <tr><td class="label">DOMICILIO</td><td><?= htmlspecialchars($det['direccion'] ?? '') ?></td></tr>
    <tr><td class="label">CARGO Y OCUPACION</td><td><?= htmlspecialchars($det['cargo'] ?? '') ?></td></tr>
    <tr><td class="label">SUELDO</td><td><?= number_format((float) $det['sueldo_mensual'], 2) ?></td></tr>
    <tr><td class="label">FORMA DE PAGO</td><td>QUINCENAL DEL <?= $rangoFechas ?></td></tr>
    <tr><td class="label">FECHA DE INGRESO</td><td><?= htmlspecialchars($fechaIngreso) ?></td></tr>
    <tr><td class="label">DIAS DE CALCULO</td><td><?= (int) $det['dias'] ?></td></tr>
    <tr><td class="label">CALCULO DIARIO</td><td><?= number_format($calculoDiario, 2) ?></td></tr>
</table>

<table class="tabla-resumen">
    <tr>
        <th rowspan="2">NOMBRE Y APELLIDOS</th>
        <th colspan="5" class="grupo-remu">REMUNERACION</th>
        <th colspan="5" class="grupo-desc">DESCUENTOS</th>
        <th rowspan="2">TOTAL A<br>PAGAR</th>
    </tr>
    <tr>
        <th>SUELDO</th><th>CALCULO DE DIAS</th><th>DIAS</th><th>CALCULO X HORAS</th><th>CALCULO X MINUTOS</th>
        <th class="grupo-desc">TARDANZAS</th><th class="grupo-desc">ABARROTES</th><th class="grupo-desc">ADELANTOS</th><th class="grupo-desc">DIAS FALTADOS</th><th class="grupo-desc">PRESTAMOS</th><th class="grupo-desc">AFP</th>
    </tr>
    <tr>
        <td><?= htmlspecialchars($nombreCompleto) ?></td>
        <td style="text-align:right"><?= number_format($sueldoPeriodo, 2) ?></td>
        <td style="text-align:right"><?= number_format($calculoDiario, 2) ?></td>
        <td style="text-align:right"><?= (int) $det['dias'] ?></td>
        <td style="text-align:right"><?= number_format($calculoHora, 2) ?></td>
        <td style="text-align:right"><?= number_format($calculoMinuto, 2) ?></td>
        <td style="text-align:right"><?= $totalTardanzas > 0 ? number_format($totalTardanzas, 2) : '-' ?></td>
        <td style="text-align:right"><?= $totalAbarrotes > 0 ? number_format($totalAbarrotes, 2) : '-' ?></td>
        <td style="text-align:right"><?= $totalAdelantos > 0 ? number_format($totalAdelantos, 2) : '-' ?></td>
        <td style="text-align:right"><?= $totalFaltas > 0 ? number_format($totalFaltas, 2) : '-' ?></td>
        <td style="text-align:right"><?= $totalPrestamos > 0 ? number_format($totalPrestamos, 2) : '-' ?></td>
        <td style="text-align:right"><?= $totalAfp > 0 ? number_format($totalAfp, 2) : '-' ?></td>
        <td class="total-pagar" style="text-align:right"><?= number_format($totalPagar, 2) ?></td>
    </tr>
</table>

<div class="titulo-descuentos">DESCUENTOS</div>

<table style="width:100%">
<tr>
    <td class="detalle-cell">
        <table class="tabla-desc">
            <tr><th colspan="3">ABARROTES</th></tr>
            <tr><th>FECHA</th><th>IMPORTE</th><th>DETALLE</th></tr>
            <?= ia_boleta_filas($porTipo['abarrotes'], ['fecha', 'importe', 'descripcion']) ?>
            <tr><td><b>TOTALES</b></td><td style="text-align:right"><b><?= number_format($totalAbarrotes, 2) ?></b></td><td></td></tr>
        </table>
    </td>
    <td class="detalle-cell">
        <table class="tabla-desc">
            <tr><th colspan="3">ADELANTOS</th></tr>
            <tr><th>FECHA</th><th>DETALLE</th><th>IMPORTE</th></tr>
            <?= ia_boleta_filas($porTipo['adelanto'], ['fecha', 'descripcion', 'importe']) ?>
            <tr><td><b>TOTALES</b></td><td></td><td style="text-align:right"><b><?= number_format($totalAdelantos, 2) ?></b></td></tr>
        </table>
    </td>
    <td class="detalle-cell">
        <table class="tabla-desc">
            <tr><th colspan="2">DIAS FALTADOS</th></tr>
            <tr><th>FECHA</th><th>IMPORTE</th></tr>
            <?= ia_boleta_filas($porTipo['falta'], ['fecha', 'importe']) ?>
            <tr><td><b>TOTALES</b></td><td style="text-align:right"><b><?= $totalFaltas > 0 ? number_format($totalFaltas, 2) : '-' ?></b></td></tr>
        </table>
    </td>
    <td class="detalle-cell">
        <table class="tabla-desc">
            <tr><th colspan="2">TARDANZAS</th></tr>
            <tr><th>FECHA</th><th>IMPORTE</th></tr>
            <?= ia_boleta_filas($porTipo['tardanza'], ['fecha', 'importe']) ?>
            <tr><td><b>TOTALES</b></td><td style="text-align:right"><b><?= $totalTardanzas > 0 ? number_format($totalTardanzas, 2) : '-' ?></b></td></tr>
        </table>
    </td>
    <td class="detalle-cell">
        <table class="tabla-desc">
            <tr><th colspan="2">PRESTAMOS</th></tr>
            <tr><th>FECHA</th><th>IMPORTE</th></tr>
            <?= ia_boleta_filas($porTipo['prestamo'], ['fecha', 'importe']) ?>
            <tr><td><b>TOTALES</b></td><td style="text-align:right"><b><?= $totalPrestamos > 0 ? number_format($totalPrestamos, 2) : '-' ?></b></td></tr>
        </table>
    </td>
    <td class="detalle-cell">
        <table class="tabla-desc">
            <tr><th colspan="2">AFP</th></tr>
            <tr><th>FECHA</th><th>IMPORTE</th></tr>
            <?= ia_boleta_filas($porTipo['afp'], ['fecha', 'importe']) ?>
            <tr><td><b>TOTALES</b></td><td style="text-align:right"><b><?= $totalAfp > 0 ? number_format($totalAfp, 2) : '-' ?></b></td></tr>
        </table>
    </td>
</tr>
</table>
<?php
$html = ob_get_clean();

$dompdf = new Dompdf();
$dompdf->set_paper('A4', 'landscape');
$dompdf->load_html($html);
$dompdf->render();

$nombreArchivo = 'boleta_' . preg_replace('/[^A-Za-z0-9_]/', '_', $nombreCompleto) . '.pdf';
$dompdf->stream($nombreArchivo, ['Attachment' => 0]);
