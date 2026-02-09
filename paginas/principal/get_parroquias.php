<?php
require_once '../conexion.php';

$id_municipio = isset($_GET['id_municipio']) ? (int) $_GET['id_municipio'] : 0;

if ($id_municipio > 0) {
    $sql = "SELECT id_parroquia, nombre_parroquia FROM parroquias WHERE id_municipio = ? ORDER BY nombre_parroquia ASC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id_municipio);
    $stmt->execute();
    $res = $stmt->get_result();

    echo '<option value="">Seleccione...</option>';
    while ($p = $res->fetch_assoc()) {
        echo '<option value="' . $p['id_parroquia'] . '">' . $p['nombre_parroquia'] . '</option>';
    }
}
?>