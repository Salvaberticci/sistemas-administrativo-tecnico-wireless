<?php
require 'paginas/conexion.php';
$res = $conn->query("SELECT id, fecha_registro, nombre_completo FROM contratos ORDER BY id DESC LIMIT 5");
$data = [];
while($row = $res->fetch_assoc()) {
    $data[] = $row;
}
echo json_encode($data);
?>
