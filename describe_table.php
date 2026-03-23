<?php
require 'conexion.php';
$res = $conn->query("DESCRIBE pagos_reportados");
while($row = $res->fetch_assoc()) {
    print_r($row);
}
?>
