<?php
require 'paginas/conexion.php';
$res = $conn->query("SHOW TABLES LIKE '%banco%'");
echo "Tablas relacionadas con bancos:\n";
while($r = $res->fetch_array()) {
    echo $r[0] . "\n";
}
?>
