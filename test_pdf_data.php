<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'paginas/conexion.php';
$res = $conn->query("SELECT id_soporte FROM soportes ORDER BY id_soporte DESC LIMIT 1");
if ($res && $row = $res->fetch_assoc()) {
    $id = $row['id_soporte'];
    echo "Testing with ID: $id\n";
    $_GET['id'] = $id;
    
    // Simular el entorno de generar_pdf_reporte.php pero guardar HTML
    require 'dompdf/autoload.inc.php';
    use Dompdf\Dompdf;
    use Dompdf\Options;

    $id_soporte = intval($_GET['id']);
    $sql = "SELECT s.*, c.nombre_completo, c.cedula, c.ip_onu as ip, c.direccion, c.telefono
            FROM soportes s
            INNER JOIN contratos c ON s.id_contrato = c.id
            WHERE s.id_soporte = $id_soporte";
    $result = $conn->query($sql);
    $r = $result->fetch_assoc();
    $saldo = $r['monto_total'] - $r['monto_pagado'];
    
    // We don't need the full logic here, just check if s.* has all columns
    echo "Columns in Soportes result: " . implode(', ', array_keys($r)) . "\n";
    
    // Check if any column is missing that might cause issues
    $fecha = date('d/m/Y', strtotime($r['fecha_soporte']));
    echo "Fecha: $fecha\n";
    
    // Capture HTML
    // (copying the HTML generation logic from the file would be too much, 
    // let's just check if THERE IS any error in the query or data)
    
} else {
    echo "No supports found to test.";
}
?>
