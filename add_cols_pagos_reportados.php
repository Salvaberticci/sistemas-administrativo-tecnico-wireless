<?php
/**
 * Migration: Add monto_usd and tasa_dolar columns to pagos_reportados
 */
require_once 'paginas/conexion.php';

$sqls = [
    "ALTER TABLE pagos_reportados ADD COLUMN IF NOT EXISTS monto_usd DECIMAL(10,2) DEFAULT NULL AFTER monto_bs",
    "ALTER TABLE pagos_reportados ADD COLUMN IF NOT EXISTS tasa_dolar DECIMAL(10,4) DEFAULT NULL AFTER monto_usd",
];

foreach ($sqls as $sql) {
    if ($conn->query($sql)) {
        echo "✅ OK: $sql<br>";
    } else {
        echo "⚠️ (ya existe o error): " . $conn->error . "<br>";
    }
}
echo "<br><strong>Listo.</strong> Puedes eliminar este archivo.";
$conn->close();
?>
