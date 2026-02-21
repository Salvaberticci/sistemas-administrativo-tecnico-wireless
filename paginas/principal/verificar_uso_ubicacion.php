<?php
/**
 * API endpoint to check if a municipality or parish is in use by contracts.
 */
header('Content-Type: application/json');
require '../conexion.php';

$tipo = $_GET['tipo'] ?? '';
$nombre = $_GET['nombre'] ?? '';

if (!$tipo || !$nombre) {
    echo json_encode(['usage' => 0]);
    exit;
}

$count = 0;
if ($tipo === 'municipio') {
    // Check contracts using this municipality name (via join)
    $stmt = $conn->prepare("
        SELECT COUNT(*) 
        FROM contratos c 
        JOIN municipio m ON c.id_municipio = m.id_municipio 
        WHERE m.nombre_municipio = ?
    ");
} else {
    // Check contracts using this parish name (via join)
    $stmt = $conn->prepare("
        SELECT COUNT(*) 
        FROM contratos c 
        JOIN parroquia p ON c.id_parroquia = p.id_parroquia 
        WHERE p.nombre_parroquia = ?
    ");
}

if ($stmt) {
    $stmt->bind_param("s", $nombre);
    $stmt->execute();
    $stmt->bind_result($count);
    $stmt->fetch();
    $stmt->close();
}

$conn->close();

echo json_encode(['usage' => $count]);
?>