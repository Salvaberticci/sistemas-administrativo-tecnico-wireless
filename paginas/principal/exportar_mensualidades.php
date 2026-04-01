<?php
/**
 * Exportar Mensualidades a Excel (.xls)
 * Columnas: Fecha, Ref, Cliente, Concepto, Monto, Cuenta
 */
require '../conexion.php';

$tipo = isset($_GET['tipo']) ? $_GET['tipo'] : 'todos';
$fecha_inicio = isset($_GET['fecha_inicio']) ? $_GET['fecha_inicio'] : '';
$fecha_fin = isset($_GET['fecha_fin']) ? $_GET['fecha_fin'] : '';
$id_banco = isset($_GET['id_banco']) ? $_GET['id_banco'] : '';
$filtro_tipo = isset($_GET['filtro_tipo']) ? $_GET['filtro_tipo'] : '';

// Construir consulta
$sWhere = "WHERE 1=1";

if (!empty($fecha_inicio) && !empty($fecha_fin)) {
    $sWhere .= " AND (cxc.fecha_emision BETWEEN '" . $conn->real_escape_string($fecha_inicio) . "' AND '" . $conn->real_escape_string($fecha_fin) . "')";
}

if ($tipo === 'filtrado' && !empty($id_banco)) {
    $sWhere .= " AND cxc.id_banco = '" . $conn->real_escape_string($id_banco) . "'";
}

if (!empty($filtro_tipo)) {
    if ($filtro_tipo === 'Mensualidad') {
        $sWhere .= " AND (h.justificacion LIKE '%[MENSUALIDAD]%' OR (h.justificacion IS NULL AND pl.nombre_plan IS NOT NULL) OR h.justificacion LIKE '%mensualidad%')";
    } elseif ($filtro_tipo === 'Instalacion') {
        $sWhere .= " AND (h.justificacion LIKE '%[INSTALACION]%' OR h.justificacion LIKE '%instalación%' OR h.justificacion LIKE '%instalacion%')";
    } elseif ($filtro_tipo === 'Equipos') {
        $sWhere .= " AND (h.justificacion LIKE '%[EQUIPOS]%' OR h.justificacion LIKE '%equipo%' OR h.justificacion LIKE '%material%')";
    } elseif ($filtro_tipo === 'Prorrateo') {
        $sWhere .= " AND (h.justificacion LIKE '%[PRORRATEO]%' OR h.justificacion LIKE '%prorrateo%')";
    } elseif ($filtro_tipo === 'Abono') {
        $sWhere .= " AND (h.justificacion LIKE '%[ABONO]%' OR h.justificacion LIKE '%abono%' OR h.justificacion LIKE '%saldo a favor%')";
    } elseif ($filtro_tipo === 'Extra') {
        $sWhere .= " AND (h.justificacion LIKE '%[EXTRA]%' OR h.justificacion LIKE '%terceros%' OR h.justificacion LIKE '%extra%')";
    }
}
// Si es 'todos' (global), NO aplicamos el filtro de banco, pero sí el de fechas si existen.

// Cargar Bancos JSON
$json_bancos = file_get_contents('bancos.json');
$bancosArr = json_decode($json_bancos, true) ?: [];
$bancosMap = [];
foreach($bancosArr as $b) {
    if(isset($b['id_banco'])) $bancosMap[$b['id_banco']] = $b['nombre_banco'];
}

// Verificar si las columnas bimonetarias ya existen en la BD
$tiene_cols_bimonetarias = false;
$check_cols = $conn->query("SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_NAME='cuentas_por_cobrar' AND COLUMN_NAME='monto_total_bs' LIMIT 1");
if ($check_cols && $check_cols->num_rows > 0) {
    $tiene_cols_bimonetarias = true;
}

$cols_bimonetarias = $tiene_cols_bimonetarias ? "cxc.monto_total_bs, cxc.tasa_bcv," : "NULL AS monto_total_bs, NULL AS tasa_bcv,";

$sql = "
    SELECT 
        cxc.fecha_emision,
        cxc.fecha_pago,
        cxc.referencia_pago,
        c.nombre_completo,
        pl.nombre_plan,
        cxc.monto_total,
        $cols_bimonetarias
        cxc.id_banco,
        cxc.estado,
        GROUP_CONCAT(h.justificacion SEPARATOR ' || ') AS justificacion
    FROM cuentas_por_cobrar cxc
    INNER JOIN contratos c ON cxc.id_contrato = c.id
    LEFT JOIN planes pl ON c.id_plan = pl.id_plan
    LEFT JOIN cobros_manuales_historial h ON cxc.id_cobro = h.id_cobro_cxc
    $sWhere
    GROUP BY cxc.id_cobro
    ORDER BY cxc.fecha_emision DESC
";

$result = $conn->query($sql);

// Nombre del archivo
$suffix = ($tipo === 'filtrado' ? 'filtrado' : 'global');
if ($fecha_inicio) $suffix .= "_" . str_replace('-', '', $fecha_inicio);
$filename = "mensualidades_" . $suffix . "_" . date('Ymd') . ".xls";

// Headers para descarga en formato Excel
header("Content-Type: application/vnd.ms-excel; charset=utf-8");
header("Content-Disposition: attachment; filename=\"$filename\"");
header("Expires: 0");
header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
header("Cache-Control: private", false);

// BOM para UTF-8
echo "\xEF\xBB\xBF";

// Empezamos la tabla HTML que Excel interpretará como celdas
echo "<table border='1'>";
echo "<thead>
        <tr style='background-color: #0d6efd; color: white;'>
            <th>Fecha</th>
            <th>Referencia</th>
            <th>Cliente</th>
            <th>Concepto</th>
            <th>Detalle</th>
            <th>Monto ($)</th>
            <th>Monto (Bs)</th>
            <th>Tasa BCV</th>
            <th>Cuenta</th>
            <th>Estado</th>
        </tr>
      </thead>";
echo "<tbody>";

// Datos
while ($row = $result->fetch_assoc()) {
    $fecha_display = $row['fecha_pago'] ? $row['fecha_pago'] : $row['fecha_emision'];
    $fecha_fmt = date('d/m/Y', strtotime($fecha_display));
    
    // Concepto Parsing (Sync with Table)
    $justif = $row['justificacion'] ?: '';
    $conceptosArr = [];
    if (strpos($justif, '[MENSUALIDAD]') !== false) $conceptosArr[] = 'Mensualidad';
    if (strpos($justif, '[INSTALACION]') !== false) $conceptosArr[] = 'Instalación';
    if (strpos($justif, '[EQUIPOS]') !== false) $conceptosArr[] = 'Equipos / Materiales';
    if (strpos($justif, '[PRORRATEO]') !== false) $conceptosArr[] = 'Prorrateo';
    if (strpos($justif, '[ABONO]') !== false) $conceptosArr[] = 'Abono / Saldo a Favor';
    if (strpos($justif, '[EXTRA]') !== false) $conceptosArr[] = 'Pago de Terceros';

    if (count($conceptosArr) > 0) {
        $concepto_main = implode(' + ', $conceptosArr);
    } elseif ($justif && strpos($justif, '||') === false) {
        $concepto_main = 'Cargo Manual / Otro';
    } elseif ($row['nombre_plan']) {
        $concepto_main = 'Mensualidad / ' . $row['nombre_plan'];
    } else {
        $concepto_main = 'Varios / Otros';
    }
    
    $detalle = str_replace(' || ', ' | ', $justif);
    if (empty($detalle)) $detalle = '-';
    
    $monto = number_format($row['monto_total'], 2, ',', '.');
    $monto_bs = !empty($row['monto_total_bs']) ? number_format($row['monto_total_bs'], 2, ',', '.') : '-';
    $tasa_bcv = !empty($row['tasa_bcv']) ? number_format($row['tasa_bcv'], 2, ',', '.') : '-';
    
    // Banco name from JSON
    $id_banco = $row['id_banco'];
    $nombre_banco = isset($bancosMap[$id_banco]) ? $bancosMap[$id_banco] : ($id_banco ? 'Desconocido' : '-');

    echo "<tr>";
    echo "<td>" . $fecha_fmt . "</td>";
    echo "<td style='mso-number-format:\"\\@\";'>" . ($row['referencia_pago'] ?? '-') . "</td>";
    echo "<td>" . htmlspecialchars($row['nombre_completo']) . "</td>";
    echo "<td>" . htmlspecialchars($concepto_main) . "</td>";
    echo "<td>" . htmlspecialchars($detalle) . "</td>";
    echo "<td>" . $monto . "</td>";
    echo "<td>" . $monto_bs . "</td>";
    echo "<td>" . $tasa_bcv . "</td>";
    echo "<td>" . htmlspecialchars($nombre_banco) . "</td>";
    echo "<td>" . htmlspecialchars($row['estado']) . "</td>";
    echo "</tr>";
}

echo "</tbody>";
echo "</table>";

$conn->close();
exit();
?>
