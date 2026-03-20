<?php
require 'paginas/conexion.php';
$tables = ['clientes_deudores', 'cobros_manuales_historial', 'cuentas_por_cobrar', 'contratos'];
foreach ($tables as $table) {
    echo "--- $table ---\n";
    $res = $conn->query("DESCRIBE $table");
    while ($row = $res->fetch_assoc()) {
        echo json_encode($row) . "\n";
    }
}
