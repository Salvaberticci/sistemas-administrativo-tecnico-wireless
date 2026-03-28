<?php
/**
 * Control de sesión y seguridad
 * Debe incluirse al inicio de cada página antes de cualquier salida HTML
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$path_fix = isset($path_to_root) ? $path_to_root : '../../';

if (!isset($_SESSION['usuario_id'])) {
    // Si no hay sesión iniciada, redirigir al login
    header("Location: " . $path_fix . "index.html");
    exit;
}
?>
