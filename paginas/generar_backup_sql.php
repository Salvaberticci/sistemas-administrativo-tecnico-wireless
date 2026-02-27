<?php
require_once 'conexion.php';

$dbname = 'tecnico-administrativo-wirelessdb';
$backup_file = 'backup_completo_utf8.sql';
$fp = fopen($backup_file, 'w');

if (!$fp) {
    die("No se pudo crear el archivo de backup.");
}

fwrite($fp, "SET NAMES utf8mb4;\n");
fwrite($fp, "SET FOREIGN_KEY_CHECKS = 0;\n\n");

$tablesResult = $conn->query("SHOW TABLES");

while ($tableRow = $tablesResult->fetch_row()) {
    $table = $tableRow[0];
    fwrite($fp, "-- Estructura de tabla para la tabla `$table`\n");
    fwrite($fp, "DROP TABLE IF EXISTS `$table`;\n");

    $createTableResult = $conn->query("SHOW CREATE TABLE `$table`");
    $createTable = $createTableResult->fetch_row()[1];
    fwrite($fp, $createTable . ";\n\n");

    fwrite($fp, "-- Volcado de datos para la tabla `$table`\n");
    $rowsResult = $conn->query("SELECT * FROM `$table`");
    if ($rowsResult->num_rows > 0) {
        // Build INSERT INTO syntax
        $insertCount = 0;
        while ($row = $rowsResult->fetch_assoc()) {
            if ($insertCount % 100 == 0) {
                if ($insertCount > 0)
                    fwrite($fp, ";\n");
                fwrite($fp, "INSERT INTO `$table` VALUES \n");
            } else {
                fwrite($fp, ",\n");
            }

            $vals = [];
            foreach ($row as $val) {
                if ($val === null) {
                    $vals[] = "NULL";
                } else {
                    $vals[] = "'" . $conn->real_escape_string($val) . "'";
                }
            }
            fwrite($fp, "(" . implode(", ", $vals) . ")");
            $insertCount++;
        }
        if ($insertCount > 0)
            fwrite($fp, ";\n\n");
    }
}

fwrite($fp, "SET FOREIGN_KEY_CHECKS = 1;\n");
fclose($fp);
echo "¡Backup completado con éxito! Archivo: $backup_file\n";
?>