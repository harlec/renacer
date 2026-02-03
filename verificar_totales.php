<?php
session_start();
include('inc/control.php');
include('inc/sdba/sdba.php');

$hoy = date('Y-m-d');

// REPORTE 1: Ventas de hoy con comparación de totales
echo "<h2>REPORTE: Ventas de Hoy (".date('d/m/Y').")</h2>";

$query_hoy = "
SELECT 
    v.id_venta,
    v.fecha,
    v.cliente,
    v.total as total_venta,
    v.estado,
    COALESCE(SUM(dv.total), 0) as total_detalle,
    (v.total - COALESCE(SUM(dv.total), 0)) as diferencia
FROM ventas v
LEFT JOIN detalle_ventas dv ON v.id_venta = dv.venta
WHERE DATE(v.fecha) = '$hoy'
GROUP BY v.id_venta
ORDER BY v.id_venta DESC
";

$result_hoy = Sdba::db()->query($query_hoy)->result();

echo "<table border='1' cellpadding='10' style='border-collapse: collapse; width: 100%; margin-bottom: 30px;'>";
echo "<thead style='background: #f0f0f0;'>
        <tr>
            <th>ID Venta</th>
            <th>Fecha</th>
            <th>Cliente</th>
            <th>Estado</th>
            <th>Total Venta</th>
            <th>Total Detalle</th>
            <th>Diferencia</th>
            <th>Estado</th>
        </tr>
      </thead>";
echo "<tbody>";

$total_ventas_campo = 0;
$total_detalle_suma = 0;

foreach ($result_hoy as $row) {
    $diferencia = round($row['diferencia'], 2);
    $estilo = '';
    $estado_texto = '';
    
    if ($row['estado'] == '2') {
        $estilo = 'background: #ffcccc;';
        $estado_texto = 'ANULADA';
    } elseif (abs($diferencia) > 0.01) {
        $estilo = 'background: #ffffcc;';
        $estado_texto = 'DISCREPANCIA';
    } else {
        $estado_texto = 'OK';
    }
    
    // Solo sumar si no está anulada
    if ($row['estado'] != '2') {
        $total_ventas_campo += $row['total_venta'];
        $total_detalle_suma += $row['total_detalle'];
    }
    
    echo "<tr style='$estilo'>";
    echo "<td>V-".$row['id_venta']."</td>";
    echo "<td>".$row['fecha']."</td>";
    echo "<td>".htmlspecialchars($row['cliente'])."</td>";
    echo "<td>".$row['estado']."</td>";
    echo "<td style='text-align: right;'>S/ ".number_format($row['total_venta'], 2)."</td>";
    echo "<td style='text-align: right;'>S/ ".number_format($row['total_detalle'], 2)."</td>";
    echo "<td style='text-align: right; ".($diferencia != 0 ? "font-weight: bold; color: red;" : "")."'>".($diferencia != 0 ? "S/ ".number_format($diferencia, 2) : "OK")."</td>";
    echo "<td><strong>".$estado_texto."</strong></td>";
    echo "</tr>";
}

echo "<tr style='background: #e0e0e0; font-weight: bold;'>";
echo "<td colspan='4'>TOTAL (sin anuladas)</td>";
echo "<td style='text-align: right;'>S/ ".number_format($total_ventas_campo, 2)."</td>";
echo "<td style='text-align: right;'>S/ ".number_format($total_detalle_suma, 2)."</td>";
echo "<td style='text-align: right;'>".($total_ventas_campo != $total_detalle_suma ? "S/ ".number_format($total_ventas_campo - $total_detalle_suma, 2) : "OK")."</td>";
echo "<td></td>";
echo "</tr>";

echo "</tbody></table>";

// REPORTE 2: Todas las ventas con discrepancias
echo "<h2>REPORTE: Todas las Ventas con Discrepancias</h2>";

$query_discrepancias = "
SELECT 
    v.id_venta,
    v.fecha,
    v.cliente,
    v.total as total_venta,
    v.estado,
    COALESCE(SUM(dv.total), 0) as total_detalle,
    (v.total - COALESCE(SUM(dv.total), 0)) as diferencia
FROM ventas v
LEFT JOIN detalle_ventas dv ON v.id_venta = dv.venta
GROUP BY v.id_venta
HAVING ABS(diferencia) > 0.01
ORDER BY ABS(diferencia) DESC
LIMIT 50
";

$result_discrepancias = Sdba::db()->query($query_discrepancias)->result();

if (count($result_discrepancias) > 0) {
    echo "<table border='1' cellpadding='10' style='border-collapse: collapse; width: 100%; margin-bottom: 30px;'>";
    echo "<thead style='background: #f0f0f0;'>
            <tr>
                <th>ID Venta</th>
                <th>Fecha</th>
                <th>Cliente</th>
                <th>Estado</th>
                <th>Total Venta</th>
                <th>Total Detalle</th>
                <th>Diferencia</th>
                <th>Ver</th>
            </tr>
          </thead>";
    echo "<tbody>";
    
    foreach ($result_discrepancias as $row) {
        $diferencia = round($row['diferencia'], 2);
        $estilo = $row['estado'] == '2' ? 'background: #ffcccc;' : 'background: #ffffcc;';
        
        echo "<tr style='$estilo'>";
        echo "<td>V-".$row['id_venta']."</td>";
        echo "<td>".$row['fecha']."</td>";
        echo "<td>".htmlspecialchars($row['cliente'])."</td>";
        echo "<td>".($row['estado'] == '2' ? 'ANULADA' : $row['estado'])."</td>";
        echo "<td style='text-align: right;'>S/ ".number_format($row['total_venta'], 2)."</td>";
        echo "<td style='text-align: right;'>S/ ".number_format($row['total_detalle'], 2)."</td>";
        echo "<td style='text-align: right; font-weight: bold; color: red;'>S/ ".number_format($diferencia, 2)."</td>";
        echo "<td><a href='ver_venta.php?id=".$row['id_venta']."' target='_blank'>Ver</a></td>";
        echo "</tr>";
    }
    
    echo "</tbody></table>";
} else {
    echo "<p style='color: green; font-weight: bold;'>✓ No se encontraron discrepancias en los totales</p>";
}

// REPORTE 3: Estadísticas generales
echo "<h2>ESTADÍSTICAS</h2>";

$query_stats = "
SELECT 
    COUNT(*) as total_ventas,
    COUNT(CASE WHEN estado != '2' THEN 1 END) as ventas_activas,
    COUNT(CASE WHEN estado = '2' THEN 1 END) as ventas_anuladas,
    SUM(CASE WHEN estado != '2' THEN total ELSE 0 END) as monto_activas
FROM ventas
WHERE DATE(fecha) = '$hoy'
";

$stats = Sdba::db()->query($query_stats)->row();

echo "<div style='background: #f9f9f9; padding: 20px; border-radius: 5px;'>";
echo "<p><strong>Total de ventas hoy:</strong> ".$stats['total_ventas']."</p>";
echo "<p><strong>Ventas activas:</strong> ".$stats['ventas_activas']."</p>";
echo "<p><strong>Ventas anuladas:</strong> ".$stats['ventas_anuladas']."</p>";
echo "<p><strong>Monto total (sin anuladas):</strong> S/ ".number_format($stats['monto_activas'], 2)."</p>";
echo "</div>";

echo "<hr>";
echo "<p style='margin-top: 20px;'><a href='dashboard.php'>← Volver al Dashboard</a></p>";
?>
