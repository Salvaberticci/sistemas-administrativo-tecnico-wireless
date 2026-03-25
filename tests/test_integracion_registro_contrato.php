<?php
/**
 * Integration Test: Llamar guarda.php REAL para insertar un contrato de prueba
 * y verificar si [REGISTRO_CONTRATO] se guarda en cobros_manuales_historial.
 *
 * Crea: "TEST CONTRACT - Verificacion Concepto"
 * Verifica: Que la factura inicial tenga la etiqueta [REGISTRO_CONTRATO].
 * Cleanup: Elimina todos los registros de prueba.
 */

require_once __DIR__ . '/../paginas/conexion.php';

echo "=== INTEGRATION TEST: guarda.php -> [REGISTRO_CONTRATO] ===\n\n";

$passed = 0;
$failed = 0;

function pass($msg) { global $passed; $passed++; echo "✅ PASS: $msg\n"; }
function fail($msg) { global $failed; $failed++; echo "❌ FAIL: $msg\n"; }

// Obtener plan válido de la BD
$plan_res = $conn->query("SELECT id_plan, monto FROM planes LIMIT 1");
if (!$plan_res || $plan_res->num_rows === 0) {
    die("❌ ABORT: No hay planes en la BD.\n");
}
$plan = $plan_res->fetch_assoc();
$id_plan    = $plan['id_plan'];
$monto_plan = floatval($plan['monto']);

// Obtener OLT y PON válidos
$olt_row = $conn->query("SELECT id_olt FROM olt LIMIT 1")->fetch_assoc();
$pon_row = $conn->query("SELECT id_pon FROM pon LIMIT 1")->fetch_assoc();
$id_olt = $olt_row['id_olt'] ?? 1;
$id_pon = $pon_row['id_pon'] ?? 1;

$test_cedula = 'V' . rand(10000000, 99999999);
$test_ip     = '10.77.' . rand(1,254) . '.' . rand(2,254);
$test_mac    = 'TC:' . strtoupper(substr(md5(rand()), 0, 14));

echo "Plan: ID=$id_plan, Monto=\$$monto_plan\n";
echo "Cedula: $test_cedula | IP: $test_ip\n\n";

// =============================================
// STEP 1: Construir el mock de $_POST y llamar guarda.php
// =============================================
$mock_post = [
    'cedula'              => $test_cedula,
    'nombre_completo'     => 'TEST CONTRACT - Verificacion Concepto',
    'telefono'            => '04140000000',
    'correo'              => 'testcontract@verificacion.test',
    'id_municipio'        => '',
    'id_parroquia'        => '',
    'id_plan'             => $id_plan,
    'monto_plan'          => $monto_plan,
    'vendedor_texto'      => 'TEST SYSTEM',
    'direccion'           => 'Calle Test #0, Sistema de Pruebas',
    'fecha_instalacion'   => date('Y-m-d'),
    'tipo_instalacion'    => 'FTTH',
    'monto_instalacion'   => 0,
    'gastos_adicionales'  => 0,
    'monto_pagar'         => $monto_plan,
    'monto_pagado'        => $monto_plan,
    'medio_pago'          => 'Efectivo',
    'moneda_pago'         => 'USD',
    'tipo_conexion'       => 'FTTH',
    'mac_onu'             => $test_mac,
    'ip_onu'              => $test_ip,
    'id_olt'              => $id_olt,
    'id_pon'              => $id_pon,
    'ident_caja_nap'      => 'NAP-TEST',
    'puerto_nap'          => '1',
    'num_presinto_odn'    => 'ODN-TEST',
    'nap_tx_power'        => '-20',
    'onu_rx_power'        => '-22',
    'distancia_drop'      => '100',
    'punto_acceso'        => 'Test AP',
    'valor_conexion_dbm'  => '-20',
    'evidencia_fibra'     => '',
    'observaciones'       => 'REGISTRO DE PRUEBA - BORRAR',
];

// Crear archivo mock temporal (dentro del proyecto para evitar espacios en ruta en Windows)
$mock_file = __DIR__ . '/tmp_mock_registro_' . rand(100,999) . '.php';
$mock_php  = "<?php\n";
$mock_php .= "chdir(" . var_export(__DIR__ . '/../paginas/principal', true) . ");\n";
$mock_php .= '$_POST = ' . var_export($mock_post, true) . ";\n";
$mock_php .= '$_SERVER["REQUEST_METHOD"] = "POST";' . "\n";
$mock_php .= '$_FILES = [];' . "\n";
file_put_contents($mock_file, $mock_php);

echo "--- Llamando guarda.php via CLI ---\n";
$cmd    = 'php -d auto_prepend_file=' . escapeshellarg($mock_file) . ' ' . escapeshellarg(__DIR__ . '/../paginas/principal/guarda.php');
$output = shell_exec($cmd);
echo "Respuesta: $output\n";

unlink($mock_file);

// =============================================
// STEP 2: Verificar la respuesta JSON
// =============================================
$res = json_decode($output, true);

if (!$res) {
    fail("La respuesta de guarda.php no es JSON válido.");
    die("\n=== TEST ABORTADO ===\n");
}

if ($res['status'] === 'error') {
    fail("guarda.php retornó error: " . ($res['msg'] ?? 'sin mensaje'));
    die("\n=== TEST ABORTADO - No se creó el contrato ===\n");
}

if (!isset($res['id']) || $res['id'] <= 0) {
    fail("guarda.php no retornó un ID de contrato válido.");
    die("\n=== TEST ABORTADO ===\n");
}

$id_contrato = intval($res['id']);
pass("guarda.php OK - Contrato creado con ID: $id_contrato");

// =============================================
// STEP 3: Verificar el contrato en contratos
// =============================================
$c = $conn->query("SELECT cedula, nombre_completo FROM contratos WHERE id = $id_contrato")->fetch_assoc();
if ($c && $c['cedula'] === $test_cedula) {
    pass("Contrato encontrado en BD: '{$c['nombre_completo']}' (cedula: {$c['cedula']})");
} else {
    fail("Contrato NO encontrado en BD con id=$id_contrato.");
}

// =============================================
// STEP 4: Verificar factura en cuentas_por_cobrar
// =============================================
$cxc = $conn->query("SELECT id_cobro, monto_total FROM cuentas_por_cobrar WHERE id_contrato = $id_contrato")->fetch_assoc();
if ($cxc) {
    $id_cobro = $cxc['id_cobro'];
    pass("Factura creada en cuentas_por_cobrar con ID: $id_cobro, monto: \$" . $cxc['monto_total']);
} else {
    fail("NO se generó factura en cuentas_por_cobrar para el contrato $id_contrato.");
    $id_cobro = null;
}

// =============================================
// STEP 5: Verificar cobros_manuales_historial con [REGISTRO_CONTRATO]
// =============================================
if ($id_cobro) {
    $hist = $conn->query("SELECT justificacion, autorizado_por, monto_cargado FROM cobros_manuales_historial WHERE id_cobro_cxc = $id_cobro")->fetch_assoc();

    if (!$hist) {
        fail("❌ NO hay registro en cobros_manuales_historial para id_cobro=$id_cobro. La nueva lógica de guarda.php NO está funcionando.");
    } else {
        pass("Registro encontrado en cobros_manuales_historial.");
        echo "   → justificacion: " . $hist['justificacion'] . "\n";
        echo "   → autorizado_por: " . $hist['autorizado_por'] . "\n";
        echo "   → monto_cargado: " . $hist['monto_cargado'] . "\n\n";

        if (strpos($hist['justificacion'], '[REGISTRO_CONTRATO]') !== false) {
            pass("Justificación contiene '[REGISTRO_CONTRATO]' correctamente.");
        } else {
            fail("Justificación NO contiene '[REGISTRO_CONTRATO]'. La modificación en guarda.php NO está activa o hay un bug.");
            echo "   Valor actual: '" . $hist['justificacion'] . "'\n";
        }

        // Verificar que NO se muestre como Mensualidad genérica
        $conceptosArr = [];
        if (strpos($hist['justificacion'], '[MENSUALIDAD') !== false) $conceptosArr[] = 'Mensualidad';
        if (strpos($hist['justificacion'], '[REGISTRO_CONTRATO') !== false) $conceptosArr[] = 'Registro de Contrato';

        if (in_array('Registro de Contrato', $conceptosArr) && !in_array('Mensualidad', $conceptosArr)) {
            pass("Concepto final = 'Registro de Contrato' (NO 'Mensualidad').");
        } else {
            fail("Concepto resuelto incorrecto: " . implode(', ', $conceptosArr ?: ['(sin etiqueta, caería en Mensualidad genérica)']));
        }
    }
}

// =============================================
// CLEANUP
// =============================================
echo "\n--- Limpiando registros de prueba ---\n";
if ($id_cobro) {
    $conn->query("DELETE FROM cobros_manuales_historial WHERE id_cobro_cxc = $id_cobro");
    $conn->query("DELETE FROM cuentas_por_cobrar WHERE id_cobro = $id_cobro");
}
$conn->query("DELETE FROM clientes_deudores WHERE id_contrato = $id_contrato");
$conn->query("DELETE FROM contratos WHERE id = $id_contrato");
echo "Registros eliminados.\n";

// =============================================
// RESUMEN
// =============================================
echo "\n=== RESUMEN: {$passed} pasados | {$failed} fallidos ===\n";
if ($failed === 0) {
    echo "🎉 Todos los tests pasaron. La modificación funciona correctamente.\n";
} else {
    echo "⚠️  PROBLEMA DETECTADO. Revisar los errores arriba.\n";
}

$conn->close();
?>
