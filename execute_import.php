<?php
// execute_import.php

// Adjust path to connection file as needed. 
// Based on file listing, conexion.php is in 'paginas/conexion.php' relative to root.
require 'paginas/conexion.php';

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$sqlFile = 'import_contratos.sql';
if (!file_exists($sqlFile)) {
    die("Error: File $sqlFile not found.");
}

echo "Starting import from $sqlFile...\n";

// Read the entire file
// Since individual statements can be large or multi-line, we'll read line by line 
// and accumulate until we hit a semicolon at the end of a statement.
$handle = fopen($sqlFile, "r");
if ($handle) {
    $currentQuery = "";
    $count = 0;
    $errors = 0;

    // Disable autocommit for speed, optional but good for large chunks
    $conn->autocommit(FALSE);

    while (($line = fgets($handle)) !== false) {
        $line = trim($line);
        if (empty($line) || strpos($line, '--') === 0) {
            continue;
        }

        $currentQuery .= $line . " ";

        // If line ends with semicolon, execute
        if (substr($line, -1) == ';') {
            if (!$conn->query($currentQuery)) {
                echo "Error executing query: " . $conn->error . "\n";
                // echo "Query: " . substr($currentQuery, 0, 100) . "...\n"; // Optional: print start of query
                $errors++;
            } else {
                $count++;
                if ($count % 100 == 0) {
                    echo "Imported $count records...\r";
                }
            }
            $currentQuery = "";
        }
    }

    // Commit transaction
    if (!$conn->commit()) {
        echo "Commit failed: " . $conn->error . "\n";
    }

    fclose($handle);
    $conn->autocommit(TRUE);

    echo "\nImport completed.\n";
    echo "Total records processed: $count\n";
    echo "Total errors: $errors\n";

} else {
    echo "Error opening file.\n";
}

$conn->close();
?>