<?php
/**
 * Test: Remote Signature Lifecycle
 * Verifies:
 * 1. Token generation.
 * 2. Signature processing via token.
 * 3. Contract status update.
 */

require_once __DIR__ . '/../paginas/conexion.php';

echo "=== TESTING: Remote Signature Lifecycle ===\n";

$test_cedula = "V" . rand(1000000, 9999999);
$id_contrato = null;

try {
    // --- 1. SETUP: REGISTER CONTRACT ---
    $conn->query("INSERT INTO contratos (cedula, nombre_completo, estado) VALUES ('$test_cedula', 'SIGNATURE TEST USER', 'ACTIVO')");
    $id_contrato = $conn->insert_id;
    echo "✅ Contract $id_contrato registered.\n";

    // --- 2. GENERATE TOKEN ---
    echo "Generating signature token...\n";
    
    $mock_post1 = ['id' => $id_contrato];
    $mock_file1 = __DIR__ . '/tmp_mock_token.php';
    file_put_contents($mock_file1, "<?php \n\$_POST = " . var_export($mock_post1, true) . ";\n\$_SERVER['REQUEST_METHOD'] = 'POST';\nchdir(__DIR__ . '/../paginas/principal');\n");
    
    $cmd1 = "php -d auto_prepend_file=" . escapeshellarg($mock_file1) . " " . escapeshellarg(__DIR__ . '/../paginas/principal/generar_token_firma.php');
    $output1 = shell_exec($cmd1);
    
    echo "Response (Generate): $output1\n";
    $res1 = json_decode($output1, true);
    if (!$res1 || !$res1['success']) {
        throw new Exception("Token generation failed: " . ($res1['message'] ?? 'Unknown error'));
    }
    $token = $res1['token'];
    echo "✅ Token generated: $token\n";

    // --- 3. SUBMIT SIGNATURE ---
    echo "Submitting remote signature...\n";
    
    $mock_post2 = [
        'token' => $token,
        'type' => 'contrato',
        'firma_data' => 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==' // 1x1 png
    ];
    $mock_file2 = __DIR__ . '/tmp_mock_sign.php';
    file_put_contents($mock_file2, "<?php \n\$_POST = " . var_export($mock_post2, true) . ";\n\$_SERVER['REQUEST_METHOD'] = 'POST';\nchdir(__DIR__ . '/../paginas/soporte');\n");
    
    $cmd2 = "php -d auto_prepend_file=" . escapeshellarg($mock_file2) . " " . escapeshellarg(__DIR__ . '/../paginas/soporte/procesar_firma_remota.php');
    $output2 = shell_exec($cmd2);
    
    echo "Response (Submit): $output2\n";
    $res2 = json_decode($output2, true);
    if (!$res2 || !$res2['success']) {
        throw new Exception("Signature submission failed: " . ($res2['message'] ?? 'Unknown error'));
    }
    echo "✅ Signature submitted successfully.\n";

    // --- 4. VERIFICATION ---
    $contract = $conn->query("SELECT * FROM contratos WHERE id = $id_contrato")->fetch_assoc();
    
    if ($contract['estado_firma'] === 'COMPLETADO' && is_null($contract['token_firma']) && !empty($contract['firma_cliente'])) {
        echo "✅ SUCCESS: Contract signature verified as COMPLETADO.\n";
    } else {
        throw new Exception("Contract signature verification failed. Status=" . $contract['estado_firma'] . " Token=" . $contract['token_firma']);
    }

    echo "\n=== ALL TESTS PASSED SUCCESSFULLY ===\n";

} catch (Exception $e) {
    echo "❌ FAILED: " . $e->getMessage() . "\n";
} finally {
    // Cleanup
    if ($id_contrato) {
        $conn->query("DELETE FROM contratos WHERE id = $id_contrato");
        if (isset($mock_file1) && file_exists($mock_file1)) unlink($mock_file1);
        if (isset($mock_file2) && file_exists($mock_file2)) unlink($mock_file2);
        echo "Cleanup completed.\n";
    }
    $conn->close();
}
?>
