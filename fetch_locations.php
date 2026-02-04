
<?php
require 'paginas/conexion.php';

$resM = $conn->query("SELECT id_municipio, nombre_municipio FROM municipio");
$municipios = [];
while($row = $resM->fetch_assoc()) {
    $municipios[] = $row;
}

$resP = $conn->query("SELECT id_parroquia, nombre_parroquia, id_municipio FROM parroquia");
$parroquias = [];
while($row = $resP->fetch_assoc()) {
    $parroquias[] = $row;
}

$data = [
    "municipios" => $municipios,
    "parroquias" => $parroquias
];

file_put_contents('db_locations.json', json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
echo "Locations fetched successfully to db_locations.json";
?>
