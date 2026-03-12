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

// 1. Obtener id_grupo_pago del cobro solicitado
$sql_group = "SELECT id_grupo_pago FROM cuentas_por_cobrar WHERE id_cobro = ?";
$stmt_group = $conn->prepare($sql_group);
$stmt_group->bind_param("i", $id_cobro);
$stmt_group->execute();
$res_group = $stmt_group->get_result();
$row_group = $res_group->fetch_assoc();
$id_grupo_pago = $row_group['id_grupo_pago'] ?? null;

// 2. Traer todos los cargos del mismo grupo (o solo el solicitado si no tiene grupo)
$where_clause = ($id_grupo_pago) ? "cxc.id_grupo_pago = ?" : "h.id_cobro_cxc = ?";
$param = ($id_grupo_pago) ? $id_grupo_pago : $id_cobro;
$type = ($id_grupo_pago) ? "s" : "i";

$sql = "
    SELECT 
        h.autorizado_por, 
        h.justificacion, 
        h.fecha_creacion, 
        h.monto_cargado,
        cxc.id_cobro,
        cxc.fecha_emision,
        cxc.referencia_pago,
        cxc.capture_pago,
        cxc.id_contrato as id_contrato_unico,
        b.nombre_banco,
        co.nombre_completo AS nombre_cliente,
        co.id AS id_contrato
    FROM cobros_manuales_historial h
    JOIN cuentas_por_cobrar cxc ON h.id_cobro_cxc = cxc.id_cobro
    JOIN contratos co ON cxc.id_contrato = co.id
    LEFT JOIN bancos b ON cxc.id_banco = b.id_banco
    WHERE $where_clause
    ORDER BY h.fecha_creacion ASC
";

$stmt = $conn->prepare($sql);
$stmt->bind_param($type, $param);
$stmt->execute();
$result = $stmt->get_result();

if ($result && $result->num_rows > 0) {
    $rows = [];
    while ($r = $result->fetch_assoc()) {
        $rows[] = $r;
    }
    // Devolvemos el primero como base y la lista completa
    echo json_encode(['success' => true, 'data' => $rows[0], 'all_concepts' => $rows]);
} else {
    echo json_encode(['success' => false, 'message' => 'No se encontró el detalle de la justificación.']);
}

$conn->close();
?>
