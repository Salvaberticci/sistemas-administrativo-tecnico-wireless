<?php
// paginas/soporte/actualizar_soporte.php
// Actualiza datos del soporte y recalcula la deuda si cambia el monto total.

require_once '../conexion.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id_soporte = isset($_POST['id_soporte_edit']) ? intval($_POST['id_soporte_edit']) : 0;
    $descripcion = isset($_POST['descripcion_edit']) ? $conn->real_escape_string($_POST['descripcion_edit']) : '';
    $tecnico = isset($_POST['tecnico_edit']) ? $conn->real_escape_string($_POST['tecnico_edit']) : '';
    $observaciones = isset($_POST['observaciones_edit']) ? $conn->real_escape_string($_POST['observaciones_edit']) : '';
    $fecha = isset($_POST['fecha_edit']) ? $conn->real_escape_string($_POST['fecha_edit']) : '';
    $nuevo_total = isset($_POST['monto_total_edit']) ? floatval($_POST['monto_total_edit']) : -1;

    if ($id_soporte > 0 && !empty($descripcion) && $nuevo_total >= 0) {
        
        $conn->begin_transaction();
        try {
            // 1. Obtener datos actuales (para comparar y obtener ID de cobro)
            $stmt = $conn->prepare("SELECT monto_pagado, id_cobro, id_contrato FROM soportes WHERE id_soporte = ? FOR UPDATE");
            $stmt->bind_param("i", $id_soporte);
            $stmt->execute();
            $res = $stmt->get_result();
            if ($res->num_rows == 0) throw new Exception("Soporte no encontrado.");
            $row = $res->fetch_assoc();
            
            $monto_pagado = $row['monto_pagado'];
            $id_cobro = $row['id_cobro'];
            $id_contrato = $row['id_contrato'];

            // 2. Actualizar tabla Soportes
            $sql_update = "UPDATE soportes SET 
                           descripcion = '$descripcion', 
                           tecnico_asignado = '$tecnico', 
                           observaciones = '$observaciones', 
                           fecha_soporte = '$fecha',
                           monto_total = '$nuevo_total' 
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
                if ($nueva_deuda_pendiente < 0) $nueva_deuda_pendiente = 0;

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
            header("Location: historial_soportes.php?status=success&msg=Soporte actualizado correctamente.");

        } catch (Exception $e) {
            $conn->rollback();
            header("Location: historial_soportes.php?status=error&msg=Error: " . urlencode($e->getMessage()));
        }
    } else {
        header("Location: historial_soportes.php?status=error&msg=Datos inválidos.");
    }
}
?>
