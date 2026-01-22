<?php
/**
 * Eliminar instalador
 */
require 'conexion.php';

$id = intval($_GET['id']);

$sql = "DELETE FROM instaladores WHERE id_instalador = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$resultado = $stmt->execute();

$stmt->close();
$conn->close();

header("Location: gestion_instaladores.php");
exit();
?>
