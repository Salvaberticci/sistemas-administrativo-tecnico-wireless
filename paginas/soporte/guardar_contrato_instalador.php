<?php
// paginas/soporte/guardar_contrato_instalador.php
// Backend handler for saving installer contract registration

require_once '../conexion.php';

header('Content-Type: application/json');

// Manejo de errores para respuestas JSON
function jsonErrorHandler($errno, $errstr, $errfile, $errline)
{
    if (ob_get_length())
        ob_clean();
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'msg' => "PHP Error: $errstr en $errfile:$errline"]);
    exit;
}
set_error_handler("jsonErrorHandler");

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // Función para guardar firmas
    function saveSignature($base64_string, $prefix)
    {
        if (empty($base64_string))
            return null;
        $data = explode(',', $base64_string);
        if (count($data) < 2)
            return null;
        $imgData = base64_decode($data[1]);
        $fileName = $prefix . '_' . uniqid() . '.png';
        $uploadDir = '../../uploads/firmas/';
        if (!file_exists($uploadDir))
            mkdir($uploadDir, 0755, true);
        if (file_put_contents($uploadDir . $fileName, $imgData))
            return $fileName;
        return null;
    }

    // Función para guardar foto de evidencia
    function savePhoto($file, $prefix = 'evidencia')
    {
        if (empty($file) || $file['error'] != 0)
            return null;
        $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
        if (!in_array($file['type'], $allowedTypes))
            return null;
        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        $fileName = $prefix . '_' . uniqid() . '.' . $ext;
        $uploadDir = '../../uploads/contratos/';
        if (!file_exists($uploadDir))
            mkdir($uploadDir, 0755, true);
        if (move_uploaded_file($file['tmp_name'], $uploadDir . $fileName))
            return 'uploads/contratos/' . $fileName;
        return null;
    }

    try {
        $conn->begin_transaction();

        // 1. CAPTURA Y SANEO DE DATOS
        $cedula = trim($conn->real_escape_string($_POST['cedula'] ?? ''));
        $nombre_completo = trim($conn->real_escape_string($_POST['nombre_completo'] ?? ''));
        $telefono = trim($conn->real_escape_string($_POST['telefono'] ?? ''));
        $correo = trim($conn->real_escape_string($_POST['correo'] ?? ''));
        $municipio_texto = $conn->real_escape_string($_POST['id_municipio'] ?? '');
        $parroquia_texto = $conn->real_escape_string($_POST['id_parroquia'] ?? '');
        $id_plan = !empty($_POST['id_plan']) ? intval($_POST['id_plan']) : null;
        $vendedor_texto = $conn->real_escape_string($_POST['vendedor_texto'] ?? '');
        $direccion = $conn->real_escape_string($_POST['direccion'] ?? '');
        $fecha_instalacion = $conn->real_escape_string($_POST['fecha_instalacion'] ?? '');

        // El nombre en el HTML es estado_contrato para coincidir con nuevo.php
        $estado = $conn->real_escape_string($_POST['estado_contrato'] ?? 'ACTIVO');

        $id_olt = !empty($_POST['id_olt']) ? intval($_POST['id_olt']) : null;
        $id_pon = !empty($_POST['id_pon']) ? intval($_POST['id_pon']) : null;

        $telefono_secundario = trim($conn->real_escape_string($_POST['telefono_secundario'] ?? ''));
        $correo_adicional = trim($conn->real_escape_string($_POST['correo_adicional'] ?? ''));
        $observaciones = $conn->real_escape_string($_POST['observaciones'] ?? '');

        // Tipo de conexión es el campo principal ahora
        $tipo_conexion = $conn->real_escape_string($_POST['tipo_conexion'] ?? '');
        // Tipo de instalación se sincroniza con tipo de conexión si no viene
        $tipo_instalacion = $conn->real_escape_string($_POST['tipo_instalacion'] ?? $tipo_conexion);

        $monto_instalacion = floatval($_POST['monto_instalacion'] ?? 0);
        $gastos_adicionales = floatval($_POST['gastos_adicionales'] ?? 0);
        $monto_pagar = floatval($_POST['monto_pagar'] ?? 0);
        $monto_pagado = floatval($_POST['monto_pagado'] ?? 0);
        $medio_pago = $conn->real_escape_string($_POST['medio_pago'] ?? '');
        $moneda_pago = $conn->real_escape_string($_POST['moneda_pago'] ?? 'USD');

        // PRORRATEO
        $incluye_prorrateo = isset($_POST['incluye_prorrateo']) && $_POST['incluye_prorrateo'] === 'SI';
        if ($incluye_prorrateo) {
            $plan_prorrateo_nombre = $conn->real_escape_string($_POST['plan_prorrateo_nombre'] ?? '');
            $dias_prorrateo = intval($_POST['dias_prorrateo'] ?? 0);
            $monto_prorrateo_usd = floatval($_POST['monto_prorrateo_usd'] ?? 0);
        } else {
            $plan_prorrateo_nombre = null;
            $dias_prorrateo = 0;
            $monto_prorrateo_usd = 0;
        }

        $mac_onu = strtoupper(trim($conn->real_escape_string($_POST['mac_onu'] ?? '')));
        $ip_onu = trim($conn->real_escape_string($_POST['ip_onu'] ?? ''));

        // Manejar duplicidad de campos Nap/Puerto
        $ident_caja_nap = $conn->real_escape_string($_POST['ident_caja_nap_tecnico'] ?? $_POST['ident_caja_nap'] ?? '');
        $puerto_nap = $conn->real_escape_string($_POST['puerto_nap_tecnico'] ?? $_POST['puerto_nap'] ?? '');

        $nap_tx_power = $conn->real_escape_string($_POST['nap_tx_power'] ?? '');
        $onu_rx_power = $conn->real_escape_string($_POST['onu_rx_power'] ?? '');
        $distancia_drop = $conn->real_escape_string($_POST['distancia_drop'] ?? '');

        // Instaladores[] es un array en el HTML de nuevo.php
        $instalador_raw = $_POST['instaladores'] ?? '';
        $instalador = is_array($instalador_raw) ? implode(', ', $instalador_raw) : $conn->real_escape_string($instalador_raw);

        $punto_acceso = $conn->real_escape_string($_POST['punto_acceso'] ?? '');
        $valor_conexion_dbm = $conn->real_escape_string($_POST['valor_conexion_dbm'] ?? '');
        $num_presinto_odn = $conn->real_escape_string($_POST['num_presinto_odn'] ?? '');

        // VALIDACIÓN
        $errors = [];
        if (empty($cedula))
            $errors[] = "La Cédula es obligatoria.";
        if (empty($nombre_completo))
            $errors[] = "El Nombre es obligatorio.";
        if ($monto_instalacion <= 0)
            $errors[] = "El monto de instalación debe ser mayor a 0.";
        if (!empty($ip_onu) && !preg_match('/^(?:\d{1,3}\.){3}\d{1,3}$/', $ip_onu))
            $errors[] = "IP ONU inválida.";

        if (!empty($errors)) {
            echo json_encode(['status' => 'error', 'msg' => implode('<br>', $errors)]);
            exit;
        }

        // VERIFICAR IP ÚNICA
        if (!empty($ip_onu)) {
            $sql_check = "SELECT id FROM contratos WHERE ip_onu = '$ip_onu' LIMIT 1";
            if ($conn->query($sql_check)->num_rows > 0) {
                echo json_encode(['status' => 'error', 'msg' => "La IP ONU '$ip_onu' ya existe."]);
                exit;
            }
        }

        // PROCESAR FIRMAS Y FOTOS
        $firma_cliente = saveSignature($_POST['firma_cliente_data'] ?? '', 'cliente');
        $firma_tecnico = saveSignature($_POST['firma_tecnico_data'] ?? '', 'tecnico');
        $evidencia_foto = savePhoto($_FILES['evidencia_foto'] ?? null, 'evidencia');
        $evidencia_documento = savePhoto($_FILES['evidencia_documento'] ?? null, 'doc');

        // INSERT
        $sql = "INSERT INTO contratos (
            cedula, nombre_completo, municipio_texto, parroquia_texto, id_plan, vendedor_texto, 
            direccion, telefono, telefono_secundario, correo, correo_adicional, fecha_instalacion, 
            tipo_instalacion, medio_pago, monto_instalacion, gastos_adicionales, plan_prorrateo_nombre, dias_prorrateo, monto_prorrateo_usd,
            monto_pagar, monto_pagado, moneda_pago, observaciones, 
            tipo_conexion, mac_onu, ip_onu, ident_caja_nap, puerto_nap, 
            nap_tx_power, onu_rx_power, distancia_drop, instalador, 
            punto_acceso, valor_conexion_dbm, num_presinto_odn, evidencia_foto, evidencia_documento, 
            firma_cliente, firma_tecnico, id_olt, id_pon, estado
        ) VALUES (
            '$cedula', '$nombre_completo', '$municipio_texto', '$parroquia_texto', " . ($id_plan ?: "NULL") . ", '$vendedor_texto', 
            '$direccion', '$telefono', '$telefono_secundario', '$correo', '$correo_adicional', '$fecha_instalacion', 
            '$tipo_instalacion', '$medio_pago', '$monto_instalacion', '$gastos_adicionales', " . ($plan_prorrateo_nombre ? "'$plan_prorrateo_nombre'" : "NULL") . ", $dias_prorrateo, $monto_prorrateo_usd,
            $monto_pagar, $monto_pagado, '$moneda_pago', '$observaciones', 
            '$tipo_conexion', '$mac_onu', '$ip_onu', '$ident_caja_nap', '$puerto_nap', 
            '$nap_tx_power', '$onu_rx_power', '$distancia_drop', '$instalador', 
            '$punto_acceso', '$valor_conexion_dbm', '$num_presinto_odn', " . ($evidencia_foto ? "'$evidencia_foto'" : "NULL") . ", " . ($evidencia_documento ? "'$evidencia_documento'" : "NULL") . ", 
            " . ($firma_cliente ? "'$firma_cliente'" : "NULL") . ", " . ($firma_tecnico ? "'$firma_tecnico'" : "NULL") . ", " . ($id_olt ?: "NULL") . ", " . ($id_pon ?: "NULL") . ", '$estado'
        )";

        if (!$conn->query($sql))
            throw new Exception("Error SQL: " . $conn->error);
        $id_contrato = $conn->insert_id;

        // SALDO PENDIENTE
        $saldo = round($monto_pagar - $monto_pagado, 2);
        if ($saldo > 0) {
            $conn->query("INSERT INTO clientes_deudores (id_contrato, monto_total, monto_pagado, saldo_pendiente, estado) 
                          VALUES ($id_contrato, $monto_pagar, $monto_pagado, $saldo, 'PENDIENTE')");
        }

        $conn->commit();
        echo json_encode(['status' => 'success', 'msg' => 'Contrato registrado correctamente.', 'id' => $id_contrato]);

    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['status' => 'error', 'msg' => $e->getMessage()]);
    }
} else {
    echo json_encode(['status' => 'error', 'msg' => 'Método no permitido']);
}
$conn->close();
?>