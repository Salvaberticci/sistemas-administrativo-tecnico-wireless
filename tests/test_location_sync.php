<?php
/**
 * Test: Location Synchronization
 * Verifies:
 * 1. Renaming a municipality updates municipio_texto in contracts.
 * 2. Renaming a parroquia updates parroquia_texto in contracts.
 */

require_once __DIR__ . '/../paginas/conexion.php';

echo "=== TESTING: Location Synchronization ===\n";

$test_cedula = "V" . rand(1000000, 9999999);
$id_contrato = null;

try {
    // --- 1. SETUP: REGISTER CONTRACT WITH OLD LOCATION NAME ---
    echo "Registering contract with 'MUNICIPIO-OLD'...\n";
    $conn->query("INSERT INTO contratos (cedula, nombre_completo, municipio_texto, parroquia_texto, estado) 
                  VALUES ('$test_cedula', 'LOCATION TEST USER', 'MUNICIPIO-OLD', 'PARROQUIA-OLD', 'ACTIVO')");
    $id_contrato = $conn->insert_id;
    echo "✅ Contract $id_contrato registered.\n";

    // --- 2. TRIGGER SYNC (MUNICIPIO) ---
    echo "Renaming MUNICIPIO-OLD to MUNICIPIO-NEW...\n";
    
    $mock_post1 = [
        'tipo' => 'municipio',
        'old_value' => 'MUNICIPIO-OLD',
        'new_value' => 'MUNICIPIO-NEW'
    ];
    $mock_file = __DIR__ . '/tmp_mock_loc.php';
    file_put_contents($mock_file, "<?php \n\$_POST = " . var_export($mock_post1, true) . ";\n\$_SERVER['REQUEST_METHOD'] = 'POST';\nchdir(__DIR__ . '/../paginas/principal');\n");
    
    $cmd1 = "php -d auto_prepend_file=" . escapeshellarg($mock_file) . " " . escapeshellarg(__DIR__ . '/../paginas/principal/actualizar_ubicacion_contratos.php');
    $output1 = shell_exec($cmd1);
    
    echo "Response: $output1\n";
    $res1 = json_decode($output1, true);
    if (!$res1 || !$res1['success'] || $res1['updated'] < 1) {
        throw new Exception("Location sync (municipio) failed or no rows updated: " . ($res1['message'] ?? 'None'));
    }
    echo "✅ Cascading update for municipio successful.\n";

    // Verify in DB
    $contract = $conn->query("SELECT municipio_texto FROM contratos WHERE id = $id_contrato")->fetch_assoc();
    if ($contract['municipio_texto'] === 'MUNICIPIO-NEW') {
        echo "✅ SUCCESS: municipio_texto verified as 'MUNICIPIO-NEW'.\n";
    } else {
        throw new Exception("municipio_texto mismatch: " . $contract['municipio_texto']);
    }

    // --- 3. TRIGGER SYNC (PARROQUIA) ---
    echo "Renaming PARROQUIA-OLD to PARROQUIA-NEW...\n";
    
    $mock_post2 = [
        'tipo' => 'parroquia',
        'old_value' => 'PARROQUIA-OLD',
        'new_value' => 'PARROQUIA-NEW'
    ];
    file_put_contents($mock_file, "<?php \n\$_POST = " . var_export($mock_post2, true) . ";\n\$_SERVER['REQUEST_METHOD'] = 'POST';\nchdir(__DIR__ . '/../paginas/principal');\n");
    
    $output2 = shell_exec($cmd1); // Same command, different mock
    echo "Response: $output2\n";
    $res2 = json_decode($output2, true);
    if (!$res2 || !$res2['success'] || $res2['updated'] < 1) {
        throw new Exception("Location sync (parroquia) failed.");
    }
    
    // Verify in DB
    $contract2 = $conn->query("SELECT parroquia_texto FROM contratos WHERE id = $id_contrato")->fetch_assoc();
    if ($contract2['parroquia_texto'] === 'PARROQUIA-NEW') {
        echo "✅ SUCCESS: parroquia_texto verified as 'PARROQUIA-NEW'.\n";
    } else {
        throw new Exception("parroquia_texto mismatch: " . $contract2['parroquia_texto']);
    }

    echo "\n=== ALL TESTS PASSED SUCCESSFULLY ===\n";

} catch (Exception $e) {
    echo "❌ FAILED: " . $e->getMessage() . "\n";
} finally {
    // Cleanup
    if ($id_contrato) {
        $conn->query("DELETE FROM contratos WHERE id = $id_contrato");
        if (isset($mock_file) && file_exists($mock_file)) unlink($mock_file);
        echo "Cleanup completed.\n";
    }
    $conn->close();
}
?>
