<?php
/**
 * get_historial_pagos.php - Fetches paid invoices for a specific contract.
 */
header('Content-Type: application/json');
require '../conexion.php';

$id_contrato = isset($_GET['id_contrato']) ? intval($_GET['id_contrato']) : 0;

if ($id_contrato <= 0) {
    echo json_encode(['success' => false, 'message' => 'ID de contrato no válido.']);
    exit;
}

$sql = "
    SELECT 
        id_cobro, 
        fecha_emision, 
        fecha_vencimiento, 
        monto_total, 
        fecha_pago,
        referencia_pago
    FROM cuentas_por_cobrar
    WHERE id_contrato = ?
    AND estado = 'PAGADO'
    ORDER BY fecha_pago DESC
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id_contrato);
$stmt->execute();
$result = $stmt->get_result();
$pagos = $result->fetch_all(MYSQLI_ASSOC);

echo json_encode(['success' => true, 'data' => $pagos]);

$conn->close();
?>
