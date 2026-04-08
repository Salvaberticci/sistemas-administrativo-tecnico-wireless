<?php
require_once 'paginas/conexion.php';
$res = $conn->query("SELECT d.id, d.saldo_pendiente, c.nombre_completo FROM clientes_deudores d INNER JOIN contratos c ON d.id_contrato = c.id WHERE c.nombre_completo LIKE '%MONICA%'");
while($row = $res->fetch_assoc()) {
    print_r($row);
}
