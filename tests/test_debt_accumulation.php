<?php
/**
 * Test: Debt Accumulation
 * Verifies that the monthly billing process accumulates debt instead of overwriting records.
 */

require_once __DIR__ . '/../paginas/conexion.php';

function get_pending_count($id_contrato) {
    global $conn;
    $res = $conn->query("SELECT COUNT(*) FROM cuentas_por_cobrar WHERE id_contrato = $id_contrato AND estado = 'PENDIENTE'");
    return (int)$res->fetch_array()[0];
}

function run_billing_process() {
    global $conn;
    $old_cwd = getcwd();
    chdir(__DIR__ . '/../paginas/principal');
    
    // We must ensure $conn is available as a global variable
    // because generar_mensual.php uses it.
    
    ob_start();
    include 'generar_mensual.php';
    $output = ob_get_clean();
    
    chdir($old_cwd);
    return $output;
}

echo "Starting Debt Accumulation Test...\n";

// 1. Find an active contract
$res = $conn->query("SELECT id FROM contratos WHERE estado = 'ACTIVO' LIMIT 1");
if ($res->num_rows == 0) {
    die("FAIL: No active contracts found in DB to test with.\n");
}
$contract = $res->fetch_assoc();
$id_test = $contract['id'];

$initial_count = get_pending_count($id_test);
echo "Initial pending bills for contract $id_test: $initial_count\n";

// 2. Run first billing cycle
echo "Running first billing cycle...\n";
run_billing_process();
$count_1 = get_pending_count($id_test);
echo "Pending bills after 1st run: $count_1\n";

if ($count_1 <= $initial_count) {
    echo "FAIL: No new bill was created. Check if contract is correctly filtered in generar_mensual.php\n";
    exit(1);
}

// 3. Run second billing cycle
echo "Running second billing cycle...\n";
run_billing_process();
$count_2 = get_pending_count($id_test);
echo "Pending bills after 2nd run: $count_2\n";

if ($count_2 <= $count_1) {
    echo "FAIL: Debt did not accumulate. Record might have been overwritten or skipped.\n";
    exit(1);
}

echo "PASS: Debt accumulated correctly ($initial_count -> $count_1 -> $count_2).\n";

// 4. Cleanup (Delete only the bills created during this test)
// We'll delete the top 2 newest pending bills for this contract
echo "Cleaning up test records...\n";
$conn->query("DELETE FROM cuentas_por_cobrar WHERE id_contrato = $id_test AND estado = 'PENDIENTE' ORDER BY id_cobro DESC LIMIT 2");

echo "Test finished successfully.\n";
