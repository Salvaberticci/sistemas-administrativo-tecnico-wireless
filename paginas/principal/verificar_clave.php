<?php
/**
 * API endpoint to verify the current user's password.
 * Returns JSON {success: true/false, message: "..."}
 */
ini_set('display_errors', 0);
header('Content-Type: application/json');

session_start();
require '../conexion.php';

if (!isset($_SESSION['usuario_id'])) {
    echo json_encode(['success' => false, 'message' => 'Sesión no iniciada.']);
    exit;
}

$clave = $_POST['clave'] ?? '';
if (!$clave) {
    echo json_encode(['success' => false, 'message' => 'Contraseña requerida.']);
    exit;
}

$usuario_id = $_SESSION['usuario_id'];
$stmt = $conn->prepare("SELECT clave FROM usuarios WHERE id_usuario = ?");
$stmt->bind_param("i", $usuario_id);
$stmt->execute();
$res = $stmt->get_result();

if ($res->num_rows !== 1) {
    echo json_encode(['success' => false, 'message' => 'Usuario no encontrado.']);
    exit;
}

$user = $res->fetch_assoc();
if (password_verify($clave, $user['clave'])) {
    echo json_encode(['success' => true, 'message' => 'Contraseña correcta.']);
} else {
    echo json_encode(['success' => false, 'message' => 'Contraseña incorrecta.']);
}

$conn->close();
?>