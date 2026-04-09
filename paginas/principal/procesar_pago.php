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
    $fecha_pago = date('Y-m-d H:i:s');

    $conn->begin_transaction();
    try {

        // 4. Actualizar el cobro: estado + fecha + referencia + banco + monto real
        if ($monto_pagado_usd !== null && $monto_pagado_usd > 0 && $monto_bs !== null) {
            // Tenemos monto Y tasa → actualizamos todo
            $stmt = $conn->prepare(
                "UPDATE cuentas_por_cobrar
                 SET estado = ?, fecha_pago = ?, referencia_pago = ?, id_banco = ?,
                     monto_total = ?, monto_total_bs = ?, tasa_bcv = ?
                 WHERE id_cobro = ?"
            );
            $stmt->bind_param("sssidddi",
                $estado, $fecha_pago, $referencia_pago, $id_banco,
                $monto_pagado_usd, $monto_bs, $tasa_bcv,
                $id_cobro
            );
        } elseif ($monto_pagado_usd !== null && $monto_pagado_usd > 0) {
            // Tenemos monto pero sin tasa (pago USD directo)
            $stmt = $conn->prepare(
                "UPDATE cuentas_por_cobrar
                 SET estado = ?, fecha_pago = ?, referencia_pago = ?, id_banco = ?,
                     monto_total = ?
                 WHERE id_cobro = ?"
            );
            $stmt->bind_param("sssidi",
                $estado, $fecha_pago, $referencia_pago, $id_banco,
                $monto_pagado_usd, $id_cobro
            );
        } else {
            // Sin monto (compatibilidad hacia atrás)
            $stmt = $conn->prepare(
                "UPDATE cuentas_por_cobrar
                 SET estado = ?, fecha_pago = ?, referencia_pago = ?, id_banco = ?
                 WHERE id_cobro = ?"
            );
            $stmt->bind_param("sssii",
                $estado, $fecha_pago, $referencia_pago, $id_banco, $id_cobro
            );
        }

        if ($stmt === false) throw new Exception("Error de preparación: " . $conn->error);
        if (!$stmt->execute())    throw new Exception("Error al registrar el pago: " . $stmt->error);
        $stmt->close();

        // 5. Crear registro en cobros_manuales_historial (si tenemos monto real)
        //    Esto permite que la exportación Excel muestre el monto correcto via COALESCE.
        if ($monto_pagado_usd !== null && $monto_pagado_usd > 0) {

            // Obtener id_contrato y tipo de cobro para la justificación
            $res = $conn->query(
                "SELECT id_contrato, id_plan_cobrado FROM cuentas_por_cobrar WHERE id_cobro = $id_cobro LIMIT 1"
            );
            $info = $res ? $res->fetch_assoc() : null;
            $id_contrato   = intval($info['id_contrato'] ?? 0);
            $es_mensualidad = !empty($info['id_plan_cobrado']);

            $tag_justif   = $es_mensualidad ? '[MENSUALIDAD]' : '[PAGO]';
            $justif_texto = "$tag_justif Pago registrado vía modal (Ref: $referencia_pago)";
            $justif_esc   = $conn->real_escape_string($justif_texto);
            $autorizado   = $conn->real_escape_string('Sistema');

            $monto_bs_h   = $monto_bs ?? 0;
            $tasa_h       = $tasa_bcv;

            $sql_h = "INSERT INTO cobros_manuales_historial
                        (id_cobro_cxc, id_contrato, autorizado_por, justificacion,
                         monto_cargado, monto_cargado_bs, tasa_bcv)
                      VALUES
                        ($id_cobro, $id_contrato, '$autorizado', '$justif_esc',
                         $monto_pagado_usd, $monto_bs_h, $tasa_h)";

            if (!$conn->query($sql_h)) {
                throw new Exception("Error al registrar historial: " . $conn->error);
            }
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