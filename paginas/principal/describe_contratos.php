<?php
$conn = new mysqli('localhost', 'root', '', 'tecnico-administrativo-wirelessdb');
$res = $conn->query("DESCRIBE contratos");
echo "Columnas de contratos:\n";
while($row = $res->fetch_assoc()) {
    echo $row['Field'] . " (" . $row['Type'] . ")\n";
}
$conn->close();
?>
