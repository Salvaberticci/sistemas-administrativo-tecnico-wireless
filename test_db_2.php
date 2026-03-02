<?php
require 'c:/xampp/htdocs/sistemas-administrativo-tecnico-wireless/paginas/conexion.php';
$res = $conn->query('SHOW COLUMNS FROM contratos');
while ($row = $res->fetch_assoc())
    echo $row["Field"] . "\n";
?>