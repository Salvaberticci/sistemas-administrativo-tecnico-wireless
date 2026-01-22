<?php
// paginas/soporte/guardar_reporte_publico.php
// Scripts para guardar el REPORTE TÉCNICO (Versión Pública)

require_once '../conexion.php';

header('Content-Type: application/json');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // Función auxiliar para guardar firmas
    function saveSignature($base64_string, $prefix) {
        if (empty($base64_string)) return null;
        
        $data = explode(',', $base64_string);
        // Validar formato data:image/png;base64,...
        if (count($data) < 2) return null;
        
        $imgData = base64_decode($data[1]);
        $fileName = $prefix . '_' . uniqid() . '.png';
        $filePath = '../../uploads/firmas/' . $fileName; // Ruta relativa desde este script
        
        if (file_put_contents($filePath, $imgData)) {
            return $fileName;
        }
        return null;
    }

    $id_contrato = isset($_POST['id_contrato']) ? intval($_POST['id_contrato']) : 0;
    $fecha = isset($_POST['fecha']) ? $conn->real_escape_string($_POST['fecha']) : date('Y-m-d');
    
    // Datos Técnicos
    $sector = isset($_POST['sector']) ? $conn->real_escape_string($_POST['sector']) : '';
    $tipo_servicio = isset($_POST['tipo_servicio']) ? $conn->real_escape_string($_POST['tipo_servicio']) : '';
    $ip = isset($_POST['ip']) ? $conn->real_escape_string($_POST['ip']) : '';
    
    $estado_onu = isset($_POST['estado_onu']) ? $conn->real_escape_string($_POST['estado_onu']) : '';
    $estado_router = isset($_POST['estado_router']) ? $conn->real_escape_string($_POST['estado_router']) : '';
    $modelo_router = isset($_POST['modelo_router']) ? $conn->real_escape_string($_POST['modelo_router']) : '';
    
    $bw_bajada = isset($_POST['bw_bajada']) ? $conn->real_escape_string($_POST['bw_bajada']) : '';
    $bw_subida = isset($_POST['bw_subida']) ? $conn->real_escape_string($_POST['bw_subida']) : '';
    $bw_ping = isset($_POST['bw_ping']) ? $conn->real_escape_string($_POST['bw_ping']) : '';
    $num_dispositivos = isset($_POST['num_dispositivos']) ? intval($_POST['num_dispositivos']) : 0;
    
    $estado_antena = isset($_POST['estado_antena']) ? $conn->real_escape_string($_POST['estado_antena']) : '';
    $valores_antena = isset($_POST['valores_antena']) ? $conn->real_escape_string($_POST['valores_antena']) : '';
    
    $observaciones = isset($_POST['observaciones']) ? $conn->real_escape_string($_POST['observaciones']) : '';
    $sugerencias = isset($_POST['sugerencias']) ? $conn->real_escape_string($_POST['sugerencias']) : '';
    $solucion_completada = isset($_POST['solucion_completada']) ? 1 : 0;
    
    // Monto (Costo de visita)
    $monto_total = isset($_POST['monto_total']) ? floatval($_POST['monto_total']) : 0.00;
    $monto_pagado = 0.00; // Por defecto 0 en reporte técnico
    
    // Firmas (Base64)
    $firma_tecnico_b64 = isset($_POST['firma_tecnico_data']) ? $_POST['firma_tecnico_data'] : '';
    $firma_cliente_b64 = isset($_POST['firma_cliente_data']) ? $_POST['firma_cliente_data'] : '';
    
    if ($id_contrato > 0) {
        $conn->begin_transaction();
        try {
            // Guardar imagenes
            $path_firma_tecnico = saveSignature($firma_tecnico_b64, 'tech');
            $path_firma_cliente = saveSignature($firma_cliente_b64, 'cli');
            
            // Descripción autogenerada para el listado simple
            $descripcion_corta = "Visita Técnica ($tipo_servicio). " . substr($observaciones, 0, 50) . "...";
            $tecnico_nombre = "Reporte Digital"; // O pedir nombre en form

            $sql = "INSERT INTO soportes (
                id_contrato, descripcion, monto_total, monto_pagado, fecha_soporte, tecnico_asignado, observaciones,
                sector, tipo_servicio, ip_address, estado_onu, estado_router, modelo_router,
                bw_bajada, bw_subida, bw_ping, num_dispositivos,
                estado_antena, valores_antena, sugerencias, solucion_completada,
                firma_tecnico, firma_cliente
            ) VALUES (
                '$id_contrato', '$descripcion_corta', '$monto_total', '$monto_pagado', '$fecha', '$tecnico_nombre', '$observaciones',
                '$sector', '$tipo_servicio', '$ip', '$estado_onu', '$estado_router', '$modelo_router',
                '$bw_bajada', '$bw_subida', '$bw_ping', '$num_dispositivos',
                '$estado_antena', '$valores_antena', '$sugerencias', '$solucion_completada',
                '$path_firma_tecnico', '$path_firma_cliente'
            )";

            if (!$conn->query($sql)) {
                throw new Exception("Error SQL: " . $conn->error);
            }
            $id_soporte = $conn->insert_id;

            // Generar Deuda (Si hay cobro)
            if ($monto_total > 0) {
                $fecha_vencimiento = date('Y-m-d', strtotime($fecha . ' + 7 days'));
                
                $sql_cxc = "INSERT INTO cuentas_por_cobrar (id_contrato, fecha_emision, fecha_vencimiento, monto_total, estado) 
                            VALUES ('$id_contrato', '$fecha', '$fecha_vencimiento', '$monto_total', 'PENDIENTE')";
                
                if ($conn->query($sql_cxc)) {
                    $id_cobro = $conn->insert_id;
                    $conn->query("UPDATE soportes SET id_cobro = '$id_cobro' WHERE id_soporte = '$id_soporte'");
                    
                    $justificacion = "Visita Técnica #$id_soporte (Reporte Digital)";
                    $sql_historial = "INSERT INTO cobros_manuales_historial (id_cobro_cxc, id_contrato, autorizado_por, justificacion, monto_cargado) 
                                      VALUES ('$id_cobro', '$id_contrato', 'Sistema Web', '$justificacion', '$monto_total')";
                    $conn->query($sql_historial);
                }
            }

            $conn->commit();
            echo json_encode(['status' => 'success', 'msg' => 'Reporte guardado correctamente. ID: ' . $id_soporte]);

        } catch (Exception $e) {
            $conn->rollback();
            echo json_encode(['status' => 'error', 'msg' => $e->getMessage()]);
        }
    } else {
        echo json_encode(['status' => 'error', 'msg' => 'Datos incompletos o cliente no seleccionado.']);
    }
}
?>
