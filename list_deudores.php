<?php
require_once 'paginas/conexion.php';
$res = $conn->query("SELECT d.id, d.saldo_pendiente, c.nombre_completo FROM clientes_deudores d INNER JOIN contratos c ON d.id_contrato = c.id WHERE d.estado = 'PENDIENTE'");
while($row = $res->fetch_assoc()) {
    echo "ID: " . $row['id'] . " | Saldo: " . $row['saldo_pendiente'] . " | Name: " . $row['nombre_completo'] . "\n";
}
