<?php
require_once 'paginas/conexion.php';

echo "<h2>Migración: Permitir valores NULL en columnas opcionales</h2>";

// Array de columnas a modificar en la tabla contratos
// Se asume que son INT. Si son VARCHAR, ajustar. Basado en el código son IDs.
$columnas = [
    'id_pon' => 'INT',
    'id_olt' => 'INT',
    'id_vendedor' => 'INT',
    'id_plan' => 'INT',
    'id_municipio' => 'INT',
    'id_parroquia' => 'INT',
    'id_comunidad' => 'INT'
];

foreach ($columnas as $columna => $tipo) {
    echo "Modificando <strong>$columna</strong> para permitir NULL... ";

    // Primero verificamos si existe la columna para evitar errores
    $check = $conn->query("SHOW COLUMNS FROM contratos LIKE '$columna'");
    if ($check->num_rows > 0) {
        // La consulta ALTER TABLE cambiará la columna para permitir NULL
        // IMPORTANTE: Al usar MODIFY, se debe re-especificar el tipo. 
        // Si hay claves foráneas, esto no suele romperlas en MySQL si el tipo es el mismo.

        $sql = "ALTER TABLE contratos MODIFY COLUMN $columna $tipo NULL";

        if ($conn->query($sql)) {
            echo "<span style='color:green;'>OK</span><br>";
        } else {
            echo "<span style='color:red;'>Error: " . $conn->error . "</span><br>";
        }
    } else {
        echo "<span style='color:orange;'>No existe la columna</span><br>";
    }
}

echo "<h3>Proceso Finalizado</h3>";
echo "<p>Intente registrar el contrato nuevamente.</p>";
?>