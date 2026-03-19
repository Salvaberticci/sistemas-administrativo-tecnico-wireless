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
    'cxc.origen',                                  // 7
    'cxc.estado_sae_plus'                          // 8
];

// 4. Handle Filters (Date Range & Account)
$whereConditions = ["1=1"];

if (isset($_POST['fecha_inicio']) && $_POST['fecha_inicio'] != '' && isset($_POST['fecha_fin']) && $_POST['fecha_fin'] != '') {
    $whereConditions[] = "(cxc.fecha_emision BETWEEN '" . $conn->real_escape_string($_POST['fecha_inicio']) . "' AND '" . $conn->real_escape_string($_POST['fecha_fin']) . "')";
}

if (isset($_POST['id_banco']) && $_POST['id_banco'] != '') {
    $whereConditions[] = "cxc.id_banco = '" . $conn->real_escape_string($_POST['id_banco']) . "'";
}

if (isset($_POST['estado_pago']) && $_POST['estado_pago'] != '') {
    $whereConditions[] = "cxc.estado = '" . $conn->real_escape_string($_POST['estado_pago']) . "'";
}

if (isset($_POST['estado_sae']) && $_POST['estado_sae'] != '') {
    $whereConditions[] = "cxc.estado_sae_plus = '" . $conn->real_escape_string($_POST['estado_sae']) . "'";
    // Restringir a mensualidades únicamente cuando se filtra por SAE
    $whereConditions[] = "(h.justificacion LIKE '%[MENSUALIDAD]%' OR (h.justificacion IS NULL AND pl.nombre_plan IS NOT NULL) OR h.justificacion LIKE '%mensualidad%')";
}

if (isset($_POST['referencia']) && $_POST['referencia'] != '') {
    $whereConditions[] = "cxc.referencia_pago LIKE '%" . $conn->real_escape_string($_POST['referencia']) . "%'";
}

if (isset($_POST['filtro_tipo']) && $_POST['filtro_tipo'] != '') {
    $tipo = $_POST['filtro_tipo'];
    if ($tipo === 'Mensualidad') {
        $whereConditions[] = "(h.justificacion LIKE '%[MENSUALIDAD]%' OR (h.justificacion IS NULL AND pl.nombre_plan IS NOT NULL) OR h.justificacion LIKE '%mensualidad%')";
    } elseif ($tipo === 'Instalacion') {
        $whereConditions[] = "(h.justificacion LIKE '%[INSTALACION]%' OR h.justificacion LIKE '%instalación%' OR h.justificacion LIKE '%instalacion%')";
    } elseif ($tipo === 'Equipos') {
        $whereConditions[] = "(h.justificacion LIKE '%[EQUIPOS]%' OR h.justificacion LIKE '%equipo%' OR h.justificacion LIKE '%material%')";
    } elseif ($tipo === 'Prorrateo') {
        $whereConditions[] = "(h.justificacion LIKE '%[PRORRATEO]%' OR h.justificacion LIKE '%prorrateo%')";
    } elseif ($tipo === 'Abono') {
        $whereConditions[] = "(h.justificacion LIKE '%[ABONO]%' OR h.justificacion LIKE '%abono%' OR h.justificacion LIKE '%saldo a favor%')";
    } elseif ($tipo === 'Extra') {
        $whereConditions[] = "(h.justificacion LIKE '%[EXTRA]%' OR h.justificacion LIKE '%terceros%' OR h.justificacion LIKE '%extra%')";
    }
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
    $direction = $conn->real_escape_string($sortDir);
    if ($sortColIndex == 0) {
        // Ordenar por ID garantiza el orden cronológico exacto de inserción (fecha y hora real)
        $sOrder = "ORDER BY cxc.id_cobro $direction";
    } else {
        $sOrder = "ORDER BY " . $aSearchColumns[$sortColIndex] . " $direction";
    }
}

if ($sOrder == "") {
    $sOrder = "ORDER BY cxc.id_cobro DESC";
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
    cxc.estado_sae_plus,
    pl.nombre_plan,
    h.justificacion,
    cxc.fecha_vencimiento,
    cxc.id_grupo_pago,
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
    $mes_servicio = date('m/Y', strtotime($aRow['fecha_emision']));
    $row[] = '<div class="text-center" title="Periodo: ' . $mes_servicio . '">
                <span class="badge bg-light text-dark border-0 mb-1" style="font-size: 0.7rem;">' . $mes_servicio . '</span><br>' 
                . date('d/m/Y', strtotime($fecha_base)) . 
             '</div>';

    // 1. Ref
    $row[] = htmlspecialchars($aRow['referencia_pago'] ?: '-');

    // 2. Cliente
    $row[] = htmlspecialchars($aRow['nombre_completo']);

    // 3. Plan / Concepto Principal
    $justif = $aRow['justificacion'] ?: '';
    $conceptosArr = [];

    if (strpos($justif, '[MENSUALIDAD') !== false) $conceptosArr[] = 'Mensualidad';
    if (strpos($justif, '[INSTALACION') !== false) $conceptosArr[] = 'Instalación';
    if (strpos($justif, '[EQUIPOS') !== false) $conceptosArr[] = 'Equipos / Materiales';
    if (strpos($justif, '[PRORRATEO') !== false) $conceptosArr[] = 'Prorrateo';
    if (strpos($justif, '[ABONO') !== false) $conceptosArr[] = 'Abono / Saldo a Favor';
    if (strpos($justif, '[EXTRA') !== false) $conceptosArr[] = 'Pago de Terceros';

    $es_mensualidad = false;
    if (strpos($justif, '[MENSUALIDAD') !== false) {
        $es_mensualidad = true;
    }

    $concepto = '';
    if (count($conceptosArr) > 0) {
        $concepto = implode(' + ', $conceptosArr);
    } elseif ($justif && strpos($justif, '||') === false) {
        // Si no tiene tags pero tiene texto, es un cargo manual genérico
        $concepto = 'Cargo Manual / Otro';
    } elseif ($aRow['nombre_plan']) {
        $concepto = 'Mensualidad / ' . $aRow['nombre_plan'];
        $es_mensualidad = true;
    } else {
        $concepto = 'Varios / Otros';
    }

    $row[] = '<span class="fw-bold">' . htmlspecialchars($concepto) . '</span>';

    // 4. Detalle / Justificación Extendida
    $justifHtml = str_replace(' || ', ' | ', $justif);
    if (strlen($justifHtml) > 55) {
        $justifHtml = '<span title="' . htmlspecialchars($justifHtml) . '" style="cursor:help">' . htmlspecialchars(substr($justifHtml, 0, 52)) . '...</span>';
    } else {
        $justifHtml = htmlspecialchars($justifHtml ?: '-');
    }
    $row[] = '<span class="small text-muted">' . $justifHtml . '</span>';

    // 5. Monto
    $row[] = '$' . number_format($aRow['monto_total'], 2, ',', '.');

    // 6. Cuenta (JSON Map)
    $id_bank = $aRow['id_banco'];
    $bank_name = isset($bancosMap[$id_bank]) ? $bancosMap[$id_bank] : ($id_bank ? 'Desconocido' : '-');
    $row[] = htmlspecialchars($bank_name);

    // 7. Estado (Badge)
    $badge_class = 'warning';
    if ($estado == 'PAGADO') $badge_class = 'success';
    elseif ($estado == 'VENCIDO') $badge_class = 'danger';
    
    $extra_info = '';
    if ($es_mensualidad && !empty($aRow['fecha_vencimiento'])) {
        $dias_diferencia = floor((strtotime($aRow['fecha_vencimiento']) - strtotime(date('Y-m-d'))) / 86400);
        
        if ($estado == 'PAGADO') {
            if ($dias_diferencia > 0) {
                $extra_info = "<br><small class='text-muted' style='font-size:0.7rem; font-weight: 500;'>vence en $dias_diferencia día(s)</small>";
            } elseif ($dias_diferencia == 0) {
                $extra_info = "<br><small class='text-warning fw-bold' style='font-size:0.7rem;'>vence hoy</small>";
            } else {
                $extra_info = "<br><small class='text-secondary' style='font-size:0.7rem; font-weight: 500;'>vencido hace " . abs($dias_diferencia) . " día(s)</small>";
            }
        } elseif ($estado == 'PENDIENTE') {
            if ($dias_diferencia > 0) {
                $extra_info = "<br><small class='text-muted' style='font-size:0.7rem; font-weight: 500;'>vence en $dias_diferencia día(s)</small>";
            } elseif ($dias_diferencia == 0) {
                $extra_info = "<br><small class='text-warning fw-bold' style='font-size:0.7rem;'>¡vence hoy!</small>";
            } else {
                $extra_info = "<br><small class='text-danger fw-bold' style='font-size:0.7rem;'>atrasado " . abs($dias_diferencia) . " día(s)</small>";
            }
        } elseif ($estado == 'VENCIDO') {
            if ($dias_diferencia < 0) {
                $extra_info = "<br><small class='text-danger fw-bold' style='font-size:0.7rem;'>hace " . abs($dias_diferencia) . " día(s)</small>";
            } else {
                $extra_info = "<br><small class='text-muted' style='font-size:0.7rem; font-weight: 500;'>en $dias_diferencia día(s)</small>";
            }
        }
    }

    $row[] = '<div class="text-center"><span class="badge w-75 bg-' . $badge_class . '">' . $estado . '</span>' . $extra_info . '</div>';

    // 8. Origen (Badge)
    $origen = $aRow['origen'] ?: 'SISTEMA';
    $orig_badge = ($origen == 'LINK') ? 'info' : 'secondary';
    $row[] = '<span class="badge bg-' . $orig_badge . '">' . $origen . '</span>';

    // 9. Estado SAE Plus (Solo si es mensualidad)
    if ($es_mensualidad) {
        $sae_status = $aRow['estado_sae_plus'] ?: 'NO CARGADO';
        $sae_class = ($sae_status == 'CARGADO') ? 'text-success fw-bold' : 'text-danger';
        $sae_select = '<select class="form-select form-select-sm sae-status-select ' . $sae_class . '" data-id="' . $id_cobro . '">
            <option value="NO CARGADO" ' . ($sae_status == 'NO CARGADO' ? 'selected' : '') . '>No Cargado</option>
            <option value="CARGADO" ' . ($sae_status == 'CARGADO' ? 'selected' : '') . '>Cargado</option>
        </select>';
        $row[] = $sae_select;
    } else {
        $row[] = '<span class="badge bg-light text-secondary border w-100 d-block py-2"><i class="fas fa-minus me-1"></i> N/A</span>';
    }

    // 10. Acciones
    $acciones = '<div class="d-flex justify-content-end gap-1">';
    // Modificar
    $acciones .= '<button type="button" onclick="confirmarEdicionCobro(' . $id_cobro . ')" class="btn btn-sm btn-warning" title="Modificar"><i class="fas fa-edit"></i></button>';
    
    // Eliminar: Solo permitida si NO está Pagado, o si es un pago MANUAL (Capture Desglosado)
    if ($estado != 'PAGADO' || $aRow['es_manual'] > 0) {
        $acciones .= '<button type="button" onclick="confirmarEliminarCobro(' . $id_cobro . ', \'' . addslashes($aRow['nombre_completo']) . '\')" class="btn btn-sm btn-danger" title="Eliminar"><i class="fas fa-trash"></i></button>';
    }
    
    // Historial
    $acciones .= '<button type="button" onclick="verHistorialPago(' . $id_contrato . ', \'' . addslashes($aRow['nombre_completo']) . '\')" class="btn btn-sm btn-info" title="Historial"><i class="fas fa-history"></i></button>';
    // Justificación (if manual)
    if ($aRow['es_manual'] > 0) {
        $acciones .= '<button type="button" onclick="verJustificacion(' . $id_cobro . ')" class="btn btn-sm btn-dark" title="Detalles del pago"><i class="fas fa-info-circle"></i></button>';
    }
    $acciones .= '</div>';

    $row[] = $acciones;
    $row['id_grupo_pago'] = $aRow['id_grupo_pago']; // Metadato para JS
    $output['aaData'][] = $row;
}

$conn->close();
// Clear buffer to avoid whitespace issues
if (ob_get_length())
    ob_clean();
echo json_encode($output);
// exit;
