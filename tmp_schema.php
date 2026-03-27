<?php
require 'paginas/conexion.php';
if ($res = $conn->query('DESCRIBE contratos')) {
    while($row = $res->fetch_assoc()) {
        echo $row['Field'] . ' (' . $row['Type'] . ")\n";
    }
}
?>
