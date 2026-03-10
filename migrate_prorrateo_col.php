<?php
require 'paginas/conexion.php';

// First check if column exists
$res = $conn->query("SHOW COLUMNS FROM contratos LIKE 'id_plan_prorrateo'");
if ($res->num_rows > 0) {
    echo "Renaming id_plan_prorrateo to plan_prorrateo_nombre...\n";
    $sql = "ALTER TABLE contratos CHANGE id_plan_prorrateo plan_prorrateo_nombre VARCHAR(255) NULL DEFAULT NULL";
    if ($conn->query($sql)) {
        echo "Successfully renamed and changed type to VARCHAR(255).\n";
    } else {
        echo "Error: " . $conn->error . "\n";
    }
} else {
    // Check if it's already renamed
    $res2 = $conn->query("SHOW COLUMNS FROM contratos LIKE 'plan_prorrateo_nombre'");
    if ($res2->num_rows > 0) {
        echo "Column plan_prorrateo_nombre already exists.\n";
    } else {
        echo "Neither id_plan_prorrateo nor plan_prorrateo_nombre found. Please check schema.\n";
    }
}
$conn->close();
?>