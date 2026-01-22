<?php
require_once 'c:/xampp/htdocs/sistemas-administrativo-tecnico-wireless/paginas/conexion.php';

$sql_updates = [
    "ALTER TABLE soportes ADD COLUMN sector VARCHAR(150) AFTER id_contrato",
    "ALTER TABLE soportes ADD COLUMN tipo_servicio VARCHAR(50) AFTER sector",
    "ALTER TABLE soportes ADD COLUMN ip_address VARCHAR(50) AFTER tipo_servicio",
    "ALTER TABLE soportes ADD COLUMN estado_onu VARCHAR(50) AFTER ip_address",
    "ALTER TABLE soportes ADD COLUMN estado_router VARCHAR(50) AFTER estado_onu",
    "ALTER TABLE soportes ADD COLUMN modelo_router VARCHAR(100) AFTER estado_router",
    "ALTER TABLE soportes ADD COLUMN bw_bajada VARCHAR(50) AFTER modelo_router",
    "ALTER TABLE soportes ADD COLUMN bw_subida VARCHAR(50) AFTER bw_bajada",
    "ALTER TABLE soportes ADD COLUMN bw_ping VARCHAR(50) AFTER bw_subida",
    "ALTER TABLE soportes ADD COLUMN num_dispositivos INT AFTER bw_ping",
    "ALTER TABLE soportes ADD COLUMN estado_antena VARCHAR(50) AFTER num_dispositivos",
    "ALTER TABLE soportes ADD COLUMN valores_antena VARCHAR(100) AFTER estado_antena",
    "ALTER TABLE soportes ADD COLUMN sugerencias TEXT AFTER observaciones",
    "ALTER TABLE soportes ADD COLUMN solucion_completada TINYINT(1) DEFAULT 0 AFTER sugerencias",
    "ALTER TABLE soportes ADD COLUMN firma_tecnico TEXT AFTER solucion_completada",
    "ALTER TABLE soportes ADD COLUMN firma_cliente TEXT AFTER firma_tecnico"
];

foreach ($sql_updates as $sql) {
    if ($conn->query($sql) === TRUE) {
        echo "Executed: " . substr($sql, 0, 40) . "...\n";
    } else {
        // Ignore duplicate column errors
        if ($conn->errno != 1060) {
            echo "Error: " . $conn->error . "\n";
        }
    }
}

// Crear directorio para firmas si no existe
$dir_firmas = 'c:/xampp/htdocs/sistemas-administrativo-tecnico-wireless/uploads/firmas';
if (!file_exists($dir_firmas)) {
    mkdir($dir_firmas, 0777, true);
    echo "Directory created: uploads/firmas\n";
} else {
    echo "Directory exists: uploads/firmas\n";
}

$conn->close();
?>
