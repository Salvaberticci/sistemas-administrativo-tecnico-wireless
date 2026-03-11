<?php
require 'conexion.php';
$res = $conn->query("SELECT DISTINCT estado FROM contratos");
while($row = $res->fetch_assoc()) echo $row['estado'] . PHP_EOL;
?>
