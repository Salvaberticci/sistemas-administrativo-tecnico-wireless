<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'paginas/conexion.php';
$res = $conn->query("SELECT id_soporte FROM soportes ORDER BY id_soporte DESC LIMIT 1");
if ($res && $row = $res->fetch_assoc()) {
    $id = $row['id_soporte'];
    echo "Testing with ID: $id\n";
    $_GET['id'] = $id;

    // Change CWD to the script's directory to simulate browser request
    chdir('paginas/soporte');
    include 'generar_pdf_reporte.php';
} else {
    echo "No supports found to test.";
}
?>