<?php
/**
 * Unified Server-side processing for DataTables - Mensualidades y Pagos
 * Columns: Fecha de registro, Referencia, Cliente, Concepto, Monto, Cuenta, Estado, Acciones
 */
header('Content-Type: application/json; charset=utf-8');
require '../conexion.php';

// 1. Load Banks from JSON for mapping
$json_bancos = @file_get_contents('bancos.json');
$bancosArr = json_decode($json_bancos, true) ?: [];
$bancosMap = [];
foreach($bancosArr as $b) {
    if(isset($b['id_banco'])) $bancosMap[$b['id_banco']] = $b['nombre_banco'];
}

// 2. Define Table and Joins
$sTabla = "
    cuentas_por_cobrar cxc
    INNER JOIN contratos co ON cxc.id_contrato = co.id
    LEFT JOIN planes pl ON co.id_plan = pl.id_plan
    LEFT JOIN cobros_manuales_historial h ON cxc.id_cobro = h.id_cobro_cxc
";

// 3. Define Columns for Search/Sort
// We use aSearchColumns for DataTables logic
$aSearchColumns = [
    'COALESCE(cxc.fecha_pago, cxc.fecha_emision)', // 0
    'cxc.referencia_pago',                         // 1
    'co.nombre_completo',                          // 2
    'COALESCE(h.justificacion, pl.nombre_plan)',   // 3
    'cxc.monto_total',                             // 4
    'cxc.id_banco',                                // 5
    'cxc.estado'                                   // 6
];

// 4. Handle Filters (Date Range & Account)
$whereConditions = ["1=1"];

if (isset($_POST['fecha_inicio']) && $_POST['fecha_inicio'] != '' && isset($_POST['fecha_fin']) && $_POST['fecha_fin'] != '') {
    $whereConditions[] = "(cxc.fecha_emision BETWEEN '" . $conn->real_escape_string($_POST['fecha_inicio']) . "' AND '" . $conn->real_escape_string($_POST['fecha_fin']) . "')";
}

if (isset($_POST['id_banco']) && $_POST['id_banco'] != '') {
    $whereConditions[] = "cxc.id_banco = '" . $conn->real_escape_string($_POST['id_banco']) . "'";
}

// Global Search
if (isset($_POST['sSearch']) && $_POST['sSearch'] != "") {
    $searchValue = $conn->real_escape_string($_POST['sSearch']);
    $searchConds = [];
    foreach ($aSearchColumns as $col) {
        $searchConds[] = "$col LIKE '%$searchValue%'";
    }
    $whereConditions[] = "(" . implode(" OR ", $searchConds) . ")";
}

$sWhere = "WHERE " . implode(" AND ", $whereConditions);

// 5. Sorting and Paging (Standard DataTables iDisplayStart/iDisplayLength)
$sLimit = "";
if (isset($_POST['iDisplayStart']) && $_POST['iDisplayLength'] != '-1') {
    $sLimit = "LIMIT " . intval($_POST['iDisplayStart']) . ", " . intval($_POST['iDisplayLength']);
}

$sOrder = "ORDER BY COALESCE(cxc.fecha_pago, cxc.fecha_emision) DESC";
if (isset($_POST['iSortCol_0'])) {
    $sOrder = "ORDER BY ";
    for ($i = 0; $i < intval($_POST['iSortingCols']); $i++) {
        $colIdx = intval($_POST['iSortCol_' . $i]);
        if ($_POST['bSortable_' . $colIdx] == "true") {
            $sOrder .= $aSearchColumns[$colIdx] . " " . $conn->real_escape_string($_POST['sSortDir_' . $i]) . ", ";
        }
    }
    $sOrder = substr_replace($sOrder, "", -2);
    if ($sOrder == "ORDER BY") $sOrder = "ORDER BY COALESCE(cxc.fecha_pago, cxc.fecha_emision) DESC";
}

// 6. Final Queries
$sSelect = "
    cxc.id_cobro,
    co.id AS id_contrato,
    co.nombre_completo,
    cxc.fecha_emision,
    cxc.fecha_pago,
    cxc.referencia_pago,
    cxc.monto_total,
    cxc.estado,
    cxc.id_banco,
    pl.nombre_plan,
    h.justificacion,
    (SELECT COUNT(h2.id) FROM cobros_manuales_historial h2 WHERE h2.id_cobro_cxc = cxc.id_cobro) AS es_manual
";

$sQuery = "
    SELECT SQL_CALC_FOUND_ROWS $sSelect
    FROM $sTabla
    $sWhere
    $sOrder
    $sLimit
";

$rResult = $conn->query($sQuery);
$rResultFilterTotal = $conn->query("SELECT FOUND_ROWS()");
$iFilteredTotal = $rResultFilterTotal->fetch_array()[0];

$rResultTotal = $conn->query("SELECT COUNT(id_cobro) FROM cuentas_por_cobrar");
$iTotal = $rResultTotal->fetch_array()[0];

// 7. Output Result
$output = [
    "sEcho" => intval($_POST['sEcho'] ?? 0),
    "iTotalRecords" => $iTotal,
    "iTotalDisplayRecords" => $iFilteredTotal,
    "aaData" => []
];

while ($aRow = $rResult->fetch_assoc()) {
    $row = [];
    $id_cobro = $aRow['id_cobro'];
    $id_contrato = $aRow['id_contrato'];
    $estado = $aRow['estado'];
    
    // 0. Fecha (Use payment date if paid, else emission)
    $fecha_base = $aRow['fecha_pago'] ?: $aRow['fecha_emision'];
    $row[] = date('d/m/Y', strtotime($fecha_base));
    
    // 1. Ref
    $row[] = htmlspecialchars($aRow['referencia_pago'] ?: '-');
    
    // 2. Cliente
    $row[] = htmlspecialchars($aRow['nombre_completo']);
    
    // 3. Concepto
    $concepto = $aRow['justificacion'] ?: $aRow['nombre_plan'];
    $row[] = htmlspecialchars($concepto ?: 'N/A');
    
    // 4. Monto
    $row[] = '$' . number_format($aRow['monto_total'], 2, ',', '.');
    
    // 5. Cuenta (JSON Map)
    $id_bank = $aRow['id_banco'];
    $bank_name = isset($bancosMap[$id_bank]) ? $bancosMap[$id_bank] : ($id_bank ? 'Desconocido' : '-');
    $row[] = htmlspecialchars($bank_name);
    
    // 6. Estado (Badge)
    $badge_class = 'warning';
    if ($estado == 'PAGADO') $badge_class = 'success';
    elseif ($estado == 'VENCIDO') $badge_class = 'danger';
    $row[] = '<span class="badge bg-' . $badge_class . '">' . $estado . '</span>';
    
    // 7. Acciones
    $acciones = '';
    // Pagar
    if ($estado != 'PAGADO') {
        $acciones .= '<button type="button" class="btn btn-sm btn-success me-1" 
                        data-bs-toggle="modal" data-bs-target="#modalPagar" 
                        data-id="' . $id_cobro . '" data-monto="' . $aRow['monto_total'] . '" 
                        data-nombre="' . htmlspecialchars($aRow['nombre_completo']) . '" title="Pagar">
                        <i class="fas fa-money-bill-wave"></i></button>';
    }
    // Modificar
    $acciones .= '<a href="modifica_cobro1.php?id=' . $id_cobro . '" class="btn btn-sm btn-warning me-1" title="Modificar"><i class="fas fa-edit"></i></a>';
    // Eliminar
    if ($estado != 'PAGADO') {
        $acciones .= '<button type="button" class="btn btn-sm btn-danger me-1" 
                        data-bs-toggle="modal" data-bs-target="#modalEliminar" 
                        data-id="' . $id_cobro . '" data-nombre="' . htmlspecialchars($aRow['nombre_completo']) . '" title="Eliminar">
                        <i class="fas fa-trash"></i></button>';
    }
    // Historial
    $acciones .= '<a href="historial_pagos.php?id=' . $id_contrato . '" class="btn btn-sm btn-info me-1" title="Historial"><i class="fas fa-history"></i></a>';
    // Justificación (if manual)
    if ($aRow['es_manual'] > 0) {
        $acciones .= '<a href="acceder_historial.php?id_cobro=' . $id_cobro . '" class="btn btn-sm btn-dark" title="Justificación"><i class="fas fa-info-circle"></i></a>';
    }
    
    $row[] = $acciones;
    $output['aaData'][] = $row;
}

$conn->close();
echo json_encode($output);
