<?php
require_once 'c:/xampp/htdocs/sistemas-administrativo-tecnico-wireless/paginas/conexion.php';

$sql = "CREATE TABLE IF NOT EXISTS soportes (
    id_soporte INT AUTO_INCREMENT PRIMARY KEY,
    id_contrato INT NOT NULL,
    descripcion TEXT NOT NULL,
    monto_total DECIMAL(10,2) NOT NULL,
    monto_pagado DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    fecha_soporte DATETIME DEFAULT CURRENT_TIMESTAMP,
    tecnico_asignado VARCHAR(100),
    observaciones TEXT,
    FOREIGN KEY (id_contrato) REFERENCES contratos(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;";

if ($conn->query($sql) === TRUE) {
    echo "Table 'soportes' created successfully or already exists.";
} else {
    echo "Error creating table: " . $conn->error;
}

$conn->close();
?>
