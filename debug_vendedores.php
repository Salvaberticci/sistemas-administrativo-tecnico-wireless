<?php
require_once 'paginas/conexion.php';

echo "<h2>Debug: Tabla Vendedores</h2>";
$sql = "SELECT * FROM vendedores";
$res = $conn->query($sql);

if ($res && $res->num_rows > 0) {
    echo "<table border='1'><tr><th>ID</th><th>Nombre</th></tr>";
    while ($row = $res->fetch_assoc()) {
        echo "<tr><td>" . $row['id_vendedor'] . "</td><td>" . $row['nombre_vendedor'] . "</td></tr>";
    }
    echo "</table>";
} else {
    echo "No hay vendedores registrados.";
}

echo "<h3>Estructura de Contratos (Columna id_vendedor)</h3>";
$res = $conn->query("SHOW COLUMNS FROM contratos LIKE 'id_vendedor'");
$row = $res->fetch_assoc();
echo "Type: " . $row['Type'] . "<br>";
echo "Null: " . $row['Null'] . "<br>";

?>