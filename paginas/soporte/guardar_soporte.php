<?php
// paginas/soporte/guardar_soporte.php
// Backend para guardar un nuevo soporte (Desde Vista ADMIN)
// Actualizado para recibir todos los campos técnicos y firmas.

require_once '../conexion.php';

// Función auxiliar para guardar firmas (Reutilizada de reporte_tecnico logic)
function saveSignatureFromAdmin($base64_string, $prefix) {
    if (empty($base64_string)) return null;
    
    $data = explode(',', $base64_string);
    // Validar formato data:image/png;base64,... (A veces puede venir sin 'data:image...' si se manipula string, pero pad.toDataURL() siempre lo manda)
    if (count($data) < 2) return null;
    
    $imgData = base64_decode($data[1]);
    $fileName = $prefix . '_Admin_' . uniqid() . '.png';
    $filePath = '../../uploads/firmas/' . $fileName; // Ruta relativa
    
    // Crear carpeta si no existe (por seguridad)
    if (!file_exists('../../uploads/firmas')) {
        mkdir('../../uploads/firmas', 0777, true);
    }

    if (file_put_contents($filePath, $imgData)) {
        return $fileName;
    }
    return null;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // 1. Sanitización de datos BÁSICOS
    $id_contrato = isset($_POST['id_contrato']) ? intval($_POST['id_contrato']) : 0;
    
    // Si viene la descripcion del textarea llamada 'descripcion', la usamos. Si no, usamos 'observaciones' como fallback o concatenamos
    // En el nuevo form tenemos 'descripcion' (Problema) y 'sugerencias'. Y campos tecnicos.
    $descripcion_problema = isset($_POST['descripcion']) ? $conn->real_escape_string($_POST['descripcion']) : '';
    $sugerencias = isset($_POST['sugerencias']) ? $conn->real_escape_string($_POST['sugerencias']) : '';
    $tecnico = isset($_POST['tecnico']) ? $conn->real_escape_string($_POST['tecnico']) : '';
    $fecha_soporte = isset($_POST['fecha_soporte']) ? $conn->real_escape_string($_POST['fecha_soporte']) : date('Y-m-d');
    
    // Financiero
    $monto_total = isset($_POST['monto_total']) ? floatval($_POST['monto_total']) : 0.0;
    $monto_pagado = isset($_POST['monto_pagado']) ? floatval($_POST['monto_pagado']) : 0.0;

    // 2. Sanitización de datos TÉCNICOS (Nuevos campos)
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
    $solucion_completada = isset($_POST['solucion_completada']) ? 1 : 0;

    // Firmas
    $firma_tecnico_b64 = isset($_POST['firma_tecnico_data']) ? $_POST['firma_tecnico_data'] : '';
    $firma_cliente_b64 = isset($_POST['firma_cliente_data']) ? $_POST['firma_cliente_data'] : '';

    if ($id_contrato > 0) {
        
        $conn->begin_transaction();

        try {
            // Guardar Firmas
            $path_firma_tech = saveSignatureFromAdmin($firma_tecnico_b64, 'tech');
            $path_firma_cli = saveSignatureFromAdmin($firma_cliente_b64, 'cli');

            // 3. Insertar en tabla `soportes`
            // Columna 'descripcion' en BD suele ser texto corto. 'observaciones' TEXT.
            // Usaremos: descripcion = descripcion_problema. observaciones = descripcion_problema + sugerencias (para tener histórico completo si se quiere) 
            // O mejor: descripcion = descripcion_problema, sugerencias = sugerencias. observaciones = descripcion_problema (legacy fallback)
            
            $sql_soporte = "INSERT INTO soportes (
                id_contrato, descripcion, monto_total, monto_pagado, fecha_soporte, tecnico_asignado, observaciones,
                sector, tipo_servicio, ip_address, estado_onu, estado_router, modelo_router,
                bw_bajada, bw_subida, bw_ping, num_dispositivos,
                estado_antena, valores_antena, sugerencias, solucion_completada,
                firma_tecnico, firma_cliente
            ) VALUES (
                '$id_contrato', '$descripcion_problema', '$monto_total', '$monto_pagado', '$fecha_soporte', '$tecnico', '$descripcion_problema',
                '$sector', '$tipo_servicio', '$ip', '$estado_onu', '$estado_router', '$modelo_router',
                '$bw_bajada', '$bw_subida', '$bw_ping', '$num_dispositivos',
                '$estado_antena', '$valores_antena', '$sugerencias', '$solucion_completada',
                '$path_firma_tech', '$path_firma_cli'
            )";
            
            if (!$conn->query($sql_soporte)) {
                throw new Exception("Error al guardar soporte: " . $conn->error);
            }
            $id_soporte = $conn->insert_id;

            // 4. Deuda / Cobranzas (Lógica Original Mantenida)
            $deuda_pendiente = $monto_total - $monto_pagado;

            if ($deuda_pendiente > 0.01) {
                // Generar cuenta por cobrar
                $fecha_vencimiento = date('Y-m-d', strtotime($fecha_soporte . ' + 7 days')); // Vence en 7 días por defecto
                $estado = 'PENDIENTE';

                // Usamos 'monto_total' de cxc como la DEUDA PENDIENTE inicial (según lógica previa)
                // OJO: Si se quiere mantener el monto total real, y un 'saldo'. El sistema actual de cobros parece usar 'monto_total' como saldo a cobrar.
                // En `guardar_reporte_publico.php` usé monto_total completo porque monto_pagado era 0.
                // Aquí el admin puede poner un pago inicial. 
                // Si Costo=100, Pagó=20. Deuda=80.
                // Insertamos CxC con monto=80.
                
                $sql_cxc = "INSERT INTO cuentas_por_cobrar (id_contrato, fecha_emision, fecha_vencimiento, monto_total, estado) 
                            VALUES ('$id_contrato', '$fecha_soporte', '$fecha_vencimiento', '$deuda_pendiente', '$estado')";

                if (!$conn->query($sql_cxc)) {
                    throw new Exception("Error al generar cuenta por cobrar: " . $conn->error);
                }
                $id_cobro_cxc = $conn->insert_id;

                // Registrar historial
                $justificacion = "Deuda generada por Soporte Técnico #" . $id_soporte;
                $autorizado = "Admin (Sistema)";

                $sql_historial = "INSERT INTO cobros_manuales_historial (id_cobro_cxc, id_contrato, autorizado_por, justificacion, monto_cargado) 
                                  VALUES ('$id_cobro_cxc', '$id_contrato', '$autorizado', '$justificacion', '$deuda_pendiente')";

                if (!$conn->query($sql_historial)) {
                    throw new Exception("Error al generar historial de cobro: " . $conn->error);
                }

                // VINCULAR
                $conn->query("UPDATE soportes SET id_cobro = '$id_cobro_cxc' WHERE id_soporte = '$id_soporte'");
            }

            $conn->commit();
            // Redirigir con éxito
            header("Location: historial_soportes.php?status=success&msg=Soporte registrado correctamente");
            exit();

        } catch (Exception $e) {
            $conn->rollback();
            header("Location: registro_soporte.php?status=error&msg=" . urlencode($e->getMessage()));
            exit();
        }
    } else {
        header("Location: registro_soporte.php?status=error&msg=Debe seleccionar un cliente.");
        exit();
    }
} else {
    header("Location: registro_soporte.php");
    exit();
}
?>
