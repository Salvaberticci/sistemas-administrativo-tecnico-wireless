<?php
require '../conexion.php';
$id_cobro = 19;
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
$data = $stmt->get_result()->fetch_assoc();
print_r($data);
?>
