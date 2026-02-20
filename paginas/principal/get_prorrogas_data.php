<?php
require_once '../conexion.php';

$sql = "SELECT id_prorroga, tipo_solicitud, cedula_titular, nombre_titular, existe_saeplus, fecha_corte, dia_prorroga, prorroga_regular, estado, fecha_registro FROM prorrogas ORDER BY fecha_registro DESC";
$resSource = $conn->query($sql);

$data = [];
if ($resSource) {
    while ($row = $resSource->fetch_assoc()) {
        $data[] = $row;
    }
}

header('Content-Type: application/json');
echo json_encode(["data" => $data]);
?>