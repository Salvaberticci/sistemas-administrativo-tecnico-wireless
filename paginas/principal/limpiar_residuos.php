<?php
/**
 * Script para ajustar residuos de deuda (< 0.50) a cero
 */
require_once '../conexion.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Primero contamos cuántos registros serán afectados
    $count_res = $conn->query("SELECT COUNT(*) as total FROM clientes_deudores WHERE saldo_pendiente < 0.50 AND saldo_pendiente > 0 AND estado = 'PENDIENTE'");
    $count = ($count_res) ? $count_res->fetch_assoc()['total'] : 0;

    if ($count == 0) {
        echo json_encode(['success' => false, 'message' => 'No se encontraron residuos menores a $0.50 con saldo pendiente mayor a cero.']);
        exit;
    }

    // Realizar el ajuste
    // Ponemos saldo_pendiente en 0 y ajustamos monto_total para que coincida con lo pagado hasta ahora
    $sql = "UPDATE clientes_deudores 
            SET monto_total = monto_pagado, 
                saldo_pendiente = 0, 
                notas = CONCAT(IFNULL(notas, ''), ' [AJUSTE AUTOMATICO DE RESIDUOS < 0.50]') 
            WHERE saldo_pendiente < 0.50 AND saldo_pendiente > 0 AND estado = 'PENDIENTE'";

    if ($conn->query($sql)) {
        echo json_encode([
            'success' => true, 
            'message' => "Se han ajustado un total de $count registros. Ahora tienen saldo $0.00.",
            'afectados' => $count
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error al ejecutar el ajuste en la base de datos: ' . $conn->error]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Método no permitido.']);
}

$conn->close();
