<?php
require '../conexion.php';
$res = $conn->query("SHOW COLUMNS FROM cuentas_por_cobrar LIKE 'justificacion'");
if ($res->num_rows > 0) {
    echo "EXISTS";
} else {
    echo "NOT_EXISTS";
}
?>
