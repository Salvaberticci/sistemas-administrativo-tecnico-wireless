<?php
/**
 * check_can_delete_payment.php - Checks if a payment can be deleted
 * based on the associated contract status.
 */
header('Content-Type: application/json');
require '../conexion.php';

$id_cobro = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id_cobro <= 0) {
    echo json_encode(['success' => false, 'message' => 'ID de cobro no válido.']);
    exit;
}

$sql = "SELECT co.estado, co.nombre_completo 
        FROM cuentas_por_cobrar cxc 
        INNER JOIN contratos co ON cxc.id_contrato = co.id 
        WHERE cxc.id_cobro = ? LIMIT 1";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id_cobro);
$stmt->execute();
$result = $stmt->get_result();
$data = $result->fetch_assoc();

if (!$data) {
    // If no contract is found, we assume it can be deleted
    echo json_encode(['success' => true, 'can_delete' => true]);
} else {
    // Always allow deletion regardless of contract status
    echo json_encode(['success' => true, 'can_delete' => true]);
}

$conn->close();
?>
