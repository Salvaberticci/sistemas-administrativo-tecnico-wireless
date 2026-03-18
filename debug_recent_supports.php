<?php
require 'paginas/conexion.php';
$res = $conn->query("SELECT id_soporte, fecha_soporte, descripcion FROM soportes ORDER BY id_soporte DESC LIMIT 5");
$data = [];
while($row = $res->fetch_assoc()) {
    $data[] = $row;
}
echo json_encode($data);
?>
