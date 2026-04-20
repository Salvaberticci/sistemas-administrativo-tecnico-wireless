<?php
$conn = new mysqli('localhost', 'root', '', 'tecnico-administrativo-wirelessdb');
$res = $conn->query("DESCRIBE clientes_deudores");
echo "Columnas de clientes_deudores:\n";
while($row = $res->fetch_assoc()) {
    echo $row['Field'] . " (" . $row['Type'] . ")\n";
}
$conn->close();
?>
