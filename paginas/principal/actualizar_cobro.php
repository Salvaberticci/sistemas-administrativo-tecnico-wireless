<?php
header('Content-Type: application/json; charset=utf-8');
require '../conexion.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método no permitido.']);
    exit;
}

$id_cobro_primario = isset($_POST['id_cobro']) ? intval($_POST['id_cobro']) : 0;
if ($id_cobro_primario <= 0) {
    echo json_encode(['success' => false, 'message' => 'ID de cobro inválido.']);
    exit;
}

// 1. Obtener datos actuales para recuperar el id_grupo_pago y la captura original
$check = $conn->query("SELECT id_grupo_pago, capture_pago, fecha_emision, id_contrato FROM cuentas_por_cobrar WHERE id_cobro = $id_cobro_primario");
if ($check->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Cobro no encontrado en la base de datos.']);
    exit;
}
$current_data = $check->fetch_assoc();
$id_grupo_pago = $current_data['id_grupo_pago'];
$capture_path = $current_data['capture_pago'];
$fecha_emision = $current_data['fecha_emision'];
$id_contrato_principal = $current_data['id_contrato'];

// 2. Procesar Campos Comunes
$estado = $_POST['estado'] ?? 'PENDIENTE';
$fecha_vencimiento = $_POST['fecha_vencimiento'] ?? date('Y-m-d');
$referencia_pago = $_POST['referencia_pago'] ?? '';
$id_banco_pago = intval($_POST['id_banco_pago'] ?? 0);
$autorizado_por = $_POST['autorizado_por'] ?? '';
$justificacion_global = $_POST['justificacion'] ?? '';
$fecha_pago = ($estado === 'PAGADO') ? date('Y-m-d') : null;

// 3. Manejo de Archivo (Captura)
if (isset($_FILES['capture_archivo']) && $_FILES['capture_archivo']['error'] === UPLOAD_ERR_OK) {
    $upload_dir = '../../img/captures/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }

    $file_extension = pathinfo($_FILES['capture_archivo']['name'], PATHINFO_EXTENSION);
    $new_file_name = 'cap_' . time() . '_' . rand(1000, 9999) . '.' . $file_extension;
    $target_file = $upload_dir . $new_file_name;

    if (move_uploaded_file($_FILES['capture_archivo']['tmp_name'], $target_file)) {
        $capture_path = 'img/captures/' . $new_file_name;
    }
}

// 4. Preparar Desglose (similar a generar_cobro_manual.php)
$cargos_a_procesar = [];
if (isset($_POST['desglose_mensualidad_activado']) && $_POST['desglose_mensualidad_activado'] == '1') {
    $monto = floatval(str_replace(',', '.', $_POST['monto_mensualidad'] ?? 0));
    $meses = intval($_POST['meses_mensualidad'] ?? 1);
    if ($monto > 0) $cargos_a_procesar[] = ['monto' => $monto, 'just' => "Mensualidad ($meses mes/es) - $justificacion_global"];
}
if (isset($_POST['desglose_instalacion_activado']) && $_POST['desglose_instalacion_activado'] == '1') {
    $monto = floatval(str_replace(',', '.', $_POST['monto_instalacion'] ?? 0));
    if ($monto > 0) $cargos_a_procesar[] = ['monto' => $monto, 'just' => "Instalación - $justificacion_global"];
}
if (isset($_POST['desglose_prorrateo_activado']) && $_POST['desglose_prorrateo_activado'] == '1') {
    $monto = floatval(str_replace(',', '.', $_POST['monto_prorrateo'] ?? 0));
    if ($monto > 0) $cargos_a_procesar[] = ['monto' => $monto, 'just' => "Prorrateo - $justificacion_global"];
}
if (isset($_POST['desglose_abono_activado']) && $_POST['desglose_abono_activado'] == '1') {
    $monto = floatval(str_replace(',', '.', $_POST['monto_abono'] ?? 0));
    if ($monto > 0) $cargos_a_procesar[] = ['monto' => $monto, 'just' => "Abono a Cuenta - $justificacion_global"];
}
if (isset($_POST['desglose_equipo_activado']) && $_POST['desglose_equipo_activado'] == '1') {
    $monto = floatval(str_replace(',', '.', $_POST['monto_equipo'] ?? 0));
    if ($monto > 0) $cargos_a_procesar[] = ['monto' => $monto, 'just' => "Pago de Equipo - $justificacion_global"];
}

if (empty($cargos_a_procesar)) {
    echo json_encode(['success' => false, 'message' => 'Debe activar al menos un concepto en el desglose.']);
    exit;
}

// Comenzar Transacción
$conn->begin_transaction();

try {
    // 5. Si tenía grupo, eliminar los OTROS miembros del grupo para re-crear el breakdown
    if (!empty($id_grupo_pago)) {
        // Eliminar del historial primero (FK)
        $conn->query("DELETE FROM cobros_manuales_historial WHERE id_cobro_cxc IN (SELECT id_cobro FROM cuentas_por_cobrar WHERE id_grupo_pago = '$id_grupo_pago' AND id_cobro != $id_cobro_primario)");
        // Eliminar de cxc
        $conn->query("DELETE FROM cuentas_por_cobrar WHERE id_grupo_pago = '$id_grupo_pago' AND id_cobro != $id_cobro_primario");
    } else {
        // Si no tenía grupo y ahora hay varios conceptos, creamos un UUID
        if (count($cargos_a_procesar) > 1) {
            $id_grupo_pago = bin2hex(random_bytes(16));
        }
    }

    // 6. El primer concepto actualiza el registro primario
    $primer_cargo = array_shift($cargos_a_procesar);
    $monto_p = $primer_cargo['monto'];
    $just_p = $primer_cargo['just'];

    $sql_u = "UPDATE cuentas_por_cobrar SET 
                monto_total = ?, 
                fecha_vencimiento = ?, 
                estado = ?, 
                referencia_pago = ?, 
                id_banco = ?, 
                capture_pago = ?, 
                id_grupo_pago = ?,
                fecha_pago = ?
              WHERE id_cobro = ?";
    $stmt = $conn->prepare($sql_u);
    $stmt->bind_param("dsssssssi", $monto_p, $fecha_vencimiento, $estado, $referencia_pago, $id_banco_pago, $capture_path, $id_grupo_pago, $fecha_pago, $id_cobro_primario);
    $stmt->execute();

    // Actualizar Historial del primario
    $conn->query("UPDATE cobros_manuales_historial SET 
                    autorizado_por = '" . $conn->real_escape_string($autorizado_por) . "', 
                    justificacion = '" . $conn->real_escape_string($just_p) . "', 
                    monto_cargado = $monto_p 
                  WHERE id_cobro_cxc = $id_cobro_primario");

    // 7. Insertar los conceptos restantes (si los hay)
    foreach ($cargos_a_procesar as $extra) {
        $monto_e = $extra['monto'];
        $just_e = $extra['just'];

        $sql_i = "INSERT INTO cuentas_por_cobrar (id_contrato, fecha_emision, fecha_vencimiento, monto_total, estado, referencia_pago, id_banco, id_grupo_pago, capture_pago, fecha_pago) 
                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt_i = $conn->prepare($sql_i);
        $stmt_i->bind_param("issdssssss", $id_contrato_principal, $fecha_emision, $fecha_vencimiento, $monto_e, $estado, $referencia_pago, $id_banco_pago, $id_grupo_pago, $capture_path, $fecha_pago);
        $stmt_i->execute();
        $new_id = $conn->insert_id;

        $conn->query("INSERT INTO cobros_manuales_historial (id_cobro_cxc, id_contrato, autorizado_por, justificacion, monto_cargado) 
                      VALUES ($new_id, $id_contrato_principal, '" . $conn->real_escape_string($autorizado_por) . "', '" . $conn->real_escape_string($just_e) . "', $monto_e)");
    }

    $conn->commit();
    echo json_encode(['success' => true, 'message' => 'Cobro actualizado correctamente.']);

} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}

$conn->close();
