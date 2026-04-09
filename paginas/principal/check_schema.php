<?php
require 'conexion.php';
$tables = ['cuentas_por_cobrar', 'cobros_manuales_historial', 'clientes_deudores'];
foreach ($tables as $table) {
    echo "--- $table ---\n";
    $res = $conn->query("DESCRIBE $table");
    while ($row = $res->fetch_assoc()) {
        echo "{$row['Field']} - {$row['Type']} - {$row['Null']} - {$row['Key']}\n";
    }
}
?>
