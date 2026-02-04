<?php
/**
 * Obtener Estadísticas de Reportes Técnicos
 * Endpoint AJAX que retorna JSON con estadísticas para el dashboard
 */
require_once '../conexion.php';

header('Content-Type: application/json');

try {
    // Parámetros de filtro (opcional)
    $fecha_desde = isset($_GET['fecha_desde']) ? $_GET['fecha_desde'] : date('Y-m-d', strtotime('-6 months'));
    $fecha_hasta = isset($_GET['fecha_hasta']) ? $_GET['fecha_hasta'] : date('Y-m-d');

    // 1. Estadísticas generales
    $sql_general = "SELECT 
                        COUNT(*) as total_reportes,
                        SUM(monto_total) as total_facturado,
                        SUM(monto_pagado) as total_cobrado,
                        SUM(CASE WHEN (monto_total - monto_pagado) > 0.01 THEN 1 ELSE 0 END) as reportes_pendientes,
                        SUM(CASE WHEN (monto_total - monto_pagado) <= 0.01 THEN 1 ELSE 0 END) as reportes_pagados
                    FROM soportes
                    WHERE fecha_soporte BETWEEN '$fecha_desde' AND '$fecha_hasta'";

    $result = $conn->query($sql_general);
    $stats_general = $result->fetch_assoc();

    // 2. Fallas por tipo
    $sql_tipo = "SELECT tipo_falla, COUNT(*) as cantidad
                 FROM soportes
                 WHERE tipo_falla IS NOT NULL 
                 AND tipo_falla != ''
                 AND fecha_soporte BETWEEN '$fecha_desde' AND '$fecha_hasta'
                 GROUP BY tipo_falla
                 ORDER BY cantidad DESC";

    $result_tipo = $conn->query($sql_tipo);
    $fallas_por_tipo = [];
    while ($row = $result_tipo->fetch_assoc()) {
        $fallas_por_tipo[$row['tipo_falla']] = (int) $row['cantidad'];
    }

    // 3. Reportes por mes (últimos 6 meses)
    $sql_mes = "SELECT 
                    DATE_FORMAT(fecha_soporte, '%Y-%m') as mes,
                    DATE_FORMAT(fecha_soporte, '%b %Y') as mes_nombre,
                    COUNT(*) as cantidad
                FROM soportes
                WHERE fecha_soporte >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
                GROUP BY mes, mes_nombre
                ORDER BY mes ASC";

    $result_mes = $conn->query($sql_mes);
    $reportes_por_mes = [];
    $meses_labels = [];
    while ($row = $result_mes->fetch_assoc()) {
        $reportes_por_mes[$row['mes']] = (int) $row['cantidad'];
        $meses_labels[] = $row['mes_nombre'];
    }

    // 4. Reportes por técnico
    $sql_tecnico = "SELECT 
                        tecnico_asignado,
                        COUNT(*) as cantidad,
                        SUM(monto_total) as total_facturado
                    FROM soportes
                    WHERE tecnico_asignado IS NOT NULL 
                    AND tecnico_asignado != ''
                    AND fecha_soporte BETWEEN '$fecha_desde' AND '$fecha_hasta'
                    GROUP BY tecnico_asignado
                    ORDER BY cantidad DESC
                    LIMIT 10";

    $result_tecnico = $conn->query($sql_tecnico);
    $reportes_por_tecnico = [];
    while ($row = $result_tecnico->fetch_assoc()) {
        $reportes_por_tecnico[$row['tecnico_asignado']] = [
            'cantidad' => (int) $row['cantidad'],
            'total_facturado' => (float) $row['total_facturado']
        ];
    }

    // 5. Ingresos por mes (últimos 6 meses)
    $sql_ingresos = "SELECT 
                        DATE_FORMAT(fecha_soporte, '%Y-%m') as mes,
                        DATE_FORMAT(fecha_soporte, '%b %Y') as mes_nombre,
                        SUM(monto_total) as total,
                        SUM(monto_pagado) as pagado
                    FROM soportes
                    WHERE fecha_soporte >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
                    GROUP BY mes, mes_nombre
                    ORDER BY mes ASC";

    $result_ingresos = $conn->query($sql_ingresos);
    $ingresos_por_mes = [];
    while ($row = $result_ingresos->fetch_assoc()) {
        $ingresos_por_mes[$row['mes']] = [
            'total' => (float) $row['total'],
            'pagado' => (float) $row['pagado']
        ];
    }

    // Construir respuesta JSON
    $response = [
        'success' => true,
        'general' => [
            'total_reportes' => (int) $stats_general['total_reportes'],
            'reportes_pendientes' => (int) $stats_general['reportes_pendientes'],
            'reportes_pagados' => (int) $stats_general['reportes_pagados'],
            'total_facturado' => (float) $stats_general['total_facturado'],
            'total_cobrado' => (float) $stats_general['total_cobrado'],
            'saldo_pendiente' => (float) ($stats_general['total_facturado'] - $stats_general['total_cobrado'])
        ],
        'fallas_por_tipo' => $fallas_por_tipo,
        'reportes_por_mes' => $reportes_por_mes,
        'meses_labels' => $meses_labels,
        'reportes_por_tecnico' => $reportes_por_tecnico,
        'ingresos_por_mes' => $ingresos_por_mes,
        'periodo' => [
            'desde' => $fecha_desde,
            'hasta' => $fecha_hasta
        ]
    ];

    echo json_encode($response, JSON_NUMERIC_CHECK);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

$conn->close();
?>