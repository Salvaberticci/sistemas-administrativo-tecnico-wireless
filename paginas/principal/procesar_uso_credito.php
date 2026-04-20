<?php
/**
 * procesar_uso_credito.php
 * Aplica saldo a favor (CRÉDITO) a una deuda pendiente (DEUDA)
 */
header('Content-Type: application/json; charset=utf-8');
require_once '../conexion.php';

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    echo json_encode(['success' => false, 'message' => 'Método no permitido.']);
    exit;
}

// 1. Recibir y Sanitizar Datos
$id_credito = isset($_POST['id_credito']) ? intval($_POST['id_credito']) : 0;
$id_deuda = isset($_POST['id_deuda']) ? intval($_POST['id_deuda']) : 0;
$monto_a_usar = isset($_POST['monto']) ? floatval(str_replace(',', '.', $_POST['monto'])) : 0.0;
$autorizado_por = 'Sistema/Abono Crédito';

if ($id_credito <= 0 || $id_deuda <= 0 || $monto_a_usar <= 0) {
    echo json_encode(['success' => false, 'message' => 'Datos insuficientes para procesar la compensación.']);
    exit;
}

$conn->begin_transaction();

try {
    // 2. Obtener y validar el Crédito (Source)
    $sql_cr = "SELECT id_contrato, saldo_pendiente, monto_pagado FROM clientes_deudores WHERE id = ? AND tipo_registro = 'CREDITO' AND estado = 'PENDIENTE' FOR UPDATE";
    $stmt_cr = $conn->prepare($sql_cr);
    $stmt_cr->bind_param("i", $id_credito);
    $stmt_cr->execute();
    $res_cr = $stmt_cr->get_result();
    $credito = $res_cr->fetch_assoc();

    if (!$credito) throw new Exception("El saldo a favor no existe o ya fue consumido.");
    if ($monto_a_usar > $credito['saldo_pendiente']) throw new Exception("El monto excede el saldo a favor disponible.");

    // 3. Obtener y validar la Deuda (Target)
    $sql_db = "SELECT id_contrato, saldo_pendiente, monto_pagado FROM clientes_deudores WHERE id = ? AND tipo_registro = 'DEUDA' AND estado = 'PENDIENTE' FOR UPDATE";
    $stmt_db = $conn->prepare($sql_db);
    $stmt_db->bind_param("i", $id_deuda);
    $stmt_db->execute();
    $res_db = $stmt_db->get_result();
    $deuda = $res_db->fetch_assoc();

    if (!$deuda) throw new Exception("La deuda no existe o ya fue saldada.");
    if ($monto_a_usar > $deuda['saldo_pendiente']) throw new Exception("El monto excede el saldo pendiente de la deuda.");
    
    // Verificación de seguridad: Mismo contrato
    if ($credito['id_contrato'] !== $deuda['id_contrato']) {
        throw new Exception("No se puede aplicar crédito de un contrato a una deuda de otro contrato.");
    }

    $id_contrato = $credito['id_contrato'];
    $fecha_actual = date('Y-m-d H:i:s');
    $fecha_solo_dia = date('Y-m-d');
    $referencia_sistema = "CR-COMP-{$id_credito}";

    // 4. Actualizar el Saldo a Favor (Reducir disponibilidad)
    $nuevo_saldo_cr = round($credito['saldo_pendiente'] - $monto_a_usar, 2);
    $nuevo_pagado_cr = round($credito['monto_pagado'] + $monto_a_usar, 2); // En créditos, pagado es lo consumido?
    $estado_cr = ($nuevo_saldo_cr < 0.01) ? 'PAGADO' : 'PENDIENTE';

    $upd_cr = "UPDATE clientes_deudores SET saldo_pendiente = ?, monto_pagado = ?, estado = ? WHERE id = ?";
    $st_upd_cr = $conn->prepare($upd_cr);
    $st_upd_cr->bind_param("ddsi", $nuevo_saldo_cr, $nuevo_pagado_cr, $estado_cr, $id_credito);
    $st_upd_cr->execute();

    // 5. Actualizar la Deuda (Reducir lo que debe)
    $nuevo_saldo_db = round($deuda['saldo_pendiente'] - $monto_a_usar, 2);
    $nuevo_pagado_db = round($deuda['monto_pagado'] + $monto_a_usar, 2);
    $estado_db = ($nuevo_saldo_db < 0.50) ? 'PAGADO' : 'PENDIENTE';
    if ($estado_db === 'PAGADO') $nuevo_saldo_db = 0;

    $upd_db = "UPDATE clientes_deudores SET saldo_pendiente = ?, monto_pagado = ?, estado = ? WHERE id = ?";
    $st_upd_db = $conn->prepare($upd_db);
    $st_upd_db->bind_param("ddsi", $nuevo_saldo_db, $nuevo_pagado_db, $estado_db, $id_deuda);
    $st_upd_db->execute();

    // 6. Crear registro en cuentas_por_cobrar (Para Sincronización)
    $sql_cxc = "INSERT INTO cuentas_por_cobrar (id_contrato, fecha_emision, fecha_vencimiento, monto_total, estado, origen, estado_sae_plus, fecha_pago, referencia_pago, id_banco) 
                VALUES (?, ?, ?, ?, 'PAGADO', 'SISTEMA', 'NO CARGADO', ?, ?, 10)"; // 10 = Divisas/Abono Interno
    $stmt_cxc = $conn->prepare($sql_cxc);
    $stmt_cxc->bind_param("issdss", $id_contrato, $fecha_solo_dia, $fecha_solo_dia, $monto_a_usar, $fecha_actual, $referencia_sistema);
    $stmt_cxc->execute();
    $id_cobro_nuevo = $conn->insert_id;

    // 7. Registrar en Historial
    $justificacion = "[COMPENSACIÓN] Uso de Saldo a Favor #{$id_credito} aplicado a Deuda #{$id_deuda}";
    $sql_hist = "INSERT INTO cobros_manuales_historial (id_cobro_cxc, id_contrato, autorizado_por, justificacion, monto_cargado) 
                 VALUES (?, ?, ?, ?, ?)";
    $stmt_hist = $conn->prepare($sql_hist);
    $stmt_hist->bind_param("iissd", $id_cobro_nuevo, $id_contrato, $autorizado_por, $justificacion, $monto_a_usar);
    $stmt_hist->execute();

    // 8. Reactivación de Contrato si la deuda quedó saldada
    if ($estado_db === 'PAGADO') {
        $sql_act = "UPDATE contratos SET estado = 'ACTIVO' WHERE id = ? AND estado = 'SUSPENDIDO'";
        $st_act = $conn->prepare($sql_act);
        $st_act->bind_param("i", $id_contrato);
        $st_act->execute();
    }

    $conn->commit();
    echo json_encode(['success' => true, 'message' => 'Compensación procesada con éxito.']);

} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
