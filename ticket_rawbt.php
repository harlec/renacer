<?php
// Sirve el ticket ESC/POS a RawBT — no requiere sesión (token aleatorio = seguridad)
$token = preg_replace('/[^a-zA-Z0-9_]/', '', $_GET['token'] ?? '');
if (!$token) { http_response_code(404); exit; }

$file = sys_get_temp_dir() . '/renacer_ticket_' . $token . '.bin';
if (!file_exists($file)) { http_response_code(404); exit; }

header('Content-Type: application/octet-stream');
header('Content-Length: ' . filesize($file));
readfile($file);
@unlink($file); // borrar después de servir
