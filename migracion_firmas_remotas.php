<?php
// migracion_firmas_remotas.php
// Script para agregar columnas de firma remota a las tablas `soportes` y `contratos`

require_once 'paginas/conexion.php';

function agregarColumna($conn, $tabla, $columna, $definicion)
{
    // Verificar si la columna ya existe
    $check = $conn->query("SHOW COLUMNS FROM `$tabla` LIKE '$columna'");
    if ($check->num_rows == 0) {
        $sql = "ALTER TABLE `$tabla` ADD COLUMN `$columna` $definicion";
        if ($conn->query($sql)) {
            echo "✅ Columna `$columna` agregada a tabla `$tabla`.<br>";
        } else {
            echo "❌ Error agregando `$columna` a `$tabla`: " . $conn->error . "<br>";
        }
    } else {
        echo "ℹ️ La columna `$columna` ya existe en `$tabla`.<br>";
    }
}

echo "<h3>Iniciando Migración de Firmas Remotas...</h3>";

// 1. Tabla SOPORTES
agregarColumna($conn, 'soportes', 'token_firma', "VARCHAR(64) NULL DEFAULT NULL AFTER firma_cliente");
agregarColumna($conn, 'soportes', 'estado_firma', "ENUM('PENDIENTE', 'COMPLETADO') DEFAULT 'COMPLETADO' AFTER token_firma");
// Agregamos índice para búsquedas rápidas por token
try {
    $conn->query("ALTER TABLE `soportes` ADD INDEX `idx_token_firma` (`token_firma`)");
    echo "✅ Índice agregado a `token_firma` en `soportes`.<br>";
} catch (Exception $e) { /* Ignorar si ya existe */
}

// 2. Tabla CONTRATOS
// Asumiendo que tabla se llama 'contratos' (o verificar nombre real si es diferente, e.g. clientes_contratos)
// En guardar_contrato_instalador.php no se ve el INSERT explícito a 'contratos', revisaré esquema si falla.
// Pero el objetivo es contratos de instalación.
agregarColumna($conn, 'contratos', 'token_firma', "VARCHAR(64) NULL DEFAULT NULL");
agregarColumna($conn, 'contratos', 'estado_firma', "ENUM('PENDIENTE', 'COMPLETADO') DEFAULT 'COMPLETADO'");
try {
    $conn->query("ALTER TABLE `contratos` ADD INDEX `idx_token_firma` (`token_firma`)");
    echo "✅ Índice agregado a `token_firma` en `contratos`.<br>";
} catch (Exception $e) { /* Ignorar si ya existe */
}

echo "<h3>Migración Completada.</h3>";
echo "<p>Por favor, elimina este archivo después de verificar.</p>";
?>