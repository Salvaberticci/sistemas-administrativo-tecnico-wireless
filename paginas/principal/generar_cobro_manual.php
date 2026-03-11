<?php
// Script para insertar una cuenta por cobrar generada manualmente y registrar el detalle en el historial.
// Este script es el procesador del formulario que se encuentra ahora dentro de gestion_cobros.php (modal).

require_once '../conexion.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
// 1. Obtener y sanitizar datos del formulario (GLOBALES)
    $id_contrato_principal = isset($_POST['id_contrato']) ? intval($_POST['id_contrato']) : 0;
    $monto_total_declarado = isset($_POST['monto']) ? floatval($_POST['monto']) : 0.0;
    
    $referencia_pago = isset($_POST['referencia_pago']) ? $conn->real_escape_string(trim($_POST['referencia_pago'])) : '';
    $id_banco_pago = isset($_POST['id_banco_pago']) ? intval($_POST['id_banco_pago']) : 0;

    // CAMPOS DE JUSTIFICACIÓN
    $autorizado_por = isset($_POST['autorizado_por']) ? $conn->real_escape_string($_POST['autorizado_por']) : '';
    $justificacion = isset($_POST['justificacion']) ? $conn->real_escape_string($_POST['justificacion']) : 'No especificada.';

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
            $monto = floatval($_POST['monto_mensualidad'] ?? 0);
            $meses = intval($_POST['meses_mensualidad'] ?? 1);
            if ($monto > 0) {
                $cargos_a_procesar[] = [
                    'id_contrato' => $id_contrato_principal,
                    'monto' => $monto,
                    'justificacion' => "Mensualidad ($meses mes/es) - " . $justificacion
                ];
                $sumatoria_backend += $monto;
            }
        }

        // --- Instalación ---
        if (isset($_POST['desglose_instalacion_activado']) && $_POST['desglose_instalacion_activado'] == '1') {
            $monto = floatval($_POST['monto_instalacion'] ?? 0);
            if ($monto > 0) {
                $cargos_a_procesar[] = [
                    'id_contrato' => $id_contrato_principal,
                    'monto' => $monto,
                    'justificacion' => "Instalación - " . $justificacion
                ];
                $sumatoria_backend += $monto;
            }
        }

        // --- Prorrateo ---
        if (isset($_POST['desglose_prorrateo_activado']) && $_POST['desglose_prorrateo_activado'] == '1') {
            $monto = floatval($_POST['monto_prorrateo'] ?? 0);
            if ($monto > 0) {
                $cargos_a_procesar[] = [
                    'id_contrato' => $id_contrato_principal,
                    'monto' => $monto,
                    'justificacion' => "Prorrateo - " . $justificacion
                ];
                $sumatoria_backend += $monto;
            }
        }

        // --- Abono ---
        if (isset($_POST['desglose_abono_activado']) && $_POST['desglose_abono_activado'] == '1') {
            $monto = floatval($_POST['monto_abono'] ?? 0);
            if ($monto > 0) {
                $cargos_a_procesar[] = [
                    'id_contrato' => $id_contrato_principal,
                    'monto' => $monto,
                    'justificacion' => "Abono a Cuenta - " . $justificacion
                ];
                $sumatoria_backend += $monto;
            }
        }

        // --- Equipo ---
        if (isset($_POST['desglose_equipo_activado']) && $_POST['desglose_equipo_activado'] == '1') {
            $monto = floatval($_POST['monto_equipo'] ?? 0);
            if ($monto > 0) {
                $cargos_a_procesar[] = [
                    'id_contrato' => $id_contrato_principal,
                    'monto' => $monto,
                    'justificacion' => "Pago de Equipo - " . $justificacion
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
                $monto_extra = floatval($extras_montos[$i] ?? 0);
                $meses_extra = intval($extras_meses[$i] ?? 1);

                if ($id_c_extra > 0 && $monto_extra > 0) {
                    $cargos_a_procesar[] = [
                        'id_contrato' => $id_c_extra, // OJO: Va al contrato extra, no al principal
                        'monto' => $monto_extra,
                        'justificacion' => "Mens. Extra (Pagado en Ref $referencia_pago) - " . $justificacion
                    ];
                    $sumatoria_backend += $monto_extra;
                }
            }
        }

        // 4. Doble Verificación de Sumatoria (Seguridad BDD)
        // Comparación flotante con epsilon
        if (abs($monto_total_declarado - $sumatoria_backend) > 0.01) {
             header("Location: gestion_mensualidades.php?maintenance_done=1&message=" . urlencode("Error de Integridad: La sumatoria del desglose ($$sumatoria_backend) no coincide con el total declarado ($$monto_total_declarado).") . "&class=danger");
             exit();
        }

        if (count($cargos_a_procesar) === 0) {
            header("Location: gestion_mensualidades.php?maintenance_done=1&message=" . urlencode("Error: No se activó ningún concepto en el desglose.") . "&class=danger");
            exit();
        }


        $conn->begin_transaction();

        try {
            $registros_exitosos = 0;

            // 5. INSERTAR CADA CARGO EN EL BUCLE
            foreach ($cargos_a_procesar as $cargo) {
                $c_id_contrato = $cargo['id_contrato'];
                $c_monto = $cargo['monto'];
                $c_justificacion = $conn->real_escape_string($cargo['justificacion']);

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
                    '$c_id_contrato', 
                    '$fecha_emision', 
                    '$fecha_vencimiento', 
                    '$c_monto', 
                    '$estado',
                    '$fecha_pago',
                    '$referencia_pago',
                    '$id_banco_pago'
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
                        '$c_justificacion', 
                        '$c_monto'
                    )";

                    if (!$conn->query($sql_historial)) {
                        throw new Exception("Error al registrar historial: " . $conn->error);
                    }

                    $registros_exitosos++;
                } else {
                    throw new Exception("Error al registrar CxC: " . $conn->error);
                }
            } // Fin foreach

            $conn->commit();
            $message = "Capture recibido correctamente. Se generaron $registros_exitosos cobros (Ref: $referencia_pago) por un total de $$monto_total_declarado.";
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