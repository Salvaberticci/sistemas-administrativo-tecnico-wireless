<?php
header('Content-Type: application/json; charset=utf-8');
require '../conexion.php';

$id_cobro = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id_cobro <= 0) {
    echo json_encode(['success' => false, 'message' => 'Cobro no especificado.']);
    exit;
}

$sql = "
    SELECT 
        cxc.*,
        co.nombre_completo AS nombre_cliente
    FROM cuentas_por_cobrar cxc
    JOIN contratos co ON cxc.id_contrato = co.id
    WHERE cxc.id_cobro = ?
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id_cobro);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 1) {
    $data = $result->fetch_assoc();
    echo json_encode(['success' => true, 'data' => $data]);
} else {
    echo json_encode(['success' => false, 'message' => 'Cobro no encontrado.']);
}

$stmt->close();
$conn->close();
