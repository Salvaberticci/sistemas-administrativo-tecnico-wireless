<?php
require 'paginas/conexion.php';
$res = $conn->query("SELECT id_cobro, fecha_emision, fecha_vencimiento, estado FROM cuentas_por_cobrar ORDER BY id_cobro DESC LIMIT 5");
while ($row = $res->fetch_assoc()) {
    print_r($row);
}
