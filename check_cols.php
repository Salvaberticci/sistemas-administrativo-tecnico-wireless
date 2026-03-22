<?php
require 'paginas/conexion.php';
$result = $conn->query("SHOW COLUMNS FROM contratos");
while($row = $result->fetch_assoc()) {
    echo $row['Field'] . "\n";
}
?>
