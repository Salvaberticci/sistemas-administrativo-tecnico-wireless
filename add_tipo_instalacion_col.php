<?php
require_once 'paginas/conexion.php';

$sql = "ALTER TABLE contratos ADD COLUMN tipo_instalacion VARCHAR(100) DEFAULT 'Nivel 1' AFTER tipo_conexion";

if ($conn->query($sql) === TRUE) {
    echo "Columna tipo_instalacion agregada correctamente.\n";
} else {
    echo "Error: " . $conn->error . "\n";
}

$conn->close();
?>
