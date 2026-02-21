<?php
// procesar_reporte_pago.php - Procesa el envío del formulario público
require_once 'paginas/conexion.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cedula = $conn->real_escape_string($_POST['cedula']);
    $nombre = $conn->real_escape_string($_POST['nombre']);
    $telefono = $conn->real_escape_string($_POST['telefono']);
    $fecha_pago = $conn->real_escape_string($_POST['fecha_pago']);
    $metodo_pago = $conn->real_escape_string($_POST['metodo_pago']);
    $id_banco_destino = isset($_POST['id_banco_destino']) ? intval($_POST['id_banco_destino']) : null;
    $referencia = isset($_POST['referencia']) ? $conn->real_escape_string($_POST['referencia']) : '';
    $monto_bs = isset($_POST['monto_bs']) ? floatval($_POST['monto_bs']) : 0.00;
    $meses = isset($_POST['meses']) ? implode(', ', $_POST['meses']) : '';
    $concepto = isset($_POST['concepto']) ? $conn->real_escape_string($_POST['concepto']) : '';

    // 1. Intentar vincular con un contrato existente por cédula (Opcional, ayuda al admin)
    $id_contrato_asociado = null;
    $sql_check = "SELECT id FROM contratos WHERE cedula = '$cedula' LIMIT 1";
    $res_check = $conn->query($sql_check);
    if ($res_check && $res_check->num_rows > 0) {
        $id_contrato_asociado = $res_check->fetch_assoc()['id'];
    }

    // 2. Manejo de archivo (Capture)
    $capture_path = '';
    if (isset($_FILES['capture_pago']) && $_FILES['capture_pago']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = 'uploads/pagos/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        $file_name = uniqid('pago_') . '_' . basename($_FILES['capture_pago']['name']);
        // Limpiar nombre de archivo para evitar problemas
        $file_name = preg_replace("/[^a-zA-Z0-9._-]/", "_", $file_name);
        $target_file = $upload_dir . $file_name;

        // Validar tipo de imagen
        $check = getimagesize($_FILES['capture_pago']['tmp_name']);
        if ($check !== false) {
            if (move_uploaded_file($_FILES['capture_pago']['tmp_name'], $target_file)) {
                $capture_path = $target_file;
            } else {
                die("Error al subir el comprobante.");
            }
        } else {
            die("El archivo subido no es una imagen válida.");
        }
    } else {
        die("Debe subir un comprobante de pago.");
    }

    // 3. Insertar en la tabla de reportes pendientes
    $sql_insert = "INSERT INTO pagos_reportados 
        (cedula_titular, nombre_titular, telefono_titular, fecha_pago, metodo_pago, id_banco_destino, referencia, monto_bs, meses_pagados, concepto, capture_path, id_contrato_asociado)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

    $stmt = $conn->prepare($sql_insert);
    $stmt->bind_param("sssssisdsssi", $cedula, $nombre, $telefono, $fecha_pago, $metodo_pago, $id_banco_destino, $referencia, $monto_bs, $meses, $concepto, $capture_path, $id_contrato_asociado);

    if ($stmt->execute()) {
        $mensaje_exito = "¡Gracias! Tu reporte de pago ha sido enviado correctamente. Será verificado por nuestro equipo administrativo a la brevedad posible.";
    } else {
        $mensaje_err = "Error al procesar el reporte: " . $stmt->error;
    }

    $stmt->close();
    $conn->close();
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reporte Enviado - Wireless Supply</title>
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            background-color: #f8f9fa;
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100vh;
        }

        .success-card {
            max-width: 500px;
            border: none;
            border-radius: 20px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
        }

        .icon-box {
            background: #d1e7dd;
            color: #0f5132;
            width: 80px;
            height: 80px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            font-size: 40px;
        }
    </style>
</head>

<body>
    <div class="card success-card p-4 text-center">
        <?php if (isset($mensaje_exito)): ?>
            <div class="icon-box"><i class="fas fa-check"></i></div>
            <h3 class="fw-bold mb-3 text-success">Envío Exitoso</h3>
            <p class="text-muted">
                <?php echo $mensaje_exito; ?>
            </p>
            <hr>
            <a href="reportar_pago.php" class="btn btn-primary">Reportar otro pago</a>
        <?php else: ?>
            <div class="icon-box bg-danger-subtle text-danger"><i class="fas fa-times"></i></div>
            <h3 class="fw-bold mb-3 text-danger">Error</h3>
            <p class="text-muted">
                <?php echo $mensaje_err; ?>
            </p>
            <a href="reportar_pago.php" class="btn btn-secondary">Intentar nuevamente</a>
        <?php endif; ?>
    </div>
</body>

</html>