<?php
require_once 'paginas/conexion.php';

echo "<h2>Insertando Vendedor PEDRO</h2>";

// Verificar si existe PEDRO
$sql = "SELECT * FROM vendedores WHERE nombre_vendedor = 'PEDRO'";
$res = $conn->query($sql);

if ($res && $res->num_rows > 0) {
    echo "PEDRO ya existe en la base de datos.";
} else {
    $sqlInsert = "INSERT INTO vendedores (nombre_vendedor) VALUES ('PEDRO')";
    if ($conn->query($sqlInsert)) {
        echo "PEDRO insertado correctamente con ID: " . $conn->insert_id;
    } else {
        echo "Error al insertar PEDRO: " . $conn->error;
    }
}
?>