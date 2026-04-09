<?php
header('Content-Type: application/json; charset=utf-8');
require_once '../conexion.php';

$id_contrato = isset($_GET['id_contrato']) ? intval($_GET['id_contrato']) : 0;

if ($id_contrato <= 0) {
    echo json_encode(['success' => false, 'message' => 'ID de contrato inválido.']);
    exit;
}

// Buscar saldo a favor (CREDITO) pendiente
$sql = "SELECT SUM(saldo_pendiente) as total_credito 
        FROM clientes_deudores 
        WHERE id_contrato = $id_contrato 
        AND tipo_registro = 'CREDITO' 
        AND estado = 'PENDIENTE'";

$res = $conn->query($sql);
$row = $res->fetch_assoc();

$total_credito = $row['total_credito'] ? floatval($row['total_credito']) : 0.0;

echo json_encode([
    'success' => true,
    'id_contrato' => $id_contrato,
    'saldo_favor' => $total_credito
]);
?>
