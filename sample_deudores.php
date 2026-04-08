<?php
require_once 'paginas/conexion.php';
$res = $conn->query("SELECT id, saldo_pendiente, estado FROM clientes_deudores WHERE estado = 'PENDIENTE' LIMIT 10");
while($row = $res->fetch_assoc()) {
    print_r($row);
}
