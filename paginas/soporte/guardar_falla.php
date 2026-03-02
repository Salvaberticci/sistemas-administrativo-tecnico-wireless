<?php
/**
 * Guardar Falla - Backend
 * Procesa el registro rápido de fallas
 */

// Función auxiliar para guardar firmas
function saveSignature($base64_string, $prefix)
{
    if (empty($base64_string))
        return null;
    $data = explode(',', $base64_string);
    if (count($data) < 2)
        return null;
    $imgData = base64_decode($data[1]);
    $fileName = $prefix . '_' . uniqid() . '.png';
    $filePath = '../../uploads/firmas/' . $fileName;
    if (!file_exists('../../uploads/firmas')) {
        mkdir('../../uploads/firmas', 0777, true);
    }
    if (file_put_contents($filePath, $imgData))
        return $fileName;
    return null;
}

header('Content-Type: application/json');
require_once '../conexion.php';

// Validar que sea POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

// Recibir datos
$id_contrato = isset($_POST['id_contrato']) ? intval($_POST['id_contrato']) : 0;
$prioridad = isset($_POST['prioridad']) ? $_POST['prioridad'] : 'MEDIA';
$tipo_falla = isset($_POST['tipo_falla']) ? trim($_POST['tipo_falla']) : '';
$tipo_servicio = isset($_POST['tipo_servicio']) ? trim($_POST['tipo_servicio']) : 'Fibra Óptica';
$es_caida_critica = isset($_POST['es_caida_critica']) ? intval($_POST['es_caida_critica']) : 0;
$clientes_afectados = isset($_POST['clientes_afectados']) ? intval($_POST['clientes_afectados']) : 1;
$sector = isset($_POST['sector']) ? trim($_POST['sector']) : '';
$zona_afectada = isset($_POST['zona_afectada']) ? trim($_POST['zona_afectada']) : '';
$observaciones = isset($_POST['observaciones']) ? trim($_POST['observaciones']) : '';
$equipos_afectados = isset($_POST['equipos_afectados']) ? trim($_POST['equipos_afectados']) : '';
$tecnico_asignado = isset($_POST['tecnico_asignado']) ? trim($_POST['tecnico_asignado']) : '';
$notas_internas = isset($_POST['notas_internas']) ? trim($_POST['notas_internas']) : '';
$fecha_reporte = isset($_POST['fecha_reporte']) ? $_POST['fecha_reporte'] : date('Y-m-d H:i:s');

// Nuevos campos unificados
$ip = isset($_POST['ip']) ? trim($_POST['ip']) : '';
$estado_onu = isset($_POST['estado_onu']) ? trim($_POST['estado_onu']) : '';
$estado_router = isset($_POST['estado_router']) ? trim($_POST['estado_router']) : '';
$modelo_router = isset($_POST['modelo_router']) ? trim($_POST['modelo_router']) : '';
$num_dispositivos = isset($_POST['num_dispositivos']) ? intval($_POST['num_dispositivos']) : 0;
$bw_bajada = isset($_POST['bw_bajada']) ? trim($_POST['bw_bajada']) : '';
$bw_subida = isset($_POST['bw_subida']) ? trim($_POST['bw_subida']) : '';
$bw_ping = isset($_POST['bw_ping']) ? trim($_POST['bw_ping']) : '';
$estado_antena = isset($_POST['estado_antena']) ? trim($_POST['estado_antena']) : '';
$valores_antena = isset($_POST['valores_antena']) ? trim($_POST['valores_antena']) : '';
$sugerencias = isset($_POST['sugerencias']) ? trim($_POST['sugerencias']) : '';
$solucion_completada = isset($_POST['solucion_completada']) ? intval($_POST['solucion_completada']) : 0;
$monto_total = isset($_POST['monto_total']) ? floatval($_POST['monto_total']) : 0.00;
$monto_pagado = isset($_POST['monto_pagado']) ? floatval($_POST['monto_pagado']) : 0.00;

// Firmas
$firma_tecnico_data = isset($_POST['firma_tecnico_data']) ? $_POST['firma_tecnico_data'] : '';
$firma_cliente_data = isset($_POST['firma_cliente_data']) ? $_POST['firma_cliente_data'] : '';

// Validaciones
$errores = [];

if ($id_contrato <= 0) {
    $errores[] = 'Debe seleccionar un cliente';
}

if (empty($tipo_falla)) {
    $errores[] = 'Debe seleccionar un tipo de falla';
}

if (empty($tecnico_asignado)) {
    $errores[] = 'Debe asignar un técnico';
}

if (strlen($observaciones) < 10) {
    $errores[] = 'La descripción debe tener al menos 10 caracteres';
}

if ($es_caida_critica && $clientes_afectados < 2) {
    $errores[] = 'Una caída crítica debe afectar al menos 2 clientes';
}

// Validar prioridad
$prioridades_validas = ['NIVEL 1', 'NIVEL 2', 'NIVEL 3'];
if (!in_array($prioridad, $prioridades_validas)) {
    $prioridad = 'NIVEL 1';
}

if (!empty($errores)) {
    echo json_encode(['success' => false, 'message' => implode(', ', $errores)]);
    exit;
}

// Obtener datos del cliente
$sql_cliente = "SELECT nombre_completo, cedula, ip_onu as ip, direccion FROM contratos WHERE id = $id_contrato";
$result_cliente = $conn->query($sql_cliente);

if ($result_cliente->num_rows == 0) {
    echo json_encode(['success' => false, 'message' => 'Cliente no encontrado']);
    exit;
}

$cliente = $result_cliente->fetch_assoc();

// Generar descripción corta automática
$descripcion_corta = $tipo_falla . ' - ' . $cliente['nombre_completo'];
if ($es_caida_critica) {
    $descripcion_corta = '[CRÍTICA] ' . $descripcion_corta;
}

// Agregar equipos afectados a observaciones si existen
if (!empty($equipos_afectados)) {
    $observaciones .= "\n\nEquipos potencialmente afectados: " . $equipos_afectados;
}

// Preparar fecha de soporte (para compatibilidad)
$fecha_soporte = date('Y-m-d', strtotime($fecha_reporte));

// Escapar datos
$descripcion_corta = $conn->real_escape_string($descripcion_corta);
$tipo_falla = $conn->real_escape_string($tipo_falla);
$tipo_servicio = $conn->real_escape_string($tipo_servicio);
$sector = $conn->real_escape_string($sector);
$zona_afectada = $conn->real_escape_string($zona_afectada);
$observaciones = $conn->real_escape_string($observaciones);
$tecnico_asignado = $conn->real_escape_string($tecnico_asignado);
$notas_internas = $conn->real_escape_string($notas_internas);
$fecha_reporte_sql = $conn->real_escape_string($fecha_reporte);

// Escapar nuevos campos
$ip = $conn->real_escape_string($ip);
$estado_onu = $conn->real_escape_string($estado_onu);
$estado_router = $conn->real_escape_string($estado_router);
$modelo_router = $conn->real_escape_string($modelo_router);
$bw_bajada = $conn->real_escape_string($bw_bajada);
$bw_subida = $conn->real_escape_string($bw_subida);
$bw_ping = $conn->real_escape_string($bw_ping);
$estado_antena = $conn->real_escape_string($estado_antena);
$valores_antena = $conn->real_escape_string($valores_antena);
$sugerencias = $conn->real_escape_string($sugerencias);

// Procesar firmas
$path_tech = saveSignature($firma_tecnico_data, 'tech_falla');
$path_cli = saveSignature($firma_cliente_data, 'cli_falla');

// Insertar en base de datos
$sql = "INSERT INTO soportes (
    id_contrato,
    fecha_soporte,
    fecha_reporte,
    descripcion,
    tipo_falla,
    prioridad,
    es_caida_critica,
    clientes_afectados,
    tipo_servicio,
    sector,
    zona_afectada,
    observaciones,
    notas_internas,
    tecnico_asignado,
    solucion_completada,
    monto_total,
    monto_pagado,
    ip_address,
    estado_onu,
    estado_router,
    modelo_router,
    num_dispositivos,
    bw_bajada,
    bw_subida,
    bw_ping,
    estado_antena,
    valores_antena,
    sugerencias,
    firma_tecnico,
    firma_cliente,
    estado_firma
) VALUES (
    $id_contrato,
    '$fecha_soporte',
    '$fecha_reporte_sql',
    '$descripcion_corta',
    '$tipo_falla',
    '$prioridad',
    $es_caida_critica,
    $clientes_afectados,
    '$tipo_servicio',
    '$sector',
    '$zona_afectada',
    '$observaciones',
    '$notas_internas',
    '$tecnico_asignado',
    $solucion_completada,
    $monto_total,
    $monto_pagado,
    '$ip',
    '$estado_onu',
    '$estado_router',
    '$modelo_router',
    $num_dispositivos,
    '$bw_bajada',
    '$bw_subida',
    '$bw_ping',
    '$estado_antena',
    '$valores_antena',
    '$sugerencias',
    '$path_tech',
    '$path_cli',
    'COMPLETADO'
)";

if ($conn->query($sql)) {
    $id_soporte = $conn->insert_id;

    // Log de actividad (opcional)
    $log_msg = "Falla registrada: Ticket #{$id_soporte} - {$tipo_falla} - Cliente: {$cliente['nombre_completo']}";
    if ($es_caida_critica) {
        $log_msg .= " [CRÍTICA - {$clientes_afectados} clientes afectados]";
    }
    error_log($log_msg);

    echo json_encode([
        'success' => true,
        'message' => 'Falla registrada exitosamente',
        'id_soporte' => $id_soporte,
        'ticket' => str_pad($id_soporte, 6, '0', STR_PAD_LEFT),
        'prioridad' => $prioridad,
        'es_critica' => $es_caida_critica
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Error al guardar: ' . $conn->error
    ]);
}

$conn->close();
?>