<?php
require 'c:/xampp/htdocs/sistemas-administrativo-tecnico-wireless/paginas/conexion.php';
$res = $conn->query('SELECT cedula, nombre_completo FROM contratos LIMIT 1');
if ($res && $res->num_rows > 0) {
    $row = $res->fetch_assoc();
    echo json_encode($row);
} else {
    echo "No contracts found";
}
?>
