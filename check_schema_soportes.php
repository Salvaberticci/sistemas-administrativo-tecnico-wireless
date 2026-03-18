<?php
require_once 'paginas/conexion.php';
$res = $conn->query("DESCRIBE soportes");
$data = [];
while($row = $res->fetch_assoc()) {
    $data[] = $row;
}
echo json_encode($data);
?>
