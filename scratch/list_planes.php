<?php
require 'paginas/conexion.php';
$res = $conn->query("SELECT * FROM planes");
while ($row = $res->fetch_assoc()) {
    print_r($row);
}
