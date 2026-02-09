<?php
// Script para insertar una cuenta por cobrar generada manualmente y registrar el detalle en el historial.
// Este script es el procesador del formulario que se encuentra ahora dentro de gestion_cobros.php (modal).

require_once '../conexion.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // 1. Obtener y sanitizar datos del formulario
    $id_contrato = isset($_POST['id_contrato']) ? intval($_POST['id_contrato']) : 0;
    $monto_total = isset($_POST['monto']) ? floatval($_POST['monto']) : 0.0;

    // CAMPOS DE JUSTIFICACIÓN
    $autorizado_por = isset($_POST['autorizado_por']) ? $conn->real_escape_string($_POST['autorizado_por']) : '';
    $justificacion = isset($_POST['justificacion']) ? $conn->real_escape_string($_POST['justificacion']) : 'No especificada.';

    // 2. Definir fechas
    $fecha_emision = date('Y-m-d');
    $fecha_vencimiento = date('Y-m-d'); // Ya no se pide, se asume hoy

    if ($id_contrato > 0 && $monto_total > 0 && !empty($autorizado_por) && !empty($justificacion)) {

        $conn->begin_transaction();

        try {
            // 3. INSERTAR la Cuenta Por Cobrar (CXC)
            $sql_cxc = "INSERT INTO cuentas_por_cobrar (
                id_contrato, 
                fecha_emision, 
                fecha_vencimiento, 
                monto_total, 
                estado,
                fecha_pago,
                referencia_pago,
                id_banco
            ) VALUES (
                '$id_contrato', 
                '$fecha_emision', 
                '$fecha_vencimiento', 
                '$monto_total', 
                '$estado',
                " . ($fecha_pago ? "'$fecha_pago'" : "NULL") . ",
                " . ($referencia_pago ? "'$referencia_pago'" : "NULL") . ",
                " . ($id_banco ? "'$id_banco'" : "NULL") . "
            )";

            if ($conn->query($sql_cxc) === TRUE) {
                $id_cobro_cxc = $conn->insert_id;

                // 4. INSERTAR el Registro en la Tabla de Historial
                $sql_historial = "INSERT INTO cobros_manuales_historial (
                    id_cobro_cxc, 
                    id_contrato, 
                    autorizado_por, 
                    justificacion, 
                    monto_cargado
                ) VALUES (
                    '$id_cobro_cxc', 
                    '$id_contrato', 
                    '$autorizado_por', 
                    '$justificacion', 
                    '$monto_total'
                )";

                if ($conn->query($sql_historial) === TRUE) {

                    // 5. GESTIÓN DE DEUDORES (PAGO PARCIAL)
                    if ($pago_inmediato) {
                        $monto_pagado_hoy = isset($_POST['monto_pagado_hoy']) ? floatval($_POST['monto_pagado_hoy']) : $monto_total;
                        $saldo_pendiente = $monto_total - $monto_pagado_hoy;

                        if ($saldo_pendiente > 0) {
                            $sql_deudor = "INSERT INTO clientes_deudores (id_contrato, monto_total, monto_pagado, saldo_pendiente, estado, notas) 
                                          VALUES (?, ?, ?, ?, 'PENDIENTE', ?)";
                            $stmt_deudor = $conn->prepare($sql_deudor);
                            $notas_deuda = "Saldo pendiente de cobro manual #$id_cobro_cxc ($justificacion)";
                            $stmt_deudor->bind_param("iddds", $id_contrato, $monto_total, $monto_pagado_hoy, $saldo_pendiente, $notas_deuda);

                            if (!$stmt_deudor->execute()) {
                                throw new Exception("Error al registrar deuda: " . $stmt_deudor->error);
                            }
                            $stmt_deudor->close();
                            $message = "Cobro #$id_cobro_cxc registrado con PAGO PARCIAL. Se generó una deuda de $$saldo_pendiente.";
                        } else {
                            $message = "Cobro manual de $$monto_total registrado y PAGADO con éxito.";
                        }
                    } else {
                        $message = "Cobro manual de $$monto_total registrado como PENDIENTE.";
                    }

                    $conn->commit();
                    $class = "success";
                } else {
                    throw new Exception("Error al registrar el detalle del historial: " . $conn->error);
                }
            } else {
                throw new Exception("Error al registrar la cuenta por cobrar principal: " . $conn->error);
            }

        } catch (Exception $e) {
            $conn->rollback();
            $message = "ERROR al registrar el cobro: " . $e->getMessage();
            $class = "danger";
        }
    } else {
        $message = "Error: Faltan datos obligatorios para registrar el cobro manual.";
        $class = "danger";
    }

    $conn->close();

    // Redirigir siempre a gestion_mensualidades.php para mostrar el mensaje de éxito/error en la lista.
    header("Location: gestion_mensualidades.php?maintenance_done=1&message=" . urlencode($message) . "&class=" . $class);
    exit();
} else {
    // Si acceden directamente al procesador, los enviamos a la lista
    header("Location: gestion_mensualidades.php?maintenance_done=1");
    exit();
}
?>