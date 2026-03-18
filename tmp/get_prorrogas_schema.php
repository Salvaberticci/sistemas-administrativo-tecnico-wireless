<?php
require 'paginas/conexion.php';
$res = $conn->query("DESCRIBE prorrogas");
while($row = $res->fetch_assoc()) {
    echo $row['Field'] . "\n";
}
?>
