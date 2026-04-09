<?php
require 'paginas/conexion.php';
$res = $conn->query('DESCRIBE cobros_manuales_historial');
echo "<pre>";
while($row = $res->fetch_assoc()) {
    print_r($row);
}
echo "</pre>";

$res3 = $conn->query('DESCRIBE clientes_deudores');
echo "<h2>clientes_deudores</h2><pre>";
while($row = $res3->fetch_assoc()) {
    print_r($row);
}
echo "</pre>";
?>
