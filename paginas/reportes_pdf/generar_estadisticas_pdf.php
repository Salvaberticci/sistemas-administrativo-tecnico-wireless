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

// === CONSTRUCCIÓN DE QUERIES (Lógica replicada de get_contract_stats.php) ===
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

// Filtro Instalador
if (!empty($installer)) {
    $where[] = "(instalador LIKE ? OR instaladores LIKE ?)";
    $wildcard = "%$installer%";
    $params[] = $wildcard;
    $params[] = $wildcard;
    $types .= "ss";
}

// Filtro Tipo Contrato
if (!empty($contract_type)) {
    $where[] = "tipo_instalacion = ?";
    $params[] = $contract_type;
    $types .= "s";
}

$sql_where = "";
if (count($where) > 0) {
    $sql_where = "WHERE " . implode(" AND ", $where);
}

// 1. Datos Instaladores
$sql_installers = "SELECT 
                    COALESCE(NULLIF(instalador, ''), 'Instalador Externo/Otro') as nombre, 
                    COUNT(*) as total 
                   FROM contratos 
                   $sql_where 
                   GROUP BY nombre 
                   ORDER BY total DESC";

// 2. Datos Vendedores
$sql_vendors = "SELECT id_vendedor, COUNT(*) as total FROM contratos $sql_where GROUP BY id_vendedor ORDER BY total DESC";

// 3. Datos Ubicación (Municipio y Parroquia)
$sql_location = "SELECT 
                    m.nombre_municipio,
                    p.nombre_parroquia,
                    COUNT(*) as total
                 FROM contratos c
                 LEFT JOIN municipio m ON c.id_municipio = m.id_municipio
                 LEFT JOIN parroquia p ON c.id_parroquia = p.id_parroquia
                 $sql_where
                 GROUP BY m.nombre_municipio, p.nombre_parroquia
                 ORDER BY m.nombre_municipio, total DESC";

// Ejecutar Queries
$stats_installers = [];
$stmt = $conn->prepare($sql_installers);
if (!empty($types))
    $stmt->bind_param($types, ...$params);
$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc())
    $stats_installers[] = $row;
$stmt->close();

$stats_vendors = [];
$stmt = $conn->prepare($sql_vendors);
if (!empty($types))
    $stmt->bind_param($types, ...$params);
$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc())
    $stats_vendors[] = $row;
$stmt->close();

$stats_location = [];
$stmt = $conn->prepare($sql_location);
// Re-bind (new stmt)
if (!empty($types))
    $stmt->bind_param($types, ...$params);
$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) {
    $mun = $row['nombre_municipio'] ?? 'Sin Municipio';
    $par = $row['nombre_parroquia'] ?? 'Sin Parroquia';
    $stats_location[] = ['ubicacion' => "$mun - $par", 'total' => $row['total']];
}
$stmt->close();

$conn->close();

// === IMÁGENES DE GRÁFICAS ===
$img_installer = $_POST['img_installer'] ?? null;
$img_vendor = $_POST['img_vendor'] ?? null;
$img_location = $_POST['img_location'] ?? null;

// === GENERACIÓN HTML ===
$html = '
<!DOCTYPE html>
<html lang="es">
<head>
    <link rel="icon" type="image/jpg" href="../../images/logo.jpg"/>
    <meta charset="UTF-8">
    <title>Reporte de Estadísticas</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 12px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; page-break-inside: avoid; }
        th, td { border: 1px solid #59acffff; padding: 6px; text-align: left; }
        th { background-color: #8fd0ffff; }
        h3 { color: #333; border-bottom: 2px solid #59acffff; padding-bottom: 5px; margin-top: 25px; page-break-after: avoid; }
        .info-filters { background-color: #f8f9fa; padding: 10px; border-radius: 5px; margin-bottom: 20px; font-size: 11px; }
        .chart-container { text-align: center; margin-bottom: 15px; }
        .chart-img { max-height: 250px; width: auto; }
        .section { page-break-inside: avoid; margin-bottom: 30px; }
    </style>
</head>
<body>';

// Encabezado
$html .= generar_encabezado_empresa('Reporte de Estadísticas y Contratos');

// Información de Filtros
$html .= '<div class="info-filters">
    <strong>Filtros Aplicados:</strong><br>';
if (!empty($start_date) && !empty($end_date))
    $html .= "Periodo: $start_date al $end_date<br>";
else
    $html .= "Periodo: Histórico Completo<br>";
if (!empty($installer))
    $html .= "Instalador: " . htmlspecialchars($installer) . "<br>";
else
    $html .= "Instalador: Todos<br>";
if (!empty($vendor_id))
    $html .= "Vendedor ID: " . htmlspecialchars($vendor_id) . "<br>";
else
    $html .= "Vendedor: Todos<br>";
if (!empty($contract_type))
    $html .= "Tipo Contrato: " . htmlspecialchars($contract_type) . "<br>";
else
    $html .= "Tipo Contrato: Todos<br>";
$html .= '</div>';

// --- SECCIÓN 1: INSTALADORES ---
$html .= '<div class="section"><h3>Instalaciones por Instalador</h3>';
if ($img_installer) {
    $html .= '<div class="chart-container"><img src="' . $img_installer . '" class="chart-img"></div>';
}
$html .= '<table>
    <thead><tr><th>Nombre Instalador</th><th style="width: 100px; text-align: center;">Total</th></tr></thead>
    <tbody>';
if (!empty($stats_installers)) {
    $totalInst = 0;
    foreach ($stats_installers as $row) {
        $html .= '<tr><td>' . htmlspecialchars($row['nombre']) . '</td><td style="text-align: center;">' . $row['total'] . '</td></tr>';
        $totalInst += $row['total'];
    }
    $html .= '<tr style="background-color: #eee; font-weight: bold;"><td>TOTAL</td><td style="text-align: center;">' . $totalInst . '</td></tr>';
} else {
    $html .= '<tr><td colspan="2">No se encontraron registros.</td></tr>';
}
$html .= '</tbody></table></div>';

// --- SECCIÓN 2: VENDEDORES ---
$html .= '<div class="section"><h3>Contratos por Vendedor</h3>';
if ($img_vendor) {
    $html .= '<div class="chart-container"><img src="' . $img_vendor . '" class="chart-img"></div>';
}
$html .= '<table>
    <thead><tr><th>ID Vendedor</th><th style="width: 100px; text-align: center;">Total</th></tr></thead>
    <tbody>';
if (!empty($stats_vendors)) {
    $totalVend = 0;
    foreach ($stats_vendors as $row) {
        $html .= '<tr><td> Vendedor ID: ' . $row['id_vendedor'] . '</td><td style="text-align: center;">' . $row['total'] . '</td></tr>';
        $totalVend += $row['total'];
    }
    $html .= '<tr style="background-color: #eee; font-weight: bold;"><td>TOTAL</td><td style="text-align: center;">' . $totalVend . '</td></tr>';
} else {
    $html .= '<tr><td colspan="2">No se encontraron registros.</td></tr>';
}
$html .= '</tbody></table></div>';

// --- SECCIÓN 3: UBICACIÓN (NUEVA) ---
$html .= '<div class="section"><h3>Contratos por Ubicación</h3>';
if ($img_location) {
    $html .= '<div class="chart-container"><img src="' . $img_location . '" class="chart-img"></div>';
}
$html .= '<table>
    <thead><tr><th>Ubicación (Municipio - Parroquia)</th><th style="width: 100px; text-align: center;">Total</th></tr></thead>
    <tbody>';
if (!empty($stats_location)) {
    $totalLoc = 0;
    foreach ($stats_location as $row) {
        $html .= '<tr><td>' . htmlspecialchars($row['ubicacion']) . '</td><td style="text-align: center;">' . $row['total'] . '</td></tr>';
        $totalLoc += $row['total'];
    }
    $html .= '<tr style="background-color: #eee; font-weight: bold;"><td>TOTAL</td><td style="text-align: center;">' . $totalLoc . '</td></tr>';
} else {
    $html .= '<tr><td colspan="2">No se encontraron registros.</td></tr>';
}
$html .= '</tbody></table></div>';

$html .= '</body></html>';

// Generar PDF
$options = new Options();
$options->set('isRemoteEnabled', true);
$dompdf = new Dompdf($options);
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();
$dompdf->stream("reporte_estadisticas_" . date('Ymd_His') . ".pdf", ["Attachment" => false]);
exit(0);
?>