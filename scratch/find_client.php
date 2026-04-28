<?php
require 'paginas/conexion.php';
$name = 'LILIA COROMOTO';
$res = $conn->query("SELECT * FROM contratos WHERE nombre_completo LIKE '%$name%'");
while ($row = $res->fetch_assoc()) {
    print_r($row);
}
