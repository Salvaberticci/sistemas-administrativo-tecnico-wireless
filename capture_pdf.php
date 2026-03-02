<?php
ob_start();
$_GET['id'] = isset($argv[1]) ? $argv[1] : 2; // Default to ID 2
include 'paginas/soporte/generar_pdf_reporte.php';
$output = ob_get_clean();
echo "Output length: " . strlen($output) . "\n";
if (strlen($output) < 100) {
    echo "Output content: " . $output . "\n";
} else {
    echo "Output looks like binary (length > 100)\n";
}
?>