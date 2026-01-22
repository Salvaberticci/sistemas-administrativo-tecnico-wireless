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
$start_date = $_GET['start'] ?? '';
$end_date = $_GET['end'] ?? '';
$installer = $_GET['installer'] ?? '';
$vendor_id = $_GET['vendor'] ?? '';

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

// Ejecutar Queries
$stats_installers = [];
$stmt = $conn->prepare($sql_installers);
if (!empty($types)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) $stats_installers[] = $row;
$stmt->close();

$stats_vendors = [];
$stmt = $conn->prepare($sql_vendors);
if (!empty($types)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) $stats_vendors[] = $row;
$stmt->close();

$conn->close();

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
        table { width: 100%; border-collapse: collapse; margin-top: 20px; page-break-inside: avoid; }
        th, td { border: 1px solid #59acffff; padding: 8px; text-align: left; }
        th { background-color: #8fd0ffff; }
        h3 { color: #333; border-bottom: 2px solid #59acffff; padding-bottom: 5px; margin-top: 30px; }
        .info-filters { background-color: #f8f9fa; padding: 10px; border-radius: 5px; margin-bottom: 20px; font-size: 11px; }
    </style>
</head>
<body>';

// Encabezado
$html .= generar_encabezado_empresa('Reporte de Estadísticas y Contratos');

// Información de Filtros
$html .= '<div class="info-filters">
    <strong>Filtros Aplicados:</strong><br>';
if(!empty($start_date) && !empty($end_date)) $html .= "Periodo: $start_date al $end_date<br>";
else $html .= "Periodo: Histórico Completo<br>";
if(!empty($installer)) $html .= "Instalador: " . htmlspecialchars($installer) . "<br>";
else $html .= "Instalador: Todos<br>";
if(!empty($vendor_id)) $html .= "Vendedor ID: " . htmlspecialchars($vendor_id) . "<br>";
else $html .= "Vendedor: Todos<br>";
$html .= '</div>';

// TABLA 1: Por Instalador
$html .= '<h3>Instalaciones por Instalador</h3>';
$html .= '<table>
    <thead>
        <tr>
            <th>Nombre Instalador</th>
            <th style="width: 100px; text-align: center;">Total</th>
        </tr>
    </thead>
    <tbody>';

if (!empty($stats_installers)) {
    $totalInst = 0;
    foreach ($stats_installers as $row) {
        $html .= '<tr>';
        $html .= '<td>' . htmlspecialchars($row['nombre']) . '</td>';
        $html .= '<td style="text-align: center;">' . $row['total'] . '</td>';
        $html .= '</tr>';
        $totalInst += $row['total'];
    }
    $html .= '<tr style="background-color: #eee; font-weight: bold;">
        <td>TOTAL</td><td style="text-align: center;">' . $totalInst . '</td>
    </tr>';
} else {
    $html .= '<tr><td colspan="2">No se encontraron registros.</td></tr>';
}
$html .= '</tbody></table>';

// TABLA 2: Por Vendedor
$html .= '<h3>Contratos por Vendedor</h3>';
$html .= '<table>
    <thead>
        <tr>
            <th>ID Vendedor</th>
            <th style="width: 100px; text-align: center;">Total</th>
        </tr>
    </thead>
    <tbody>';

if (!empty($stats_vendors)) {
    $totalVend = 0;
    foreach ($stats_vendors as $row) {
        $html .= '<tr>';
        $html .= '<td> Vendedor ID: ' . $row['id_vendedor'] . '</td>';
        $html .= '<td style="text-align: center;">' . $row['total'] . '</td>';
        $html .= '</tr>';
        $totalVend += $row['total'];
    }
    $html .= '<tr style="background-color: #eee; font-weight: bold;">
        <td>TOTAL</td><td style="text-align: center;">' . $totalVend . '</td>
    </tr>';
} else {
    $html .= '<tr><td colspan="2">No se encontraron registros.</td></tr>';
}
$html .= '</tbody></table>';

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
