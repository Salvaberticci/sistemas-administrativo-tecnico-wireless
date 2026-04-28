<?php
require 'paginas/conexion.php';
$res = $conn->query("SELECT * FROM vendedores");
while ($row = $res->fetch_assoc()) {
    print_r($row);
}
