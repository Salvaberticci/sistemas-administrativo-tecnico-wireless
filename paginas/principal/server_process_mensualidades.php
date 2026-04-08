<?php
/**
 * Unified Server-side processing for DataTables - Mensualidades y Pagos
 * Columns: Fecha de registro, Referencia, Cliente, Concepto, Monto, Cuenta, Estado, Acciones
 */
ob_start(); // Iniciar búfer de salida para prevenir fugas de texto accidentales

// CORS header for cross‑origin requests
header('Access-Control-Allow-Origin: *');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Content-Type: application/json; charset=utf-8');
require '../conexion.php';
// Detailed request logging for debugging intermittent issues
$logFile = __DIR__ . '/logs/mensualidades_requests.log';
$logEntry = sprintf(
    "[%s] IP: %s | Method: %s | Params: %s\n",
    date('Y-m-d H:i:s'),
    $_SERVER['REMOTE_ADDR'] ?? 'unknown',
    $_SERVER['REQUEST_METHOD'] ?? 'CLI',
    json_encode($_POST)
);

// Solo intentar escribir si el directorio y el archivo permiten escritura
if (is_writable(dirname($logFile)) && (!file_exists($logFile) || is_writable($logFile))) {
    @file_put_contents($logFile, $logEntry, FILE_APPEND);
}

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

// 2.1 Verificar si las columnas bimonetarias existen (Detección rápida sin information_schema)
$tiene_cols_bimonetarias = false;
$check_query = "SELECT monto_total_bs FROM cuentas_por_cobrar LIMIT 1";
if ($res_check = mysqli_query($conn, $check_query)) {
    $tiene_cols_bimonetarias = true;
    mysqli_free_result($res_check);
}


// 3. Define Columns for Search/Sort
$where_mensualidad = "(h.justificacion LIKE '%[MENSUALIDAD]%' OR h.justificacion LIKE '%[EXTRA]%' OR (h.justificacion IS NULL AND pl.nombre_plan IS NOT NULL))";

// We use aSearchColumns for DataTables logic
$aSearchColumns = [
    'COALESCE(cxc.fecha_pago, cxc.fecha_emision)', // 0
    'co.cedula',                                   // 1 (NEW)
    'cxc.referencia_pago',                         // 2
    'co.nombre_completo',                          // 3
    'COALESCE(h.justificacion, pl.nombre_plan)',   // 4
    'cxc.monto_total',                             // 5
    'cxc.id_banco',                                // 6
    'cxc.estado',                                  // 7
    'cxc.origen',                                  // 8
    'cxc.estado_sae_plus',                         // 9
    'co.sae_plus'                                  // 10 (NEW)
];

// 4. Handle Filters (Date Range & Account)
$whereConditions = ["1=1"];

if (isset($_POST['fecha_inicio']) && $_POST['fecha_inicio'] != '' && isset($_POST['fecha_fin']) && $_POST['fecha_fin'] != '') {
    $whereConditions[] = "(COALESCE(cxc.fecha_pago, cxc.fecha_emision) BETWEEN '" . $conn->real_escape_string($_POST['fecha_inicio']) . "' AND '" . $conn->real_escape_string($_POST['fecha_fin']) . "')";
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
    $whereConditions[] = $where_mensualidad;
}

if (isset($_POST['referencia']) && $_POST['referencia'] != '') {
    $whereConditions[] = "cxc.referencia_pago LIKE '%" . $conn->real_escape_string($_POST['referencia']) . "%'";
}

if (isset($_POST['filtro_tipo']) && $_POST['filtro_tipo'] != '') {
    $tipo = $_POST['filtro_tipo'];
    if ($tipo === 'Mensualidad') {
        $whereConditions[] = $where_mensualidad;
    } elseif ($tipo === 'Instalacion') {
        $whereConditions[] = "(h.justificacion LIKE '%[INSTALACION]%' OR h.justificacion LIKE '%instalación%' OR h.justificacion LIKE '%instalacion%')";
    } elseif ($tipo === 'Equipos') {
        $whereConditions[] = "(h.justificacion LIKE '%[EQUIPOS]%' OR h.justificacion LIKE '%equipo%' OR h.justificacion LIKE '%material%')";
    } elseif ($tipo === 'Prorrateo') {
        $whereConditions[] = "(h.justificacion LIKE '%[PRORRATEO]%' OR h.justificacion LIKE '%prorrateo%')";
    } elseif ($tipo === 'Extra') {
        $whereConditions[] = "(h.justificacion LIKE '%[EXTRA]%' OR h.justificacion LIKE '%terceros%' OR h.justificacion LIKE '%extra%')";
    }
}

// Filtro por meses sin pagar: clientes con >= N mensualidades PENDIENTE o VENCIDO
if (isset($_POST['meses_mora']) && $_POST['meses_mora'] !== '' && intval($_POST['meses_mora']) > 0) {
    $minMeses = intval($_POST['meses_mora']);
    // Subquery: contratos que tienen al menos $minMeses facturas PENDIENTES o VENCIDAS 
    // estrictamente de MENSUALIDAD y cuya fecha de vencimiento ya pasó (<= Hoy).
    $whereConditions[] = "cxc.id_contrato IN (
        SELECT sub_cxc.id_contrato
        FROM cuentas_por_cobrar sub_cxc
        LEFT JOIN cobros_manuales_historial sub_h ON sub_cxc.id_cobro = sub_h.id_cobro_cxc
        LEFT JOIN contratos sub_co ON sub_cxc.id_contrato = sub_co.id
        LEFT JOIN planes sub_pl ON sub_co.id_plan = sub_pl.id_plan
        WHERE sub_cxc.estado = 'PENDIENTE'
          AND sub_cxc.fecha_vencimiento <= '" . date('Y-m-d') . "'
          AND (
              sub_h.justificacion LIKE '%[MENSUALIDAD]%'
              OR (sub_h.justificacion IS NULL AND sub_pl.id_plan IS NOT NULL)
          )
        GROUP BY sub_cxc.id_contrato
        HAVING COUNT(*) >= $minMeses
    )";
}

// 4.1 Global Search
if ($searchVal != "") {
    $searchValue = $conn->real_escape_string($searchVal);
    $searchConds = [];
    foreach ($aSearchColumns as $col) {
        $searchConds[] = "$col LIKE '%$searchValue%'";
    }
    $whereConditions[] = "(" . implode(" OR ", $searchConds) . ")";
}

// === FILTROS BASE (Sin el filtro de pestaña activa) ===
$sWhereBase = "WHERE " . implode(" AND ", $whereConditions);

// 4.2 Tab Filters (SAE Plus) - Solo para la consulta principal
$tab = $_POST['tab'] ?? 'general';
if ($tab === 'sae_pendiente') {
    $whereConditions[] = $where_mensualidad;
    $whereConditions[] = "cxc.estado_sae_plus = 'NO CARGADO'";
} elseif ($tab === 'sae_cargado') {
    $whereConditions[] = $where_mensualidad;
    $whereConditions[] = "cxc.estado_sae_plus = 'CARGADO'";
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
    co.cedula,
    co.sae_plus,
    cxc.fecha_emision,
    cxc.fecha_pago,
    cxc.referencia_pago,
    cxc.monto_total,
    " . ($tiene_cols_bimonetarias ? "cxc.monto_total_bs, cxc.tasa_bcv," : "NULL AS monto_total_bs, NULL AS tasa_bcv,") . "
    cxc.estado,
    cxc.id_banco,
    cxc.origen,
    cxc.estado_sae_plus,
    pl.nombre_plan,
    GROUP_CONCAT(h.justificacion SEPARATOR ' || ') AS justificacion,
    cxc.fecha_vencimiento,
    cxc.id_grupo_pago,
    (SELECT COUNT(h2.id) FROM cobros_manuales_historial h2 WHERE h2.id_cobro_cxc = cxc.id_cobro) AS es_manual
";

$sQuery = "
    SELECT SQL_CALC_FOUND_ROWS $sSelect
    FROM $sTabla
    $sWhere
    GROUP BY cxc.id_cobro
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

// Total records query (Optimizado)
$rResultTotal = $conn->query("SELECT COUNT(id_cobro) FROM cuentas_por_cobrar");
$iTotal = $rResultTotal ? (int)$rResultTotal->fetch_array()[0] : 0;

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
    "aaData" => [], 
    "tabCounts" => [
        "general" => $iFilteredTotal,
        "sae_pendiente" => 0,
        "sae_cargado" => 0
    ]
];



// Calcular conteos de SAE para badges (respetando filtros base para precisión)
$output['tabCounts']['sae_pendiente'] = 0;
$output['tabCounts']['sae_cargado'] = 0;

// Calcular conteos de SAE para badges de la forma más rápida y sin JOINs
$output['tabCounts']['sae_pendiente'] = 0;
$output['tabCounts']['sae_cargado'] = 0;

// Reconstruir un Where muy básico sin joins
$basicWhereConds = ["1=1"];
if (isset($_POST['fecha_inicio']) && $_POST['fecha_inicio'] != '' && isset($_POST['fecha_fin']) && $_POST['fecha_fin'] != '') {
    $basicWhereConds[] = "(COALESCE(cxc.fecha_pago, cxc.fecha_emision) BETWEEN '" . $conn->real_escape_string($_POST['fecha_inicio']) . "' AND '" . $conn->real_escape_string($_POST['fecha_fin']) . "')";
}
if (isset($_POST['id_banco']) && $_POST['id_banco'] != '') {
    $basicWhereConds[] = "cxc.id_banco = '" . $conn->real_escape_string($_POST['id_banco']) . "'";
}
if (isset($_POST['estado_pago']) && $_POST['estado_pago'] != '') {
    $basicWhereConds[] = "cxc.estado = '" . $conn->real_escape_string($_POST['estado_pago']) . "'";
}
$basicWhere = "WHERE " . implode(" AND ", $basicWhereConds);

if (empty($searchVal) && empty($_POST['filtro_tipo']) && empty($_POST['meses_mora'])) {
    $res_p = $conn->query("SELECT COUNT(id_cobro) FROM cuentas_por_cobrar cxc $basicWhere AND cxc.estado_sae_plus = 'NO CARGADO'");
    if ($res_p) $output['tabCounts']['sae_pendiente'] = (int)$res_p->fetch_array()[0];

    $res_c = $conn->query("SELECT COUNT(id_cobro) FROM cuentas_por_cobrar cxc $basicWhere AND cxc.estado_sae_plus = 'CARGADO'");
    if ($res_c) $output['tabCounts']['sae_cargado'] = (int)$res_c->fetch_array()[0];
}


while ($aRow = $rResult->fetch_assoc()) {
    $row = [];
    $id_cobro = $aRow['id_cobro'];
    $id_contrato = $aRow['id_contrato'];
    $estado = $aRow['estado'];

    // 0. Fecha (Use payment date if paid, else emission)
    $fecha_base = $aRow['fecha_pago'] ?: $aRow['fecha_emision'];
    $mes_servicio = date('m/Y', strtotime($aRow['fecha_emision']));
    
    // Si la justificacion contiene un mes explícito [Mes], usamos ese
    $justif = $aRow['justificacion'] ?: '';
    if (preg_match('/\[(Enero|Febrero|Marzo|Abril|Mayo|Junio|Julio|Agosto|Septiembre|Octubre|Noviembre|Diciembre)\]/i', $justif, $matches)) {
        $año_servicio = date('Y', strtotime($aRow['fecha_emision'])); // Asume el mismo año base
        $mes_servicio = ucfirst(strtolower($matches[1])) . ' ' . $año_servicio;
    }

    $row[] = '<div class="text-center col-fecha-vibrante" title="Periodo: ' . $mes_servicio . '">
                <div class="periodo-badge">' . $mes_servicio . '</div>
                <div class="fecha-detalle">' . date('d/m/Y', strtotime($fecha_base)) . '</div>
             </div>';

    // 1. Cédula (NEW)
    $row[] = '<div class="text-dark fw-bold" style="font-size: 0.85rem;">' . htmlspecialchars($aRow['cedula'] ?: 'N/A') . '</div>';

    // 1. Ref
    $row[] = htmlspecialchars($aRow['referencia_pago'] ?: '-');

    // 2. Cliente
    $row[] = htmlspecialchars($aRow['nombre_completo']);

    // 3. Plan / Concepto Principal
    $conceptosArr = [];

    if (strpos($justif, '[MENSUALIDAD') !== false) $conceptosArr[] = 'Mensualidad';
    if (strpos($justif, '[INSTALACION') !== false) $conceptosArr[] = 'Instalación';
    if (strpos($justif, '[EQUIPOS') !== false) $conceptosArr[] = 'Equipos / Materiales';
    if (strpos($justif, '[PRORRATEO') !== false) $conceptosArr[] = 'Prorrateo';
    if (strpos($justif, '[ABONO') !== false && strpos($justif, '[ABONO_DEUDA]') === false) $conceptosArr[] = 'Abono / Saldo a Favor';
    if (strpos($justif, '[EXTRA') !== false) $conceptosArr[] = 'Pago de Terceros';
    if (strpos($justif, '[REGISTRO_CONTRATO') !== false) $conceptosArr[] = 'Registro de Contrato';
    if (strpos($justif, '[PAGO_DEUDA]') !== false) $conceptosArr[] = 'Pago de Deuda';
    if (strpos($justif, '[ABONO_DEUDA]') !== false) $conceptosArr[] = 'Abono de Deuda';

    $es_mensualidad = false;
    if (strpos($justif, '[MENSUALIDAD') !== false || strpos($justif, '[EXTRA') !== false) {
        $es_mensualidad = true;
    }
    // Registro de Contrato: NO es mensualidad, no necesita control SAE
    $es_registro_contrato = strpos($justif, '[REGISTRO_CONTRATO') !== false;

    $concepto = '';
    if (count($conceptosArr) > 0) {
        // If Mensualidad is among the concepts, append the plan name for consistency
        $mapped = array_map(function($c) use ($aRow) {
            if ($c === 'Mensualidad' && !empty($aRow['nombre_plan'])) {
                return 'Mensualidad / ' . $aRow['nombre_plan'];
            }
            return $c;
        }, $conceptosArr);
        $concepto = implode(' + ', $mapped);
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
    $montoHtml = '<div class="text-end fw-bold text-success">$' . number_format($aRow['monto_total'], 2, ',', '.') . '</div>';
    if (!empty($aRow['monto_total_bs']) && !empty($aRow['tasa_bcv'])) {
        $montoHtml .= '<div class="text-end small text-secondary" style="font-size: 0.75rem;">Bs. ' . number_format($aRow['monto_total_bs'], 2, ',', '.') . ' <br><span style="font-size:0.65rem; opacity: 0.8;">(Tasa BCV: ' . number_format($aRow['tasa_bcv'], 2, ',', '.') . ')</span></div>';
    }
    $row[] = $montoHtml;

    // 6. Cuenta (JSON Map)
    $id_bank = $aRow['id_banco'];
    $bank_name = isset($bancosMap[$id_bank]) ? $bancosMap[$id_bank] : ($id_bank ? 'Desconocido' : '-');
    $row[] = htmlspecialchars($bank_name);

    // 7. Estado (Badge)
    $badge_class = 'warning';
    
    if ($estado == 'PAGADO') {
        $badge_class = 'success';
    } elseif ($estado == 'PENDIENTE') {
        $badge_class = 'warning';
    } elseif ($estado == 'RECHAZADO') {
        $badge_class = 'secondary';
    }
    
    $row[] = '<div class="text-center"><span class="badge bg-' . $badge_class . '">' . $estado . '</span></div>';

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

    // 11. Código SAE Plus (NEW)
    $row[] = '<div class="text-center"><span class="badge bg-dark bg-opacity-10 text-dark border border-dark border-opacity-25 fw-bold" style="font-size: 0.75rem;">' . htmlspecialchars($aRow['sae_plus'] ?: '-') . '</span></div>';

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
    $row['id_cobro'] = $id_cobro; // Metadato para JS
    $row['id_grupo_pago'] = $aRow['id_grupo_pago']; // Metadato para JS
    $output['aaData'][] = $row;
}

$conn->close();

// Robust JSON output blocking any previous garbage
if (ob_get_length()) ob_clean(); 
header('Content-Type: application/json; charset=utf-8');

// Use JSON_UNESCAPED_UNICODE for better readability/compatibility with accents
// and JSON_PARTIAL_OUTPUT_ON_ERROR to avoid empty response on malformed data
echo json_encode($output, JSON_UNESCAPED_UNICODE | JSON_PARTIAL_OUTPUT_ON_ERROR);
exit;

