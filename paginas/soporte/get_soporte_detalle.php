<?php
// paginas/soporte/get_soporte_detalle.php
// Retorna todos los campos de un soporte para poblar el modal de edición.

header('Content-Type: application/json; charset=utf-8');
require_once '../conexion.php';

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id <= 0) {
    echo json_encode(['error' => 'ID inválido']);
    exit;
}

$sql = "SELECT s.*, c.nombre_completo, c.cedula
        FROM soportes s
        INNER JOIN contratos c ON s.id_contrato = c.id
        WHERE s.id_soporte = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['error' => 'Soporte no encontrado']);
    exit;
}

$row = $result->fetch_assoc();

// Formatear fecha para input[type=date]
$row['fecha_soporte_form'] = date('Y-m-d', strtotime($row['fecha_soporte']));

echo json_encode($row, JSON_UNESCAPED_UNICODE);
?>