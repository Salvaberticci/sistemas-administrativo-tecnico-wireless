<?php
// paginas/principal/generar_token_firma.php
require '../conexion.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

$id = isset($_POST['id']) ? intval($_POST['id']) : 0;

if ($id <= 0) {
    echo json_encode(['success' => false, 'message' => 'ID de contrato inválido']);
    exit;
}

// Generar un token único
$token = bin2hex(random_bytes(16));

// Actualizar el contrato con el nuevo token y poner estado en PENDIENTE
$sql = "UPDATE contratos SET token_firma = ?, estado_firma = 'PENDIENTE' WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("si", $token, $id);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'token' => $token]);
} else {
    echo json_encode(['success' => false, 'message' => 'Error al actualizar base de datos: ' . $conn->error]);
}

$stmt->close();
$conn->close();
?>