<?php
require 'paginas/conexion.php';
$res = $conn->query("SELECT DISTINCT estado FROM cuentas_por_cobrar");
while($r = $res->fetch_assoc()) {
    echo $r['estado'] . "\n";
}
