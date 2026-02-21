<?php
/**
 * Returns all fields of a single contract as JSON for modal editing.
 */
header('Content-Type: application/json');
require '../conexion.php';

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if (!$id) {
    echo json_encode(['error' => 'ID inválido']);
    exit;
}

$sql = "
    SELECT c.*,
        m.nombre_municipio, pa.nombre_parroquia, com.nombre_comunidad,
        pl.nombre_plan, v.nombre_vendedor,
        ol.nombre_olt, pn.nombre_pon
    FROM contratos c
    LEFT JOIN municipio m   ON c.id_municipio  = m.id_municipio
    LEFT JOIN parroquia pa  ON c.id_parroquia  = pa.id_parroquia
    LEFT JOIN comunidad com ON c.id_comunidad  = com.id_comunidad
    LEFT JOIN planes pl     ON c.id_plan       = pl.id_plan
    LEFT JOIN vendedores v  ON c.id_vendedor   = v.id_vendedor
    LEFT JOIN olt ol        ON c.id_olt        = ol.id_olt
    LEFT JOIN pon pn        ON c.id_pon        = pn.id_pon
    WHERE c.id = ?
    LIMIT 1
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['error' => 'Contrato no encontrado']);
    exit;
}

echo json_encode($result->fetch_assoc());
$conn->close();
?>