<?php
// api_planes.php - API para obtener planes con sus montos
require_once '../conexion.php';

header('Content-Type: application/json');

$sql = "SELECT id_plan, nombre_plan, monto FROM planes ORDER BY nombre_plan ASC";
$result = $conn->query($sql);

$planes = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $planes[] = $row;
    }
}

echo json_encode($planes);
$conn->close();
?>
