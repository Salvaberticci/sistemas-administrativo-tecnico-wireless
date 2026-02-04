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
    $installer = $_GET['installer'] ?? '';
    $vendor_text = $_GET['vendor'] ?? '';
    $contract_type = $_GET['type'] ?? '';

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

    // Filtro Vendedor (Buscamos por el texto guardado en vendedor_texto)
    if (!empty($vendor_text)) {
        $where[] = "vendedor_texto = ?";
        $params[] = $vendor_text;
        $types .= "s";
    }

    // Filtro Instalador
    if (!empty($installer)) {
        $where[] = "instalador = ?";
        $params[] = $installer;
        $types .= "s";
    }

    // Filtro Tipo Contrato (tipo_conexion)
    if (!empty($contract_type)) {
        $where[] = "tipo_conexion = ?";
        $params[] = $contract_type;
        $types .= "s";
    }

    $sql_where = "";
    if (count($where) > 0) {
        $sql_where = "WHERE " . implode(" AND ", $where);
    }

    // 1. Por Instalador
    $sql_installers = "SELECT 
                        COALESCE(NULLIF(instalador, ''), 'Sin Asignar') as nombre, 
                        COUNT(*) as total 
                       FROM contratos 
                       $sql_where 
                       GROUP BY nombre 
                       ORDER BY total DESC";

    // 2. Por Vendedor (Usamos vendedor_texto para agrupar)
    $sql_vendors = "SELECT 
                        COALESCE(NULLIF(vendedor_texto, ''), 'Sin Asignar') as nombre_vendedor, 
                        COUNT(*) as total 
                       FROM contratos 
                       $sql_where 
                       GROUP BY nombre_vendedor 
                       ORDER BY total DESC";

    // 3. Por Ubicación (Municipio y Parroquia)
    $sql_location = "SELECT 
                        COALESCE(m.nombre_municipio, 'Sin Municipio') as nombre_municipio,
                        COALESCE(p.nombre_parroquia, 'Sin Parroquia') as nombre_parroquia,
                        COUNT(*) as total
                     FROM contratos c
                     LEFT JOIN municipio m ON c.id_municipio = m.id_municipio
                     LEFT JOIN parroquia p ON c.id_parroquia = p.id_parroquia
                     $sql_where
                     GROUP BY m.nombre_municipio, p.nombre_parroquia
                     ORDER BY total DESC";

    // Ejecutar Query Instaladores
    $stmt = $conn->prepare($sql_installers);
    if ($stmt) {
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
    }

    // Ejecutar Query Vendedores
    $stmt = $conn->prepare($sql_vendors);
    if ($stmt) {
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
    }

    // Ejecutar Query Ubicación
    $stmt = $conn->prepare($sql_location);
    if ($stmt) {
        if (!empty($types)) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $res_loc = $stmt->get_result();
        $stats_location = [];
        while ($row = $res_loc->fetch_assoc()) {
            $stats_location[] = [
                'ubicacion' => "{$row['nombre_municipio']} - {$row['nombre_parroquia']}",
                'total' => $row['total']
            ];
        }
        $stmt->close();
    }

    echo json_encode([
        'by_installer' => $stats_installers ?? [],
        'by_vendor' => $stats_vendors ?? [],
        'by_location' => $stats_location ?? []
    ]);
    exit;
}

if ($action === 'get_lists') {
    // Obtener lista única de Instaladores (Nombres) y Vendedores (IDs) para los filtros

    // Instaladores: Distintos nombres de la columna texto
    $sql_inst = "SELECT DISTINCT instalador FROM contratos WHERE instalador IS NOT NULL AND instalador != '' ORDER BY instalador ASC";
    $res_inst = $conn->query($sql_inst);
    $installers = [];
    while ($row = $res_inst->fetch_row())
        $installers[] = $row[0];

    // Vendedores: IDs distintos
    $sql_vend = "SELECT DISTINCT id_vendedor FROM contratos WHERE id_vendedor > 0 ORDER BY id_vendedor ASC";
    $res_vend = $conn->query($sql_vend);
    $vendors = [];
    while ($row = $res_vend->fetch_row())
        $vendors[] = $row[0];

    echo json_encode([
        'installers' => $installers,
        'vendors' => $vendors
    ]);
    exit;
}
?>