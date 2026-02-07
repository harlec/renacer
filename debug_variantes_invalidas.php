<?php
include('inc/control.php');
include('inc/sdba/sdba.php');

$query = "
SELECT vp.id_vp,
       vp.producto,
       p.nom_prod,
       p.stockp,
       vp.cantidad_vp,
       vp.precio_vp
FROM variante_p vp
LEFT JOIN productos p ON vp.producto = p.id_producto
WHERE vp.cantidad_vp IS NULL
   OR vp.cantidad_vp = ''
   OR vp.cantidad_vp = 0
   OR vp.cantidad_vp = '0'
   OR vp.cantidad_vp REGEXP '[^0-9\\.]'
ORDER BY vp.producto, vp.id_vp
";

$resultados = Sdba::db()->query($query)->result();

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Variantes con cantidad_vp invalida</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" type="text/css" href="/assets/css/bootstrap.min.css">
</head>
<body>
<div class="container" style="margin-top:20px;">
    <h3>Variantes con cantidad_vp invalida</h3>
    <table class="table table-striped table-condensed">
        <thead>
            <tr>
                <th>id_vp</th>
                <th>producto</th>
                <th>nom_prod</th>
                <th>stockp</th>
                <th>cantidad_vp</th>
                <th>precio_vp</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($resultados)) : ?>
                <?php foreach ($resultados as $row) : ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['id_vp'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo htmlspecialchars($row['producto'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo htmlspecialchars($row['nom_prod'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo htmlspecialchars($row['stockp'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo htmlspecialchars($row['cantidad_vp'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo htmlspecialchars($row['precio_vp'], ENT_QUOTES, 'UTF-8'); ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php else : ?>
                <tr>
                    <td colspan="6" class="text-center">No se encontraron registros invalidos.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>
</body>
</html>
