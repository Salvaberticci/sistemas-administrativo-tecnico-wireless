<?php
require_once 'conexion.php';
$res = $conn->query("SELECT * FROM contratos LIMIT 1");
print_r($res->fetch_assoc());
?>