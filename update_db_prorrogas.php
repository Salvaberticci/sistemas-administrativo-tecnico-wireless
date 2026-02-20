<?php
require_once 'paginas/conexion.php';

$sql = "ALTER TABLE prorrogas ADD COLUMN dia_prorroga INT AFTER fecha_corte";

if ($conn->query($sql)) {
    echo "Columna 'dia_prorroga' añadida con éxito.\n";
} else {
    // Si ya existe, no importa
    if (strpos($conn->error, "Duplicate column name") !== false) {
        echo "La columna 'dia_prorroga' ya existe.\n";
    } else {
        echo "Error al añadir columna: " . $conn->error . "\n";
    }
}

$conn->close();
?>