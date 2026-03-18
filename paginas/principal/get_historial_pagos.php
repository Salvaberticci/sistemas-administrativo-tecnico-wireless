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
        MAX(cxc.id_cobro) as id_cobro, 
        MAX(cxc.fecha_emision) as fecha_emision, 
        MAX(cxc.fecha_vencimiento) as fecha_vencimiento, 
        SUM(cxc.monto_total) as monto_total, 
        MAX(cxc.fecha_pago) as fecha_pago,
        MAX(cxc.referencia_pago) as referencia_pago,
        GROUP_CONCAT(COALESCE(h.justificacion, pl.nombre_plan) SEPARATOR ' || ') as justificacion
    FROM cuentas_por_cobrar cxc
    LEFT JOIN contratos co ON cxc.id_contrato = co.id
    LEFT JOIN planes pl ON co.id_plan = pl.id_plan
    LEFT JOIN cobros_manuales_historial h ON cxc.id_cobro = h.id_cobro_cxc
    WHERE cxc.id_contrato = ?
    AND cxc.estado = 'PAGADO'
    GROUP BY 
        IF(NULLIF(cxc.id_grupo_pago, '') IS NOT NULL, 
           cxc.id_grupo_pago, 
           IF(NULLIF(cxc.referencia_pago, '') IS NOT NULL, 
              CONCAT(cxc.referencia_pago, '_', cxc.fecha_pago), 
              cxc.id_cobro
           )
        )
    ORDER BY MAX(cxc.fecha_pago) DESC
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id_contrato);
$stmt->execute();
$result = $stmt->get_result();
$pagos = $result->fetch_all(MYSQLI_ASSOC);

echo json_encode(['success' => true, 'data' => $pagos]);

$conn->close();
?>
