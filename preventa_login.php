<?php
session_start();
ini_set('display_errors', '0');
require_once 'inc/sdba/sdba.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['doc_identidad'])) {
    $doc = trim($_POST['doc_identidad']);

    if (!preg_match('/^\d{6,15}$/', $doc)) {
        $error = 'Ingresa un número de documento válido.';
    } else {
        $clientes = Sdba::table('clientes');
        $clientes->where('doc_identidad', $doc);
        $clientes->where('estado', '1');
        $cliente = $clientes->get_one();

        if (!$cliente) {
            $error = 'No encontramos un cliente con ese documento. Consulta con la tienda.';
        } else {
            $_SESSION['pv_cliente_id']     = (int)$cliente['id_cliente'];
            $_SESSION['pv_cliente_nombre'] = $cliente['cliente'];
            $_SESSION['pv_expira']         = time() + 1200;
            header('Location: preventa_nueva.php');
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
<title>Hacer pedido</title>
<style>
@import url('https://fonts.googleapis.com/css2?family=Barlow+Condensed:wght@700;900&family=Barlow:wght@400;600&display=swap');
*{box-sizing:border-box;margin:0;padding:0}
body{
  min-height:100vh;
  display:flex;
  align-items:center;
  justify-content:center;
  background:#1a1a1a;
  color:#f0f0f0;
  font-family:'Barlow',sans-serif;
  padding:20px;
}
.card{
  width:100%;
  max-width:360px;
  background:#242424;
  border:1px solid #3a3a3a;
  border-radius:14px;
  padding:28px 24px;
}
h1{
  font-family:'Barlow Condensed',sans-serif;
  font-size:24px;
  font-weight:900;
  text-transform:uppercase;
  letter-spacing:1px;
  text-align:center;
  margin-bottom:6px;
}
p.sub{
  text-align:center;
  font-size:13px;
  color:#999;
  margin-bottom:22px;
}
label{
  display:block;
  font-size:12px;
  text-transform:uppercase;
  letter-spacing:1px;
  color:#999;
  margin-bottom:6px;
}
input[type=text]{
  width:100%;
  padding:14px;
  font-size:20px;
  letter-spacing:1px;
  border-radius:10px;
  border:2px solid #3a3a3a;
  background:#1a1a1a;
  color:#fff;
  margin-bottom:16px;
}
input[type=text]:focus{outline:none;border-color:#f5a623}
button{
  width:100%;
  padding:14px;
  border:none;
  border-radius:10px;
  background:#27ae60;
  color:#fff;
  font-family:'Barlow Condensed',sans-serif;
  font-size:18px;
  font-weight:900;
  letter-spacing:1px;
  text-transform:uppercase;
  cursor:pointer;
}
button:active{background:#1e8449}
.error{
  background:rgba(231,76,60,.15);
  border:1px solid #e74c3c;
  color:#ff9d92;
  padding:10px 12px;
  border-radius:8px;
  font-size:13px;
  margin-bottom:16px;
}
</style>
</head>
<body>
<div class="card">
  <h1>Hacer pedido</h1>
  <p class="sub">Ingresa tu número de documento para continuar</p>
  <?php if ($error): ?>
    <div class="error"><?= htmlspecialchars($error) ?></div>
  <?php endif; ?>
  <form method="POST" autocomplete="off">
    <label for="doc_identidad">Documento de identidad</label>
    <input type="text" inputmode="numeric" pattern="[0-9]*" id="doc_identidad" name="doc_identidad" maxlength="15" required autofocus>
    <button type="submit">Continuar</button>
  </form>
</div>
</body>
</html>
