<?php
require 'paginas/conexion.php';
$res = $conn->query("DESCRIBE contratos");
$cols = [];
while ($row = $res->fetch_assoc()) {
    $cols[] = $row;
}
echo json_encode($cols);
?>