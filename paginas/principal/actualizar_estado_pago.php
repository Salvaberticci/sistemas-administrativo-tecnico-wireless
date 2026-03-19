<?php
// actualizar_estado_pago.php - Actualiza el estado principal de cobro vía AJAX
header('Content-Type: application/json; charset=utf-8');
require '../conexion.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_cobro = intval($_POST['id_cobro']);
    $estado = $conn->real_escape_string($_POST['estado_pago']);

    if (!in_array($estado, ['PAGADO', 'PENDIENTE', 'VENCIDO', 'CANCELADO'])) {
        echo json_encode(["success" => false, "message" => "Estado no válido."]);
        exit;
    }

    $sql_update = "UPDATE cuentas_por_cobrar SET estado = '$estado'";
    
    // Si se pasa a PAGADO, colocar fecha actual si no tenía una
    if ($estado === 'PAGADO') {
        $sql_update .= ", fecha_pago = IF(fecha_pago IS NULL OR fecha_pago = '0000-00-00' OR fecha_pago = '', CURDATE(), fecha_pago)";
    } elseif ($estado === 'PENDIENTE' || $estado === 'VENCIDO') {
        // Si regresamos a pendiente, por seguridad quitamos el estado cargado en SAE (cumple validación UX)
        $sql_update .= ", estado_sae_plus = 'NO CARGADO'"; 
    }
    
    $sql_update .= " WHERE id_cobro = $id_cobro";

    if ($conn->query($sql_update)) {
        echo json_encode(["success" => true, "message" => "Estado actualizado correctamente."]);
    } else {
        echo json_encode(["success" => false, "message" => "Error BD: " . $conn->error]);
    }
} else {
    echo json_encode(["success" => false, "message" => "Método no permitido."]);
}

$conn->close();
?>
