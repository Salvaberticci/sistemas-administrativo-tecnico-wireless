<?php
$conn = new mysqli('localhost', 'root', '', 'tecnico-administrativo-wirelessdb');
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$sql = "SELECT id_contrato, COUNT(DISTINCT tipo_registro) as tipos 
        FROM clientes_deudores 
        WHERE estado = 'PENDIENTE' 
        GROUP BY id_contrato 
        HAVING tipos > 1";

$res = $conn->query($sql);
echo "Clientes con Deuda y Crédito simultáneos:\n";
while($row = $res->fetch_assoc()) {
    print_r($row);
}
$conn->close();
?>
