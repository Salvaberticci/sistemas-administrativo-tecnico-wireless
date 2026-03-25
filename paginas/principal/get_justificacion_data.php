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

// Cargar mapeo de bancos desde JSON
$json_bancos = @file_get_contents('bancos.json');
$bancosArr = json_decode($json_bancos, true) ?: [];
$bancosMap = [];
foreach ($bancosArr as $b) {
    if (isset($b['id_banco'])) $bancosMap[$b['id_banco']] = $b['nombre_banco'];
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
        cxc.id_banco,
        cxc.id_contrato as id_contrato_unico,
        co.nombre_completo AS nombre_cliente,
        co.id AS id_contrato
    FROM cuentas_por_cobrar cxc
    LEFT JOIN cobros_manuales_historial h ON h.id_cobro_cxc = cxc.id_cobro
    JOIN contratos co ON cxc.id_contrato = co.id
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
        // Fallback for non-manual charges
        if (!$r['autorizado_por']) $r['autorizado_por'] = 'SISTEMA';
        if (!$r['justificacion']) $r['justificacion'] = 'Cobro automático del sistema';
        if (!$r['fecha_creacion']) $r['fecha_creacion'] = $r['fecha_emision'];
        if (!$r['monto_cargado']) $r['monto_cargado'] = $r['monto_total'] ?? 0;
        $r['nombre_banco'] = $bancosMap[$r['id_banco']] ?? 'No especificado';
        $rows[] = $r;
    }
    // Devolvemos el primero como base y la lista completa
    echo json_encode(['success' => true, 'data' => $rows[0], 'all_concepts' => $rows]);
} else {
    // If it's not and manual but exists in cxc
    $sql_basic = "
        SELECT 
            'SISTEMA' as autorizado_por, 
            'Cobro automático del sistema' as justificacion, 
            cxc.fecha_emision as fecha_creacion, 
            cxc.monto_total as monto_cargado,
            cxc.id_cobro,
            cxc.fecha_emision,
            cxc.referencia_pago,
            cxc.capture_pago,
            cxc.id_banco,
            cxc.id_contrato as id_contrato_unico,
            co.nombre_completo AS nombre_cliente,
            co.id AS id_contrato
        FROM cuentas_por_cobrar cxc
        JOIN contratos co ON cxc.id_contrato = co.id
        WHERE cxc.id_cobro = ?
    ";
    $stmt_b = $conn->prepare($sql_basic);
    $stmt_b->bind_param("i", $id_cobro);
    $stmt_b->execute();
    $res_b = $stmt_b->get_result();
    if ($res_b && $res_b->num_rows > 0) {
        $row_b = $res_b->fetch_assoc();
        $row_b['nombre_banco'] = $bancosMap[$row_b['id_banco']] ?? 'No especificado';
        echo json_encode(['success' => true, 'data' => $row_b, 'all_concepts' => [$row_b]]);
    } else {
        echo json_encode(['success' => false, 'message' => 'No se encontró el detalle de la justificación.']);
    }
}

$conn->close();
?>
