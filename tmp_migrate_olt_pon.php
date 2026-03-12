<?php
require_once 'paginas/conexion.php';

$queries = [
    "ALTER TABLE soportes ADD COLUMN IF NOT EXISTS id_olt INT NULL AFTER tecnico_asignado",
    "ALTER TABLE soportes ADD COLUMN IF NOT EXISTS id_pon INT NULL AFTER id_olt"
];

foreach ($queries as $sql) {
    if ($conn->query($sql)) {
        echo "Query successful: $sql\n";
    } else {
        echo "Error in query: $sql - " . $conn->error . "\n";
    }
}
?>
