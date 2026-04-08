<?php
require_once 'paginas/conexion.php';
$res = $conn->query("SELECT COUNT(*) as total FROM clientes_deudores WHERE saldo_pendiente < 0.50 AND estado = 'PENDIENTE'");
$row = $res->fetch_assoc();
echo "Total residuos (< 0.50): " . $row['total'] . "\n";
