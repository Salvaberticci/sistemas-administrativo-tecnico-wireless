<?php
require_once 'paginas/conexion.php';
$res = $conn->query("SELECT DISTINCT estado FROM clientes_deudores");
while($row = $res->fetch_assoc()) {
    echo $row['estado'] . "\n";
}
