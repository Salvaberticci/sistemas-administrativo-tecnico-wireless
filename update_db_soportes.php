<?php
require_once 'c:/xampp/htdocs/sistemas-administrativo-tecnico-wireless/paginas/conexion.php';

// Check if column exists
$result = $conn->query("SHOW COLUMNS FROM soportes LIKE 'id_cobro'");
if ($result->num_rows == 0) {
    $sql = "ALTER TABLE soportes ADD COLUMN id_cobro INT DEFAULT NULL AFTER id_contrato";
    if ($conn->query($sql) === TRUE) {
        echo "Column 'id_cobro' added successfully.";
    } else {
        echo "Error adding column: " . $conn->error;
    }
} else {
    echo "Column 'id_cobro' already exists.";
}

$conn->close();
?>
