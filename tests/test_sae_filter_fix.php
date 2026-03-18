<?php
/**
 * Test: SAE Plus Filter Fix
 * Verifies that non-monthly payments (like installations) are excluded 
 * when filtering by SAE Plus status.
 */

require_once __DIR__ . '/../paginas/conexion.php';

function test_sae_filter() {
    global $conn;
    
    // 1. Ensure we have an Installation record to test against
    // We'll look for one or create a temporary one
    $res = $conn->query("SELECT cxc.id_cobro FROM cuentas_por_cobrar cxc 
                         JOIN cobros_manuales_historial h ON cxc.id_cobro = h.id_cobro_cxc 
                         WHERE h.justificacion LIKE '%instalacion%' LIMIT 1");
    
    if ($res->num_rows == 0) {
        echo "INFO: No installation record found in DB. Creating a temporary one for testing...\n";
        // Create a dummy contract if needed or use an existing one
        $conn->query("INSERT INTO cuentas_por_cobrar (id_contrato, fecha_emision, monto_total, estado, estado_sae_plus) VALUES (1, '2026-03-18', 50.00, 'PENDIENTE', 'NO CARGADO')");
        $id_temp = $conn->insert_id;
        $conn->query("INSERT INTO cobros_manuales_historial (id_cobro_cxc, justificacion, autorizado_por) VALUES ($id_temp, '[INSTALACION] Test record', 'TEST')");
    } else {
        $id_temp = null;
        echo "INFO: Using existing installation record for testing.\n";
    }

    // 2. Mock $_POST data for 'NO CARGADO' filter
    $_POST = [
        'draw' => 1,
        'start' => 0,
        'length' => 100,
        'search' => ['value' => ''],
        'order' => [['column' => 0, 'dir' => 'desc']],
        'estado_sae' => 'NO CARGADO'
    ];

    // 3. Capture output
    ob_start();
    $old_cwd = getcwd();
    chdir(__DIR__ . '/../paginas/principal');
    
    global $conn; // Ensure connection is available to the included script
    include 'server_process_mensualidades.php';
    
    chdir($old_cwd);
    $output = ob_get_clean();
    
    // 4. Extract JSON
    if (preg_match('/({.*})/', $output, $matches)) {
        $response = json_decode($matches[1], true);
    } else {
        echo "FAIL: No JSON found in output.\n";
        return false;
    }

    $data = $response['aaData'] ?? $response['data'] ?? [];
    $found_installation = false;
    
    foreach ($data as $row) {
        // Concepto is at index 3
        $concepto = $row[3];
        if (stripos($row[4], 'instalacion') !== false || stripos($concepto, 'instalación') !== false) {
            $found_installation = true;
            echo "FAIL: Installation record found in results despite SAE filter: " . $row[4] . "\n";
        }
    }

    // Cleanup temporary record
    if ($id_temp) {
        $conn->query("DELETE FROM cobros_manuales_historial WHERE id_cobro_cxc = $id_temp");
        $conn->query("DELETE FROM cuentas_por_cobrar WHERE id_cobro = $id_temp");
    }

    if (!$found_installation) {
        echo "PASS: No installation records found when filtering by SAE status.\n";
        return true;
    }
    return false;
}

echo "Starting tests for SAE Plus Filter Fix...\n";
if (test_sae_filter()) {
    echo "\nVerification SUCCESSFUL.\n";
} else {
    echo "\nVerification FAILED.\n";
    exit(1);
}
