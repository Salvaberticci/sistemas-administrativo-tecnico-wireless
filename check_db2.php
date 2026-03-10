<?php
require 'paginas/conexion.php';
$res = $conn->query("SHOW CREATE TABLE contratos");
$row = $res->fetch_assoc();
echo $row['Create Table'];
?>