<?php
require_once '../conexion.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $cedula = $_POST['cedula_titular'];
    $nombre = $_POST['nombre_titular'];
    $fecha_corte = !empty($_POST['fecha_corte']) ? $_POST['fecha_corte'] : null;

    // Prórroga Interna
    $existe_saeplus = $_POST['existe_saeplus'] ?? 'NO';
    $prorroga_regular = $_POST['prorroga_regular'] ?? 'SI';
    $estado_venta = $_POST['estado_venta'] ?? null;

    $path_contrato = null;

    // Manejo de Archivo (Foto del Contrato)
    if (isset($_FILES['foto_contrato']) && $_FILES['foto_contrato']['error'] == 0) {
        $upload_dir = '../../uploads/contratos/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        $ext = pathinfo($_FILES['foto_contrato']['name'], PATHINFO_EXTENSION);
        $filename = 'contrato_' . time() . '_' . $cedula . '.' . $ext;
        if (move_uploaded_file($_FILES['foto_contrato']['tmp_name'], $upload_dir . $filename)) {
            $path_contrato = $filename;
        }
    }

    $sql = "INSERT INTO prorrogas (
        tipo_solicitud, cedula_titular, nombre_titular, fecha_corte, existe_saeplus, prorroga_regular,
        telefono, telefono_extra, email, id_municipio, id_parroquia, direccion, id_plan, fecha_firma,
        path_contrato, prorateo, metodo_pago, fecha_instalacion, estado_venta
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param(
        "sssssssssiisiisssss",
        $tipo,
        $cedula,
        $nombre,
        $fecha_corte,
        $existe_saeplus,
        $prorroga_regular,
        $telefono,
        $telefono_extra,
        $email,
        $id_municipio,
        $id_parroquia,
        $direccion,
        $id_plan,
        $fecha_firma,
        $path_contrato,
        $prorateo,
        $metodo_pago,
        $fecha_instalacion,
        $estado_venta
    );

    if ($stmt->execute()) {
        header("Location: gestion_prorrogas.php?message=Solicitud registrada con éxito&class=success");
    } else {
        header("Location: gestion_prorrogas.php?message=Error al registrar: " . $stmt->error . "&class=danger");
    }
}
?>