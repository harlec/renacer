<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['ingress']) || $_SESSION['ingress'] !== true) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'mensaje' => 'Sesión no válida']);
    exit;
}

require_once __DIR__ . '/ia_helper.php';

function mensaje_error_subida(int $codigo): string
{
    switch ($codigo) {
        case UPLOAD_ERR_INI_SIZE:
        case UPLOAD_ERR_FORM_SIZE:
            return 'El archivo es demasiado grande para el servidor';
        case UPLOAD_ERR_PARTIAL:
            return 'La subida se interrumpió, intenta de nuevo';
        case UPLOAD_ERR_NO_FILE:
            return 'No se seleccionó ningún archivo';
        default:
            return 'No se pudo subir el archivo';
    }
}

$conn = new mysqli('localhost', 'admin_renacer', 'ikm169uhn', 'admin_renacer');
$conn->set_charset('utf8');

try {
    $tipo = $_POST['tipo'] ?? '';
    $textoOriginal = null;
    $rutaImagen = null;

    if ($tipo === 'texto') {
        $textoOriginal = trim($_POST['texto'] ?? '');
        if ($textoOriginal === '') {
            throw new Exception('Escribe o pega el texto del pedido');
        }
    } elseif ($tipo === 'foto') {
        if (empty($_FILES['archivo'])) {
            throw new Exception('No se recibió ninguna foto');
        }
        if ($_FILES['archivo']['error'] !== UPLOAD_ERR_OK) {
            throw new Exception(mensaje_error_subida($_FILES['archivo']['error']));
        }
        $rutaImagen = $_FILES['archivo']['tmp_name'];
    } elseif ($tipo === 'audio') {
        if (empty($_FILES['archivo'])) {
            throw new Exception('No se recibió ningún audio');
        }
        if ($_FILES['archivo']['error'] !== UPLOAD_ERR_OK) {
            throw new Exception(mensaje_error_subida($_FILES['archivo']['error']));
        }
        $textoOriginal = ia_transcribir_audio($_FILES['archivo']['tmp_name'], $_FILES['archivo']['name']);
    } else {
        throw new Exception('Tipo de pedido no reconocido');
    }

    $historial = []; // nom_prod => id_producto
    // Si el usuario eligió el cliente del desplegable (que muestra cuántos pedidos tiene cada
    // uno), usamos ese id exacto — hay nombres duplicados en la base y buscar solo por texto
    // puede agarrar el registro equivocado (uno con poco o nada de historial).
    $idClienteExistente = ia_validar_id_cliente($conn, $_POST['cliente_id'] ?? '')
        ?? ia_buscar_id_cliente($conn, $_POST['cliente'] ?? '');
    if ($idClienteExistente) {
        $historial = ia_historial_cliente_con_id($conn, $idClienteExistente);
    }

    $resultado = ia_interpretar_pedido($textoOriginal, $rutaImagen, array_keys($historial));

    $items = [];
    foreach ($resultado['items'] ?? [] as $item) {
        $texto = trim($item['texto'] ?? '');
        $cantidad = round((float)($item['cantidad'] ?? 1), 3);
        if ($texto === '' || $cantidad <= 0) {
            continue;
        }

        $sugeridoHistorial = isset($item['producto_historial']) ? trim((string)$item['producto_historial']) : null;
        $match = ia_buscar_producto_similar($conn, $texto, $historial, $sugeridoHistorial ?: null);
        $items[] = [
            'texto_leido' => $texto,
            'cantidad'    => $cantidad,
            'producto_id' => $match['id_producto'] ?? null,
            'nom_prod'    => $match['nom_prod'] ?? null,
            'score'       => $match['score'] ?? 0,
        ];
    }

    if (empty($items)) {
        throw new Exception('No se pudo identificar ningún producto en el pedido');
    }

    echo json_encode([
        'ok' => true,
        'cliente_texto' => $resultado['cliente_texto'] ?? null,
        'texto_leido_completo' => $textoOriginal,
        'items' => $items,
        'historial_usado' => count($historial),
    ]);
} catch (Exception $e) {
    echo json_encode(['ok' => false, 'mensaje' => $e->getMessage()]);
} finally {
    $conn->close();
}
