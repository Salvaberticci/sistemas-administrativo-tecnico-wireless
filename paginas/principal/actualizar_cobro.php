<?php
header('Content-Type: application/json; charset=utf-8');
require '../conexion.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método no permitido.']);
    exit;
}

$id_cobro = isset($_POST['id_cobro']) ? intval($_POST['id_cobro']) : 0;
$monto_total = isset($_POST['monto_total']) ? floatval($_POST['monto_total']) : 0;
$fecha_vencimiento = isset($_POST['fecha_vencimiento']) ? $_POST['fecha_vencimiento'] : '';
$estado = isset($_POST['estado']) ? $_POST['estado'] : '';

if ($id_cobro <= 0) {
    echo json_encode(['success' => false, 'message' => 'ID de cobro inválido.']);
    exit;
}

$stmt = $conn->prepare("UPDATE cuentas_por_cobrar SET monto_total = ?, fecha_vencimiento = ?, estado = ? WHERE id_cobro = ?");
$stmt->bind_param("dssi", $monto_total, $fecha_vencimiento, $estado, $id_cobro);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => "Cobro #{$id_cobro} actualizado con éxito."]);
} else {
    echo json_encode(['success' => false, 'message' => 'Error al actualizar: ' . $stmt->error]);
}

$stmt->close();
$conn->close();
