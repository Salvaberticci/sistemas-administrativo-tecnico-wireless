<?php
require 'paginas/conexion.php';

function dumpTable($conn, $table) {
    echo "\n--- Table: $table ---\n";
    $res = $conn->query("DESCRIBE $table");
    while ($row = $res->fetch_assoc()) {
        echo "{$row['Field']} ({$row['Type']})\n";
    }
}

dumpTable($conn, 'cuentas_por_cobrar');
dumpTable($conn, 'bancos');
dumpTable($conn, 'contratos');
dumpTable($conn, 'planes');

$res = $conn->query("SELECT * FROM bancos");
echo "\n--- Banks ---\n";
while ($row = $res->fetch_assoc()) {
    echo "{$row['id_banco']}: {$row['nombre_banco']}\n";
}
