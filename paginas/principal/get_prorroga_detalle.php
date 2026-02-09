<?php
require_once '../conexion.php';

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$res = $conn->query("SELECT * FROM prorrogas WHERE id_prorroga = $id");

header('Content-Type: application/json');
if ($res && $row = $res->fetch_assoc()) {
    echo json_encode($row);
} else {
    echo json_encode(["error" => "No encontrado"]);
}
?>