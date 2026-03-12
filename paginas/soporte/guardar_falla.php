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
}// Recibir datos
$id_contrato = isset($_POST['id_contrato']) ? intval($_POST['id_contrato']) : 0;
$prioridad = 'NIVEL 3'; // Forzar Nivel 3 para este formulario refactorizado
$id_olt = isset($_POST['id_olt']) ? intval($_POST['id_olt']) : NULL;
$id_pon = isset($_POST['id_pon']) ? intval($_POST['id_pon']) : NULL;
$tipo_falla = isset($_POST['tipo_falla']) ? trim($_POST['tipo_falla']) : '';
$tipo_servicio = isset($_POST['tipo_servicio']) ? trim($_POST['tipo_servicio']) : 'Fibra Óptica';
$es_caida_critica = 1; // Siempre crítica en nivel 3
$clientes_afectados = isset($_POST['clientes_afectados']) ? intval($_POST['clientes_afectados']) : 50;
$sector = isset($_POST['sector']) ? trim($_POST['sector']) : '';
$zona_afectada = isset($_POST['zona_afectada']) ? trim($_POST['zona_afectada']) : '';
$observaciones = isset($_POST['observaciones']) ? trim($_POST['observaciones']) : '';
$tecnico_asignado = isset($_POST['tecnico_asignado']) ? trim($_POST['tecnico_asignado']) : '';
$notas_internas = isset($_POST['notas_internas']) ? trim($_POST['notas_internas']) : '';
$fecha_reporte = isset($_POST['fecha_reporte']) ? $_POST['fecha_reporte'] : date('Y-m-d H:i:s');

// Campos por defecto (estos ya no vienen del formulario de nivel 3)
$ip = '';
$estado_onu = '';
$estado_router = '';
$modelo_router = '';
$num_dispositivos = 0;
$bw_bajada = '';
$bw_subida = '';
$bw_ping = '';
$estado_antena = '';
$valores_antena = '';
$sugerencias = '';
$solucion_completada = 0;
$monto_total = 0.00;
$monto_pagado = 0.00;


// Validaciones
$errores = [];

if ($id_contrato <= 0) {
    $errores[] = 'Debe seleccionar un cliente de referencia';
}

if (!$id_olt) {
    $errores[] = 'Debe seleccionar la OLT afectada';
}

if (empty($tipo_falla)) {
    $errores[] = 'Debe seleccionar un tipo de falla';
}

if (empty($tecnico_asignado)) {
    $errores[] = 'Debe asignar un técnico responsable';
}

if (strlen($observaciones) < 10) {
    $errores[] = 'La descripción debe tener al menos 10 caracteres';
}

if (!empty($errores)) {
    echo json_encode(['success' => false, 'message' => implode(', ', $errores)]);
    exit;
}

// Obtener datos del cliente
$sql_cliente = "SELECT nombre_completo, cedula, ip_onu as ip, direccion FROM contratos WHERE id = $id_contrato";
$result_cliente = $conn->query($sql_cliente);

if ($result_cliente->num_rows == 0) {
    echo json_encode(['success' => false, 'message' => 'Cliente de referencia no encontrado']);
    exit;
}

$cliente = $result_cliente->fetch_assoc();

// Generar descripción corta automática
$descripcion_corta = "[MASIVA] " . $tipo_falla . ' - ' . $cliente['nombre_completo'];

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
    id_olt,
    id_pon,
    solucion_completada,
    monto_total,
    monto_pagado,
    estado_firma
) VALUES (
    $id_contrato,
    '$fecha_soporte',
    '$fecha_reporte_sql',
    '$descripcion_corta',
    '$tipo_falla',
    'NIVEL 3',
    1,
    $clientes_afectados,
    '$tipo_servicio',
    '$sector',
    '$zona_afectada',
    '$observaciones',
    '$notas_internas',
    '$tecnico_asignado',
    " . ($id_olt ? $id_olt : "NULL") . ",
    " . ($id_pon ? $id_pon : "NULL") . ",
    0,
    0,
    0,
    'PENDIENTE'
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