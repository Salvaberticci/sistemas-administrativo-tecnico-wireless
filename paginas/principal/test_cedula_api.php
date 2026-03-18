<?php
// test_cedula_api.php
$_GET['tipo_cedula'] = 'V';
$_GET['cedula'] = '12721951';

// Buffer output as the API might send headers
ob_start();
include 'c:/xampp/htdocs/sistemas-administrativo-tecnico-wireless/paginas/principal/check_cedula_api.php';
$output = ob_get_clean();

echo "API Response for V-12721951:\n";
echo $output . "\n";

// Test non-existing
$_GET['tipo_cedula'] = 'V';
$_GET['cedula'] = '99999999';
ob_start();
include 'c:/xampp/htdocs/sistemas-administrativo-tecnico-wireless/paginas/principal/check_cedula_api.php';
$output = ob_get_clean();

echo "\nAPI Response for V-99999999:\n";
echo $output . "\n";
?>
