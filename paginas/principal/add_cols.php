<?php
require '../conexion.php';

$columns = [
    "telefono_secundario VARCHAR(50)",
    "correo_adicional VARCHAR(100)",
    "medio_pago VARCHAR(50)",
    "monto_pagar DECIMAL(10,2)",
    "monto_pagado DECIMAL(10,2)",
    "dias_prorrateo INT",
    "monto_prorrateo_usd DECIMAL(10,2)",
    "observaciones TEXT",
    "tipo_conexion VARCHAR(50)",
    "numero_onu VARCHAR(50)",
    "mac_onu VARCHAR(50)",
    "ip_onu VARCHAR(50)",
    "nap_tx_power VARCHAR(20)",
    "onu_rx_power VARCHAR(20)",
    "distancia_drop VARCHAR(20)",
    "instalador VARCHAR(100)",
    "punto_acceso VARCHAR(100)",
    "valor_conexion_dbm VARCHAR(20)",
    "evidencia_foto VARCHAR(255)"
];

foreach ($columns as $col) {
    // Extract column name to check if it exists
    $parts = explode(' ', $col);
    $colName = $parts[0];
    
    // Check if column exists
    $check = $conn->query("SHOW COLUMNS FROM contratos LIKE '$colName'");
    if ($check->num_rows == 0) {
        $sql = "ALTER TABLE contratos ADD COLUMN $col";
        if ($conn->query($sql) === TRUE) {
            echo "Added column: $colName\n";
        } else {
            echo "Error adding $colName: " . $conn->error . "\n";
        }
    } else {
        echo "Column $colName already exists\n";
    }
}

$conn->close();
?>
