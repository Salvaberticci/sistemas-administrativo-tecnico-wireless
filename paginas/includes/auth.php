<?php
/**
 * auth.php - Centralized Session and Security Logic
 * This file MUST be included at the very top of each page before ANY HTML output.
 */

// 1. Initialize Session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 2. Resolve Path for Redirects
// Normally $path_to_root is set in the main file
$auth_path_fix = isset($path_to_root) ? $path_to_root : '../../';

// 3. Security Check: Authenticated User
if (!isset($_SESSION['usuario_id'])) {
    // If not logged in, redirect to login page immediately
    header("Location: " . $auth_path_fix . "index.html");
    exit;
}
?>
