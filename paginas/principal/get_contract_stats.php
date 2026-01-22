<?php
require_once '../conexion.php';

header('Content-Type: application/json');

$action = $_GET['action'] ?? '';

if ($action === 'dashboard') {
    // 1. Total Contratos
    $sql_contracts = "SELECT COUNT(*) as total FROM contratos";
    $res_contracts = $conn->query($sql_contracts);
    $total_contracts = $res_contracts->fetch_assoc()['total'];

    // 2. Total Clientes (Cédulas únicas)
    $sql_clients = "SELECT COUNT(DISTINCT cedula) as total FROM contratos WHERE cedula IS NOT NULL AND cedula != ''";
    $res_clients = $conn->query($sql_clients);
    $total_clients = $res_clients->fetch_assoc()['total'];

    echo json_encode([
        'total_contracts' => $total_contracts,
        'total_clients' => $total_clients
    ]);
    exit;
}

if ($action === 'modal_stats') {
    $start_date = $_GET['start'] ?? '';
    $end_date = $_GET['end'] ?? '';
    $installer = $_GET['installer'] ?? ''; // Puede ser ID o Nombre
    $vendor_id = $_GET['vendor'] ?? '';

    $where = [];
    $params = [];
    $types = "";

    // Filtro Fechas
    if (!empty($start_date) && !empty($end_date)) {
        $where[] = "fecha_instalacion BETWEEN ? AND ?";
        $params[] = $start_date;
        $params[] = $end_date;
        $types .= "ss";
    }

    // Filtro Vendedor
    if (!empty($vendor_id)) {
        $where[] = "id_vendedor = ?";
        $params[] = $vendor_id;
        $types .= "i";
    }

    // Filtro Instalador (Complejo: busca en instalador (texto) o instaladores (ids))
    // Simplificación: Si se selecciona un instalador, buscamos coincidencia en texto o JSON/IDs
    if (!empty($installer)) {
        // Asumimos que el filtro envía un Nombre o un ID.
        // Si es numérico, buscamos en instaladores (LIKE) o id_instalador si existiera
        // Si es texto, buscamos en instalador (LIKE)
        $where[] = "(instalador LIKE ? OR instaladores LIKE ?)";
        $wildcard = "%$installer%";
        $params[] = $wildcard;
        $params[] = $wildcard;
        $types .= "ss";
    }

    $sql_where = "";
    if (count($where) > 0) {
        $sql_where = "WHERE " . implode(" AND ", $where);
    }

    // Estadísticas Agrupadas
    // 1. Por Instalador (Normalizando nombres)
    // Usamos 'instalador' si existe, sino 'Sin Asignar'
    // Nota: Esto es básico. Para reportes precisos con IDs JSON, se requiere lógica compleja no soportada fácilmente en SQL simple.
    $sql_installers = "SELECT 
                        COALESCE(NULLIF(instalador, ''), 'Instalador Externo/Otro') as nombre, 
                        COUNT(*) as total 
                       FROM contratos 
                       $sql_where 
                       GROUP BY nombre 
                       ORDER BY total DESC";

    // 2. Por Vendedor
    // Hacemos JOIN con usuarios o vendedores si existe la tabla, o devolvemos ID
    // Asumimos tabla 'usuarios' o 'vendedores' para obtener nombre. Si no, devolvemos ID.
    // Verificamos si existe tabla 'usuarios' para hacer JOIN. (Asunción basada en práctica común, si falla, corregimos)
    // Mejor devolvemos ID y el frontend que resuelva si tiene la lista, o hacemos un intento simple.
    $sql_vendors = "SELECT id_vendedor, COUNT(*) as total FROM contratos $sql_where GROUP BY id_vendedor ORDER BY total DESC";
    
    // Ejecutar Query Instaladores
    $stmt = $conn->prepare($sql_installers);
    if (!empty($types)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $res_inst = $stmt->get_result();
    $stats_installers = [];
    while ($row = $res_inst->fetch_assoc()) {
        $stats_installers[] = $row;
    }
    $stmt->close();

    // Ejecutar Query Vendedores
    $stmt = $conn->prepare($sql_vendors);
    if (!empty($types)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $res_vend = $stmt->get_result();
    $stats_vendors = [];
    while ($row = $res_vend->fetch_assoc()) {
        $stats_vendors[] = $row;
    }
    $stmt->close();

    echo json_encode([
        'by_installer' => $stats_installers,
        'by_vendor' => $stats_vendors
    ]);
    exit;
}

if ($action === 'get_lists') {
    // Obtener lista única de Instaladores (Nombres) y Vendedores (IDs) para los filtros
    
    // Instaladores: Distintos nombres de la columna texto
    $sql_inst = "SELECT DISTINCT instalador FROM contratos WHERE instalador IS NOT NULL AND instalador != '' ORDER BY instalador ASC";
    $res_inst = $conn->query($sql_inst);
    $installers = [];
    while($row = $res_inst->fetch_row()) $installers[] = $row[0];

    // Vendedores: IDs distintos
    $sql_vend = "SELECT DISTINCT id_vendedor FROM contratos WHERE id_vendedor > 0 ORDER BY id_vendedor ASC";
    $res_vend = $conn->query($sql_vend);
    $vendors = [];
    while($row = $res_vend->fetch_row()) $vendors[] = $row[0];

    echo json_encode([
        'installers' => $installers,
        'vendors' => $vendors
    ]);
    exit;
}
?>
