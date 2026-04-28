<?php
require 'paginas/conexion.php';
$res = $conn->query("SELECT * FROM contratos LIMIT 5");
while ($row = $res->fetch_assoc()) {
    print_r($row);
}
