<?php
require 'paginas/conexion.php';
$res = $conn->query("DESCRIBE comunidad");
while ($row = $res->fetch_assoc()) {
    echo json_encode($row) . "\n";
}
?>