<?php
// update_db_installer_contracts.php
// Database migration script to add missing columns for installer contract registration

require_once 'paginas/conexion.php';

echo "<!DOCTYPE html>\n<html><head><meta charset='UTF-8'><title>Actualización BD - Contratos Instalador</title></head><body>\n";
echo "<h2>Actualizando base de datos para contratos de instalador...</h2>\n";

$alterations = [
    "ALTER TABLE contratos ADD COLUMN telefono_secundario VARCHAR(50) AFTER telefono" => "telefono_secundario",
    "ALTER TABLE contratos ADD COLUMN correo_adicional VARCHAR(100) AFTER correo" => "correo_adicional",
    "ALTER TABLE contratos ADD COLUMN tipo_instalacion ENUM('NUEVO', 'MIGRACION', 'MUDANZA') AFTER fecha_instalacion" => "tipo_instalacion",
    "ALTER TABLE contratos ADD COLUMN medio_pago VARCHAR(50) AFTER tipo_instalacion" => "medio_pago",
    "ALTER TABLE contratos ADD COLUMN gastos_adicionales DECIMAL(10,2) DEFAULT 0 AFTER medio_pago" => "gastos_adicionales",
    "ALTER TABLE contratos ADD COLUMN dias_prorrateo INT DEFAULT 0 AFTER gastos_adicionales" => "dias_prorrateo",
    "ALTER TABLE contratos ADD COLUMN monto_prorrateo_usd DECIMAL(10,2) DEFAULT 0 AFTER dias_prorrateo" => "monto_prorrateo_usd",
    "ALTER TABLE contratos ADD COLUMN monto_pagar DECIMAL(10,2) DEFAULT 0 AFTER monto_prorrateo_usd" => "monto_pagar",
    "ALTER TABLE contratos ADD COLUMN monto_pagado DECIMAL(10,2) DEFAULT 0 AFTER monto_pagar" => "monto_pagado",
    "ALTER TABLE contratos ADD COLUMN moneda_pago ENUM('BS', 'USD') DEFAULT 'USD' AFTER monto_pagado" => "moneda_pago",
    "ALTER TABLE contratos ADD COLUMN observaciones TEXT AFTER moneda_pago" => "observaciones",
    "ALTER TABLE contratos ADD COLUMN tipo_conexion VARCHAR(50) AFTER observaciones" => "tipo_conexion",
    "ALTER TABLE contratos ADD COLUMN numero_onu VARCHAR(100) AFTER tipo_conexion" => "numero_onu",
    "ALTER TABLE contratos ADD COLUMN mac_onu VARCHAR(100) AFTER numero_onu" => "mac_onu",
    "ALTER TABLE contratos ADD COLUMN ip_onu VARCHAR(20) AFTER mac_onu" => "ip_onu",
    "ALTER TABLE contratos ADD COLUMN nap_tx_power VARCHAR(20) AFTER puerto_nap" => "nap_tx_power",
    "ALTER TABLE contratos ADD COLUMN onu_rx_power VARCHAR(20) AFTER nap_tx_power" => "onu_rx_power",
    "ALTER TABLE contratos ADD COLUMN distancia_drop VARCHAR(20) AFTER onu_rx_power" => "distancia_drop",
    "ALTER TABLE contratos ADD COLUMN instalador VARCHAR(100) AFTER distancia_drop" => "instalador",
    "ALTER TABLE contratos ADD COLUMN evidencia_fibra VARCHAR(200) AFTER instalador" => "evidencia_fibra",
    "ALTER TABLE contratos ADD COLUMN punto_acceso VARCHAR(100) AFTER evidencia_fibra" => "punto_acceso",
    "ALTER TABLE contratos ADD COLUMN valor_conexion_dbm VARCHAR(20) AFTER punto_acceso" => "valor_conexion_dbm",
    "ALTER TABLE contratos ADD COLUMN evidencia_foto VARCHAR(255) AFTER num_presinto_odn" => "evidencia_foto",
    "ALTER TABLE contratos ADD COLUMN firma_cliente VARCHAR(255) AFTER evidencia_foto" => "firma_cliente",
    "ALTER TABLE contratos ADD COLUMN firma_tecnico VARCHAR(255) AFTER firma_cliente" => "firma_tecnico",
    "ALTER TABLE contratos ADD COLUMN vendedor_texto VARCHAR(100) AFTER firma_tecnico" => "vendedor_texto",
    "ALTER TABLE contratos ADD COLUMN sae_plus VARCHAR(100) AFTER vendedor_texto" => "sae_plus",
];

$successCount = 0;
$skipCount = 0;
$errorCount = 0;

foreach ($alterations as $sql => $fieldName) {
    // Check if column already exists
    $checkSql = "SHOW COLUMNS FROM contratos LIKE '$fieldName'";
    $result = $conn->query($checkSql);
    
    if ($result && $result->num_rows > 0) {
        echo "<p style='color: orange;'>⚠️ Campo '$fieldName' ya existe, omitiendo...</p>\n";
        $skipCount++;
        continue;
    }
    
    // Execute alteration
    if ($conn->query($sql)) {
        echo "<p style='color: green;'>✅ Campo '$fieldName' agregado exitosamente</p>\n";
        $successCount++;
    } else {
        echo "<p style='color: red;'>❌ Error al agregar '$fieldName': " . $conn->error . "</p>\n";
        $errorCount++;
    }
}

echo "<hr>\n";
echo "<h3>Resumen:</h3>\n";
echo "<p><strong>Campos agregados:</strong> $successCount</p>\n";
echo "<p><strong>Campos omitidos (ya existen):</strong> $skipCount</p>\n";
echo "<p><strong>Errores:</strong> $errorCount</p>\n";

if ($errorCount === 0) {
    echo "<p style='color: green; font-weight: bold;'>✅ Migración completada exitosamente</p>\n";
} else {
    echo "<p style='color: orange; font-weight: bold;'>⚠️ Migración completada con algunos errores</p>\n";
}

echo "<p><a href='index.html'>Volver al inicio</a></p>\n";
echo "</body></html>";

$conn->close();
?>
