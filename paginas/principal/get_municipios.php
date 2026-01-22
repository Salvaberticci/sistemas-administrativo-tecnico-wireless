<?php
// paginas/principal/get_municipios.php  
// Helper to get list of municipios for dropdowns

require '../conexion.php';

$sql = "SELECT id_municipio, nombre_municipio FROM municipio ORDER BY nombre_municipio ASC";
$result = $conn->query($sql);

$html = '';
if ($result && $result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $html .= '<option value="' . $row['id_municipio'] . '">' . htmlspecialchars($row['nombre_municipio']) . '</option>';
    }
}

echo $html;
$conn->close();
?>
