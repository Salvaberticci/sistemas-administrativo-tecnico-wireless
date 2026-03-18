<?php
require '../conexion.php';
$res = $conn->query("SELECT * FROM cobros_manuales_historial WHERE id_cobro_cxc = 19");
if ($res) {
    print_r($res->fetch_all(MYSQLI_ASSOC));
} else {
    echo "Error: " . $conn->error;
}
?>
