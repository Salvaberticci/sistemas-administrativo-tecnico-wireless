<?php
/**
 * Comprehensive Test Script: Contract Workflow
 * Verifies:
 * 1. Registration (guarda.php logic) without sae_plus, with numero_onu and multi-installer.
 * 2. Update (actualizar_contrato_ajax.php logic) with sae_plus, modified numero_onu and installers.
 */

require_once __DIR__ . '/../paginas/conexion.php';

echo "=== STARTING COMPREHENSIVE WORKFLOW TEST ===\n";

$test_id = 888888;
$conn->query("DELETE FROM contratos WHERE id = $test_id");

// --- PHASE 1: REGISTRATION ---
echo "\n--- PHASE 1: REGISTRATION ---\n";

$reg_data = [
    'id' => $test_id,
    'cedula' => 'V-12345678',
    'nombre_completo' => 'COMPREHENSIVE TEST USER',
    'id_municipio' => 1,
    'id_parroquia' => 1,
    'id_plan' => 1,
    'monto_plan' => 30.00,
    'vendedor_texto' => 'VENDEDOR A',
    'direccion' => 'CALLE TEST 123',
    'instaladores' => ['JUAN PEREZ', 'MARIA LOPEZ'], // Mocking array from form
    'numero_onu' => 'ONU-REG-001',
    'tipo_instalacion' => 'FTTH',
    'monto_instalacion' => 45.00,
    'monto_pagar' => 75.00,
    'monto_pagado' => 75.00,
    'estado' => 'ACTIVO',
    'fecha_instalacion' => date('Y-m-d')
];

// Combine installers for DB
$instalador_str = implode(', ', $reg_data['instaladores']);

$sql_ins = "INSERT INTO contratos (
    id, cedula, nombre_completo, id_municipio, id_parroquia, id_plan, monto_plan, vendedor_texto,
    direccion, instalador, numero_onu, tipo_instalacion, monto_instalacion, monto_pagar, monto_pagado,
    estado, fecha_instalacion
) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

$stmt = $conn->prepare($sql_ins);
if (!$stmt) die("Prepare Phase 1 failed: " . $conn->error . "\n");

$types_ins = "isssiidsssssdddds"; // 17 fields
$stmt->bind_param(
    $types_ins,
    $reg_data['id'], $reg_data['cedula'], $reg_data['nombre_completo'], $reg_data['id_municipio'],
    $reg_data['id_parroquia'], $reg_data['id_plan'], $reg_data['monto_plan'], $reg_data['vendedor_texto'],
    $reg_data['direccion'], $instalador_str, $reg_data['numero_onu'], $reg_data['tipo_instalacion'],
    $reg_data['monto_instalacion'], $reg_data['monto_pagar'], $reg_data['monto_pagado'],
    $reg_data['estado'], $reg_data['fecha_instalacion']
);

if ($stmt->execute()) {
    echo "SUCCESS: Registration simulated.\n";
} else {
    die("FAILED: Registration failed: " . $stmt->error . "\n");
}

// Verification Phase 1
$res1 = $conn->query("SELECT instalador, numero_onu, sae_plus FROM contratos WHERE id = $test_id");
$row1 = $res1->fetch_assoc();

if ($row1['instalador'] === 'JUAN PEREZ, MARIA LOPEZ') echo "✅ Installer saved correctly: " . $row1['instalador'] . "\n";
else echo "❌ Installer error: " . $row1['instalador'] . "\n";

if ($row1['numero_onu'] === 'ONU-REG-001') echo "✅ Numero ONU saved correctly.\n";
else echo "❌ Numero ONU error: " . $row1['numero_onu'] . "\n";

if (empty($row1['sae_plus'])) echo "✅ SAE Plus is empty as expected for new registration.\n";
else echo "❌ SAE Plus unexpected value: " . $row1['sae_plus'] . "\n";


// --- PHASE 2: UPDATE ---
echo "\n--- PHASE 2: UPDATE ---\n";

$upd_data = [
    'id' => $test_id,
    'instaladores' => ['PEDRO PABLO'], // Change installers
    'numero_onu' => 'ONU-UPD-999',    // Change ONU
    'sae_plus' => 'SAE-FINAL-123'      // Add SAE PLUS (only in update)
];

// Combine installers for DB
$instalador_upd = implode(', ', $upd_data['instaladores']);

$sql_upd = "UPDATE contratos SET 
    instalador = ?, 
    numero_onu = ?, 
    sae_plus = ? 
    WHERE id = ?";

$stmt_upd = $conn->prepare($sql_upd);
if (!$stmt_upd) die("Prepare Phase 2 failed: " . $conn->error . "\n");

$stmt_upd->bind_param("sssi", $instalador_upd, $upd_data['numero_onu'], $upd_data['sae_plus'], $upd_data['id']);

if ($stmt_upd->execute()) {
    echo "SUCCESS: Update simulated.\n";
} else {
    die("FAILED: Update failed: " . $stmt_upd->error . "\n");
}

// Verification Phase 2
$res2 = $conn->query("SELECT instalador, numero_onu, sae_plus FROM contratos WHERE id = $test_id");
$row2 = $res2->fetch_assoc();

if ($row2['instalador'] === 'PEDRO PABLO') echo "✅ Installer updated correctly: " . $row2['instalador'] . "\n";
else echo "❌ Installer update error: " . $row2['instalador'] . "\n";

if ($row2['numero_onu'] === 'ONU-UPD-999') echo "✅ Numero ONU updated correctly.\n";
else echo "❌ Numero ONU update error: " . $row2['numero_onu'] . "\n";

if ($row2['sae_plus'] === 'SAE-FINAL-123') echo "✅ SAE Plus added correctly via update.\n";
else echo "❌ SAE Plus update error: " . $row2['sae_plus'] . "\n";


// --- CLEANUP ---
$conn->query("DELETE FROM contratos WHERE id = $test_id");
echo "\n=== CLEANED UP AND TEST COMPLETED ===\n";
$conn->close();
?>
