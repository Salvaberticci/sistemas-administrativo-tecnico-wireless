<?php
/**
 * get_contract_balance.php — Datos financieros de un contrato
 * Actions: resumen | movimientos | deuda
 */

ini_set('display_errors', 0);
error_reporting(0);
ob_start();

// Garantizar que siempre se devuelva JSON aunque muera el script
register_shutdown_function(function () {
    $error = error_get_last();
    if ($error !== null && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        ob_clean();
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'success' => false,
            'message' => 'PHP Fatal Error: ' . $error['message'] . ' in ' . basename($error['file']) . ':' . $error['line']
        ]);
    } elseif (ob_get_length() === 0) {
        // Buffer vacío — script terminó sin enviar nada
        ob_clean();
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => false, 'message' => 'Script terminated without output.']);
    }
    ob_end_flush();
});

header('Content-Type: application/json; charset=utf-8');
require '../conexion.php';

$id_contrato = isset($_GET['id'])     ? intval($_GET['id'])     : 0;
$action      = isset($_GET['action']) ? trim($_GET['action'])  : 'resumen';

function send_json($data) {
    ob_clean();
    echo json_encode($data);
    exit;
}

if ($id_contrato <= 0) {
    send_json(['success' => false, 'message' => 'ID de contrato inválido.']);
}

// ─────────────────────────────────────────────────────────
// 1. RESUMEN FINANCIERO
// ─────────────────────────────────────────────────────────
if ($action === 'resumen') {

    $stmt = $conn->prepare("
        SELECT
            COALESCE(SUM(monto_total), 0) AS total_facturado,
            COALESCE(SUM(CASE WHEN estado = 'PAGADO' THEN monto_total ELSE 0 END), 0) AS total_pagado,
            COALESCE(SUM(CASE WHEN estado IN ('PENDIENTE','VENCIDO') THEN monto_total ELSE 0 END), 0) AS total_pendiente,
            COALESCE(COUNT(CASE WHEN estado = 'PAGADO' THEN 1 END), 0) AS meses_pagados
        FROM cuentas_por_cobrar
        WHERE id_contrato = ?
    ");
    if (!$stmt) send_json(['success'=>false,'message'=>'DB resumen: '.$conn->error]);
    $stmt->bind_param('i', $id_contrato);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    // Saldo a favor (cobro manual con justificacion = Saldo a Favor, estado PENDIENTE)
    $saldo_a_favor = 0;
    $stmt_sf = $conn->prepare("
        SELECT COALESCE(SUM(cxc.monto_total), 0) AS saldo
        FROM cuentas_por_cobrar cxc
        LEFT JOIN cobros_manuales_historial h ON cxc.id_cobro = h.id_cobro_cxc
        WHERE cxc.id_contrato = ? AND h.justificacion LIKE '%Saldo a Favor%' AND cxc.estado = 'PENDIENTE'
    ");
    if ($stmt_sf) {
        $stmt_sf->bind_param('i', $id_contrato);
        $stmt_sf->execute();
        $saldo_a_favor = floatval($stmt_sf->get_result()->fetch_assoc()['saldo'] ?? 0);
        $stmt_sf->close();
    }

    // Deuda activa
    $row_d = null;
    $stmt_d = $conn->prepare("SELECT saldo_pendiente FROM clientes_deudores WHERE id_contrato = ? AND estado = 'PENDIENTE' LIMIT 1");
    if ($stmt_d) {
        $stmt_d->bind_param('i', $id_contrato);
        $stmt_d->execute();
        $row_d = $stmt_d->get_result()->fetch_assoc();
        $stmt_d->close();
    }

    send_json([
        'success'            => true,
        'total_facturado'    => round(floatval($row['total_facturado']), 2),
        'total_pagado'       => round(floatval($row['total_pagado']), 2),
        'total_pendiente'    => round(floatval($row['total_pendiente']), 2),
        'meses_pagados'      => intval($row['meses_pagados']),
        'saldo_a_favor'      => $saldo_a_favor,
        'tiene_saldo_favor'  => $saldo_a_favor > 0,
        'tiene_deuda_activa' => !empty($row_d),
        'saldo_deuda'        => $row_d ? round(floatval($row_d['saldo_pendiente']), 2) : 0
    ]);
}

// ─────────────────────────────────────────────────────────
// 2. TODOS LOS MOVIMIENTOS
// ─────────────────────────────────────────────────────────
if ($action === 'movimientos') {

    // Verificar si existen columnas bimonetarias
    $col_bs = 'NULL AS monto_total_bs, NULL AS tasa_bcv,';
    $chk = $conn->query("SELECT 1 FROM information_schema.COLUMNS
                          WHERE TABLE_SCHEMA=DATABASE()
                            AND TABLE_NAME='cuentas_por_cobrar'
                            AND COLUMN_NAME='monto_total_bs' LIMIT 1");
    if ($chk && $chk->num_rows > 0) {
        $col_bs = 'cxc.monto_total_bs, cxc.tasa_bcv,';
    }

    // ── Query principal: CXC sin JOINs a tablas potencialmente inexistentes ──
    $sql_cxc = "
        SELECT
            cxc.id_cobro,
            cxc.fecha_emision,
            cxc.fecha_pago,
            cxc.monto_total,
            $col_bs
            cxc.estado,
            cxc.referencia_pago,
            cxc.id_banco
        FROM cuentas_por_cobrar cxc
        WHERE cxc.id_contrato = ?
        ORDER BY COALESCE(cxc.fecha_pago, cxc.fecha_emision) DESC, cxc.id_cobro DESC
    ";
    $stmt = $conn->prepare($sql_cxc);
    if (!$stmt) send_json(['success'=>false,'message'=>'Prepare CXC: '.$conn->error]);
    $stmt->bind_param('i', $id_contrato);
    if (!$stmt->execute()) send_json(['success'=>false,'message'=>'Execute CXC: '.$stmt->error]);

    $res = $stmt->get_result();
    $movimientos = [];
    while ($row = $res->fetch_assoc()) {
        $row['concepto_limpio'] = 'Mensualidad';
        $row['nombre_banco']    = '—';
        $movimientos[] = $row;
    }
    $stmt->close();

    // ── Enriquecer con concepto desde cobros_manuales_historial (si existe) ──
    $chk_h = $conn->query("SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='cobros_manuales_historial' LIMIT 1");
    if ($chk_h && $chk_h->num_rows > 0) {
        foreach ($movimientos as &$m) {
            $stmt_h = $conn->prepare("SELECT justificacion FROM cobros_manuales_historial WHERE id_cobro_cxc = ? LIMIT 1");
            if ($stmt_h) {
                $stmt_h->bind_param('i', $m['id_cobro']);
                $stmt_h->execute();
                $rh = $stmt_h->get_result()->fetch_assoc();
                if ($rh && $rh['justificacion']) {
                    $m['concepto_limpio'] = preg_replace('/\[([^\]]*)\]/', '$1', $rh['justificacion']);
                }
                $stmt_h->close();
            }
        }
        unset($m);
    }

    // ── Enriquecer con nombre del banco ──
    $chk_b = $conn->query("SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='bancos' LIMIT 1");
    if ($chk_b && $chk_b->num_rows > 0) {
        foreach ($movimientos as &$m) {
            if (!$m['id_banco']) continue;
            $stmt_b = $conn->prepare("SELECT nombre_banco FROM bancos WHERE id_banco = ? LIMIT 1");
            if ($stmt_b) {
                $stmt_b->bind_param('i', $m['id_banco']);
                $stmt_b->execute();
                $rb = $stmt_b->get_result()->fetch_assoc();
                if ($rb) $m['nombre_banco'] = $rb['nombre_banco'];
                $stmt_b->close();
            }
        }
        unset($m);
    }

    // ── Abonos parciales (si existe la tabla) ──
    $chk_a = $conn->query("SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='abonos_deudores' LIMIT 1");
    if ($chk_a && $chk_a->num_rows > 0) {
        $stmt_a = $conn->prepare("
            SELECT a.id, a.fecha_pago, a.monto_cargado AS monto_total,
                   a.referencia_pago, a.justificacion AS concepto_limpio,
                   b.nombre_banco, 'ABONO PARCIAL' AS estado
            FROM abonos_deudores a
            INNER JOIN clientes_deudores d ON a.id_deudor = d.id
            LEFT JOIN bancos b ON a.id_banco = b.id_banco
            WHERE d.id_contrato = ?
            ORDER BY a.fecha_pago DESC
        ");
        if ($stmt_a) {
            $stmt_a->bind_param('i', $id_contrato);
            $stmt_a->execute();
            $res_a = $stmt_a->get_result();
            while ($row = $res_a->fetch_assoc()) {
                $movimientos[] = $row;
            }
            $stmt_a->close();
        }
    }

    // Ordenar por fecha desc
    usort($movimientos, function($a, $b) {
        return strcmp($b['fecha_pago'] ?? '', $a['fecha_pago'] ?? '');
    });

    send_json(['success' => true, 'data' => $movimientos]);
}

// ─────────────────────────────────────────────────────────
// 3. DEUDA ACTIVA
// ─────────────────────────────────────────────────────────
if ($action === 'deuda') {
    $stmt = $conn->prepare("
        SELECT id, monto_total, monto_pagado, saldo_pendiente, estado, fecha_registro
        FROM clientes_deudores
        WHERE id_contrato = ?
        ORDER BY fecha_registro DESC
    ");
    if (!$stmt) send_json(['success'=>false,'message'=>'DB deuda: '.$conn->error]);
    $stmt->bind_param('i', $id_contrato);
    $stmt->execute();
    $deudas = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    send_json(['success' => true, 'data' => $deudas]);
}

send_json(['success' => false, 'message' => 'Accion no reconocida: ' . htmlspecialchars($action)]);
