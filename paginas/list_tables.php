<?php
require_once 'conexion.php';

$result = $conn->query("DESCRIBE contratos");
while ($row = $result->fetch_assoc()) {
    echo $row['Field'] . " - " . $row['Type'] . "\n";
}
?>