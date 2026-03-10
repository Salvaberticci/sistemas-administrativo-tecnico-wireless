<?php
require 'paginas/conexion.php';

echo "=== CONTRATOS TABLE ===\n";
$res = $conn->query("DESCRIBE contratos");
while ($row = $res->fetch_assoc()) {
    if (strpos($row['Field'], 'vendedor') !== false) {
        print_r($row);
    }
}

echo "=== VENDEDORES TABLE ===\n";
$res = $conn->query("DESCRIBE vendedores");
if ($res) {
    while ($row = $res->fetch_assoc()) {
        print_r($row);
    }
} else {
    echo "No vendedores table\n";
}

echo "=== SAMPLE CONTRATOS ===\n";
$res = $conn->query("SELECT id, id_vendedor, vendedor_texto FROM contratos LIMIT 5");
if ($res) {
    while ($row = $res->fetch_assoc()) {
        print_r($row);
    }
}
?>