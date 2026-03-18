<?php
require_once 'paginas/conexion.php';
$res = $conn->query("DESCRIBE soporte_tecnico");
$data = [];
while($row = $res->fetch_assoc()) {
    $data[] = $row;
}
echo json_encode($data);
?>
