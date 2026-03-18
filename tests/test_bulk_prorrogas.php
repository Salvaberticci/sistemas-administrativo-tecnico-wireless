<?php
/**
 * Test: Bulk Prorrogas
 * Verifies:
 * 1. advancing month for regular extensions.
 * 2. cleaning up processed temporary extensions.
 */

require_once __DIR__ . '/../paginas/conexion.php';

echo "=== TESTING: Bulk Prorrogas ===\n";

try {
    // --- 1. SETUP: TEST RECORDS ---
    echo "Inserting test prorrogas...\n";
    
    // Regular (SI), should advance month
    $date1 = '2026-03-01';
    $conn->query("INSERT INTO prorrogas (nombre_titular, fecha_corte, prorroga_regular) VALUES ('REGULAR TEST', '$date1', 'SI')");
    $id1 = $conn->insert_id;
    
    // Temporary (NO), processed, should be deleted
    $conn->query("INSERT INTO prorrogas (nombre_titular, prorroga_regular, estado) VALUES ('TEMP TEST', 'NO', 'PROCESADO')");
    $id2 = $conn->insert_id;
    
    echo "✅ Setup complete. P1=$id1, P2=$id2.\n";

    // --- 2. ACTION: AVANZAR MES ---
    echo "Executing 'avanzar_mes' action...\n";
    
    $json1 = '{"action":"avanzar_mes"}';
    $script_path = __DIR__ . '/../paginas/principal/acciones_bulk_prorrogas.php';
    
    // Use eval approach to mock php://input reliably on any platform
    function run_bulk_action($script_path, $json_input) {
        $code = file_get_contents($script_path);
        // Robust replacement: swap the file_get_contents call with the literal JSON string
        // We use single quotes for the PHP string, so we only need to escape single quotes (if any)
        $escaped_json = str_replace("'", "\\'", $json_input);
        $code = preg_replace("/file_get_contents\(['\"]php:\/\/input['\"]\)/i", "'" . $escaped_json . "'", $code);
        // Fix relative include for the eval context
        $code = str_replace("require_once '../conexion.php';", "global \$conn;", $code);
        
        // DEBUG: Uncomment to see the code being eval'd
        // file_put_contents(__DIR__ . '/debug_eval.php', $code);
        
        ob_start();
        eval('?>' . $code);
        return ob_get_clean();
    }

    $output1 = run_bulk_action($script_path, $json1);
    
    echo "Response (Avanzar): $output1\n";
    // Extract JSON in case there were warnings
    if (preg_match('/\{.*\}/s', $output1, $matches)) {
        $res1 = json_decode($matches[0], true);
    } else {
        $res1 = null;
    }
    
    if (!$res1 || !$res1['success']) {
        throw new Exception("Avanzar mes failed: " . ($output1 ?: 'Empty output'));
    }

    // Verify Date in DB
    $p1 = $conn->query("SELECT fecha_corte FROM prorrogas WHERE id_prorroga = $id1")->fetch_assoc();
    if ($p1['fecha_corte'] === '2026-04-01') {
        echo "✅ SUCCESS: Regular prorroga date advanced.\n";
    } else {
        throw new Exception("Date arithmetic failed: " . $p1['fecha_corte']);
    }

    // --- 3. ACTION: LIMPIAR TEMPORALES ---
    echo "Executing 'limpiar_temporales' action...\n";
    
    $json2 = '{"action":"limpiar_temporales"}';
    $output2 = run_bulk_action($script_path, $json2);
    
    echo "Response (Limpiar): $output2\n";
    if (preg_match('/\{.*\}/s', $output2, $matches)) {
        $res2 = json_decode($matches[0], true);
    } else {
        $res2 = null;
    }

    if (!$res2 || !$res2['success']) {
        throw new Exception("Limpiar failed: " . ($output2 ?: 'Empty output'));
    }

    // Verify Deletion
    $check2 = $conn->query("SELECT id_prorroga FROM prorrogas WHERE id_prorroga = $id2");
    if ($check2->num_rows === 0) {
        echo "✅ SUCCESS: Temporary processed prorroga was deleted.\n";
    } else {
        throw new Exception("Deletion failed for temporary prorroga.");
    }

    echo "\n=== ALL TESTS PASSED SUCCESSFULLY ===\n";

} catch (Exception $e) {
    echo "❌ FAILED: " . $e->getMessage() . "\n";
} finally {
    // Cleanup
    if (isset($id1)) $conn->query("DELETE FROM prorrogas WHERE id_prorroga = $id1");
    if (isset($id2)) $conn->query("DELETE FROM prorrogas WHERE id_prorroga = $id2");
    if (isset($mock_file) && file_exists($mock_file)) unlink($mock_file);
    echo "Cleanup completed.\n";
    $conn->close();
}
?>
