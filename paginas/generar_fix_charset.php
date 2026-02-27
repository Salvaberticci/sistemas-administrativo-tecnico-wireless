<?php
require_once 'conexion.php';

$dbname = 'tecnico-administrativo-wirelessdb';

echo "SET NAMES utf8mb4;\n";
echo "ALTER DATABASE `$dbname` CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci;\n\n";

$tablesResult = $conn->query("SHOW TABLES");

while ($tableRow = $tablesResult->fetch_row()) {
    $table = $tableRow[0];
    echo "-- Fix Table: $table\n";
    echo "ALTER TABLE `$table` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;\n\n";

    // Also explicitly force columns just in case CONVERT TO misses existing data structure nuances
    $columnsResult = $conn->query("SHOW FULL COLUMNS FROM `$table`");
    $alterCommands = [];
    while ($colRow = $columnsResult->fetch_assoc()) {
        $type = $colRow['Type'];
        // Only target character-based columns
        if (strpos($type, 'char') !== false || strpos($type, 'text') !== false) {
            $field = $colRow['Field'];
            $null = $colRow['Null'] == 'YES' ? 'NULL' : 'NOT NULL';
            $default = $colRow['Default'] !== null ? "DEFAULT '" . addslashes($colRow['Default']) . "'" : ($null == 'NULL' ? "DEFAULT NULL" : "");

            $alterCommands[] = "MODIFY `$field` $type CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci $null $default";
        }
    }

    if (!empty($alterCommands)) {
        echo "ALTER TABLE `$table` " . implode(",\n    ", $alterCommands) . ";\n\n";
    }
}

echo "-- Process completed.\n";
?>