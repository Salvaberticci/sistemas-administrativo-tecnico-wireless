<?php
require 'paginas/conexion.php';
$sql = "ALTER TABLE contratos ADD COLUMN instalador_c VARCHAR(100) DEFAULT NULL AFTER instalador";
if ($conn->query($sql)) {
    echo "Column instalador_c added successfully.\n";
} else {
    echo "Error adding column: " . $conn->error . "\n";
}
?>
