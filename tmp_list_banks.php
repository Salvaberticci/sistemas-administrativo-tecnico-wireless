<?php
require 'paginas/conexion.php';
$res = $conn->query('SELECT * FROM bancos');
echo "Contenido de la tabla bancos:\n";
while($r = $res->fetch_assoc()) {
    print_r($r);
}
?>
