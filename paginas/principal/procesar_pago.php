<?php
require_once '../conexion.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. Capturar y sanear los datos
    $id_cobro = $_POST['id_cobro'];
    // Aunque el monto pagado puede ser útil para auditoría, no lo necesitamos para el UPDATE de estado.
    // $monto_pagado = $_POST['monto_pagado']; 
    $referencia_pago = $conn->real_escape_string($_POST['referencia_pago']);
    $id_banco = isset($_POST['id_banco']) ? intval($_POST['id_banco']) : null;

    // 2. Preparar la consulta UPDATE
    $estado = 'PAGADO';
    $fecha_pago = date('Y-m-d H:i:s');

    // Actualizamos el estado, la fecha de pago, la referencia y el banco.
    $stmt = $conn->prepare("UPDATE cuentas_por_cobrar SET estado = ?, fecha_pago = ?, referencia_pago = ?, id_banco = ? WHERE id_cobro = ?");

    if ($stmt === false) {
        header("Location: gestion_mensualidades.php?maintenance_done=1&message=Error al preparar la consulta de pago.&class=danger");
        exit();
    }

    $stmt->bind_param("sssii", $estado, $fecha_pago, $referencia_pago, $id_banco, $id_cobro);

    // 3. Ejecutar la consulta
    if ($stmt->execute()) {
        header("Location: gestion_mensualidades.php?maintenance_done=1&message=" . urlencode("Pago registrado con éxito.") . "&class=success");
    } else {
        header("Location: gestion_mensualidades.php?maintenance_done=1&message=Error al registrar el pago: " . urlencode($stmt->error) . "&class=danger");
    }

    $stmt->close();
    $conn->close();
} else {
    // Si no es un método POST, redirigir
    header("Location: gestion_mensualidades.php");
}
exit();
?>