<?php
require_once 'c:/xampp/htdocs/sistemas-administrativo-tecnico-wireless/paginas/conexion.php';
$result = $conn->query("SHOW COLUMNS FROM cuentas_por_cobrar");
while($row = $result->fetch_assoc()) {
    echo $row['Field'] . "\n";
}
?>
