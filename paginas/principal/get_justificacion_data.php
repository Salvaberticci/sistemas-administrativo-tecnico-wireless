<?php
/**
 * get_justificacion_data.php - Fetches detailed justification for a manual charge.
 */
header('Content-Type: application/json');
require '../conexion.php';

$id_cobro = isset($_GET['id_cobro']) ? intval($_GET['id_cobro']) : 0;

if ($id_cobro <= 0) {
    echo json_encode(['success' => false, 'message' => 'ID de cobro no válido.']);
    exit;
}

$sql = "
    SELECT 
        h.autorizado_por, 
        h.justificacion, 
        h.fecha_creacion, 
        h.monto_cargado,
        cxc.fecha_emision,
        cxc.referencia_pago,
        b.nombre_banco,
        co.nombre_completo AS nombre_cliente,
        co.id AS id_contrato
    FROM cobros_manuales_historial h
    JOIN cuentas_por_cobrar cxc ON h.id_cobro_cxc = cxc.id_cobro
    JOIN contratos co ON cxc.id_contrato = co.id
    LEFT JOIN bancos b ON cxc.id_banco = b.id_banco
    WHERE h.id_cobro_cxc = ?
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id_cobro);
$stmt->execute();
$result = $stmt->get_result();

if ($result && $result->num_rows > 0) {
    $data = $result->fetch_assoc();
    echo json_encode(['success' => true, 'data' => $data]);
} else {
    echo json_encode(['success' => false, 'message' => 'No se encontró el detalle de la justificación.']);
}

$conn->close();
?>
