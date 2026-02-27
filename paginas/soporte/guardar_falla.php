<?php
/**
 * Guardar Falla - Backend
 * Procesa el registro rápido de fallas
 */

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
    monto_pagado
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
    0,
    0.00,
    0.00
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