<?php
require 'paginas/conexion.php';

echo "--- Iniciando Test de Separación de Instaladores ---\n";
$_SERVER['REQUEST_METHOD'] = 'POST';

function testSave($conn, $tipo, $installer_val) {
    echo "Probando para tipo: $tipo con instalador: $installer_val\n";
    
    $_POST['cedula'] = 'V' . time() . rand(10, 99);
    $_POST['nombre_completo'] = 'CLIENTE TEST INSTALLER';
    $_POST['id_municipio'] = 'Escuque';
    $_POST['id_parroquia'] = 'Sabana Libre';
    $_POST['tipo_conexion'] = $tipo;
    
    if ($tipo === 'FTTH') {
        $_POST['instalador_ftth'] = $installer_val;
        $_POST['instalador_radio'] = '';
    } else {
        $_POST['instalador_ftth'] = '';
        $_POST['instalador_radio'] = $installer_val;
    }

    // Mock other required fields
    $_POST['monto_instalacion'] = 0;
    $_POST['monto_pagar'] = 0;
    $_POST['monto_pagado'] = 0;

    ob_start();
    $old_cwd = getcwd();
    chdir(__DIR__ . '/../paginas/soporte');
    error_reporting(0); // Suppress warnings like 'headers already sent' during test
    @include 'guardar_contrato_instalador.php';
    error_reporting(E_ALL);
    chdir($old_cwd);
    $res_json = ob_get_clean();
    $res = json_decode($res_json, true);

    if ($res && $res['status'] === 'success') {
        $id = $res['id'];
        echo "[OK] Registro creado con ID: $id\n";
        
        require 'paginas/conexion.php'; // Re-open since it's closed by the included script
        $check = $conn->query("SELECT instalador, instalador_c FROM contratos WHERE id = $id")->fetch_assoc();
        if ($tipo === 'FTTH') {
            if ($check['instalador'] === $installer_val && empty($check['instalador_c'])) {
                echo "[SUCCESS] FTTH guardó correctamente en 'instalador'.\n";
            } else {
                echo "[FAIL] FTTH no guardó correctamente. DB: " . json_encode($check) . "\n";
            }
        } else {
            if ($check['instalador_c'] === $installer_val && empty($check['instalador'])) {
                echo "[SUCCESS] RADIO guardó correctamente en 'instalador_c'.\n";
            } else {
                echo "[FAIL] RADIO no guardó correctamente. DB: " . json_encode($check) . "\n";
            }
        }
        
        // Cleanup
        $conn->query("DELETE FROM contratos WHERE id = $id");
        $conn->query("DELETE FROM clientes_deudores WHERE id_contrato = $id");
        $conn->query("DELETE FROM cuentas_por_cobrar WHERE id_contrato = $id");
    } else {
        echo "[FAIL] Error al guardar: " . ($res['msg'] ?? 'Desconocido') . "\n";
        echo "JSON: $res_json\n";
    }
}

// testSave($conn, 'FTTH', 'INST_FTTH_TEST');
testSave($conn, 'RADIO', 'INST_RADIO_TEST');

echo "--- Test Finalizado ---\n";
?>
