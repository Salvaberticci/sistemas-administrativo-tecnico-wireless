<?php
require '../conexion.php';
$sql = "ALTER TABLE contratos ADD COLUMN fecha_registro DATETIME DEFAULT CURRENT_TIMESTAMP";
if ($conn->query($sql) === TRUE) {
    echo "Added column: fecha_registro\n";
} else {
    echo "Error adding fecha_registro: " . $conn->error . "\n";
}
$conn->close();
?>
