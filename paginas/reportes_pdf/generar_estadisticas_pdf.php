<?php
// Muestra todos los errores de PHP para una mejor depuración
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Carga el autoloader de Dompdf
require '../../dompdf/vendor/autoload.php';

// Importa las clases de Dompdf
use Dompdf\Dompdf;
use Dompdf\Options;

// Incluye conexión
require_once '../conexion.php';

// Incluye encabezado estándar
require_once 'encabezado_reporte.php';

// === CAPTURA DE FILTROS ===
$start_date = $_POST['start'] ?? $_GET['start'] ?? '';
$end_date = $_POST['end'] ?? $_GET['end'] ?? '';
$installer = $_POST['installer'] ?? $_GET['installer'] ?? '';
$vendor_id = $_POST['vendor'] ?? $_GET['vendor'] ?? '';
$contract_type = $_POST['type'] ?? $_GET['type'] ?? '';

// === CONSTRUCCIÓN DE QUERIES (Lógica sincronizada con Versión 4) ===
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

// Filtro Vendedor (Sincronizado con vendedor_texto)
if (!empty($vendor_id)) {
    $where[] = "vendedor_texto = ?";
    $params[] = $vendor_id;
    $types .= "s";
}

// Filtro Instalador (Exacto)
if (!empty($installer)) {
    $where[] = "instalador = ?";
    $params[] = $installer;
    $types .= "s";
}

// Filtro Tipo Conexión (tipo_conexion)
if (!empty($contract_type)) {
    $where[] = "tipo_conexion = ?";
    $params[] = $contract_type;
    $types .= "s";
}

$sql_where = "";
if (count($where) > 0) {
    $sql_where = "WHERE " . implode(" AND ", $where);
}

// 1. Zonas de Ventas (Location)
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

// 2. Instalaciones por Tipo (tipo_instalacion)
$sql_type = "SELECT 
                    COALESCE(NULLIF(tipo_instalacion, ''), 'Sin Definir') as tipo, 
                    COUNT(*) as total 
                   FROM contratos 
                   $sql_where 
                   GROUP BY tipo 
                   ORDER BY total DESC";

// 3. Instalaciones (Monthly)
$sql_monthly = "SELECT 
                    DATE_FORMAT(fecha_instalacion, '%Y-%m') as mes, 
                    COUNT(*) as total 
                   FROM contratos 
                   $sql_where 
                   GROUP BY mes 
                   ORDER BY mes ASC";

// 4. Tipos de Conexión (tipo_conexion)
$sql_connection = "SELECT 
                    COALESCE(NULLIF(tipo_conexion, ''), 'Sin Definir') as conexion, 
                    COUNT(*) as total 
                   FROM contratos 
                   $sql_where 
                   GROUP BY conexion 
                   ORDER BY total DESC";

// 5. Instaladores
$sql_installers = "SELECT 
                    COALESCE(NULLIF(instalador, ''), 'Sin Asignar') as nombre, 
                    COUNT(*) as total 
                   FROM contratos 
                   $sql_where 
                   GROUP BY nombre 
                   ORDER BY total DESC";

// 6. Ventas (Vendedor)
$sql_vendors = "SELECT 
                    COALESCE(NULLIF(vendedor_texto, ''), 'Sin Asignar') as nombre_vendedor, 
                    COUNT(*) as total 
                   FROM contratos 
                   $sql_where 
                   GROUP BY nombre_vendedor 
                   ORDER BY total DESC";


// Ejecutar Queries
$stats_location = [];
$stmt = $conn->prepare($sql_location);
if ($stmt) {
    if (!empty($types))
        $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) {
        $stats_location[] = ['ubicacion' => "{$row['nombre_municipio']} - {$row['nombre_parroquia']}", 'total' => $row['total']];
    }
    $stmt->close();
}

$stats_type = [];
$stmt = $conn->prepare($sql_type);
if ($stmt) {
    if (!empty($types))
        $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc())
        $stats_type[] = $row;
    $stmt->close();
}

$stats_monthly = [];
$stmt = $conn->prepare($sql_monthly);
if ($stmt) {
    if (!empty($types))
        $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc())
        $stats_monthly[] = $row;
    $stmt->close();
}

$stats_connection = [];
$stmt = $conn->prepare($sql_connection);
if ($stmt) {
    if (!empty($types))
        $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc())
        $stats_connection[] = $row;
    $stmt->close();
}

$stats_installers = [];
$stmt = $conn->prepare($sql_installers);
if ($stmt) {
    if (!empty($types))
        $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc())
        $stats_installers[] = $row;
    $stmt->close();
}

$stats_vendors = [];
$stmt = $conn->prepare($sql_vendors);
if ($stmt) {
    if (!empty($types))
        $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc())
        $stats_vendors[] = $row;
    $stmt->close();
}

$conn->close();

// === IMÁGENES DE GRÁFICAS ===
$img_location = $_POST['img_location'] ?? null;
$img_type = $_POST['img_type'] ?? null;
$img_monthly = $_POST['img_monthly'] ?? null;
$img_connection = $_POST['img_connection'] ?? null;
$img_installer = $_POST['img_installer'] ?? null;
$img_vendor = $_POST['img_vendor'] ?? null;

// === GENERACIÓN HTML ===
$html = '
<!DOCTYPE html>
<html lang="es">
<head>
    <link rel="icon" type="image/jpg" href="../../images/logo.jpg"/>
    <meta charset="UTF-8">
    <title>Reporte Estadístico Sincronizado</title>
    <style>
        body { font-family: "Helvetica", Arial, sans-serif; font-size: 11px; color: #333; line-height: 1.4; }
        .info-filters { background-color: #f0f7ff; padding: 12px; border-left: 4px solid #3a7bd5; margin-bottom: 20px; font-size: 10px; }
        h3 { color: #3a7bd5; text-transform: uppercase; font-size: 14px; border-bottom: 1px solid #eee; padding-bottom: 5px; margin-top: 25px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #e0e0e0; padding: 6px 8px; text-align: left; }
        th { background-color: #f8f9fa; color: #3a7bd5; font-weight: bold; }
        .chart-container { text-align: center; margin: 15px 0; background: #fff; padding: 10px; border: 1px solid #f0f0f0; }
        .chart-img { max-height: 280px; width: auto; max-width: 100%; }
        .section { page-break-inside: avoid; margin-bottom: 40px; }
        .total-row { background-color: #f0f7ff; font-weight: bold; }
        .text-center { text-align: center; }
    </style>
</head>
<body>';

// Encabezado
$html .= generar_encabezado_empresa('Reporte Estadístico [VERSIÓN SINCRONIZADA]');

// Información de Filtros
$html .= '<div class="info-filters">
    <strong>FILTROS APLICADOS:</strong><br>';
if (!empty($start_date) && !empty($end_date))
    $html .= "Periodo: De $start_date a $end_date<br>";
else
    $html .= "Periodo: Histórico<br>";
$html .= "Vendedor: " . (!empty($vendor_id) ? htmlspecialchars($vendor_id) : "Todos") . " | ";
$html .= "Instalador: " . (!empty($installer) ? htmlspecialchars($installer) : "Todos") . " | ";
$html .= "Tipo Conexión: " . (!empty($contract_type) ? htmlspecialchars($contract_type) : "Todos") . "<br>";
$html .= '</div>';

// --- SECCIÓN 1: ZONAS DE VENTAS ---
$html .= '<div class="section"><h3>1. Zonas de Ventas</h3>';
if ($img_location)
    $html .= '<div class="chart-container"><img src="' . $img_location . '" class="chart-img"></div>';
$html .= '<table><thead><tr><th>Ubicación (Municipio - Parroquia)</th><th class="text-center" style="width: 80px;">Total</th></tr></thead><tbody>';
$total = 0;
foreach ($stats_location as $row) {
    $html .= '<tr><td>' . htmlspecialchars($row['ubicacion']) . '</td><td class="text-center">' . $row['total'] . '</td></tr>';
    $total += $row['total'];
}
$html .= '<tr class="total-row"><td>TOTAL GENERAL</td><td class="text-center">' . $total . '</td></tr></tbody></table></div>';

// --- SECCIÓN 2: INSTALACIONES POR TIPO ---
$html .= '<div class="section"><h3>2. Instalaciones por Tipo</h3>';
if ($img_type)
    $html .= '<div class="chart-container"><img src="' . $img_type . '" class="chart-img"></div>';
$html .= '<table><thead><tr><th>Tipo de Instalación</th><th class="text-center" style="width: 80px;">Total</th></tr></thead><tbody>';
$total = 0;
foreach ($stats_type as $row) {
    $html .= '<tr><td>' . htmlspecialchars($row['tipo']) . '</td><td class="text-center">' . $row['total'] . '</td></tr>';
    $total += $row['total'];
}
$html .= '<tr class="total-row"><td>TOTAL GENERAL</td><td class="text-center">' . $total . '</td></tr></tbody></table></div>';

// --- SECCIÓN 3: INSTALACIONES ---
$html .= '<div class="section"><h3>3. Instalaciones</h3>';
if ($img_monthly)
    $html .= '<div class="chart-container"><img src="' . $img_monthly . '" class="chart-img"></div>';
$html .= '<table><thead><tr><th>Mes</th><th class="text-center" style="width: 80px;">Total</th></tr></thead><tbody>';
$total = 0;
foreach ($stats_monthly as $row) {
    $html .= '<tr><td>' . htmlspecialchars($row['mes']) . '</td><td class="text-center">' . $row['total'] . '</td></tr>';
    $total += $row['total'];
}
$html .= '<tr class="total-row"><td>TOTAL GENERAL</td><td class="text-center">' . $total . '</td></tr></tbody></table></div>';

// --- SECCIÓN 4: TIPOS DE CONEXIÓN ---
$html .= '<div class="section"><h3>4. Tipos de Conexión</h3>';
if ($img_connection)
    $html .= '<div class="chart-container"><img src="' . $img_connection . '" class="chart-img"></div>';
$html .= '<table><thead><tr><th>Tipo de Conexión</th><th class="text-center" style="width: 80px;">Total</th></tr></thead><tbody>';
$total = 0;
foreach ($stats_connection as $row) {
    $html .= '<tr><td>' . htmlspecialchars($row['conexion']) . '</td><td class="text-center">' . $row['total'] . '</td></tr>';
    $total += $row['total'];
}
$html .= '<tr class="total-row"><td>TOTAL GENERAL</td><td class="text-center">' . $total . '</td></tr></tbody></table></div>';

// --- SECCIÓN 5: INSTALADORES ---
$html .= '<div class="section"><h3>5. Instaladores</h3>';
if ($img_installer)
    $html .= '<div class="chart-container"><img src="' . $img_installer . '" class="chart-img"></div>';
$html .= '<table><thead><tr><th>Nombre Instalador</th><th class="text-center" style="width: 80px;">Total</th></tr></thead><tbody>';
$total = 0;
foreach ($stats_installers as $row) {
    $html .= '<tr><td>' . htmlspecialchars($row['nombre']) . '</td><td class="text-center">' . $row['total'] . '</td></tr>';
    $total += $row['total'];
}
$html .= '<tr class="total-row"><td>TOTAL GENERAL</td><td class="text-center">' . $total . '</td></tr></tbody></table></div>';

// --- SECCIÓN 6: VENTAS ---
$html .= '<div class="section"><h3>6. Ventas</h3>';
if ($img_vendor)
    $html .= '<div class="chart-container"><img src="' . $img_vendor . '" class="chart-img"></div>';
$html .= '<table><thead><tr><th>Vendedor</th><th class="text-center" style="width: 80px;">Total</th></tr></thead><tbody>';
$total = 0;
foreach ($stats_vendors as $row) {
    $html .= '<tr><td>' . htmlspecialchars($row['nombre_vendedor']) . '</td><td class="text-center">' . $row['total'] . '</td></tr>';
    $total += $row['total'];
}
$html .= '<tr class="total-row"><td>TOTAL GENERAL</td><td class="text-center">' . $total . '</td></tr></tbody></table></div>';

$html .= '</body></html>';

// Generar PDF
$options = new Options();
$options->set('isRemoteEnabled', true);
$dompdf = new Dompdf($options);
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();
$dompdf->stream("reporte_estadisticas_WS.pdf", ["Attachment" => false]);
exit(0);
?>