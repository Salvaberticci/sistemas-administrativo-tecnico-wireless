<?php
session_start();
if (!isset($_SESSION['cliente_cedula'])) {
    header('Location: index.php');
    exit;
}

require '../paginas/conexion.php';

$cedula = $_SESSION['cliente_cedula'];
$nombre = $_SESSION['cliente_nombre'];

// Intentar obtener tasa BCV
$tasa_bcv = 1;
$tasa_fecha = '';
$url_bcv = "https://ve.dolarapi.com/v1/dolares/oficial";
$ch = curl_init($url_bcv);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_TIMEOUT, 3);
$resp_bcv = curl_exec($ch);
if (!curl_errno($ch)) {
    $data_bcv = json_decode($resp_bcv, true);
    if (isset($data_bcv['promedio'])) {
        $tasa_bcv = floatval($data_bcv['promedio']);
        $tasa_fecha = date('d/m/Y h:i A', strtotime($data_bcv['fechaActualizacion'] ?? 'now'));
    }
}
curl_close($ch);

// Obtener contratos del cliente y su plan
$contratos = [];
$sql_contratos = "
    SELECT c.id, c.estado, c.direccion, c.monto_plan, p.nombre_plan,
           (SELECT saldo_pendiente FROM clientes_deudores d WHERE d.id_contrato = c.id AND d.estado = 'PENDIENTE' LIMIT 1) as deuda_actual
    FROM contratos c
    LEFT JOIN planes p ON c.id_plan = p.id_plan
    WHERE c.cedula = ? AND c.estado != 'ELIMINADO'
";
$stmt = $conn->prepare($sql_contratos);
if ($stmt) {
    $stmt->bind_param("s", $cedula);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) {
        $row['deuda_actual'] = floatval($row['deuda_actual'] ?? 0);
        $row['nombre_plan'] = $row['nombre_plan'] ?: 'Plan Básico';
        
        // Cargar últimos 5 pagos para este contrato
        $row['historial'] = [];
        $sql_hist = "SELECT fecha_pago, monto_total, estado, referencia_pago 
                     FROM cuentas_por_cobrar 
                     WHERE id_contrato = ? AND estado IN ('PAGADO', 'PENDIENTE')
                     ORDER BY fecha_emision DESC LIMIT 5";
        $stmt_hist = $conn->prepare($sql_hist);
        if ($stmt_hist) {
            $stmt_hist->bind_param("i", $row['id']);
            $stmt_hist->execute();
            $res_hist = $stmt_hist->get_result();
            while ($h = $res_hist->fetch_assoc()) {
                $row['historial'][] = $h;
            }
            $stmt_hist->close();
        }
        $contratos[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi Panel - Wireless Supply</title>
    <link href="../css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

    <!-- Header -->
    <header class="glass-header py-3 mb-4">
        <div class="container d-flex justify-content-between align-items-center">
            <div class="d-flex align-items-center">
                <img src="../images/logo.jpg" alt="Logo" class="me-3" style="height: 40px; border-radius: 6px;">
                <h5 class="mb-0 fw-bold d-none d-sm-block">Portal de Clientes</h5>
            </div>
            <div>
                <span class="me-3 text-muted" style="font-size: 0.9rem;"><i class="fas fa-user-circle me-1"></i> <?php echo htmlspecialchars($nombre); ?></span>
                <a href="auth.php?logout=1" class="btn btn-sm btn-glass text-danger border-danger"><i class="fas fa-sign-out-alt"></i> Salir</a>
            </div>
        </div>
    </header>

    <div class="container main-container animate-fade">
        <div class="d-flex justify-content-between align-items-end mb-4">
            <div>
                <h2 class="mb-1 text-gradient">Gestión de Servicios</h2>
                <p class="text-muted mb-0">Revisa tus planes, historial de pagos y ponte al día.</p>
            </div>
            <?php if ($tasa_bcv > 1): ?>
            <div class="text-end d-none d-md-block">
                <span class="badge bg-primary glass-panel p-2">Tasa BCV: Bs <?php echo number_format($tasa_bcv, 2, ',', '.'); ?></span>
                <div class="small text-muted mt-1" style="font-size: 0.75rem;">Ref: <?php echo $tasa_fecha; ?></div>
            </div>
            <?php endif; ?>
        </div>

        <?php if ($tasa_bcv > 1): ?>
            <div class="d-block d-md-none mb-4 text-center">
                <span class="badge bg-primary glass-panel p-2 w-100">Tasa BCV: Bs <?php echo number_format($tasa_bcv, 2, ',', '.'); ?></span>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['pago_msg'])): ?>
            <div class="alert alert-success glass-panel mb-4" style="border-left: 4px solid var(--success);">
                <i class="fas fa-check-circle text-success me-2"></i> <?php echo $_SESSION['pago_msg']; unset($_SESSION['pago_msg']); ?>
            </div>
        <?php endif; ?>

        <div class="row g-4">
            <?php if (empty($contratos)): ?>
                <div class="col-12 text-center py-5 glass-panel">
                    <i class="fas fa-satellite-dish fa-3x text-muted mb-3"></i>
                    <h4>No se encontraron servicios asociados</h4>
                    <p class="text-muted">Si crees que esto es un error, por favor contacta a soporte técnico.</p>
                </div>
            <?php else: ?>
                <?php foreach ($contratos as $c): ?>
                    <div class="col-12">
                        <div class="glass-panel p-4 contract-card">
                            <div class="row">
                                <!-- Info Col -->
                                <div class="col-md-7 mb-4 mb-md-0 border-end border-secondary border-opacity-25 pe-md-4">
                                    <div class="d-flex justify-content-between align-items-start mb-3">
                                        <div>
                                            <h4 class="mb-1 fw-bold text-white">Contrato #<?php echo $c['id']; ?></h4>
                                            <?php 
                                                $badge_class = 'status-active';
                                                if ($c['estado'] === 'SUSPENDIDO') $badge_class = 'status-suspended';
                                                if ($c['estado'] === 'POR INSTALAR') $badge_class = 'status-pending';
                                            ?>
                                            <span class="status-badge <?php echo $badge_class; ?>"><?php echo htmlspecialchars($c['estado']); ?></span>
                                            <span class="badge bg-info text-dark ms-2"><i class="fas fa-bolt me-1"></i> <?php echo htmlspecialchars($c['nombre_plan']); ?></span>
                                        </div>
                                    </div>
                                    
                                    <p class="text-muted small mb-4"><i class="fas fa-map-marker-alt me-1 text-primary"></i> <?php echo htmlspecialchars($c['direccion']); ?></p>
                                    
                                    <h6 class="text-white mb-3 border-bottom border-secondary border-opacity-25 pb-2">Últimos Movimientos</h6>
                                    <div class="table-responsive">
                                        <table class="table table-sm text-white" style="background: transparent;">
                                            <thead>
                                                <tr class="text-muted" style="font-size: 0.85rem;">
                                                    <th>Fecha</th>
                                                    <th>Monto</th>
                                                    <th>Ref.</th>
                                                    <th>Estado</th>
                                                </tr>
                                            </thead>
                                            <tbody style="font-size: 0.9rem;">
                                                <?php if (empty($c['historial'])): ?>
                                                    <tr><td colspan="4" class="text-center text-muted">No hay movimientos recientes.</td></tr>
                                                <?php else: ?>
                                                    <?php foreach ($c['historial'] as $h): ?>
                                                    <tr>
                                                        <td><?php echo $h['fecha_pago'] ? date('d/m/Y', strtotime($h['fecha_pago'])) : '-'; ?></td>
                                                        <td>$<?php echo number_format($h['monto_total'], 2); ?></td>
                                                        <td class="text-muted"><?php echo $h['referencia_pago'] ?: '-'; ?></td>
                                                        <td>
                                                            <?php if ($h['estado'] === 'PAGADO'): ?>
                                                                <span class="text-success"><i class="fas fa-check-circle"></i> Pagado</span>
                                                            <?php else: ?>
                                                                <span class="text-warning"><i class="fas fa-clock"></i> Pendiente</span>
                                                            <?php endif; ?>
                                                        </td>
                                                    </tr>
                                                    <?php endforeach; ?>
                                                <?php endif; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>

                                <!-- Payment Col -->
                                <div class="col-md-5 ps-md-4 d-flex flex-column justify-content-center">
                                    <div class="p-3 mb-4" style="background: rgba(0,0,0,0.3); border-radius: 12px; border: 1px solid rgba(255,255,255,0.05);">
                                        <div class="d-flex justify-content-between mb-2">
                                            <span class="text-muted small">Tarifa Mensual</span>
                                            <span class="fw-bold text-white">$<?php echo number_format($c['monto_plan'], 2); ?></span>
                                        </div>
                                        <hr style="border-color: rgba(255,255,255,0.1); margin: 8px 0;">
                                        <div class="d-flex justify-content-between align-items-center mb-1">
                                            <span class="text-muted small">Deuda Actual USD</span>
                                            <span class="fs-4 fw-bold <?php echo ($c['deuda_actual'] > 0) ? 'text-danger' : 'text-success'; ?>">
                                                $<?php echo number_format($c['deuda_actual'], 2); ?>
                                            </span>
                                        </div>
                                        <?php if ($tasa_bcv > 1 && $c['deuda_actual'] > 0): ?>
                                        <div class="d-flex justify-content-between align-items-center">
                                            <span class="text-muted" style="font-size: 0.75rem;">Equivalente en Bs</span>
                                            <span class="text-muted fw-bold" style="font-size: 0.9rem;">
                                                Bs <?php echo number_format($c['deuda_actual'] * $tasa_bcv, 2, ',', '.'); ?>
                                            </span>
                                        </div>
                                        <?php endif; ?>
                                    </div>

                                    <?php if ($c['deuda_actual'] > 0): ?>
                                        <button type="button" class="btn btn-premium w-100 py-3" data-bs-toggle="modal" data-bs-target="#modalPago_<?php echo $c['id']; ?>">
                                            <i class="fas fa-credit-card me-2"></i> PROCEDER AL PAGO
                                        </button>
                                    <?php else: ?>
                                        <button type="button" class="btn btn-premium w-100" data-bs-toggle="modal" data-bs-target="#modalPago_<?php echo $c['id']; ?>">
                                            <i class="fas fa-arrow-up me-2"></i> ADELANTAR PAGO
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        
        <div class="mt-5 text-center text-muted small">
            &copy; <?php echo date('Y'); ?> Wireless Supply, C.A. Todos los derechos reservados.<br>
            Desarrollado con <i class="fas fa-heart text-danger"></i>
        </div>
    </div>

    <!-- Generación de Modales fuera del flujo principal (Evita bug del backdrop de Bootstrap) -->
    <?php if (!empty($contratos)): ?>
        <?php foreach ($contratos as $c): ?>
            <!-- Modal de Pago para este contrato -->
            <div class="modal fade" id="modalPago_<?php echo $c['id']; ?>" tabindex="-1" aria-labelledby="modalPagoLabel_<?php echo $c['id']; ?>" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content glass-panel" style="border: 1px solid rgba(255,255,255,0.15);">
                        <div class="modal-header border-bottom border-secondary border-opacity-25">
                            <h5 class="modal-title fw-bold" id="modalPagoLabel_<?php echo $c['id']; ?>">
                                <i class="fas fa-wallet text-primary me-2"></i> Procesar Pago
                            </h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <form action="pagar_pagoflash.php" method="POST">
                            <div class="modal-body p-4">
                                <div class="text-center mb-4">
                                    <img src="../images/logo.jpg" alt="Logo" class="mb-3" style="height: 50px; border-radius: 8px;">
                                    <h6 class="text-white mb-1">Contrato #<?php echo $c['id']; ?></h6>
                                    <p class="text-muted small mb-0"><?php echo htmlspecialchars($c['nombre_plan']); ?></p>
                                </div>

                                <input type="hidden" name="id_contrato" value="<?php echo $c['id']; ?>">
                                <input type="hidden" name="deuda_base" value="<?php echo $c['deuda_actual']; ?>">
                                <input type="hidden" name="monto_plan" value="<?php echo $c['monto_plan']; ?>">
                                
                                <div class="p-3 mb-4 rounded" style="background: rgba(0,0,0,0.3);">
                                    <div class="d-flex justify-content-between mb-2">
                                        <span class="text-muted">Deuda Pendiente:</span>
                                        <span class="fw-bold text-white">$<?php echo number_format($c['deuda_actual'], 2); ?></span>
                                    </div>
                                    <div class="d-flex justify-content-between">
                                        <span class="text-muted">Valor de la Mensualidad:</span>
                                        <span class="fw-bold text-white">$<?php echo number_format($c['monto_plan'], 2); ?></span>
                                    </div>
                                </div>

                                <?php if ($c['deuda_actual'] > 0): ?>
                                    <div class="mb-3">
                                        <label class="form-label text-white small">Opciones de Pago</label>
                                        <select name="meses_adelanto" class="form-select glass-input">
                                            <option value="0">Solo pagar deuda actual ($<?php echo number_format($c['deuda_actual'],2); ?>)</option>
                                            <option value="1">Pagar deuda + 1 mes ($<?php echo number_format($c['deuda_actual'] + $c['monto_plan'],2); ?>)</option>
                                            <option value="2">Pagar deuda + 2 meses ($<?php echo number_format($c['deuda_actual'] + ($c['monto_plan']*2),2); ?>)</option>
                                            <option value="3">Pagar deuda + 3 meses ($<?php echo number_format($c['deuda_actual'] + ($c['monto_plan']*3),2); ?>)</option>
                                        </select>
                                    </div>
                                <?php else: ?>
                                    <div class="mb-3">
                                        <label class="form-label text-success small"><i class="fas fa-check-circle"></i> Estás al día. Selecciona los meses a adelantar:</label>
                                        <select name="meses_adelanto" class="form-select glass-input">
                                            <option value="1">Adelantar 1 mes ($<?php echo number_format($c['monto_plan'],2); ?>)</option>
                                            <option value="2">Adelantar 2 meses ($<?php echo number_format($c['monto_plan']*2,2); ?>)</option>
                                            <option value="3">Adelantar 3 meses ($<?php echo number_format($c['monto_plan']*3,2); ?>)</option>
                                        </select>
                                    </div>
                                <?php endif; ?>

                                <div class="alert alert-info mt-3 p-2 small mb-0" style="background: rgba(14, 165, 233, 0.1); border: 1px solid rgba(14, 165, 233, 0.2); color: #bae6fd;">
                                    <i class="fas fa-info-circle me-1"></i> Serás redirigido a una pasarela de pago segura para completar tu transacción.
                                </div>
                            </div>
                            <div class="modal-footer border-top border-secondary border-opacity-25">
                                <button type="button" class="btn btn-glass" data-bs-dismiss="modal">Cancelar</button>
                                <button type="submit" class="btn btn-premium"><i class="fas fa-lock me-1"></i> Pagar Seguro</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>

    <!-- Bootstrap JS para Modales -->
    <script src="../js/bootstrap.bundle.min.js"></script>
</body>
</html>
