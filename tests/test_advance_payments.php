<?php
/**
 * Test: Advance Payments Refactor
 * Verifies that multi-month manual payments create separate records
 * and that the monthly process correctly skips pre-paid clients.
 */

require_once __DIR__ . '/../paginas/conexion.php';

function get_bills_count($id_contrato) {
    global $conn;
    $res = $conn->query("SELECT COUNT(*) FROM cuentas_por_cobrar WHERE id_contrato = $id_contrato");
    return (int)$res->fetch_array()[0];
}

function run_manual_payment($id_contrato, $meses, $monto) {
    global $conn;
    $_SERVER["REQUEST_METHOD"] = "POST";
    $_POST = [
        'id_contrato' => $id_contrato,
        'monto' => $monto * $meses,
        'referencia_pago' => 'TEST_ADVANCE_' . uniqid(),
        'id_banco_pago' => 1,
        'autorizado_por' => 'TEST_BOT',
        'justificacion' => 'Test de adelanto',
        'desglose_mensualidad_activado' => '1',
        'monto_mensualidad' => $monto,
        'meses_mensualidad' => $meses
    ];

    ob_start();
    $old_cwd = getcwd();
    chdir(__DIR__ . '/../paginas/principal');
    
    // We expect a redirect (header Location)
    include 'generar_cobro_manual.php';
    
    chdir($old_cwd);
    ob_end_clean();
}

function run_monthly_job() {
    ob_start();
    $old_cwd = getcwd();
    chdir(__DIR__ . '/../paginas/principal');
    
    // Define global conn since it's used in generar_mensual.php
    global $conn;
    include 'generar_mensual.php';
    
    chdir($old_cwd);
    ob_end_clean();
}

echo "Starting Advance Payments Test...\n";

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

$initial_count = get_bills_count($id_test);
echo "Initial bills for contract $id_test: $initial_count\n";

// 2. Simulate 2 months payment
echo "Registering manual payment for 2 months...\n";
run_manual_payment($id_test, 2, $monto_plan);

$count_after_manual = get_bills_count($id_test);
echo "Bills after manual payment: $count_after_manual\n";

if ($count_after_manual != ($initial_count + 2)) {
    echo "FAIL: Expected " . ($initial_count + 2) . " bills, found $count_after_manual. Refactor failed to create separate records.\n";
    exit(1);
}
echo "PASS: Two separate records created for 2-month payment.\n";

// 3. Verify future date for the 2nd record
$res_dates = $conn->query("SELECT fecha_emision FROM cuentas_por_cobrar WHERE id_contrato = $id_test ORDER BY id_cobro DESC LIMIT 2");
$dates = [];
while($d = $res_dates->fetch_assoc()) $dates[] = $d['fecha_emision'];

echo "Dates created: " . implode(", ", $dates) . "\n";
$next_month_start = date('Y-m-01', strtotime("+1 month"));
if (!in_array($next_month_start, $dates)) {
    echo "FAIL: Next month emission date ($next_month_start) not found in records.\n";
    exit(1);
}
echo "PASS: Future emission date correctly set for advance record.\n";

// 4. Run monthly job and verify NO DUPLICATE for the current month
echo "Running monthly billing job...\n";
run_monthly_job();

$final_count = get_bills_count($id_test);
echo "Bills after monthly job: $final_count\n";

if ($final_count > $count_after_manual) {
    echo "FAIL: Monthly job created a duplicate bill for a pre-paid client.\n";
    exit(1);
}
echo "PASS: Monthly job correctly skipped the pre-paid client.\n";

// 5. Cleanup
echo "Cleaning up test records...\n";
// Get the last 2 IDs to delete their history first
$res_ids = $conn->query("SELECT id_cobro FROM cuentas_por_cobrar WHERE id_contrato = $id_test ORDER BY id_cobro DESC LIMIT 2");
$ids_to_del = [];
while($row = $res_ids->fetch_assoc()) $ids_to_del[] = $row['id_cobro'];

if (!empty($ids_to_del)) {
    $ids_str = implode(',', $ids_to_del);
    $conn->query("DELETE FROM cobros_manuales_historial WHERE id_cobro_cxc IN ($ids_str)");
    $conn->query("DELETE FROM cuentas_por_cobrar WHERE id_cobro IN ($ids_str)");
}

echo "\nAll Advance Payment tests PASSED.\n";
