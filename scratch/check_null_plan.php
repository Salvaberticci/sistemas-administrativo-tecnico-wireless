<?php
require 'paginas/conexion.php';
$res = $conn->query("SELECT * FROM cuentas_por_cobrar WHERE id_plan_cobrado IS NULL LIMIT 5");
while ($row = $res->fetch_assoc()) {
    print_r($row);
}
