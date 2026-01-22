<?php

/**
 * Script para insertar nuevos datos de registro
 *
 * Este script recibe los nuevos datos del registro a través del método POST
 * y realiza la inserción en la base de datos. También permite la carga de archivos adjuntos.
 *
 * @author MRoblesDev
 * @version 1.0
 * https://github.com/mroblesdev
 *
 */
// Conexión a la base de datos
require '../conexion.php';

// 1. CAPTURA Y SANEO DE DATOS
// Se usa real_escape_string para prevenir inyección SQL.
$ip = $conn->real_escape_string($_POST['ip']);
$cedula = $conn->real_escape_string($_POST['cedula']);
$nombre_completo = $conn->real_escape_string($_POST['nombre_completo']);
$telefono = $conn->real_escape_string($_POST['telefono']);
$correo = $conn->real_escape_string($_POST['correo']);
$id_municipio = $conn->real_escape_string($_POST['id_municipio']);
$id_parroquia = $conn->real_escape_string($_POST['id_parroquia']);
// ⚠️ NUEVO CAMPO: Captura de id_comunidad
$id_comunidad = $conn->real_escape_string($_POST['id_comunidad']); 
$id_plan = $conn->real_escape_string($_POST['id_plan']);
$id_vendedor = $conn->real_escape_string($_POST['id_vendedor']);
$direccion = $conn->real_escape_string($_POST['direccion']);
$fecha_instalacion = $conn->real_escape_string($_POST['fecha_instalacion']);
$ident_caja_nap = $conn->real_escape_string($_POST['ident_caja_nap']);
$puerto_nap = $conn->real_escape_string($_POST['puerto_nap']);
$num_presinto_odn = $conn->real_escape_string($_POST['num_presinto_odn']);
$id_olt = $conn->real_escape_string($_POST['id_olt']);
$id_pon = $conn->real_escape_string($_POST['id_pon']?? null);
$estado = 'ACTIVO'; // Estado inicial por defecto

// NUEVOS CAMPOS ADMINISTRATIVOS Y TÉCNICOS
$telefono_secundario = $conn->real_escape_string($_POST['telefono_secundario'] ?? '');
$correo_adicional = $conn->real_escape_string($_POST['correo_adicional'] ?? '');
$observaciones = $conn->real_escape_string($_POST['observaciones'] ?? '');

$tipo_instalacion = $conn->real_escape_string($_POST['tipo_instalacion'] ?? '');
$monto_instalacion = floatval($_POST['monto_instalacion'] ?? 0);
$gastos_adicionales = floatval($_POST['gastos_adicionales'] ?? 0);
$monto_pagar = floatval($_POST['monto_pagar'] ?? 0);
$monto_pagado = floatval($_POST['monto_pagado'] ?? 0);

$medio_pago = $conn->real_escape_string($_POST['medio_pago'] ?? '');
$moneda_pago = $conn->real_escape_string($_POST['moneda_pago'] ?? 'USD');
$dias_prorrateo = intval($_POST['dias_prorrateo'] ?? 0);
// Calculamos monto prorrateo si fuera necesario, por ahora lo dejamos en 0 o se podría calcular
$monto_prorrateo_usd = 0; 

// DETALLES TÉCNICOS
$tipo_conexion = $conn->real_escape_string($_POST['tipo_conexion'] ?? '');
$mac_onu = $conn->real_escape_string($_POST['mac_onu'] ?? '');
$ip_onu = $conn->real_escape_string($_POST['ip_onu'] ?? '');
$nap_tx_power = $conn->real_escape_string($_POST['nap_tx_power'] ?? '');
$onu_rx_power = $conn->real_escape_string($_POST['onu_rx_power'] ?? '');
$distancia_drop = $conn->real_escape_string($_POST['distancia_drop'] ?? '');
$punto_acceso = $conn->real_escape_string($_POST['punto_acceso'] ?? '');
$valor_conexion_dbm = $conn->real_escape_string($_POST['valor_conexion_dbm'] ?? '');
$evidencia_fibra = $conn->real_escape_string($_POST['evidencia_fibra'] ?? '');

// PROCESAR FOTO EVIDENCIA
$evidencia_foto = null;
if (isset($_FILES['evidencia_foto']) && $_FILES['evidencia_foto']['error'] == 0) {
    $allowed = ['jpg', 'jpeg', 'png', 'gif'];
    $filename = $_FILES['evidencia_foto']['name'];
    $filetype = $_FILES['evidencia_foto']['type'];
    $filesize = $_FILES['evidencia_foto']['size'];
    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

    if (in_array($ext, $allowed)) {
        $new_filename = 'evidencia_' . uniqid() . '.' . $ext;
        $upload_dir = '../../uploads/contratos/';
        
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }

        if (move_uploaded_file($_FILES['evidencia_foto']['tmp_name'], $upload_dir . $new_filename)) {
            $evidencia_foto = 'uploads/contratos/' . $new_filename;
        }
    }
}

// Instaladores (array a string separado por comas)
$instaladores_array = $_POST['instaladores'] ?? [];
$instaladores_ids = implode(',', array_map('intval', $instaladores_array));

// --- FUNCIÓN PARA GUARDAR FIRMAS (Base64 a PNG) ---
function saveSignature($base64_string, $prefix) {
    if (empty($base64_string)) return null;
    $data = explode(',', $base64_string);
    if (count($data) < 2) return null;
    $imgData = base64_decode($data[1]);
    $fileName = $prefix . '_' . uniqid() . '.png';
    $uploadDir = '../../uploads/firmas/';
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    if (file_put_contents($uploadDir . $fileName, $imgData)) {
        return $fileName;
    }
    return null;
}

// Procesar Firmas
$firma_cliente_b64 = $_POST['firma_cliente_data'] ?? '';
$firma_tecnico_b64 = $_POST['firma_tecnico_data'] ?? '';
$firma_cliente = saveSignature($firma_cliente_b64, 'cliente');
$firma_tecnico = saveSignature($firma_tecnico_b64, 'tecnico');

// Variable para manejar mensajes de error específicos
$error_mensaje = null;
$resultado = false; // Inicializamos a falso por si hay errores de validación

// =========================================================================
// 2. <<<< VALIDACIÓN AGREGADA >>>>: Verificar si la IP ya existe 
// =========================================================================
$sql_check_ip = "SELECT * FROM contratos WHERE ip = ?";
$stmt_check_ip = $conn->prepare($sql_check_ip);
$stmt_check_ip->bind_param("s", $ip);
$stmt_check_ip->execute();
$stmt_check_ip->store_result();

if ($stmt_check_ip->num_rows > 0) {
    // Si la IP ya existe, configuramos el error. No se ejecuta el código de inserción.
    $error_mensaje = "Error de Validación: La dirección IP <strong>'{$ip}'</strong> ya se encuentra registrada en otro contrato.";
    $stmt_check_ip->close();
} else {
    // La IP es única, podemos continuar con la inserción del contrato.
    $stmt_check_ip->close(); 

    // 3. INSERCIÓN EN LA TABLA DE CONTRATOS
    $sql = "INSERT INTO contratos (
        ip, cedula, nombre_completo, telefono, correo, 
        id_municipio, id_parroquia, id_comunidad, id_plan, id_vendedor, 
        direccion, fecha_instalacion, estado, ident_caja_nap, puerto_nap, 
        num_presinto_odn, id_olt, id_pon, tipo_instalacion, monto_instalacion, 
        gastos_adicionales, monto_pagar, monto_pagado, instaladores,
        telefono_secundario, correo_adicional, medio_pago, moneda_pago, dias_prorrateo,
        monto_prorrateo_usd, observaciones, tipo_conexion, mac_onu, ip_onu,
        nap_tx_power, onu_rx_power, distancia_drop, punto_acceso, valor_conexion_dbm,
        evidencia_fibra, evidencia_foto, firma_cliente, firma_tecnico
    ) VALUES (
        ?, ?, ?, ?, ?, 
        ?, ?, ?, ?, ?, 
        ?, ?, ?, ?, ?, 
        ?, ?, ?, ?, ?, 
        ?, ?, ?, ?,
        ?, ?, ?, ?, ?,
        ?, ?, ?, ?, ?,
        ?, ?, ?, ?, ?,
        ?, ?, ?, ?
    )";

    $stmt = $conn->prepare($sql);
    
    // Total string: sssssiiiiissssssiisddds (23) + ssssids (7) + sssssssssss (11) + ss (2) = 43
    $stmt->bind_param("sssssiiiiissssssiisddds" . "ssssidsssssssssssss", 
        $ip, $cedula, $nombre_completo, $telefono, $correo, 
        $id_municipio, $id_parroquia, $id_comunidad, $id_plan, $id_vendedor, 
        $direccion, $fecha_instalacion, $estado, $ident_caja_nap, $puerto_nap, 
        $num_presinto_odn, $id_olt, $id_pon, $tipo_instalacion, $monto_instalacion, 
        $gastos_adicionales, $monto_pagar, $monto_pagado, $instaladores_ids,
        $telefono_secundario, $correo_adicional, $medio_pago, $moneda_pago, $dias_prorrateo,
        $monto_prorrateo_usd, $observaciones, $tipo_conexion, $mac_onu, $ip_onu,
        $nap_tx_power, $onu_rx_power, $distancia_drop, $punto_acceso, $valor_conexion_dbm,
        $evidencia_fibra, $evidencia_foto, $firma_cliente, $firma_tecnico
    );

    $resultado = $stmt->execute();
    $id_contrato = $conn->insert_id; // Obtiene el ID del contrato recién insertado
    $stmt->close();

    // ⚠️ REDIRECCIÓN A GENERAR EL PDF
       /* if ($resultado) { // Si el contrato se guardó correctamente
            $pdf_url = "../reportes_pdf/generar_contrato_pdf.php?id_contrato=" . $id_contrato;
            $conn->close(); // Cerrar conexión antes de la redirección
            header("Location: " . $pdf_url); // Envía la cabecera de redirección
            exit(); // Detiene el script para asegurar la redirección
        }*/
        
    // 5. GENERACIÓN DE LA PRIMERA CUENTA POR COBRAR (Solo si el contrato fue exitoso)
    if ($resultado && $id_contrato > 0) {
        
        // Obtener el monto total del plan
        $sql_monto = "SELECT monto FROM planes WHERE id_plan = ? LIMIT 1";
        $stmt_monto = $conn->prepare($sql_monto);
        $stmt_monto->bind_param("i", $id_plan);
        $stmt_monto->execute();
        $result_monto = $stmt_monto->get_result();
        
        if ($result_monto->num_rows > 0) {
            $row_monto = $result_monto->fetch_assoc();
            $monto_total = $row_monto['monto'];
            
            // Define fechas para la factura
            $fecha_emision = $fecha_instalacion; // La fecha de instalación es la de emisión
            $fecha_vencimiento = date('Y-m-d', strtotime($fecha_emision . ' + 30 days'));
            
            // Inserción en la tabla de cuentas por cobrar (cxc)
            $sql_cobro = "INSERT INTO cuentas_por_cobrar (id_contrato, fecha_emision, fecha_vencimiento, monto_total)
            VALUES (?, ?, ?, ?)";
            
            $stmt_cobro = $conn->prepare($sql_cobro);
            $stmt_cobro->bind_param("issd", 
                $id_contrato, 
                $fecha_emision, 
                $fecha_vencimiento, 
                $monto_total
            );

            if (!$stmt_cobro->execute()) {
                // El contrato se guardó ($resultado sigue siendo true), pero la factura falló.
                $error_mensaje = "ADVERTENCIA: Contrato guardado, pero falló la generación de la primera factura. (No se encontró el plan o error interno)";
            }
            $stmt_cobro->close();
        } else {
            // El contrato se guardó ($resultado sigue siendo true), pero no se encontró el plan.
            $error_mensaje = "ADVERTENCIA: Contrato guardado, pero no se pudo generar la factura: Plan de servicio no encontrado.";
        }
        
        $stmt_monto->close();
    }

    // 6. REGISTRO DE DEUDOR SI HAY SALDO PENDIENTE
    if ($resultado && $id_contrato > 0) {
        $saldo_pendiente = $monto_pagar - $monto_pagado;
        
        if ($saldo_pendiente > 0) {
            $sql_deudor = "INSERT INTO clientes_deudores (id_contrato, monto_total, monto_pagado, saldo_pendiente, estado) 
                          VALUES (?, ?, ?, ?, 'PENDIENTE')";
            $stmt_deudor = $conn->prepare($sql_deudor);
            $stmt_deudor->bind_param("iddd", $id_contrato, $monto_pagar, $monto_pagado, $saldo_pendiente);
            $stmt_deudor->execute();
            $stmt_deudor->close();
        }
    }
} 
// Cierre de la conexión (asegúrate de que todas las ramas la cierren o la cierras al final)
$conn->close();
?>

<!DOCTYPE html>
<html lang="es">

<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Nuevo Contrato</title>
	<link href="../../css/bootstrap.min.css" rel="stylesheet">
    <link href="../../css/style4.css" rel="stylesheet">
       <link rel="icon" type="image/jpg" href="../../images/logo.jpg"/>

    <style>
        .text-success { color: #198754 !important; }
        .text-danger { color: #dc3545 !important; }
        .text-warning { color: #ffc107 !important; }
    </style>
</head>

<body>
	<main class="container">

		<?php if ($resultado) { ?>
			<h3 class="text-center text-success">✅ REGISTRO GUARDADO</h3>
            <?php if ($error_mensaje && strpos($error_mensaje, 'ADVERTENCIA') !== false) { ?>
                <p class="text-center text-warning">El nuevo contrato ha sido registrado, pero se generó una advertencia: <?php echo $error_mensaje; ?></p>
            <?php } else { ?>
			    <p class="text-center">El nuevo contrato ha sido registrado exitosamente y se ha generado la primera factura.</p>
                <div class="col-12 text-center">
                  <a href="../reportes_pdf/generar_contrato_pdf.php?id_contrato=<?php echo $id_contrato; ?>" target="_blank">
                        Generar Contrato PDF
                    </a>
                </div>
                <div class="col-12 text-center">
		        	<div class="col-md-12">
		        		<a href="gestion_contratos.php" class="btn btn-primary">Regresar</a>
		        	</div>
		        </div>
            <?php } ?>
		<?php } else { ?>
			<h3 class="text-center text-danger">❌ ERROR AL GUARDAR</h3>
            <?php if ($error_mensaje) { ?>
                <p class="text-center text-danger"><?php echo $error_mensaje; ?></p>
                <div class="col-12 text-center">
		        	<div class="col-md-12">
		        		<a href="nuevo.php" class="btn btn-primary btn-danger">Regresar</a>
		        	</div>
		        </div>
            <?php } else { ?>
			    <p class="text-center">Hubo un problema desconocido al registrar el contrato o un error al ejecutar la consulta.</p>
		    <?php } ?>
        <?php } ?>

		
	</main>
</body>

</html>