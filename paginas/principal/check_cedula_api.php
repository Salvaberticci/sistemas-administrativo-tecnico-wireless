<?php
// check_cedula_api.php
require_once '../conexion.php';

header('Content-Type: application/json');

$tipo_cedula = $_GET['tipo_cedula'] ?? '';
$cedula_num = $_GET['cedula'] ?? '';
$exclude_id = $_GET['exclude_id'] ?? '';

if (empty($cedula_num)) {
    echo json_encode(['exists' => false]);
    exit;
}

// Format cédula as it's stored in the database (e.g., V12345678)
$cedula_completa = strtoupper($tipo_cedula . $cedula_num);

$sql = "SELECT nombre_completo FROM contratos WHERE cedula = ?";
$params = [$cedula_completa];
$types = "s";

if (!empty($exclude_id)) {
    $sql .= " AND id != ?";
    $params[] = $exclude_id;
    $types .= "i";
}

$sql .= " LIMIT 1";

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    echo json_encode([
        'exists' => true,
        'nombre_completo' => $row['nombre_completo']
    ]);
} else {
    echo json_encode(['exists' => false]);
}

$stmt->close();
$conn->close();
?>
