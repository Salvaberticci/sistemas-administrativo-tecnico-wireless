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
        co.nombre_completo AS nombre_cliente,
        h.justificacion,
        h.autorizado_por
    FROM cuentas_por_cobrar cxc
    JOIN contratos co ON cxc.id_contrato = co.id
    LEFT JOIN cobros_manuales_historial h ON cxc.id_cobro = h.id_cobro_cxc
    WHERE cxc.id_cobro = ?
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id_cobro);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 1) {
    $data = $result->fetch_assoc();
    
    // Si tiene un grupo, traemos todos los conceptos del grupo
    $all_concepts = [];
    if (!empty($data['id_grupo_pago'])) {
        $sql_group = "
            SELECT 
                cxc.*,
                co.nombre_completo AS nombre_cliente,
                h.justificacion,
                h.autorizado_por
            FROM cuentas_por_cobrar cxc
            JOIN contratos co ON cxc.id_contrato = co.id
            LEFT JOIN cobros_manuales_historial h ON cxc.id_cobro = h.id_cobro_cxc
            WHERE cxc.id_grupo_pago = ?
        ";
        $stmt_g = $conn->prepare($sql_group);
        $stmt_g->bind_param("s", $data['id_grupo_pago']);
        $stmt_g->execute();
        $res_g = $stmt_g->get_result();
        while ($row = $res_g->fetch_assoc()) {
            $all_concepts[] = $row;
        }
        $stmt_g->close();
    } else {
        // Si no tiene grupo, él mismo es el único concepto
        $all_concepts[] = $data;
    }

    echo json_encode(['success' => true, 'is_updated' => true, 'data' => $data, 'all_concepts' => $all_concepts]);
} else {
    echo json_encode(['success' => false, 'message' => 'Cobro no encontrado.']);
}

$stmt->close();
$conn->close();
