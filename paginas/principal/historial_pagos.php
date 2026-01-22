<?php
// Incluye su archivo de conexión
require_once '../conexion.php'; 

// ----------------------------------------------------------------------
// 1. CAPTURA Y VALIDACIÓN DEL ID DEL CONTRATO
// ----------------------------------------------------------------------
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("Error: ID de contrato no especificado o inválido.");
}

$id_contrato = (int)$_GET['id'];
$nombre_cliente = "Cliente Desconocido"; // Valor por defecto

// ----------------------------------------------------------------------
// 2. CONSULTA PRINCIPAL
// ----------------------------------------------------------------------

// Consulta para obtener el historial de pagos (facturas con estado 'PAGADO')
// y el nombre del cliente.
$sql = "
    SELECT 
        cxc.id_cobro, 
        cxc.fecha_emision, 
        cxc.fecha_vencimiento, 
        cxc.monto_total, 
        cxc.fecha_pago,
        cxc.referencia_pago,
        co.nombre_completo 
    FROM cuentas_por_cobrar cxc
    JOIN contratos co ON cxc.id_contrato = co.id
    WHERE cxc.id_contrato = ?
    AND cxc.estado = 'PAGADO'
    ORDER BY cxc.fecha_pago DESC
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id_contrato);
$stmt->execute();
$resultado = $stmt->get_result();

// Almacenar los resultados y obtener el nombre del cliente
$pagos = [];
if ($resultado->num_rows > 0) {
    // Si hay resultados, tomamos el nombre del cliente de la primera fila
    $pagos = $resultado->fetch_all(MYSQLI_ASSOC);
    $nombre_cliente = $pagos[0]['nombre_completo'];
}

// Configuración Layout
$path_to_root = "../../";
$page_title = "Historial de Pagos - " . $nombre_cliente;
require_once '../includes/layout_head.php';
require_once '../includes/sidebar.php';
?>

<main class="main-content">
    <?php include '../includes/header.php'; ?>

    <div class="page-content">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center bg-white border-bottom-0 pt-4 px-4">
                <div>
                    <h5 class="fw-bold text-primary mb-1">Historial de Pagos</h5>
                    <p class="text-muted small mb-0">Cliente: <strong><?php echo htmlspecialchars($nombre_cliente); ?></strong> (Contrato #<?php echo $id_contrato; ?>)</p>
                </div>
                <div>
                    <a href="gestion_cobros.php?maintenance_done=1" class="btn btn-secondary btn-sm">
                        <i class="fas fa-arrow-left me-2"></i>Volver a Cuentas por Cobrar
                    </a>
                </div>
            </div>

            <div class="card-body px-4">
                <div class="table-responsive">
                    <table id="tablaHistorial" class="table table-hover w-100" style="font-size: 0.9rem;">
                        <thead class="bg-light">
                            <tr>
                                <th>Factura ID</th>
                                <th>Emisión</th>
                                <th>Vencimiento</th>
                                <th>Fecha de Pago</th>
                                <th>Monto Pagado</th>
                                <th>Referencia</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($pagos)): ?>
                                <?php foreach ($pagos as $fila): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($fila['id_cobro']); ?></td>
                                    <td><?php echo htmlspecialchars($fila['fecha_emision']); ?></td>
                                    <td><?php echo htmlspecialchars($fila['fecha_vencimiento']); ?></td>
                                    <td><?php echo htmlspecialchars($fila['fecha_pago']); ?></td>
                                    <td class="fw-bold text-success">$<?php echo number_format($fila['monto_total'], 2); ?></td>
                                    <td><?php echo htmlspecialchars($fila['referencia_pago']); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-4">No se encontraron pagos registrados para este cliente.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</main>

<?php require_once '../includes/layout_foot.php'; ?>