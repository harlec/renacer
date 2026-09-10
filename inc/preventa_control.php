<?php
// Gate de sesión temporal para el flujo público de preventas (cliente
// identificado solo por DNI, sin contraseña). No llama session_start(): cada
// archivo que lo incluye ya la inició.

function preventa_sesion_valida()
{
    if (empty($_SESSION['pv_cliente_id']) || empty($_SESSION['pv_expira']) || time() > (int)$_SESSION['pv_expira']) {
        return false;
    }
    // Ventana deslizante: cada acción del cliente renueva los 20 minutos.
    $_SESSION['pv_expira'] = time() + 1200;
    return true;
}

function preventa_requerir_html()
{
    if (!preventa_sesion_valida()) {
        session_unset();
        session_destroy();
        header('Location: preventa_login.php');
        exit;
    }
}

function preventa_requerir_json()
{
    if (!preventa_sesion_valida()) {
        session_unset();
        session_destroy();
        header('Content-Type: application/json');
        http_response_code(401);
        echo json_encode(['ok' => false, 'mensaje' => 'Tu sesión expiró, vuelve a ingresar tu DNI']);
        exit;
    }
}
