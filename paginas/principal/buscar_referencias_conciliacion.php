<?php
// buscar_referencias_conciliacion.php
require_once '../conexion.php';

header('Content-Type: application/json');

// Recibir JSON con lista de referencias: ["12345", "67890", ...]
$data = json_decode(file_get_contents('php://input'), true);
$referencias = $data['referencias'] ?? [];

$resultados = [];

if (!empty($referencias)) {
    foreach ($referencias as $ref) {
        $ref_clean = $conn->real_escape_string($ref);

        // Buscar en mensualidades (pagos procesados)
        $sql = "SELECT m.monto_pagado, m.fecha_pago, c.nombre_completo, c.id as id_contrato, 'MENSUALIDAD' as tipo
                FROM mensualidades m
                JOIN contratos c ON m.id_contrato = c.id
                WHERE m.referencia_pago LIKE '%$ref_clean%' 
                   OR m.referencia_pago = '$ref_clean'";

        $res = $conn->query($sql);

        if ($res && $res->num_rows > 0) {
            while ($row = $res->fetch_assoc()) {
                $row['referencia'] = $ref;
                $row['encontrado'] = true;
                $resultados[] = $row;
            }
        } else {
            // Buscar en pagos_reportados (pendientes de aprobación)
            $sql2 = "SELECT monto as monto_pagado, fecha_reporte as fecha_pago, nombre_titular as nombre_completo, id_contrato, 'REPORTE_WEB' as tipo
                     FROM pagos_reportados
                     WHERE referencia LIKE '%$ref_clean%'
                        OR referencia = '$ref_clean'";

            $res2 = $conn->query($sql2);

            if ($res2 && $res2->num_rows > 0) {
                while ($row = $res2->fetch_assoc()) {
                    $row['referencia'] = $ref;
                    $row['encontrado'] = true;
                    $resultados[] = $row;
                }
            } else {
                // No encontrado
                $resultados[] = [
                    'referencia' => $ref,
                    'encontrado' => false,
                    'monto_pagado' => 0,
                    'nombre_completo' => 'N/A',
                    'tipo' => 'N/A'
                ];
            }
        }
    }
}

echo json_encode($resultados);
?>