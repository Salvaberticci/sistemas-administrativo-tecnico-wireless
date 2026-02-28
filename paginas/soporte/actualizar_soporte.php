<?php
// paginas/soporte/actualizar_soporte.php
// Actualiza datos del soporte y recalcula la deuda si cambia el monto total.

require_once '../conexion.php';

// Función auxiliar para guardar firmas (Reutilizada)
function saveSignatureFromAdmin($base64_string, $prefix)
{
    if (empty($base64_string))
        return null;

    $data = explode(',', $base64_string);
    if (count($data) < 2)
        return null;

    $imgData = base64_decode($data[1]);
    $fileName = $prefix . '_Admin_' . uniqid() . '.png';
    $filePath = '../../uploads/firmas/' . $fileName; // Ruta relativa

    if (!file_exists('../../uploads/firmas')) {
        mkdir('../../uploads/firmas', 0777, true);
    }

    if (file_put_contents($filePath, $imgData)) {
        return $fileName;
    }
    return null;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // The modal uses these exact names
    $id_soporte = isset($_POST['id_soporte_edit']) ? intval($_POST['id_soporte_edit']) : 0;

    // Campos básicos
    $fecha = isset($_POST['fecha_edit']) ? $conn->real_escape_string($_POST['fecha_edit']) : '';
    $tecnico = isset($_POST['tecnico_edit']) ? $conn->real_escape_string($_POST['tecnico_edit']) : '';
    $sector = isset($_POST['sector']) ? $conn->real_escape_string($_POST['sector']) : '';
    $descripcion = isset($_POST['descripcion_edit']) ? $conn->real_escape_string($_POST['descripcion_edit']) : '';
    $sugerencias = isset($_POST['sugerencias']) ? $conn->real_escape_string($_POST['sugerencias']) : '';
    $notas_internas = isset($_POST['notas_internas_edit']) ? $conn->real_escape_string($_POST['notas_internas_edit']) : '';
    $prioridad = isset($_POST['prioridad_edit']) ? $conn->real_escape_string($_POST['prioridad_edit']) : 'NIVEL 1';
    $tipo_falla = isset($_POST['tipo_falla_edit']) ? $conn->real_escape_string($_POST['tipo_falla_edit']) : '';
    $es_caida_critica = isset($_POST['es_caida_critica_edit']) ? 1 : 0;
    $nuevo_total = isset($_POST['monto_total_edit']) ? floatval($_POST['monto_total_edit']) : -1;
    $solucion_completada = isset($_POST['solucion_completada']) ? 1 : 0;
    $origen = isset($_POST['origen']) ? $conn->real_escape_string($_POST['origen']) : 'historial_soportes';

    // Campos técnicos
    $tipo_servicio = isset($_POST['tipo_servicio']) ? $conn->real_escape_string($_POST['tipo_servicio']) : '';
    $ip = isset($_POST['ip']) ? $conn->real_escape_string($_POST['ip']) : '';
    $estado_onu = isset($_POST['estado_onu']) ? $conn->real_escape_string($_POST['estado_onu']) : '';
    $estado_router = isset($_POST['estado_router']) ? $conn->real_escape_string($_POST['estado_router']) : '';
    $modelo_router = isset($_POST['modelo_router']) ? $conn->real_escape_string($_POST['modelo_router']) : '';
    $num_dispositivos = isset($_POST['num_dispositivos']) ? intval($_POST['num_dispositivos']) : 0;
    $bw_bajada = isset($_POST['bw_bajada']) ? $conn->real_escape_string($_POST['bw_bajada']) : '';
    $bw_subida = isset($_POST['bw_subida']) ? $conn->real_escape_string($_POST['bw_subida']) : '';
    $bw_ping = isset($_POST['bw_ping']) ? $conn->real_escape_string($_POST['bw_ping']) : '';
    $estado_antena = isset($_POST['estado_antena']) ? $conn->real_escape_string($_POST['estado_antena']) : '';
    $valores_antena = isset($_POST['valores_antena']) ? $conn->real_escape_string($_POST['valores_antena']) : '';

    // Firmas (base64)
    $firma_tecnico_b64 = isset($_POST['firma_tecnico_data']) ? $_POST['firma_tecnico_data'] : '';
    $firma_cliente_b64 = isset($_POST['firma_cliente_data']) ? $_POST['firma_cliente_data'] : '';

    // Si todo está vacío, tal vez falló la captura POST
    if ($id_soporte > 0 && !empty($descripcion) && $nuevo_total >= 0) {

        $conn->begin_transaction();
        try {
            // 1. Obtener datos actuales (para comparar y obtener ID de cobro)
            $stmt = $conn->prepare("SELECT monto_pagado, id_cobro, id_contrato FROM soportes WHERE id_soporte = ? FOR UPDATE");
            $stmt->bind_param("i", $id_soporte);
            $stmt->execute();
            $res = $stmt->get_result();
            if ($res->num_rows == 0)
                throw new Exception("Soporte no encontrado.");
            $row = $res->fetch_assoc();

            $monto_pagado = $row['monto_pagado'];
            $id_cobro = $row['id_cobro'];
            $id_contrato = $row['id_contrato'];

            // Preparar bloque de actualización de firmas si vienen en el POST
            $update_firmas = "";
            $path_firma_tech = saveSignatureFromAdmin($firma_tecnico_b64, 'tech');
            if ($path_firma_tech) {
                $update_firmas .= ", firma_tecnico = '$path_firma_tech'";
            }

            $path_firma_cli = saveSignatureFromAdmin($firma_cliente_b64, 'cli');
            if ($path_firma_cli) {
                $update_firmas .= ", firma_cliente = '$path_firma_cli'";
            }

            // 2. Actualizar tabla Soportes
            $sql_update = "UPDATE soportes SET 
                           fecha_soporte = '$fecha',
                           tecnico_asignado = '$tecnico', 
                           sector = '$sector',
                           tipo_servicio = '$tipo_servicio',
                           ip_address = '$ip',
                           estado_onu = '$estado_onu',
                           estado_router = '$estado_router',
                           modelo_router = '$modelo_router',
                           num_dispositivos = '$num_dispositivos',
                           bw_bajada = '$bw_bajada',
                           bw_subida = '$bw_subida',
                           bw_ping = '$bw_ping',
                           estado_antena = '$estado_antena',
                           valores_antena = '$valores_antena',
                           descripcion = '$descripcion', 
                           sugerencias = '$sugerencias',
                           notas_internas = '$notas_internas',
                           prioridad = '$prioridad',
                           tipo_falla = '$tipo_falla',
                           es_caida_critica = '$es_caida_critica',
                           solucion_completada = '$solucion_completada',
                           monto_total = '$nuevo_total' 
                           $update_firmas
                           WHERE id_soporte = '$id_soporte'";

            if (!$conn->query($sql_update)) {
                throw new Exception("Error al actualizar soporte: " . $conn->error);
            }

            // 3. Recalcular y Actualizar Deuda (si existe un cobro asociado)
            if ($id_cobro) {
                // Nueva deuda pendiente = Nuevo Total - Lo que ya pagó
                $nueva_deuda_pendiente = $nuevo_total - $monto_pagado;

                // Si por alguna razón el nuevo total es MEJOR que lo pagado (ej. descuento), 
                // la deuda es 0 (y técnicamente saldo a favor, pero lo dejamos en 0 por ahora).
                if ($nueva_deuda_pendiente < 0)
                    $nueva_deuda_pendiente = 0;

                // Determinar estado
                $nuevo_estado = ($nueva_deuda_pendiente <= 0.01) ? 'PAGADO' : 'PENDIENTE';
                $fecha_pago_sql = ($nuevo_estado == 'PAGADO') ? ", fecha_pago = NOW()" : ", fecha_pago = NULL";

                // Actualizar la Cuenta por Cobrar
                $stmt_cxc = $conn->prepare("UPDATE cuentas_por_cobrar SET monto_total = ?, estado = ? $fecha_pago_sql WHERE id_cobro = ?");
                $stmt_cxc->bind_param("dsi", $nueva_deuda_pendiente, $nuevo_estado, $id_cobro);

                if (!$stmt_cxc->execute()) {
                    throw new Exception("Error al actualizar la deuda asociada.");
                }
            } else if (!empty($id_cobro) == false && ($nuevo_total - $monto_pagado) > 0) {
                // CASO ESPECIAL: No tenía deuda (quizás se creó pagado), pero ahora se subió el precio y DEBE tener deuda.
                // Generar nueva cuenta por cobrar.
                $deuda_pendiente = $nuevo_total - $monto_pagado;
                $fecha_vencimiento = date('Y-m-d', strtotime($fecha . ' + 7 days'));

                $sql_cxc = "INSERT INTO cuentas_por_cobrar (id_contrato, fecha_emision, fecha_vencimiento, monto_total, estado) 
                            VALUES ('$id_contrato', '$fecha', '$fecha_vencimiento', '$deuda_pendiente', 'PENDIENTE')";

                if ($conn->query($sql_cxc)) {
                    $nuevo_id_cobro = $conn->insert_id;
                    $conn->query("UPDATE soportes SET id_cobro = '$nuevo_id_cobro' WHERE id_soporte = '$id_soporte'");

                    // Historial
                    $justificacion = "Ajuste de precio en Soporte #" . $id_soporte;
                    $sql_historial = "INSERT INTO cobros_manuales_historial (id_cobro_cxc, id_contrato, autorizado_por, justificacion, monto_cargado) 
                                      VALUES ('$nuevo_id_cobro', '$id_contrato', 'Sistema', '$justificacion', '$deuda_pendiente')";
                    $conn->query($sql_historial);
                }
            }

            $conn->commit();
            $redirect_page = ($origen === 'gestion_fallas') ? 'gestion_fallas.php' : 'historial_soportes.php';
            header("Location: {$redirect_page}?status=success&msg=Soporte actualizado correctamente.");
            exit();

        } catch (Exception $e) {
            $conn->rollback();
            header("Location: historial_soportes.php?status=error&msg=Error: " . urlencode($e->getMessage()));
            exit();
        }
    } else {
        // Redirigir en caso de error de validación
        header("Location: historial_soportes.php?status=error&msg=" . urlencode("Datos inválidos. Por favor, complete todos los campos requeridos."));
        exit();
    }
}
?>