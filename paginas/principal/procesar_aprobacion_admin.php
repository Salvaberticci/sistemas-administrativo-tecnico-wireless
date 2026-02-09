<?php
// procesar_aprobacion_admin.php - Procesa la decisión del administrador sobre un reporte de pago
require_once '../conexion.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_reporte = intval($_POST['id_reporte']);
    $accion = isset($_POST['accion']) ? $_POST['accion'] : 'APROBAR';

    $message = "Operación no reconocida.";
    $class = "danger";

    if ($accion === 'APROBAR') {
        $id_contrato = intval($_POST['id_contrato']);
        $monto_total = floatval($_POST['monto_total']);
        $fecha_pago = $conn->real_escape_string($_POST['fecha_pago']);
        $referencia = $conn->real_escape_string($_POST['referencia']);
        $id_banco = intval($_POST['id_banco']);

        // 1. Obtener detalles del reporte original para el historial
        $sql_orig = "SELECT meses_pagados, concepto, capture_path FROM pagos_reportados WHERE id_reporte = $id_reporte";
        $res_orig = $conn->query($sql_orig);
        $reporte = $res_orig->fetch_assoc();
        $justificacion = "Aprobado desde reporte Web. Meses: " . $reporte['meses_pagados'] . ". Notas: " . $reporte['concepto'];
        $path_archivo = $reporte['capture_path'];

        $conn->begin_transaction();
        try {
            // 2. Insertar en cuentas_por_cobrar
            $sql_cxc = "INSERT INTO cuentas_por_cobrar 
                (id_contrato, fecha_emision, fecha_vencimiento, monto_total, estado, fecha_pago, referencia_pago, id_banco, origen, capture_pago)
                VALUES (?, CURRENT_DATE, CURRENT_DATE, ?, 'PAGADO', ?, ?, ?, 'LINK', ?)";

            $stmt_cxc = $conn->prepare($sql_cxc);
            $stmt_cxc->bind_param("idssis", $id_contrato, $monto_total, $fecha_pago, $referencia, $id_banco, $path_archivo);
            $stmt_cxc->execute();
            $id_cobro_nuevo = $conn->insert_id;

            // 3. Insertar en historial de cobros manuales
            $sql_hist = "INSERT INTO cobros_manuales_historial 
                (id_cobro_cxc, autorizado_por, justificacion, monto_cargado)
                VALUES (?, 'SISTEMA (APROBACIÓN WEB)', ?, ?)";

            $stmt_hist = $conn->prepare($sql_hist);
            $stmt_hist->bind_param("isd", $id_cobro_nuevo, $justificacion, $monto_total);
            $stmt_hist->execute();

            // 4. Actualizar reporte original
            $sql_upd = "UPDATE pagos_reportados SET estado = 'APROBADO' WHERE id_reporte = $id_reporte";
            $conn->query($sql_upd);

            $conn->commit();
            $message = "¡Pago aprobado y registrado exitosamente!";
            $class = "success";
        } catch (Exception $e) {
            $conn->rollback();
            $message = "Error en la transacción: " . $e->getMessage();
            $class = "danger";
        }
    } elseif ($accion === 'RECHAZAR') {
        $motivo = isset($_POST['motivo']) ? $conn->real_escape_string($_POST['motivo']) : 'Sin motivo especificado.';
        $sql_rej = "UPDATE pagos_reportados SET estado = 'RECHAZADO', concepto = CONCAT(concepto, '\n\nMOTIVO RECHAZO: ', '$motivo') WHERE id_reporte = $id_reporte";

        if ($conn->query($sql_rej)) {
            $message = "El reporte ha sido rechazado.";
            $class = "warning";
        } else {
            $message = "Error al rechazar reporte: " . $conn->error;
            $class = "danger";
        }
    }

    $conn->close();
    header("Location: aprobar_pagos.php?maintenance_done=1&message=" . urlencode($message) . "&class=" . $class);
    exit();
}
?>