<?php
$baseUrl = 'http://localhost/sistemas-administrativo-tecnico-wireless';

$baseData = [
    'cedula' => 'V99999999',
    'telefono' => '0414-1234567',
    'correo' => 'test@example.com',
    'id_municipio' => '1',
    'id_parroquia' => '1',
    'id_plan' => '1',
    'vendedor_texto' => 'Vendedor Test',
    'direccion' => 'Direccion de prueba',
    'fecha_instalacion' => date('Y-m-d'),
    'ident_caja_nap' => 'Caja-Test',
    'puerto_nap' => '10',
    'num_presinto_odn' => 'ODN-01',
    'id_olt' => '1',
    'id_pon' => '1',
    'tipo_conexion' => 'FTTH',
    'mac_onu' => 'AA:BB:CC:DD:EE:FF',
    'nap_tx_power' => '-20.5',
    'onu_rx_power' => '-22.1',
    'distancia_drop' => '50',
    'monto_instalacion' => '50',
    'monto_pagar' => '50',
    'monto_pagado' => '50',
    'medio_pago' => 'Efectivo',
    'estado_contrato' => 'ACTIVO' // used by installer
];

function testEndpoint($url, $data) {
    echo "========================================\n";
    echo "Testing Endpoint: $url\n";
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    echo "HTTP Status: $httpCode\n";
    echo "Response: $response\n";
}

// 1. Test Admin form
$dataAdmin = $baseData;
$dataAdmin['nombre_completo'] = 'Test Admin ' . uniqid();
$dataAdmin['ip_onu'] = '192.168.1.' . rand(10, 250);
$urlAdmin = $baseUrl . '/paginas/principal/guarda.php';
testEndpoint($urlAdmin, $dataAdmin);

// 2. Test Installer form
$dataInst = $baseData;
$dataInst['nombre_completo'] = 'Test Installer ' . uniqid();
$dataInst['ip_onu'] = '192.168.1.' . rand(10, 250);
// Ensure we use the fallback or normal nap fields
$dataInst['ident_caja_nap'] = 'Caja-Inst-Test';
$dataInst['puerto_nap'] = '20';
$urlInstaller = $baseUrl . '/paginas/soporte/guardar_contrato_instalador.php';
testEndpoint($urlInstaller, $dataInst);

echo "========================================\n";
?>
