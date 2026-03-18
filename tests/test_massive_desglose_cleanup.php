<?php
/**
 * Test: Massive Cleanup & Thorough Payment Types Registration
 * This test will CLEAR ALL PAYMENTS from the local DB (cuentas_por_cobrar & cobros_manuales_historial),
 * and then simulate the creation of various individual and combined payments.
 */

$_SERVER["REQUEST_METHOD"] = "POST";
require_once __DIR__ . '/../paginas/conexion.php';

echo "====== STARTING SUPER CLEANUP & VERIFICATION ======\n";

// 1. CLEAR TABLES
echo "1. Truncating tables...\n";
$conn->query("SET FOREIGN_KEY_CHECKS = 0;");
$conn->query("TRUNCATE TABLE cobros_manuales_historial");
$conn->query("TRUNCATE TABLE cuentas_por_cobrar");
$conn->query("SET FOREIGN_KEY_CHECKS = 1;");
echo " - Tables cleared.\n\n";

// 2. Getting Active Contracts for Test
$res = $conn->query("SELECT id, id_plan FROM contratos WHERE estado = 'ACTIVO' LIMIT 2");
if ($res->num_rows < 2) {
    die("FAIL: Need at least 2 active contracts for testing.\n");
}
$rows = [];
while ($r = $res->fetch_assoc()) $rows[] = $r;
$id_test_main = $rows[0]['id'];
$id_test_other = $rows[1]['id'];

$res_p = $conn->query("SELECT monto FROM planes WHERE id_plan = " . $rows[0]['id_plan']);
$monto_plan = (float)$res_p->fetch_assoc()['monto'];

// We will do several runs to test different features.

function do_manual_payment($post_data) {
    global $conn;
    $_POST = $post_data;
    
    ob_start();
    $old_cwd = getcwd();
    chdir(__DIR__ . '/../paginas/principal');
    
    // We isolate inside a function so variables don't bleed too much, 
    // but include behaves weirdly. We use an IIFE-like structure.
    call_user_func(function() {
        global $conn; // NEEDED for the include to see the connection!
        include 'generar_cobro_manual.php';
    });
    
    chdir($old_cwd);
    ob_end_clean();
}

// ============================================
// TEST A: Simple Mensualidad (1 mes)
// ============================================
echo "A. Testing simple 1-month Mensualidad...\n";
do_manual_payment([
    'id_contrato' => $id_test_main,
    'monto' => $monto_plan,
    'referencia_pago' => 'TEST_A_' . uniqid(),
    'id_banco_pago' => 1,
    'autorizado_por' => 'TEST_BOT',
    'justificacion' => 'Pago simple',
    'desglose_mensualidad_activado' => '1',
    'monto_mensualidad' => $monto_plan,
    'meses_mensualidad' => 1
]);

// ============================================
// TEST B: Instalación, Equipos, Prorrateo 
// ============================================
echo "B. Testing combined (Instalación + Equipos + Prorrateo)...\n";
do_manual_payment([
    'id_contrato' => $id_test_main,
    'monto' => 100.00,
    'referencia_pago' => 'TEST_B_' . uniqid(),
    'id_banco_pago' => 1,
    'autorizado_por' => 'TEST_BOT',
    'justificacion' => 'Instalacion y equipos combinados',
    'desglose_instalacion_activado' => '1',
    'monto_instalacion' => 40.00,
    'desglose_equipo_activado' => '1',
    'monto_equipo' => 45.00,
    'desglose_prorrateo_activado' => '1',
    'monto_prorrateo' => 15.00
]);

// ============================================
// TEST C: Pago de Terceros (1 mes)
// ============================================
echo "C. Testing Pago de Terceros (1 month)...\n";
do_manual_payment([
    'id_contrato' => $id_test_main,
    'monto' => 20.00,
    'referencia_pago' => 'TEST_C_' . uniqid(),
    'id_banco_pago' => 1,
    'autorizado_por' => 'TEST_BOT',
    'justificacion' => 'Pagando la mensualidad de la mama',
    'desglose_extra_activado' => '1',
    'extra_contrato' => [(string)$id_test_other],
    'extra_monto' => ['20.00'],
    'extra_meses' => ['1']
]);

// ============================================
// TEST D: Pago de Terceros (MULTIPLE MONTHS!)
// ============================================
echo "D. Testing Pago de Terceros (Advance 3 months!)...\n";
do_manual_payment([
    'id_contrato' => $id_test_main,
    'monto' => 60.00,
    'referencia_pago' => 'TEST_D_' . uniqid(),
    'id_banco_pago' => 1,
    'autorizado_por' => 'TEST_BOT',
    'justificacion' => 'Adelanto al primo',
    'desglose_extra_activado' => '1',
    'extra_contrato' => [(string)$id_test_other],
    'extra_monto' => ['60.00'],
    'extra_meses' => ['3']
]);

// ============================================
// VERIFICATION PHASE
// ============================================
echo "\n====== VERIFICATION ======\n";

$res = $conn->query("SELECT * FROM cuentas_por_cobrar ORDER BY id_cobro ASC");
$records = [];
while ($r = $res->fetch_assoc()) {
    // get history justification
    $res_h = $conn->query("SELECT justificacion FROM cobros_manuales_historial WHERE id_cobro_cxc = {$r['id_cobro']} LIMIT 1");
    $justif = $res_h->fetch_assoc()['justificacion'] ?? '';
    
    $records[] = [
        'id' => $r['id_cobro'],
        'monto' => (float)$r['monto_total'],
        'ref' => $r['referencia_pago'],
        'justif' => $justif,
        'fecha' => $r['fecha_emision']
    ];
}

$errors = 0;

function assertCheck($name, $condition, $fail_msg) {
    global $errors;
    if ($condition) {
        echo " [PASS] $name\n";
    } else {
        echo " [FAIL] $name: $fail_msg\n";
        $errors++;
    }
}

// Expected total records: A(1) + B(3) + C(1) + D(3) = 8
assertCheck("Total Rows", count($records) === 8, "Expected 8 rows, found " . count($records));

// Verify B: 3 separate concepts
$b_records = array_filter($records, fn($r) => str_starts_with($r['ref'], 'TEST_B'));
assertCheck("Test B (Separation)", count($b_records) === 3, "Expected 3 rows for B, got " . count($b_records));

// Verify D: 3 separate months for Third-Party
$d_records = array_filter($records, fn($r) => str_starts_with($r['ref'], 'TEST_D'));
assertCheck("Test D (Terceros Multi-month)", count($d_records) === 3, "Expected 3 rows for multi-month Terceros, got " . count($d_records));

// Sort D to check months
usort($d_records, fn($a, $b) => strcmp($a['fecha'], $b['fecha']));
if (count($d_records) == 3) {
    assertCheck("Test D (Correct Montos)", $d_records[0]['monto'] == 20.00 && $d_records[1]['monto'] == 20.00, "Amounts were not divided correctly.");
    assertCheck("Test D (Different Months)", $d_records[0]['fecha'] != $d_records[1]['fecha'], "Emission dates were not projected for 3rd party.");
}

echo "\n====== RESULTS ======\n";
if ($errors == 0) {
    echo "ALL TESTS PASSED PERFECTLY!\n";
} else {
    echo "$errors ERRORS FOUND.\n";
    // Dump for debug
    print_r($records);
    exit(1);
}
