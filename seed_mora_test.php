<?php
/**
 * seed_mora_test.php
 * Inserta registros de prueba de mensualidades vencidas (2026)
 *
 * Meses (Hoy es 2026-03-19):
 * ENERO:   Vence 2026-01-05
 * FEBRERO: Vence 2026-02-05
 * MARZO:   Vence 2026-03-05
 *
 * Clientes:
 * ID 780 (CARMEN): 3 meses (Ene, Feb, Mar) -> Aparece en >=1, >=2, >=3
 * ID 783 (DALIA):  3 meses (Ene, Feb, Mar) -> Aparece en >=1, >=2, >=3
 * ID 781 (DARWIN): 2 meses (Feb, Mar)      -> Aparece en >=1, >=2
 * ID 784 (MORELA): 2 meses (Feb, Mar)      -> Aparece en >=1, >=2
 * ID 782 (OMAR):   1 mes (Mar)             -> Aparece en >=1
 */

require 'paginas/conexion.php';

$accion = $_GET['accion'] ?? ($argv[1] ?? 'insertar');

if ($accion === 'eliminar') {
    $conn->query("DELETE FROM cuentas_por_cobrar WHERE referencia_pago LIKE 'TEST-MORA-%'");
    $filas = $conn->affected_rows;
    echo "Eliminados $filas registros de prueba.\n";
    $conn->close();
    exit;
}

// Limpiar antes de insertar
$conn->query("DELETE FROM cuentas_por_cobrar WHERE referencia_pago LIKE 'TEST-MORA-%'");

$ids = [780, 783, 781, 784, 782];
$contratos = [];
$res = $conn->query("SELECT id, nombre_completo, monto_plan FROM contratos WHERE id IN (" . implode(',', $ids) . ")");
while ($r = $res->fetch_assoc()) { $contratos[$r['id']] = $r; }

$meses = [
    'ENERO'   => ['e' => '2026-01-01', 'v' => '2026-01-05'],
    'FEBRERO' => ['e' => '2026-02-01', 'v' => '2026-02-05'],
    'MARZO'   => ['e' => '2026-03-01', 'v' => '2026-03-05'],
];

$data = [
    780 => ['ENERO', 'FEBRERO', 'MARZO'],
    783 => ['ENERO', 'FEBRERO', 'MARZO'],
    781 => ['FEBRERO', 'MARZO'],
    784 => ['FEBRERO', 'MARZO'],
    782 => ['MARZO'],
];

$insertados = 0;
foreach ($data as $id => $listaMeses) {
    if (!isset($contratos[$id])) continue;
    $monto = $contratos[$id]['monto_plan'] > 0 ? $contratos[$id]['monto_plan'] : 10.00;
    foreach ($listaMeses as $mName) {
        $emision = $meses[$mName]['e'];
        $vence   = $meses[$mName]['v'];
        $ref     = "TEST-MORA-$id-$mName";
        $sql = "INSERT INTO cuentas_por_cobrar 
                (id_contrato, fecha_emision, fecha_vencimiento, monto_total, estado, origen, estado_sae_plus, referencia_pago)
                VALUES ($id, '$emision', '$vence', $monto, 'PENDIENTE', 'SISTEMA', 'NO CARGADO', '$ref')";
        if ($conn->query($sql)) $insertados++;
    }
}

echo "Insertados $insertados registros de prueba (2026).\n";
$conn->close();
?>
