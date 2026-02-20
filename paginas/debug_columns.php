<?php
require_once 'conexion.php';
$res = $conn->query("SHOW COLUMNS FROM contratos");
while ($row = $res->fetch_assoc()) {
    echo $row['Field'] . "\n";
}
?>