<?php
require_once '../conexion.php';

// SQL mejorada con JOIN para detectar pagos del mes actual
$sql = "SELECT 
            p.*, 
            co.nombre_completo as nombre_cliente,
            (SELECT COUNT(*) 
             FROM cuentas_por_cobrar cxc 
             WHERE (cxc.id_contrato = p.id_contrato_asociado OR cxc.id_contrato IN (SELECT id FROM contratos WHERE cedula = p.cedula_titular))
               AND cxc.estado = 'PAGADO' 
               AND MONTH(cxc.fecha_emision) = MONTH(CURRENT_DATE) 
               AND YEAR(cxc.fecha_emision) = YEAR(CURRENT_DATE)
            ) as pagos_mes_actual
        FROM prorrogas p
        LEFT JOIN contratos co ON p.id_contrato_asociado = co.id
        WHERE p.tipo_solicitud = 'PRORROGA' 
        ORDER BY p.fecha_registro DESC";

$resSource = $conn->query($sql);

$data = [];
if ($resSource) {
    while ($row = $resSource->fetch_assoc()) {
        $data[] = $row;
    }
}

header('Content-Type: application/json');
echo json_encode(["data" => $data]);
?>