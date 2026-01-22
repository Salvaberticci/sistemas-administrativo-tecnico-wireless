<?php
/**
 * Marcar deuda como pagada
 */
require '../conexion.php';

$id = intval($_POST['id']);

$sql = "UPDATE clientes_deudores SET estado = 'PAGADO', saldo_pendiente = 0 WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);

if ($stmt->execute()) {
    echo "OK";
} else {
    echo "ERROR";
}

$stmt->close();
$conn->close();
?>
