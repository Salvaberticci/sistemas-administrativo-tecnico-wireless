<?php
// Script para insertar una cuenta por cobrar generada manualmente y registrar el detalle en el historial.
// Este script es el procesador del formulario que se encuentra ahora dentro de gestion_cobros.php (modal).

require_once '../conexion.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
// 1. Obtener y sanitizar datos del formulario (GLOBALES)
    $id_contrato_principal = isset($_POST['id_contrato']) ? intval($_POST['id_contrato']) : 0;
    $monto_total_declarado = isset($_POST['monto']) ? floatval(str_replace(',', '.', $_POST['monto'])) : 0.0;
    
    $referencia_pago = isset($_POST['referencia_pago']) ? $conn->real_escape_string(trim($_POST['referencia_pago'])) : '';
    $id_banco_pago = isset($_POST['id_banco_pago']) ? intval($_POST['id_banco_pago']) : 0;

    // CAMPOS DE JUSTIFICACIÓN
    $autorizado_por = isset($_POST['autorizado_por']) ? $conn->real_escape_string($_POST['autorizado_por']) : '';
    $justificacion = isset($_POST['justificacion']) ? $conn->real_escape_string($_POST['justificacion']) : 'No especificada.';

    // 1.5 Manejo de archivo (Capture)
    $capture_path = '';
    if (isset($_FILES['capture_archivo']) && $_FILES['capture_archivo']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = '../../uploads/pagos/'; // Ajustamos ruta relativa al script actual
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        $file_extension = pathinfo($_FILES['capture_archivo']['name'], PATHINFO_EXTENSION);
        $file_name = uniqid('pago_manual_') . '.' . $file_extension;
        $target_file = $upload_dir . $file_name;

        // Validar tipo de imagen
        $check = getimagesize($_FILES['capture_archivo']['tmp_name']);
        if ($check !== false) {
            if (move_uploaded_file($_FILES['capture_archivo']['tmp_name'], $target_file)) {
                $capture_path = 'uploads/pagos/' . $file_name; // Guardamos ruta relativa al ROOT del sistema
            }
        }
    }

    // 2. Definir fechas y estados por defecto (Es un capture de pago)
    $fecha_emision = date('Y-m-d');
    $fecha_vencimiento = date('Y-m-d'); 
    $fecha_pago = date('Y-m-d'); // Todo nace pagado hoy
    $estado = 'PAGADO'; // Requisito: Todo cargo manual/desglose nace pagado.

    if ($id_contrato_principal > 0 && $monto_total_declarado > 0 && !empty($referencia_pago) && $id_banco_pago > 0) {

        // 3. RECUPERAR DATOS DEL DESGLOSE
        $cargos_a_procesar = []; // Array de sub-cargos a insertar
        $sumatoria_backend = 0;

        // --- Mensualidad Principal ---
        if (isset($_POST['desglose_mensualidad_activado']) && $_POST['desglose_mensualidad_activado'] == '1') {
            $monto = floatval(str_replace(',', '.', $_POST['monto_mensualidad'] ?? 0));
            $meses = intval($_POST['meses_mensualidad'] ?? 1);
            if ($monto > 0) {
                $cargos_a_procesar[] = [
                    'id_contrato' => $id_contrato_principal,
                    'monto' => $monto, // Frontend ya mandaba el total
                    'justificacion' => "[MENSUALIDAD] Mensualidad ($meses mes/es) - " . $justificacion
                ];
                $sumatoria_backend += $monto;
            }
        }

        // --- Instalación ---
        if (isset($_POST['desglose_instalacion_activado']) && $_POST['desglose_instalacion_activado'] == '1') {
            $monto = floatval(str_replace(',', '.', $_POST['monto_instalacion'] ?? 0));
            if ($monto > 0) {
                $cargos_a_procesar[] = [
                    'id_contrato' => $id_contrato_principal,
                    'monto' => $monto,
                    'justificacion' => "[INSTALACION] Instalación - " . $justificacion
                ];
                $sumatoria_backend += $monto;
            }
        }

        // --- Prorrateo ---
        if (isset($_POST['desglose_prorrateo_activado']) && $_POST['desglose_prorrateo_activado'] == '1') {
            $monto = floatval(str_replace(',', '.', $_POST['monto_prorrateo'] ?? 0));
            if ($monto > 0) {
                $cargos_a_procesar[] = [
                    'id_contrato' => $id_contrato_principal,
                    'monto' => $monto,
                    'justificacion' => "[PRORRATEO] Prorrateo - " . $justificacion
                ];
                $sumatoria_backend += $monto;
            }
        }

        // --- Equipo ---
        if (isset($_POST['desglose_equipo_activado']) && $_POST['desglose_equipo_activado'] == '1') {
            $monto = floatval(str_replace(',', '.', $_POST['monto_equipo'] ?? 0));
            if ($monto > 0) {
                $cargos_a_procesar[] = [
                    'id_contrato' => $id_contrato_principal,
                    'monto' => $monto,
                    'justificacion' => "[EQUIPOS] Pago de Equipo - " . $justificacion
                ];
                $sumatoria_backend += $monto;
            }
        }

        // --- Mensualidad Extra (Otros Contratos) ---
        if (isset($_POST['desglose_extra_activado']) && $_POST['desglose_extra_activado'] == '1') {
            $extras_contratos = $_POST['extra_contrato'] ?? [];
            $extras_montos = $_POST['extra_monto'] ?? [];
            $extras_meses = $_POST['extra_meses'] ?? [];

            for ($i = 0; $i < count($extras_contratos); $i++) {
                $id_c_extra = intval($extras_contratos[$i] ?? 0);
                $monto_extra = floatval(str_replace(',', '.', $extras_montos[$i] ?? 0));
                $meses_extra = intval($extras_meses[$i] ?? 1);

                if ($id_c_extra > 0 && $monto_extra > 0) {
                    $cargos_a_procesar[] = [
                        'id_contrato' => $id_c_extra, // OJO: Va al contrato extra, no al principal
                        'monto' => $monto_extra,
                        'justificacion' => "[EXTRA] Mens. Extra ($meses_extra mes/es) (Pagado en Ref $referencia_pago) - " . $justificacion
                    ];
                    $sumatoria_backend += $monto_extra;
                }
            }
        }

        // 4. Verificación de integridad - RELAJADA
        // Ahora permitimos que la sumatoria no sea exacta.
        // Si falta dinero, se registrará una deuda.
        // Si sobra, se notificará pero se procesará.
        
        if (count($cargos_a_procesar) === 0) {
            header("Location: gestion_mensualidades.php?maintenance_done=1&message=" . urlencode("Error: No se seleccionó ningún concepto para cobrar.") . "&class=danger");
            exit();
        }

        if (count($cargos_a_procesar) === 0) {
            header("Location: gestion_mensualidades.php?maintenance_done=1&message=" . urlencode("Error: No se activó ningún concepto en el desglose.") . "&class=danger");
            exit();
        }


        $conn->begin_transaction();

        try {
            $registros_exitosos = 0;
            // Generar UUID para el grupo de cobros
            $id_grupo_pago = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
                mt_rand(0, 0xffff), mt_rand(0, 0xffff),
                mt_rand(0, 0xffff),
                mt_rand(0, 0x0fff) | 0x4000,
                mt_rand(0, 0x3fff) | 0x8000,
                mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
            );

            // 5. INSERTAR CADA CARGO EN EL BUCLE
            foreach ($cargos_a_procesar as $cargo) {
                $c_id_contrato = $cargo['id_contrato'];
                $c_monto = $cargo['monto'];
                $c_justificacion_base = $cargo['justificacion'];
                $is_mensualidad = (strpos($c_justificacion_base, '[MENSUALIDAD]') !== false || strpos($c_justificacion_base, '[EXTRA]') !== false);
                $num_meses = 1;
                
                // Extraer número de meses si es mensualidad o extra para el bucle
                if ($is_mensualidad) {
                    if (preg_match('/\((\d+) mes\/es\)/', $c_justificacion_base, $matches)) {
                        $num_meses = intval($matches[1]);
                    }
                }

                $monto_por_mes = ($num_meses > 1) ? round($c_monto / $num_meses, 2) : $c_monto;

                for ($m = 0; $m < $num_meses; $m++) {
                    $target_time = strtotime("+$m month");
                    $mes_nombre = strftime('%B %Y', $target_time);
                    // Fallback for systems where strftime is deprecated or locales aren't set
                    if (!$mes_nombre || strpos($mes_nombre, '%') !== false) {
                        $mes_nombre = date('F Y', $target_time);
                    }
                    
                    // Traducción básica si es necesario (opcional pero ayuda)
                    $mes_es = ['January' => 'Enero', 'February' => 'Febrero', 'March' => 'Marzo', 'April' => 'Abril', 'May' => 'Mayo', 'June' => 'Junio', 'July' => 'Julio', 'August' => 'Agosto', 'September' => 'Septiembre', 'October' => 'Octubre', 'November' => 'Noviembre', 'December' => 'Diciembre'];
                    foreach($mes_es as $en => $es) $mes_nombre = str_ireplace($en, $es, $mes_nombre);

                    if ($is_mensualidad) {
                        $base_text = str_replace(['[MENSUALIDAD] ', '[EXTRA] '], '', $c_justificacion_base);
                        $tag = (strpos($c_justificacion_base, '[EXTRA]') !== false) ? '[EXTRA]' : '[MENSUALIDAD]';
                        
                        // Proyectamos fechas exactas sumando meses a la fecha base (hoy)
                        $loop_fecha_emision = date('Y-m-d', strtotime("+$m month"));
                        $loop_fecha_vencimiento = date('Y-m-d', strtotime("+" . ($m + 1) . " month"));
                        
                        if ($m > 0) {
                            $loop_justificacion = $conn->real_escape_string("$tag [$mes_nombre] - Adelanto de: " . $base_text);
                        } else {
                            $loop_justificacion = $conn->real_escape_string("$tag [$mes_nombre] - " . $base_text);
                        }
                    } else {
                        // Si es Instalación, Equipo, Prorrateo o Genérico
                        $loop_fecha_emision = $fecha_emision;
                        $loop_fecha_vencimiento = $fecha_vencimiento;
                        $loop_justificacion = $conn->real_escape_string($c_justificacion_base);
                    }

                    $sql_cxc = "INSERT INTO cuentas_por_cobrar (
                        id_contrato, 
                        fecha_emision, 
                        fecha_vencimiento, 
                        monto_total, 
                        estado,
                        fecha_pago,
                        referencia_pago,
                        id_banco,
                        id_grupo_pago,
                        capture_pago,
                        id_plan_cobrado
                    ) VALUES (
                        '$c_id_contrato', 
                        '$loop_fecha_emision', 
                        '$loop_fecha_vencimiento', 
                        '$monto_por_mes', 
                        '$estado',
                        '$fecha_pago',
                        '$referencia_pago',
                        '$id_banco_pago',
                        '$id_grupo_pago',
                        '$capture_path',
                        (SELECT id_plan FROM contratos WHERE id = '$c_id_contrato' LIMIT 1)
                    )";

                    if ($conn->query($sql_cxc) === TRUE) {
                        $id_cobro_cxc = $conn->insert_id;

                        // INSERTAR HISTORIAL
                        $sql_historial = "INSERT INTO cobros_manuales_historial (
                            id_cobro_cxc, 
                            id_contrato, 
                            autorizado_por, 
                            justificacion, 
                            monto_cargado
                        ) VALUES (
                            '$id_cobro_cxc', 
                            '$c_id_contrato', 
                            '$autorizado_por', 
                            '$loop_justificacion', 
                            '$c_monto'
                        )";

                        if (!$conn->query($sql_historial)) {
                            throw new Exception("Error al registrar historial: " . $conn->error);
                        }

                        $registros_exitosos++;
                    } else {
                        throw new Exception("Error al registrar CxC: " . $conn->error);
                    }
                }
            } // Fin foreach

            // 6. LÓGICA DE DEUDA AUTOMÁTICA
            $saldo_deudor = round($sumatoria_backend - $monto_total_declarado, 2);
            if ($saldo_deudor > 0) {
                $notas_deuda = "Generado automáticamente desde Cobro Manual (Ref: $referencia_pago). Justificación: " . $justificacion;
                $sql_deudor = "INSERT INTO clientes_deudores (
                    id_contrato, 
                    monto_total, 
                    monto_pagado, 
                    saldo_pendiente, 
                    estado,
                    notas
                ) VALUES (
                    '$id_contrato_principal', 
                    '$sumatoria_backend', 
                    '$monto_total_declarado', 
                    '$saldo_deudor', 
                    'PENDIENTE',
                    '$notas_deuda'
                )";
                
                if (!$conn->query($sql_deudor)) {
                    throw new Exception("Error al registrar en lista de deudores: " . $conn->error);
                }
            }

            $conn->commit();
            
            $status_msg = "";
            if ($saldo_deudor > 0) {
                $status_msg = " [SALDO PENDIENTE: $$saldo_deudor registrado en lista de deudores]";
            } elseif ($saldo_deudor < 0) {
                $status_msg = " [AVISO: Sobrante de $" . abs($saldo_deudor) . " no asignado]";
            }

            $message = "Capture procesado con éxito. Se generaron $registros_exitosos cobros (Ref: $referencia_pago) por un total de $$monto_total_declarado.$status_msg";
            $class = "success";

        } catch (Exception $e) {
            $conn->rollback();
            $message = "ERROR Crítico: " . $e->getMessage();
            $class = "danger";
        }
    } else {
        $message = "Error: Faltan datos obligatorios para registrar el cobro manual.";
        $class = "danger";
    }

    // $conn->close();

    // Redirigir siempre a gestion_mensualidades.php para mostrar el mensaje de éxito/error en la lista.
    header("Location: gestion_mensualidades.php?message=" . urlencode($message) . "&class=" . $class);
    // exit();
} else {
    // Si acceden directamente al procesador, los enviamos a la lista
    header("Location: gestion_mensualidades.php");
    exit();
}
?>