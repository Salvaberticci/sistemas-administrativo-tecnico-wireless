<?php
require_once 'conexion.php';

$dbname = 'tecnico-administrativo-wirelessdb';

echo "Iniciando corrección de caracteres (añadiendo soporte para 'ñ' y acentos) en la base de datos local...\n";

// Arreglar BD
$conn->query("ALTER DATABASE `$dbname` CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci");
echo "✓ Base de datos `$dbname` configurada a utf8mb4.\n";

$tablesResult = $conn->query("SHOW TABLES");

while ($tableRow = $tablesResult->fetch_row()) {
    $table = $tableRow[0];

    // Arreglar Tabla
    $conn->query("ALTER TABLE `$table` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    echo "  ✓ Tabla `$table` configurada.\n";

    // Arreglar Columnas
    $columnsResult = $conn->query("SHOW FULL COLUMNS FROM `$table`");
    $alterCommands = [];
    while ($colRow = $columnsResult->fetch_assoc()) {
        $type = $colRow['Type'];
        if (strpos($type, 'char') !== false || strpos($type, 'text') !== false) {
            $field = $colRow['Field'];
            $null = $colRow['Null'] == 'YES' ? 'NULL' : 'NOT NULL';
            $default = $colRow['Default'] !== null ? "DEFAULT '" . addslashes($colRow['Default']) . "'" : ($null == 'NULL' ? "DEFAULT NULL" : "");

            $alterCommands[] = "MODIFY `$field` $type CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci $null $default";
        }
    }

    if (!empty($alterCommands)) {
        $sql = "ALTER TABLE `$table` " . implode(", ", $alterCommands);
        $conn->query($sql);
    }
}

echo "¡Proceso de corrección de caracteres (UTF-8) completado en el entorno local!\n";
?>