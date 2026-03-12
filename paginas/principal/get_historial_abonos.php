<?php
header('Content-Type: application/json; charset=utf-8');
require_once '../conexion.php';

if ($_SERVER["REQUEST_METHOD"] !== "GET") {
    echo json_encode(['success' => false, 'message' => 'Método no permitido.']);
    exit;
}

$id_deudor = isset($_GET['id_deudor']) ? intval($_GET['id_deudor']) : 0;

if ($id_deudor <= 0) {
    echo json_encode(['success' => false, 'message' => 'ID de deudor inválido.']);
    exit;
}

// 1. Obtener el id_contrato asociado a este deudor
$sql_contrato = "SELECT id_contrato FROM clientes_deudores WHERE id = $id_deudor";
$res_contrato = $conn->query($sql_contrato);

if ($res_contrato->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'El registro de deuda no existe.']);
    exit;
}

$deudor = $res_contrato->fetch_assoc();
$id_contrato = $deudor['id_contrato'];

// 2. Buscar historiales que correspondan a "Abonos" para este contrato
// Aquí filtramos por la palabra clave 'Abono #' que hemos inyectado genéricamente en la justificación.
$sql_historial = "
    SELECT 
        h.id_cobro_cxc,
        h.fecha_creacion,
        h.justificacion,
        h.monto_cargado,
        c.fecha_pago,
        c.referencia_pago,
        c.capture_pago,
        b.nombre_banco
    FROM cobros_manuales_historial h
    INNER JOIN cuentas_por_cobrar c ON h.id_cobro_cxc = c.id_cobro
    LEFT JOIN bancos b ON c.id_banco = b.id_banco
    WHERE h.id_contrato = $id_contrato 
      AND h.justificacion LIKE 'Abono #%'
    ORDER BY h.fecha_creacion DESC
";

$result = $conn->query($sql_historial);

$historial = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $historial[] = $row;
    }
}

echo json_encode(['success' => true, 'data' => $historial]);
?>
