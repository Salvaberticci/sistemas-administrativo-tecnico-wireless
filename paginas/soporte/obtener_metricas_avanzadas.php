<?php
ob_start();
/**
 * Obtener Métricas Avanzadas
 * Endpoint AJAX para métricas de gestión avanzada de fallas
 */

error_reporting(0);
ini_set('display_errors', 0);
require_once '../conexion.php';

// Parámetros de filtro
$fecha_desde = isset($_GET['fecha_desde']) ? $_GET['fecha_desde'] : date('Y-m-d', strtotime('-1 month'));
$fecha_hasta = isset($_GET['fecha_hasta']) ? $_GET['fecha_hasta'] : date('Y-m-d');

$fecha_desde = $conn->real_escape_string($fecha_desde);
$fecha_hasta = $conn->real_escape_string($fecha_hasta);

$response = [
    'success' => true,
    'tiempo_respuesta' => [],
    'fallas_criticas' => [],
    'clientes_recurrentes' => [],
    'zonas_afectadas' => [],
    'caidas_recientes' => []
];

// 1. TIEMPO DE RESPUESTA
try {
    // Promedio general
    $sql_tiempo = "SELECT 
                    AVG(TIMESTAMPDIFF(HOUR, fecha_reporte, fecha_atencion)) as promedio_respuesta_horas,
                    AVG(TIMESTAMPDIFF(HOUR, fecha_atencion, fecha_resolucion)) as promedio_resolucion_horas,
                    AVG(TIMESTAMPDIFF(HOUR, fecha_reporte, fecha_resolucion)) as promedio_total_horas
                   FROM soportes
                   WHERE fecha_atencion IS NOT NULL
                   AND fecha_resolucion IS NOT NULL
                   AND fecha_reporte BETWEEN '$fecha_desde' AND '$fecha_hasta'";

    $result = $conn->query($sql_tiempo);
    if ($result && $row = $result->fetch_assoc()) {
        $response['tiempo_respuesta']['promedio_respuesta'] = round($row['promedio_respuesta_horas'] ?? 0, 1);
        $response['tiempo_respuesta']['promedio_resolucion'] = round($row['promedio_resolucion_horas'] ?? 0, 1);
        $response['tiempo_respuesta']['promedio_total'] = round($row['promedio_total_horas'] ?? 0, 1);
    }

    // Tiempo por mes (últimos 6 meses)
    $sql_tiempo_mes = "SELECT 
                        DATE_FORMAT(fecha_reporte, '%Y-%m') as mes,
                        AVG(TIMESTAMPDIFF(HOUR, fecha_reporte, fecha_atencion)) as promedio_horas
                       FROM soportes
                       WHERE fecha_atencion IS NOT NULL
                       AND fecha_reporte >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
                       GROUP BY mes
                       ORDER BY mes ASC";

    $result = $conn->query($sql_tiempo_mes);
    $por_mes = [];
    while ($row = $result->fetch_assoc()) {
        $por_mes[$row['mes']] = round($row['promedio_horas'], 1);
    }
    $response['tiempo_respuesta']['por_mes'] = $por_mes;

} catch (Exception $e) {
    $response['tiempo_respuesta']['error'] = $e->getMessage();
}

// 2. FALLAS CRÍTICAS
try {
    // Activas (no resueltas)
    $sql_criticas_activas = "SELECT COUNT(*) as total
                             FROM soportes
                             WHERE es_caida_critica = 1
                             AND (fecha_resolucion IS NULL OR solucion_completada = 0)";

    $result = $conn->query($sql_criticas_activas);
    $row = $result->fetch_assoc();
    $response['fallas_criticas']['activas'] = intval($row['total']);

    // Resueltas hoy
    $sql_criticas_hoy = "SELECT COUNT(*) as total
                         FROM soportes
                         WHERE es_caida_critica = 1
                         AND DATE(fecha_resolucion) = CURDATE()";

    $result = $conn->query($sql_criticas_hoy);
    $row = $result->fetch_assoc();
    $response['fallas_criticas']['resueltas_hoy'] = intval($row['total']);

    // Total del mes
    $sql_criticas_mes = "SELECT COUNT(*) as total
                         FROM soportes
                         WHERE es_caida_critica = 1
                         AND fecha_reporte >= DATE_FORMAT(NOW(), '%Y-%m-01')";

    $result = $conn->query($sql_criticas_mes);
    $row = $result->fetch_assoc();
    $response['fallas_criticas']['total_mes'] = intval($row['total']);

} catch (Exception $e) {
    $response['fallas_criticas']['error'] = $e->getMessage();
}

// 3. CLIENTES RECURRENTES (más de 3 fallas en 30 días)
try {
    $sql_recurrentes = "SELECT 
                            c.id,
                            c.nombre_completo,
                            c.cedula,
                            c.ip,
                            c.telefono,
                            COUNT(s.id_soporte) as total_fallas,
                            MAX(s.fecha_reporte) as ultima_falla,
                            GROUP_CONCAT(DISTINCT s.tipo_falla SEPARATOR ', ') as tipos_falla
                        FROM contratos c
                        INNER JOIN soportes s ON c.id = s.id_contrato
                        WHERE s.fecha_reporte >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                        GROUP BY c.id, c.nombre_completo, c.cedula, c.ip, c.telefono
                        HAVING total_fallas > 3
                        ORDER BY total_fallas DESC
                        LIMIT 20";

    $result = $conn->query($sql_recurrentes);
    $recurrentes = [];
    while ($row = $result->fetch_assoc()) {
        $recurrentes[] = [
            'id_cliente' => $row['id'],
            'nombre' => $row['nombre_completo'],
            'cedula' => $row['cedula'],
            'ip' => $row['ip'],
            'telefono' => $row['telefono'],
            'total_fallas' => intval($row['total_fallas']),
            'ultima_falla' => $row['ultima_falla'],
            'tipos_falla' => $row['tipos_falla']
        ];
    }
    $response['clientes_recurrentes'] = $recurrentes;

} catch (Exception $e) {
    $response['clientes_recurrentes'] = [];
    $response['error_recurrentes'] = $e->getMessage();
}

// 4. ZONAS AFECTADAS
try {
    $sql_zonas = "SELECT 
                    zona_afectada,
                    COUNT(*) as total_fallas,
                    SUM(CASE WHEN es_caida_critica = 1 THEN 1 ELSE 0 END) as fallas_criticas
                  FROM soportes
                  WHERE zona_afectada IS NOT NULL
                  AND zona_afectada != ''
                  AND fecha_reporte BETWEEN '$fecha_desde' AND '$fecha_hasta'
                  GROUP BY zona_afectada
                  ORDER BY total_fallas DESC
                  LIMIT 10";

    $result = $conn->query($sql_zonas);
    $zonas = [];
    while ($row = $result->fetch_assoc()) {
        $zonas[$row['zona_afectada']] = [
            'total' => intval($row['total_fallas']),
            'criticas' => intval($row['fallas_criticas'])
        ];
    }
    $response['zonas_afectadas'] = $zonas;

} catch (Exception $e) {
    $response['zonas_afectadas'] = [];
    $response['error_zonas'] = $e->getMessage();
}

// 5. CAÍDAS CRÍTICAS RECIENTES (últimas 24 horas)
try {
    $sql_caidas = "SELECT 
                    s.id_soporte,
                    s.tipo_falla,
                    s.zona_afectada,
                    s.sector,
                    s.clientes_afectados,
                    s.fecha_reporte,
                    s.fecha_resolucion,
                    s.prioridad,
                    c.nombre_completo,
                    TIMESTAMPDIFF(HOUR, s.fecha_reporte, COALESCE(s.fecha_resolucion, NOW())) as horas_caida
                   FROM soportes s
                   INNER JOIN contratos c ON s.id_contrato = c.id
                   WHERE s.es_caida_critica = 1
                   AND s.fecha_reporte >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
                   ORDER BY s.fecha_reporte DESC
                   LIMIT 10";

    $result = $conn->query($sql_caidas);
    $caidas = [];
    while ($row = $result->fetch_assoc()) {
        $caidas[] = [
            'id' => $row['id_soporte'],
            'tipo' => $row['tipo_falla'],
            'zona' => $row['zona_afectada'] ?: $row['sector'],
            'clientes_afectados' => intval($row['clientes_afectados']),
            'fecha_reporte' => $row['fecha_reporte'],
            'fecha_resolucion' => $row['fecha_resolucion'],
            'horas_caida' => round($row['horas_caida'], 1),
            'estado' => $row['fecha_resolucion'] ? 'Resuelta' : 'Activa',
            'prioridad' => $row['prioridad']
        ];
    }
    $response['caidas_recientes'] = $caidas;

} catch (Exception $e) {
    $response['caidas_recientes'] = [];
    $response['error_caidas'] = $e->getMessage();
}

// 6. DISTRIBUCIÓN POR PRIORIDAD
try {
    $sql_prioridad = "SELECT 
                        prioridad,
                        COUNT(*) as total
                      FROM soportes
                      WHERE fecha_reporte BETWEEN '$fecha_desde' AND '$fecha_hasta'
                      GROUP BY prioridad
                      ORDER BY FIELD(prioridad, 'NIVEL 3', 'NIVEL 2', 'NIVEL 1')";

    $result = $conn->query($sql_prioridad);
    $por_prioridad = [];
    while ($row = $result->fetch_assoc()) {
        $por_prioridad[$row['prioridad']] = intval($row['total']);
    }
    $response['por_prioridad'] = $por_prioridad;

} catch (Exception $e) {
    if (isset($response)) {
        $response['por_prioridad'] = [];
        $response['error_general'] = $e->getMessage();
    }
}

if (isset($conn) && $conn instanceof mysqli)
    $conn->close();

if (ob_get_length())
    ob_end_clean();
header('Content-Type: application/json');
echo json_encode($response, JSON_PRETTY_PRINT);
?>