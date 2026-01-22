<?php
require_once 'c:/xampp/htdocs/sistemas-administrativo-tecnico-wireless/paginas/conexion.php';

$sql_updates = [
    "ALTER TABLE contratos ADD COLUMN vendedor_texto VARCHAR(150) DEFAULT '' AFTER id_vendedor",
    "ALTER TABLE contratos ADD COLUMN sae_plus VARCHAR(100) DEFAULT '' AFTER vendedor_texto"
];

foreach ($sql_updates as $sql) {
    if ($conn->query($sql) === TRUE) {
        echo "Executed: " . substr($sql, 0, 40) . "...\n";
    } else {
        // Ignore duplicate column errors (1060)
        if ($conn->errno != 1060) {
            echo "Error: " . $conn->error . "\n";
        } else {
             echo "Column already exists (skipped).\n";
        }
    }
}

$conn->close();
?>
