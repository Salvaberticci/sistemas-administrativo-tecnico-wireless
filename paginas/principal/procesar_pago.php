<?php
require_once '../conexion.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // 1. Capturar y sanear datos básicos
    $id_cobro        = intval($_POST['id_cobro']);
    $referencia_pago = $conn->real_escape_string(trim($_POST['referencia_pago'] ?? ''));
    $id_banco        = isset($_POST['id_banco']) ? intval($_POST['id_banco']) : null;

    // 2. Monto real pagado (viene de monto_pagado_hidden, SIEMPRE en USD)
    //    Si el usuario pagó en Bs la función calcPagar() ya hizo la conversión antes de este submit.
    $monto_pagado_usd = isset($_POST['monto_pagado']) && $_POST['monto_pagado'] !== ''
        ? round(floatval($_POST['monto_pagado']), 2)
        : null;

    // 3. Tasa BCV que se usó (sincronizada por el submit handler en JS)
    $tasa_bcv = isset($_POST['tasa_bcv_pagar']) ? floatval($_POST['tasa_bcv_pagar']) : 0;

    // Calcular equivalente en Bs si tenemos tasa
    $monto_bs = ($monto_pagado_usd !== null && $tasa_bcv > 0)
        ? round($monto_pagado_usd * $tasa_bcv, 2)
        : null;

    $estado     = 'PAGADO';
    $fecha_actual = date('Y-m-d H:i:s');

    $conn->begin_transaction();
    try {

        // 4. Obtener monto original para detectar sobrepago
        $res_orig = $conn->query("SELECT id_contrato, monto_total as deuda_original, id_grupo_pago FROM cuentas_por_cobrar WHERE id_cobro = $id_cobro LIMIT 1");
        $info_orig = $res_orig->fetch_assoc();
        $deuda_original = floatval($info_orig['deuda_original']);
        $id_contrato = intval($info_orig['id_contrato']);
        $id_grupo_pago_base = $info_orig['id_grupo_pago'] ?: sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x', mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0x0fff) | 0x4000, mt_rand(0, 0x3fff) | 0x8000, mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff));

        $sobrepago = 0;
        if ($monto_pagado_usd !== null && $monto_pagado_usd > $deuda_original) {
            $sobrepago = round($monto_pagado_usd - $deuda_original, 2);
            $monto_pagado_usd_final = $deuda_original; // Capamos el actual a la deuda original
            $monto_bs_final = round($monto_pagado_usd_final * $tasa_bcv, 2);
        } else {
            $monto_pagado_usd_final = $monto_pagado_usd;
            $monto_bs_final = $monto_bs;
        }

        if ($monto_pagado_usd_final !== null && $monto_pagado_usd_final > 0) {
            $stmt = $conn->prepare(
                "UPDATE cuentas_por_cobrar
                 SET estado = ?, fecha_pago = ?, referencia_pago = ?, id_banco = ?,
                     monto_total = ?, monto_total_bs = ?, tasa_bcv = ?, id_grupo_pago = ?
                 WHERE id_cobro = ?"
            );
            $stmt->bind_param("sssidddsi",
                $estado, $fecha_actual, $referencia_pago, $id_banco,
                $monto_pagado_usd_final, $monto_bs_final, $tasa_bcv, $id_grupo_pago_base,
                $id_cobro
            );
        } else {
            $stmt = $conn->prepare(
                "UPDATE cuentas_por_cobrar
                 SET estado = ?, fecha_pago = ?, referencia_pago = ?, id_banco = ?, id_grupo_pago = ?
                 WHERE id_cobro = ?"
            );
            $stmt->bind_param("sssisi",
                $estado, $fecha_actual, $referencia_pago, $id_banco, $id_grupo_pago_base, $id_cobro
            );
        }

        if ($stmt === false) throw new Exception("Error de preparación: " . $conn->error);
        if (!$stmt->execute()) throw new Exception("Error al registrar el pago: " . $stmt->error);
        $stmt->close();

        // 5. Manejar el Sobrepago (Si existe)
        if ($sobrepago > 0) {
            $fecha_dia = date('Y-m-d');
            $justif_sobrepago = "[ABONO] Saldo a Favor (Sobrepago en Ref: $referencia_pago)";
            $monto_bs_sobrepago = round($sobrepago * $tasa_bcv, 2);
            
            // Insertar Nueva CxC para el Saldo a Favor
            $sql_extra = "INSERT INTO cuentas_por_cobrar (id_contrato, fecha_emision, fecha_vencimiento, monto_total, monto_total_bs, tasa_bcv, estado, fecha_pago, referencia_pago, id_banco, id_grupo_pago) 
                          VALUES (?, ?, ?, ?, ?, ?, 'PAGADO', ?, ?, ?, ?)";
            $stmt_extra = $conn->prepare($sql_extra);
            $stmt_extra->bind_param("issdddsssis", $id_contrato, $fecha_dia, $fecha_dia, $sobrepago, $monto_bs_sobrepago, $tasa_bcv, $fecha_actual, $referencia_pago, $id_banco, $id_grupo_pago_base);
            $stmt_extra->execute();
            $id_cobro_extra = $stmt_extra->insert_id;
            $stmt_extra->close();

            // Insertar Historial para el extra
            $autorizado = 'Sistema';
            $sql_h_extra = "INSERT INTO cobros_manuales_historial (id_cobro_cxc, id_contrato, autorizado_por, justificacion, monto_cargado, monto_cargado_bs, tasa_bcv)
                            VALUES (?, ?, ?, ?, ?, ?, ?)";
            $stmt_h_e = $conn->prepare($sql_h_extra);
            $stmt_h_e->bind_param("iissddd", $id_cobro_extra, $id_contrato, $autorizado, $justif_sobrepago, $sobrepago, $monto_bs_sobrepago, $tasa_bcv);
            $stmt_h_e->execute();
            $stmt_h_e->close();

            // Insertar en clientes_deudores como tipo CREDITO
            $sql_credito = "INSERT INTO clientes_deudores (id_contrato, tipo_registro, monto_total, monto_pagado, saldo_pendiente, estado, notas) 
                            VALUES (?, 'CREDITO', 0, ?, ?, 'PENDIENTE', ?)";
            $notas_credito = "Saldo a favor generado por sobrepago (Ref: $referencia_pago).";
            $stmt_cred = $conn->prepare($sql_credito);
            $stmt_cred->bind_param("idds", $id_contrato, $sobrepago, $sobrepago, $notas_credito);
            $stmt_cred->execute();
            $stmt_cred->close();
        }

        // 6. Crear registro en historial para el cobro principal
        if ($monto_pagado_usd_final !== null && $monto_pagado_usd_final > 0) {
            $tag_justif   = $es_mensualidad ? '[MENSUALIDAD]' : '[PAGO]';
            $justif_texto = "$tag_justif Pago registrado vía modal (Ref: $referencia_pago) (Total Operación: $" . number_format($monto_pagado_usd, 2) . ")";
            $monto_bs_h   = $monto_bs_final ?? 0;
            
            $sql_h = "INSERT INTO cobros_manuales_historial
                        (id_cobro_cxc, id_contrato, autorizado_por, justificacion,
                         monto_cargado, monto_cargado_bs, tasa_bcv)
                      VALUES
                        (?, ?, ?, ?, ?, ?, ?)";
            $stmt_h = $conn->prepare($sql_h);
            $stmt_h->bind_param("iissddd", $id_cobro, $id_contrato, $autorizado, $justif_texto, $monto_pagado_usd_final, $monto_bs_h, $tasa_bcv);
            $stmt_h->execute();
            $stmt_h->close();
        }

        $conn->commit();
        header("Location: gestion_mensualidades.php?message=" . urlencode("Pago registrado con éxito.") . "&class=success");

    } catch (Exception $e) {
        $conn->rollback();
        header("Location: gestion_mensualidades.php?message=" . urlencode("Error: " . $e->getMessage()) . "&class=danger");
    }

    $conn->close();

} else {
    header("Location: gestion_mensualidades.php");
}
exit();
?>