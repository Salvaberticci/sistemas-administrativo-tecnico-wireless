<?php
require '../conexion.php'; 

$table = 'contratos';
$result = $conn->query("DESCRIBE $table");

echo "Columns in $table:\n";
while ($row = $result->fetch_assoc()) {
    echo $row['Field'] . " - " . $row['Type'] . "\n";
}

$conn->close();
?>
