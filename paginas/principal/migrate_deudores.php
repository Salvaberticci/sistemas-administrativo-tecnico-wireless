<?php
require 'paginas/conexion.php';
$sql = "ALTER TABLE clientes_deudores ADD COLUMN tipo_registro ENUM('DEUDA', 'CREDITO') DEFAULT 'DEUDA' AFTER id_contrato";
if($conn->query($sql)) {
    echo "Column added successfully";
} else {
    echo "Error: " . $conn->error;
}
?>
