<?php
/**
 * Test: Monthly Billing Filters
 * Verifies that the server-side processing correctly filters by payment status.
 */

require_once __DIR__ . '/../paginas/conexion.php';

function test_filter($status) {
    global $conn;
    
    // Mock $_POST data
    $_POST = [
        'draw' => 1,
        'start' => 0,
        'length' => 10,
        'search' => ['value' => ''],
        'order' => [['column' => 0, 'dir' => 'desc']],
        'estado_pago' => $status
    ];

    // Capture output
    ob_start();
    $old_cwd = getcwd();
    chdir(__DIR__ . '/../paginas/principal');
    
    // Define $conn as global since it's used inside the included script
    global $conn;
    
    include 'server_process_mensualidades.php';
    chdir($old_cwd);
    $output = ob_get_clean();
    
    // Extract JSON using regex (to ignore warnings)
    if (preg_match('/({.*})/', $output, $matches)) {
        $response = json_decode($matches[1], true);
    } else {
        echo "FAIL: No JSON found in output for status '$status'. Output: " . substr($output, 0, 100) . "...\n";
        return false;
    }
    
    if (!$response || isset($response['error'])) {
        echo "FAIL: Error in response for status '$status': " . ($response['error'] ?? 'Invalid JSON') . "\n";
        return false;
    }

    $data = $response['aaData'] ?? $response['data'] ?? [];
    
    foreach ($data as $row) {
        // The status is at index 7 in the returned array (or similar, checking the server script)
        // In server_process_mensualidades.php: 
        // 0: Fecha, 1: Ref, 2: Cliente, 3: Concepto, 4: Detalle, 5: Monto, 6: Cuenta, 7: Estado (Badge)
        // Wait, index 7 is the Badge HTML. We need the raw status.
        // Let's check the server script again. 
        // 0:Fecha, 1:Ref, 2:Cliente, 3:Concepto, 4:Detalle, 5:Monto, 6:Cuenta, 7:Estado Badge
        
        $status_html = $row[7];
        if ($status !== '' && strpos($status_html, $status) === false) {
            echo "FAIL: Found record with incorrect status. Expected '$status', got: $status_html\n";
            return false;
        }
    }

    echo "PASS: Filter for status '$status' returned " . count($data) . " records correctly.\n";
    return true;
}

echo "Starting tests for Monthly Billing Filters...\n";

$all_pass = true;
$all_pass &= test_filter('PENDIENTE');
$all_pass &= test_filter('PAGADO');
$all_pass &= test_filter(''); // Any status

if ($all_pass) {
    echo "\nAll filtering tests PASSED successfully.\n";
} else {
    echo "\nSome tests FAILED.\n";
    exit(1);
}
