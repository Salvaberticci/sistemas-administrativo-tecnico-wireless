<?php
/**
 * Test: Expiration Days & Desglose
 * Verifies that a manual payment with Desglose separates into distinct rows
 * and that the server-side processor correctly calculates expiration days.
 */

// Define this clearly so the backend scripts don't redirect or close connections blindly
$_SERVER["REQUEST_METHOD"] = "POST";

require_once __DIR__ . '/../paginas/conexion.php';

echo "Starting Verification Test: Expiration Days & Desglose...\n\n";

// 1. Find an active contract
$res = $conn->query("SELECT id, id_plan FROM contratos WHERE estado = 'ACTIVO' LIMIT 1");
if ($res->num_rows == 0) {
    die("FAIL: No active contracts found.\n");
}
$contract = $res->fetch_assoc();
$id_test = $contract['id'];

// Get plan amount
$res_p = $conn->query("SELECT monto FROM planes WHERE id_plan = " . $contract['id_plan']);
$monto_plan = (float)$res_p->fetch_assoc()['monto'];

// 2. Generate a reference
$reference = 'TEST_DESGLOSE_' . uniqid();

// 3. Mock POST for manual payment with Desglose (Mensualidad + Instalacion)
$_POST = [
    'id_contrato' => $id_test,
    'monto' => $monto_plan + 50.00, // Total
    'referencia_pago' => $reference,
    'id_banco_pago' => 1,
    'autorizado_por' => 'TEST_BOT',
    'justificacion' => 'Test de desglose y vencimiento',
    'desglose_mensualidad_activado' => '1', // The crucial flag
    'monto_mensualidad' => $monto_plan,
    'meses_mensualidad' => 1,
    'desglose_instalacion_activado' => '1', // Added missing flag
    'monto_instalacion' => 50.00
];

echo "1. Simulating Manual Payment Submission (Mensualidad + Instalacion)...\n";
ob_start();
$old_cwd = getcwd();
chdir(__DIR__ . '/../paginas/principal');

// Run the manual payment generation
include 'generar_cobro_manual.php';

chdir($old_cwd);
ob_end_clean();

// 4. Verify Database Records
$res_records = $conn->query("SELECT id_cobro, monto_total FROM cuentas_por_cobrar WHERE referencia_pago = '$reference'");
$count = $res_records->num_rows;

echo "2. Checking Database Records...\n";
if ($count !== 2) {
    echo "FAIL: Expected 2 separate records, found $count.\n";
    exit(1);
}
echo "PASS: Manual payment successfully separated into 2 distinct rows!\n";

// Get the IDs to manipulate
$ids = [];
while ($row = $res_records->fetch_assoc()) {
    $ids[] = $row['id_cobro'];
}

// 5. Force the Mensualidad to be PENDIENTE and expire in exactly 5 days
$expiration_target = date('Y-m-d', strtotime('+5 days'));
// The loop in generar_cobro_manual inserts Mensualidad first if both exist.
$id_mensualidad = $ids[0]; 

$conn->query("UPDATE cuentas_por_cobrar SET estado = 'PENDIENTE', fecha_vencimiento = '$expiration_target' WHERE id_cobro = $id_mensualidad");

// 6. Test DataTables Server Processor
echo "3. Querying Server-Side Processor (Datatables)...\n";

$_POST = [
    'draw' => 1,
    'start' => 0,
    'length' => 10,
    'order' => [['column' => 0, 'dir' => 'desc']],
    'referencia' => $reference
];

ob_start();
chdir(__DIR__ . '/../paginas/principal');
include 'server_process_mensualidades.php';
chdir($old_cwd);
$json_output = ob_get_clean();

// Use regex since json might contain warnings if error handling isn't perfect
preg_match('/\{.*\}/s', $json_output, $matches);
if (empty($matches)) {
    echo "FAIL: Could not parse JSON from server_process_mensualidades.\n";
    echo "Output was:\n$json_output\n";
    exit(1);
}

$data = json_decode($matches[0], true);
if (!$data || !isset($data['aaData'])) {
    echo "FAIL: Invalid Datatables JSON format.\n";
    exit(1);
}

$rows = $data['aaData'];

if (count($rows) !== 2) {
    echo "FAIL: Expected Datatables to return 2 distinct rows for this reference, got " . count($rows) . ".\n";
    exit(1);
}
echo "PASS: Datatables successfully returns individual rows for the separated concepts!\n";

// Verify expiration logic
$found_expiration = false;
foreach ($rows as $row) {
    // Row 7 is 'Estado'
    $estado_html = $row[7];
    if (strpos($estado_html, 'PENDIENTE') !== false) {
        if (strpos($estado_html, 'en 5 día(s)') !== false) {
            $found_expiration = true;
        }
        echo "   HTML Output for Status: " . strip_tags(str_replace('<br>', ' | ', $estado_html)) . "\n";
    }
}

if (!$found_expiration) {
    echo "FAIL: The expiration text 'en 5 día(s)' was not found in the status column output.\n";
    exit(1);
}
echo "PASS: The 'Days Remaining' (en 5 día(s)) indicator is correctly generated and injected!\n";

// 7. Cleanup
echo "4. Cleaning up test records...\n";
require __DIR__ . '/../paginas/conexion.php'; // Reconnect because server_process_mensualidades closes it
$ids_str = implode(',', $ids);
$conn->query("DELETE FROM cobros_manuales_historial WHERE id_cobro_cxc IN ($ids_str)");
$conn->query("DELETE FROM cuentas_por_cobrar WHERE id_cobro IN ($ids_str)");

echo "\nALL TESTS PASSED SUCCESSFULLY.\n";
