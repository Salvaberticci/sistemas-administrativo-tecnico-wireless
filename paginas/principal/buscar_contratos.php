<?php
// Script para buscar contratos por ID o nombre del cliente (usado por AJAX)

header('Content-Type: application/json');

// Incluye su archivo de conexión
require_once '../conexion.php';

$resultados = [];
$search_query = isset($_GET['q']) ? $conn->real_escape_string($_GET['q']) : '';

if (strlen($search_query) >= 3) {
    // Buscamos contratos activos con su respectivo plan
    $sql = "SELECT c.id, c.nombre_completo, c.cedula, c.telefono, c.direccion, c.ip_onu as ip, c.tipo_conexion as tipo_servicio, 
                   p.nombre_plan, p.monto as monto_plan
            FROM contratos c
            LEFT JOIN planes p ON c.id_plan = p.id_plan
            WHERE c.nombre_completo LIKE '%" . $search_query . "%' 
               OR c.id LIKE '%" . $search_query . "%'
               OR c.cedula LIKE '%" . $search_query . "%'
            LIMIT 10"; // Limitar a 10 resultados para no sobrecargar

    $resultado = $conn->query($sql);

    if ($resultado && $resultado->num_rows > 0) {
        while ($fila = $resultado->fetch_assoc()) {
            $resultados[] = $fila;
        }
    }
}

echo json_encode($resultados);

$conn->close();
?>