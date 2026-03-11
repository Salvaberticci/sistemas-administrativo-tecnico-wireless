<?php
require 'conexion.php';
$res = $conn->query("SELECT COUNT(*) AS total FROM cuentas_por_cobrar WHERE id_contrato IS NULL OR id_contrato = 0");
$row = $res->fetch_assoc();
echo "Entries without contract: " . $row['total'] . PHP_EOL;
?>
