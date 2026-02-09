<?php
/**
 * Unified Server-side processing for DataTables - Mensualidades y Pagos
 * Columns: Fecha de registro, Referencia, Cliente, Concepto, Monto, Cuenta, Estado, Acciones
 */
header('Content-Type: application/json; charset=utf-8');
require '../conexion.php';

// 1. Load Banks from JSON for mapping
// 1. Load Banks from JSON for mapping
$json_bancos = @file_get_contents('bancos.json');
$bancosArr = json_decode($json_bancos, true) ?: [];
$bancosMap = [];
foreach ($bancosArr as $b) {
    if (isset($b['id_banco']))
        $bancosMap[$b['id_banco']] = $b['nombre_banco'];
}

// PARAMETER MAPPING (Modern vs Legacy)
$start = $_POST['start'] ?? $_POST['iDisplayStart'] ?? 0;
$length = $_POST['length'] ?? $_POST['iDisplayLength'] ?? 10;
$draw = $_POST['draw'] ?? $_POST['sEcho'] ?? 0;
$searchVal = $_POST['search']['value'] ?? $_POST['sSearch'] ?? '';

// Sort mapping
$sortColIndex = $_POST['order'][0]['column'] ?? $_POST['iSortCol_0'] ?? 0;
$sortDir = $_POST['order'][0]['dir'] ?? $_POST['sSortDir_0'] ?? 'desc';

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
    'cxc.estado',                                  // 6
    'cxc.origen'                                   // 7
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
if ($searchVal != "") {
    $searchValue = $conn->real_escape_string($searchVal);
    $searchConds = [];
    foreach ($aSearchColumns as $col) {
        $searchConds[] = "$col LIKE '%$searchValue%'";
    }
    $whereConditions[] = "(" . implode(" OR ", $searchConds) . ")";
}

$sWhere = "WHERE " . implode(" AND ", $whereConditions);

// 5. Sorting and Paging
$sLimit = "";
if ($length != -1) {
    $sLimit = "LIMIT " . intval($start) . ", " . intval($length);
}

// Order By Logic
$sOrder = "";
// Use mapped sort parameters
if (isset($aSearchColumns[$sortColIndex])) {
    $sOrder = "ORDER BY " . $aSearchColumns[$sortColIndex] . " " . $conn->real_escape_string($sortDir);
}

if ($sOrder == "") {
    $sOrder = "ORDER BY COALESCE(cxc.fecha_pago, cxc.fecha_emision) DESC";
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
    cxc.origen,
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
if (!$rResult) {
    echo json_encode(["error" => "SQL Error (Main Query): " . $conn->error]);
    exit;
}

$rResultFilterTotal = $conn->query("SELECT FOUND_ROWS()");
if (!$rResultFilterTotal) {
    echo json_encode(["error" => "SQL Error (Filter Total): " . $conn->error]);
    exit;
}
$iFilteredTotal = $rResultFilterTotal->fetch_array()[0];

// Total records query
$rResultTotal = $conn->query("SELECT COUNT(id_cobro) FROM cuentas_por_cobrar");
if (!$rResultTotal) {
    echo json_encode(["error" => "SQL Error (Total Records): " . $conn->error]);
    exit;
}
$iTotal = $rResultTotal->fetch_array()[0];

// 7. Output Result
$output = [
    // Legacy mapping
    "sEcho" => intval($draw),
    "iTotalRecords" => $iTotal,
    "iTotalDisplayRecords" => $iFilteredTotal,
    // Modern mapping
    "draw" => intval($draw),
    "recordsTotal" => $iTotal,
    "recordsFiltered" => $iFilteredTotal,
    "aaData" => [] // DataTables < 1.10 uses aaData, modern uses data (usually auto-detected, but aaData is safe)
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
    if ($estado == 'PAGADO')
        $badge_class = 'success';
    elseif ($estado == 'VENCIDO')
        $badge_class = 'danger';
    $row[] = '<span class="badge bg-' . $badge_class . '">' . $estado . '</span>';

    // 7. Origen (Badge)
    $origen = $aRow['origen'] ?: 'SISTEMA';
    $orig_badge = ($origen == 'LINK') ? 'info' : 'secondary';
    $row[] = '<span class="badge bg-' . $orig_badge . '">' . $origen . '</span>';

    // 8. Acciones
    $acciones = '';
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
// Clear buffer to avoid whitespace issues
if (ob_get_length())
    ob_clean();
echo json_encode($output);
exit;
