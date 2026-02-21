<?php
// paginas/soporte/guardar_contrato_instalador.php
// Backend handler for saving installer contract registration

require_once '../conexion.php';

header('Content-Type: application/json');

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

        // Crear directorio si no existe
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $filePath = $uploadDir . $fileName;

        if (file_put_contents($filePath, $imgData)) {
            return $fileName;
        }
        return null;
    }

    // Función para guardar foto de evidencia
    function savePhoto($file)
    {
        if (empty($file) || $file['error'] != 0)
            return null;

        $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
        if (!in_array($file['type'], $allowedTypes)) {
            return null;
        }

        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        $fileName = 'evidencia_' . uniqid() . '.' . $ext;
        $uploadDir = '../../uploads/contratos/';

        // Crear directorio si no existe
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $filePath = $uploadDir . $fileName;

        if (move_uploaded_file($file['tmp_name'], $filePath)) {
            return 'uploads/contratos/' . $fileName;
        }
        return null;
    }

    try {
        $conn->begin_transaction();

        // Función para obtener/crear ID de Municipio
        function getMunicipioId($conn, $nombre)
        {
            $nombre = trim($conn->real_escape_string($nombre));
            if (empty($nombre))
                return 0;

            $sql = "SELECT id_municipio FROM municipio WHERE nombre_municipio = '$nombre' LIMIT 1";
            $res = $conn->query($sql);
            if ($res && $res->num_rows > 0) {
                return $res->fetch_assoc()['id_municipio'];
            } else {
                // Insertar nuevo
                $sqlIns = "INSERT INTO municipio (nombre_municipio) VALUES ('$nombre')";
                if ($conn->query($sqlIns)) {
                    return $conn->insert_id;
                }
            }
            return 0;
        }

        // Función para obtener/crear ID de Parroquia
        function getParroquiaId($conn, $nombre, $id_municipio)
        {
            $nombre = trim($conn->real_escape_string($nombre));
            if (empty($nombre) || empty($id_municipio))
                return 0;

            $sql = "SELECT id_parroquia FROM parroquia WHERE nombre_parroquia = '$nombre' AND id_municipio = $id_municipio LIMIT 1";
            $res = $conn->query($sql);
            if ($res && $res->num_rows > 0) {
                return $res->fetch_assoc()['id_parroquia'];
            } else {
                $sqlIns = "INSERT INTO parroquia (id_municipio, nombre_parroquia) VALUES ($id_municipio, '$nombre')";
                if ($conn->query($sqlIns)) {
                    return $conn->insert_id;
                }
            }
            return 0;
        }

        // Función para obtener/crear ID de Comunidad
        function getComunidadId($conn, $nombre, $id_parroquia)
        {
            $nombre = trim($conn->real_escape_string($nombre));
            if (empty($nombre) || empty($id_parroquia))
                return 0;

            $sql = "SELECT id_comunidad FROM comunidad WHERE nombre_comunidad = '$nombre' AND id_parroquia = $id_parroquia LIMIT 1";
            $res = $conn->query($sql);
            if ($res && $res->num_rows > 0) {
                return $res->fetch_assoc()['id_comunidad'];
            } else {
                $sqlIns = "INSERT INTO comunidad (id_parroquia, nombre_comunidad) VALUES ($id_parroquia, '$nombre')";
                if ($conn->query($sqlIns)) {
                    return $conn->insert_id;
                }
            }
            return 0;
        }
        // === VALIDACIÓN DE CAMPOS (REQUERIMIENTO USUARIO) ===
        $errors = [];
        $cedula = trim($conn->real_escape_string($_POST['cedula'] ?? ''));
        $nombre_completo = trim($conn->real_escape_string($_POST['nombre_completo'] ?? ''));
        $monto_instalacion = isset($_POST['monto_instalacion']) ? floatval($_POST['monto_instalacion']) : 0;

        if (empty($cedula))
            $errors[] = "La Cédula es obligatoria.";
        if (empty($nombre_completo))
            $errors[] = "El nombre es obligatorio.";
        if ($monto_instalacion <= 0)
            $errors[] = "El monto de instalación debe ser mayor a 0.";

        // Validar formato de Cédula
        if (!empty($cedula) && !preg_match('/^[VJEGP]\d+$/i', $cedula)) {
            $errors[] = "Formato de Cédula inválido (ej: V12345678).";
        }

        $ip_onu = trim($_POST['ip_onu'] ?? '');
        if (!empty($ip_onu) && $ip_onu !== '192.168.' && !preg_match('/^(?:\d{1,3}\.){3}\d{1,3}$/', $ip_onu)) {
            $errors[] = "Formato de IP ONU inválido.";
        }

        // Validar Teléfonos
        $telefono = trim($_POST['telefono'] ?? '');
        $telefono_sec = trim($_POST['telefono_secundario'] ?? '');
        if (!empty($telefono) && !preg_match('/^[0-9-+\s]{7,15}$/', $telefono)) {
            $errors[] = "Formato de Teléfono principal inválido.";
        }
        if (!empty($telefono_sec) && !preg_match('/^[0-9-+\s]{7,15}$/', $telefono_sec)) {
            $errors[] = "Formato de Teléfono secundario inválido.";
        }

        // Validar Correos
        $correo = trim($_POST['correo'] ?? '');
        $correo_adicional = trim($_POST['correo_adicional'] ?? '');
        if (!empty($correo) && !filter_var($correo, FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Formato de Correo principal inválido.";
        }
        if (!empty($correo_adicional) && !filter_var($correo_adicional, FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Formato de Correo adicional inválido.";
        }

        // Validar MAC / Serial
        $mac_onu = trim($_POST['mac_onu'] ?? '');
        if (!empty($mac_onu) && !preg_match('/^[A-Za-z0-9:.-]{8,20}$/', $mac_onu)) {
            $errors[] = "Formato de MAC o Serial ONU inválido.";
        }

        // Validar Potencias (dBm)
        $nap_tx = trim($_POST['nap_tx_power'] ?? '');
        $onu_rx = trim($_POST['onu_rx_power'] ?? '');
        $dbm_radio = trim($_POST['valor_conexion_dbm'] ?? '');
        if (!empty($nap_tx) && !preg_match('/^-?\d+(\.\d+)?$/', $nap_tx)) {
            $errors[] = "El valor NAP TX Power debe ser numérico.";
        }
        if (!empty($onu_rx) && !preg_match('/^-?\d+(\.\d+)?$/', $onu_rx)) {
            $errors[] = "El valor ONU RX Power debe ser numérico.";
        }
        if (!empty($dbm_radio) && !preg_match('/^-?\d+(\.\d+)?$/', $dbm_radio)) {
            $errors[] = "El valor de Conexión (dBm) debe ser numérico.";
        }

        if (!empty($errors)) {
            echo json_encode(['status' => 'error', 'msg' => implode('<br>', $errors)]);
            exit;
        }

        if (!empty($ip_onu)) {
            $sql_ip_onu = "SELECT id FROM contratos WHERE ip_onu = '$ip_onu' LIMIT 1";
            $res_ip_onu = $conn->query($sql_ip_onu);
            if ($res_ip_onu && $res_ip_onu->num_rows > 0) {
                echo json_encode(['status' => 'error', 'msg' => "La IP de ONU '$ip_onu' ya está registrada."]);
                exit;
            }
        }

        // === DATOS DEL CLIENTE ===
        $cedula = $conn->real_escape_string($_POST['cedula']);
        $nombre_completo = $conn->real_escape_string($_POST['nombre_completo']);
        $direccion = $conn->real_escape_string($_POST['direccion']);

        // Resolviendo IDs de Ubicaciones por Nombre
        $id_municipio = getMunicipioId($conn, $_POST['id_municipio'] ?? '');
        $id_parroquia = getParroquiaId($conn, $_POST['id_parroquia'] ?? '', $id_municipio);
        $id_comunidad = getComunidadId($conn, $_POST['id_comunidad'] ?? '', $id_parroquia);

        // Validar Plan y Vendedor (estos siguen viniendo como IDs)
        $id_plan = isset($_POST['id_plan']) ? intval($_POST['id_plan']) : 0;
        $id_vendedor = isset($_POST['id_vendedor']) ? intval($_POST['id_vendedor']) : 0;
        $telefono = $conn->real_escape_string($_POST['telefono']);
        $telefono_secundario = isset($_POST['telefono_secundario']) ? $conn->real_escape_string($_POST['telefono_secundario']) : '';
        $correo = isset($_POST['correo']) ? $conn->real_escape_string($_POST['correo']) : '';
        $correo_adicional = isset($_POST['correo_adicional']) ? $conn->real_escape_string($_POST['correo_adicional']) : '';

        // === INFORMACIÓN DE INSTALACIÓN Y PAGO ===
        $fecha_instalacion = $conn->real_escape_string($_POST['fecha_instalacion']);
        $tipo_instalacion = isset($_POST['tipo_instalacion']) ? $conn->real_escape_string($_POST['tipo_instalacion']) : '';
        $medio_pago = isset($_POST['medio_pago']) ? $conn->real_escape_string($_POST['medio_pago']) : '';
        $monto_instalacion = isset($_POST['monto_instalacion']) ? floatval($_POST['monto_instalacion']) : 0;
        $gastos_adicionales = isset($_POST['gastos_adicionales']) ? floatval($_POST['gastos_adicionales']) : 0;
        $dias_prorrateo = isset($_POST['dias_prorrateo']) ? intval($_POST['dias_prorrateo']) : 0;
        $monto_pagar = isset($_POST['monto_pagar']) ? floatval($_POST['monto_pagar']) : 0;
        $monto_pagado = isset($_POST['monto_pagado']) ? floatval($_POST['monto_pagado']) : 0;
        $moneda_pago = isset($_POST['moneda_pago']) ? $conn->real_escape_string($_POST['moneda_pago']) : 'USD';
        $observaciones = isset($_POST['observaciones']) ? $conn->real_escape_string($_POST['observaciones']) : '';

        // Calcular monto de prorrateo (simplificado, se puede mejorar)
        $monto_prorrateo_usd = 0;

        // === DETALLES TÉCNICOS ===
        $tipo_conexion = isset($_POST['tipo_conexion']) ? $conn->real_escape_string($_POST['tipo_conexion']) : '';
        $mac_onu = strtoupper(trim($_POST['mac_onu'] ?? ''));
        $mac_onu = $conn->real_escape_string($mac_onu);
        $ip_onu = trim($_POST['ip_onu'] ?? '');
        $ip_onu = ($ip_onu !== '192.168.') ? $conn->real_escape_string($ip_onu) : '';
        $ident_caja_nap = isset($_POST['ident_caja_nap']) ? $conn->real_escape_string($_POST['ident_caja_nap']) : '';
        $puerto_nap = isset($_POST['puerto_nap']) ? $conn->real_escape_string($_POST['puerto_nap']) : '';
        $nap_tx_power = isset($_POST['nap_tx_power']) ? $conn->real_escape_string($_POST['nap_tx_power']) : '';
        $onu_rx_power = isset($_POST['onu_rx_power']) ? $conn->real_escape_string($_POST['onu_rx_power']) : '';
        $distancia_drop = isset($_POST['distancia_drop']) ? $conn->real_escape_string($_POST['distancia_drop']) : '';
        $instalador = $conn->real_escape_string($_POST['instalador']);
        $evidencia_fibra = isset($_POST['evidencia_fibra']) ? $conn->real_escape_string($_POST['evidencia_fibra']) : '';
        $punto_acceso = isset($_POST['punto_acceso']) ? $conn->real_escape_string($_POST['punto_acceso']) : '';
        $valor_conexion_dbm = isset($_POST['valor_conexion_dbm']) ? $conn->real_escape_string($_POST['valor_conexion_dbm']) : '';
        $num_presinto_odn = isset($_POST['num_presinto_odn']) ? $conn->real_escape_string($_POST['num_presinto_odn']) : '';

        // === EVIDENCIAS (Firmas y Foto) ===
        $firma_cliente_b64 = isset($_POST['firma_cliente_data']) ? $_POST['firma_cliente_data'] : '';
        $firma_tecnico_b64 = isset($_POST['firma_tecnico_data']) ? $_POST['firma_tecnico_data'] : '';

        $firma_cliente = saveSignature($firma_cliente_b64, 'cliente');
        $firma_tecnico = saveSignature($firma_tecnico_b64, 'tecnico');

        $evidencia_foto = null;
        if (isset($_FILES['evidencia_foto_file'])) {
            $evidencia_foto = savePhoto($_FILES['evidencia_foto_file']);
        }

        $evidencia_documento = null;
        if (isset($_FILES['evidencia_documento_file'])) {
            $evidencia_documento = savePhoto($_FILES['evidencia_documento_file']);
        }

        // Valores por defecto si no vienen en el POST
        $id_olt = !empty($_POST['id_olt']) ? intval($_POST['id_olt']) : 1;
        $id_pon = !empty($_POST['id_pon']) ? intval($_POST['id_pon']) : 0;
        $estado = !empty($_POST['estado']) ? $conn->real_escape_string($_POST['estado']) : 'ACTIVO';

        // === INSERT EN BASE DE DATOS ===
        // === INSERT EN BASE DE DATOS ===

        // Manejo de Firma Remota
        $generate_link = isset($_POST['generate_link']) && $_POST['generate_link'] === '1';
        $token_firma = null;
        $estado_firma = 'COMPLETADO';

        if ($generate_link) {
            $token_firma = bin2hex(random_bytes(32));
            $estado_firma = 'PENDIENTE';
            // Ignoramos firmas enviadas si es generación de link
            $firma_cliente = null;
            $firma_tecnico = null;
        }

        $sql = "INSERT INTO contratos (
            cedula, nombre_completo, id_municipio, id_parroquia, id_comunidad, id_plan, id_vendedor,
            direccion, telefono, telefono_secundario, correo, correo_adicional, fecha_instalacion,
            tipo_instalacion, medio_pago, monto_instalacion, gastos_adicionales, dias_prorrateo, monto_prorrateo_usd,
            monto_pagar, monto_pagado, moneda_pago, observaciones,
            tipo_conexion, mac_onu, ip_onu, ident_caja_nap, puerto_nap,
            nap_tx_power, onu_rx_power, distancia_drop, instalador, evidencia_fibra,
            punto_acceso, valor_conexion_dbm, num_presinto_odn, evidencia_foto, evidencia_documento,
            firma_cliente, firma_tecnico, id_olt, id_pon, estado, token_firma, estado_firma
        ) VALUES (
            '$cedula', '$nombre_completo', '$id_municipio', '$id_parroquia', '$id_comunidad', '$id_plan', '$id_vendedor',
            '$direccion', '$telefono', '$telefono_secundario', '$correo', '$correo_adicional', '$fecha_instalacion',
            '$tipo_instalacion', '$medio_pago', '$monto_instalacion', '$gastos_adicionales', '$dias_prorrateo', '$monto_prorrateo_usd',
            '$monto_pagar', '$monto_pagado', '$moneda_pago', '$observaciones',
            '$tipo_conexion', '$mac_onu', '$ip_onu', '$ident_caja_nap', '$puerto_nap',
            '$nap_tx_power', '$onu_rx_power', '$distancia_drop', '$instalador', '$evidencia_fibra',
            '$punto_acceso', '$valor_conexion_dbm', '$num_presinto_odn', '$evidencia_foto', '$evidencia_documento',
            '$firma_cliente', '$firma_tecnico', '$id_olt', '$id_pon', '$estado', " . ($token_firma ? "'$token_firma'" : "NULL") . ", '$estado_firma'
        )";

        if (!$conn->query($sql)) {
            throw new Exception("Error SQL: " . $conn->error);
        }

        $id_contrato = $conn->insert_id;

        // Si es generación de link, retornamos respuesta especial
        if ($generate_link) {
            $conn->commit();
            $link = "firmar_remoto.php?token=" . $token_firma . "&type=contrato";
            echo json_encode(['status' => 'success', 'link' => $link, 'msg' => 'Contrato guardado temporalmente. Comparta el enlace.', 'id' => $id_contrato]);
            exit;
        }

        // REGISTRO DE DEUDOR SI HAY SALDO PENDIENTE
        $saldo_pendiente = $monto_pagar - $monto_pagado;

        if ($saldo_pendiente > 0) {
            $sql_deudor = "INSERT INTO clientes_deudores (id_contrato, monto_total, monto_pagado, saldo_pendiente, estado) 
                          VALUES (?, ?, ?, ?, 'PENDIENTE')";
            $stmt_deudor = $conn->prepare($sql_deudor);
            $stmt_deudor->bind_param("iddd", $id_contrato, $monto_pagar, $monto_pagado, $saldo_pendiente);
            $stmt_deudor->execute();
            $stmt_deudor->close();
        }

        $conn->commit();

        // ---------------------------------------------------------
        // ENVIO AUTOMÁTICO DE CORREO CON CONTRATO PDF
        // ---------------------------------------------------------
        $email_sent = false;
        $pdf_path = '';

        if (!empty($correo)) {
            // 1. Generar el PDF guardándolo en el servidor
            // Determinamos la URL base actual para llamar al generador
            $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
            $host = $_SERVER['HTTP_HOST'];
            $path = dirname($_SERVER['PHP_SELF']);
            // Subimos un nivel ../soporte -> ../reportes_pdf
            $pdf_url = $protocol . "://" . $host . str_replace('soporte', 'reportes_pdf', $path) . "/generar_contrato_pdf.php?id_contrato=" . $id_contrato . "&save_to_file=1";

            // Usamos curl para generar el PDF
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $pdf_url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Solo para dev local
            $response = curl_exec($ch);
            curl_close($ch);

            $resp_json = json_decode($response, true);

            if ($resp_json && isset($resp_json['status']) && $resp_json['status'] == 'success') {
                $pdf_path = $resp_json['path']; // Ruta relativa guardada por el script

                // Convertir ruta relativa a absoluta para PHPMailer
                // El script PDF guarda en ../../uploads... desde reportes_pdf
                // Aquí estamos en soporte, ../../uploads es lo mismo.
                // Ajustamos la ruta para que sea relativa a ESTE script (guardar_contrato)
                // Ruta relativa guardada: ../../uploads/contratos/pdf/Nombre.pdf
                // Como estamos en soporte, ../../uploads funciona igual.

                $pdf_absolute_path = realpath(__DIR__ . '/../../') . str_replace('../../', '/', $pdf_path);


                // 2. Enviar el Correo
                require_once 'enviar_contrato_email.php';
                $email_sent = enviarContratoEmail($correo, $nombre_completo, $pdf_absolute_path);
            }
        }

        echo json_encode(['status' => 'success', 'msg' => 'Contrato registrado correctamente. ' . ($email_sent ? 'Correo enviado.' : 'Correo no enviado.'), 'id' => $id_contrato]);

    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['status' => 'error', 'msg' => $e->getMessage()]);
    }

} else {
    echo json_encode(['status' => 'error', 'msg' => 'Método no permitido']);
}

$conn->close();
?>