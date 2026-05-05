<?php
session_start();
if (!isset($_SESSION['cliente_cedula'])) {
    header('Location: index.php');
    exit;
}

require '../paginas/conexion.php';

$cedula = $_SESSION['cliente_cedula'];
$nombre = $_SESSION['cliente_nombre'];

// Cargar bancos para el reporte manual
$json_bancos = @file_get_contents('../paginas/principal/bancos.json');
$bancosArr = json_decode($json_bancos, true) ?: [];

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
    SELECT c.id, c.estado as estado_contrato, c.direccion, c.monto_plan, p.nombre_plan,
           (SELECT SUM(monto_total) FROM cuentas_por_cobrar cxc WHERE cxc.id_contrato = c.id AND cxc.estado IN ('PENDIENTE', 'VENCIDO')) as deuda_mensualidades,
           (SELECT MIN(fecha_vencimiento) FROM cuentas_por_cobrar cxc WHERE cxc.id_contrato = c.id AND cxc.estado IN ('PENDIENTE', 'VENCIDO')) as vencimiento_pendiente
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
        $row['deuda_mensualidades'] = floatval($row['deuda_mensualidades'] ?? 0);
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
<html lang="es" data-theme="dark">
<head>
    <script>
        // Iniciar tema lo más rápido posible para evitar parpadeo
        const savedTheme = localStorage.getItem('theme') || 'dark';
        document.documentElement.setAttribute('data-theme', savedTheme);
    </script>
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
                <img src="../images/logo-galanet.png" alt="Logo Galanet" class="me-3" style="height: 40px; border-radius: 6px;">
                <h5 class="mb-0 fw-bold d-none d-sm-block text-gradient">Portal de Clientes</h5>
            </div>
            <div class="d-flex align-items-center gap-3">
                <button class="theme-toggle" id="themeToggleBtn" title="Cambiar Tema">
                    <i class="fas fa-sun"></i>
                </button>
                <div>
                    <span class="me-3 text-muted d-none d-md-inline" style="font-size: 0.9rem;"><i class="fas fa-user-circle me-1"></i> <?php echo htmlspecialchars($nombre); ?></span>
                    <a href="auth.php?logout=1" class="btn btn-sm btn-glass text-danger border-danger"><i class="fas fa-sign-out-alt"></i> Salir</a>
                </div>
            </div>
        </div>
    </header>

    <div class="container main-container animate-fade">
        <div class="d-flex justify-content-between align-items-end mb-4">
            <div>
                <h2 class="mb-1 text-gradient">Gestión de Mensualidades</h2>
                <p class="text-muted mb-0">Revisa tus mensualidades, historial de pagos y mantente al día.</p>
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

        <!-- Mensaje Recordatorio Premium -->
        <div class="glass-panel p-3 mb-4 text-center border-0 shadow-sm animate-pulse-slow" style="background: linear-gradient(135deg, rgba(37, 99, 235, 0.1), rgba(14, 165, 233, 0.1)); border-left: 4px solid var(--primary) !important;">
            <p class="mb-0 fw-bold text-main" style="letter-spacing: 0.5px;">
                <i class="fas fa-bell me-2 text-primary"></i> 
                RECUERDA CANCELAR LOS PRIMEROS <span class="text-primary fs-5">5</span> DE CADA MES
            </p>
        </div>

        <?php if (isset($_SESSION['pago_msg'])): ?>
            <div class="alert alert-success glass-panel mb-4">
                <i class="fas fa-check-circle me-2"></i> <?php echo $_SESSION['pago_msg']; unset($_SESSION['pago_msg']); ?>
            </div>
        <?php endif; ?>
        <?php if (isset($_SESSION['pago_err'])): ?>
            <div class="alert alert-danger glass-panel mb-4">
                <i class="fas fa-times-circle me-2"></i> <?php echo $_SESSION['pago_err']; unset($_SESSION['pago_err']); ?>
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
                                            <h4 class="mb-1 fw-bold">Contrato #<?php echo $c['id']; ?></h4>
                                            <?php 
                                                $badge_class = 'status-active';
                                                if ($c['estado_contrato'] === 'SUSPENDIDO') $badge_class = 'status-suspended';
                                                if ($c['estado_contrato'] === 'POR INSTALAR') $badge_class = 'status-pending';
                                            ?>
                                            <span class="status-badge <?php echo $badge_class; ?>"><?php echo htmlspecialchars($c['estado_contrato']); ?></span>
                                            <span class="badge bg-info text-dark ms-2"><i class="fas fa-bolt me-1"></i> <?php echo htmlspecialchars($c['nombre_plan']); ?></span>
                                        </div>
                                    </div>
                                    
                                    <p class="text-muted small mb-3"><i class="fas fa-map-marker-alt me-1 text-primary"></i> <?php echo htmlspecialchars($c['direccion']); ?></p>

                                    <div class="d-flex flex-wrap gap-2 mb-4">
                                        <div class="glass-panel p-2 px-3 d-flex align-items-center border-0" style="background: var(--border-glass);">
                                            <i class="fas fa-circle-check me-2 <?php echo $c['deuda_mensualidades'] > 0 ? 'text-warning' : 'text-success'; ?>"></i>
                                            <div>
                                                <span class="text-muted d-block" style="font-size: 0.65rem; font-weight: 700; letter-spacing: 0.5px;">ESTADO DE PAGO</span>
                                                <span class="fw-bold small">
                                                    <?php echo $c['deuda_mensualidades'] > 0 ? 'Pago Pendiente' : 'Al día'; ?>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <!-- Payment Col -->
                                <div class="col-md-5 ps-md-4 d-flex flex-column justify-content-center">
                                    <div class="payment-summary-box mb-4 shadow-sm">
                                        <div class="d-flex justify-content-between mb-2">
                                            <span class="text-muted small fw-semibold">Tarifa Mensual</span>
                                            <span class="fw-bold">$<?php echo number_format($c['monto_plan'], 2); ?></span>
                                        </div>
                                        <hr>
                                        <div class="d-flex justify-content-between align-items-center mb-1">
                                            <span class="text-muted small fw-semibold">Deuda Pendiente</span>
                                            <span class="fs-4 fw-bold <?php echo ($c['deuda_mensualidades'] > 0) ? 'text-danger' : 'text-success'; ?>">
                                                $<?php echo number_format($c['deuda_mensualidades'], 2); ?>
                                            </span>
                                        </div>
                                        <?php if ($tasa_bcv > 1 && $c['deuda_mensualidades'] > 0): ?>
                                        <div class="d-flex justify-content-between align-items-center mt-2">
                                            <span class="text-muted small" style="font-size: 0.75rem;">Equivalente en Bs</span>
                                            <span class="text-muted fw-bold" style="font-size: 0.9rem;">
                                                Bs <?php echo number_format($c['deuda_mensualidades'] * $tasa_bcv, 2, ',', '.'); ?>
                                            </span>
                                        </div>
                                        <?php endif; ?>
                                    </div>

                                    <?php if ($c['deuda_mensualidades'] > 0): ?>
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
        
        <footer class="text-center py-4 mt-5 border-top border-white border-opacity-10">
            <p class="text-muted small mb-0">&copy; <?php echo date('Y'); ?> Wireless Supply. Todos los derechos reservados.</p>
        </footer>
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
                        <form action="procesar_pago_cliente.php" method="POST" enctype="multipart/form-data" class="form-reporte-pago">
                            <div class="modal-body p-4">
                                <div class="text-center mb-4">
                                    <img src="../images/logo-galanet.png" alt="Logo Galanet" class="mb-3" style="height: 50px; border-radius: 8px;">
                                    <h6 class="text-white mb-1">Contrato #<?php echo $c['id']; ?></h6>
                                    <p class="text-muted small mb-0"><?php echo htmlspecialchars($c['nombre_plan']); ?></p>
                                </div>

                                <input type="hidden" name="id_contrato" value="<?php echo $c['id']; ?>">
                                <input type="hidden" name="tasa_dolar" value="<?php echo $tasa_bcv; ?>">
                                <input type="hidden" name="monto_usd" class="monto_usd_hidden" value="<?php echo ($c['deuda_mensualidades'] > 0 ? $c['deuda_mensualidades'] : $c['monto_plan']); ?>">
                                
                                <div class="p-3 mb-4 rounded" style="background: rgba(0,0,0,0.3);">
                                    <div class="d-flex justify-content-between mb-2">
                                        <span class="text-muted">Deuda Pendiente:</span>
                                        <span class="fw-bold text-white">$<?php echo number_format($c['deuda_mensualidades'], 2); ?></span>
                                    </div>
                                    <div class="d-flex justify-content-between">
                                        <span class="text-muted">Valor Mensualidad:</span>
                                        <span class="fw-bold text-white">$<?php echo number_format($c['monto_plan'], 2); ?></span>
                                    </div>
                                </div>

                                <!-- Selección de Meses -->
                                <div class="mb-3">
                                    <label class="form-label text-white small">¿Qué deseas pagar?</label>
                                    <select name="meses_adelanto" class="form-select glass-input select-meses" data-deuda="<?php echo $c['deuda_mensualidades']; ?>" data-mensualidad="<?php echo $c['monto_plan']; ?>">
                                        <?php if ($c['deuda_mensualidades'] > 0): ?>
                                            <option value="0">Solo deuda actual ($<?php echo number_format($c['deuda_mensualidades'],2); ?>)</option>
                                            <option value="1">Deuda + 1 mes ($<?php echo number_format($c['deuda_mensualidades'] + $c['monto_plan'],2); ?>)</option>
                                            <option value="2">Deuda + 2 meses ($<?php echo number_format($c['deuda_mensualidades'] + ($c['monto_plan']*2),2); ?>)</option>
                                            <option value="3">Deuda + 3 meses ($<?php echo number_format($c['deuda_mensualidades'] + ($c['monto_plan']*3),2); ?>)</option>
                                        <?php else: ?>
                                            <option value="1">Adelantar 1 mes ($<?php echo number_format($c['monto_plan'],2); ?>)</option>
                                            <option value="2">Adelantar 2 meses ($<?php echo number_format($c['monto_plan']*2,2); ?>)</option>
                                            <option value="3">Adelantar 3 meses ($<?php echo number_format($c['monto_plan']*3,2); ?>)</option>
                                        <?php endif; ?>
                                    </select>
                                </div>

                                <!-- Monto a Pagar Display -->
                                <div class="mb-3">
                                    <label class="form-label text-white small">Total a Transferir</label>
                                    <div class="input-group mb-1">
                                        <span class="input-group-text glass-input text-success fw-bold">$</span>
                                        <input type="text" class="form-control glass-input fw-bold text-success display-usd" value="<?php echo number_format(($c['deuda_mensualidades'] > 0 ? $c['deuda_mensualidades'] : $c['monto_plan']), 2); ?>" readonly>
                                    </div>
                                    <?php if ($tasa_bcv > 1): ?>
                                    <div class="input-group">
                                        <span class="input-group-text glass-input text-primary fw-bold">Bs</span>
                                        <input type="text" class="form-control glass-input fw-bold text-primary display-bs" value="<?php echo number_format((($c['deuda_mensualidades'] > 0 ? $c['deuda_mensualidades'] : $c['monto_plan']) * $tasa_bcv), 2, ',', '.'); ?>" readonly>
                                    </div>
                                    <div class="form-text text-muted" style="font-size: 0.75rem;">Calculado a tasa BCV: <?php echo number_format($tasa_bcv, 2, ',', '.'); ?></div>
                                    <?php endif; ?>
                                </div>

                                <!-- Método de Pago y Banco -->
                                <div class="row g-2 mb-3">
                                    <div class="col-6">
                                        <label class="form-label text-white small">Método</label>
                                        <select name="metodo_pago" class="form-select glass-input select-metodo" required>
                                            <option value="">Seleccione...</option>
                                            <option value="Pago Móvil">Pago Móvil</option>
                                            <option value="Transferencia">Transferencia</option>
                                            <option value="Zelle">Zelle</option>
                                            <option value="Efectivo">Efectivo</option>
                                            <option value="Divisas">Divisas</option>
                                        </select>
                                    </div>
                                    <div class="col-6">
                                        <label class="form-label text-white small">Banco Destino</label>
                                        <select name="id_banco_destino" class="form-select glass-input select-banco" required>
                                            <option value="">Seleccione banco...</option>
                                        </select>
                                    </div>
                                </div>

                                <!-- Box de Datos del Banco Dinámico -->
                                <div class="banco-info-box alert alert-info p-2 mb-3 d-none" style="background: rgba(14, 165, 233, 0.1); border: 1px solid rgba(14, 165, 233, 0.2); font-size: 0.8rem; color: #bae6fd;">
                                    <div class="fw-bold mb-1"><i class="fas fa-university me-1"></i> Datos para el pago:</div>
                                    <div class="banco-detalles"></div>
                                </div>

                                <!-- Referencia y Capture -->
                                <div class="row g-2 mb-3">
                                    <div class="col-12">
                                        <label class="form-label text-white small">Fecha de Pago</label>
                                        <input type="date" name="fecha_pago" class="form-control glass-input" value="<?php echo date('Y-m-d'); ?>" required max="<?php echo date('Y-m-d'); ?>">
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label text-white small">N° de Referencia</label>
                                        <input type="text" name="referencia" class="form-control glass-input" placeholder="Ej: 123456" required pattern="[0-9A-Za-z]{4,}">
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label text-white small">Capture o Comprobante</label>
                                    <input type="file" name="capture_pago" class="form-control glass-input" accept="image/*" required>
                                    <div class="form-text text-muted" style="font-size: 0.75rem;">Sube la imagen de tu transferencia o pago móvil.</div>
                                </div>

                            </div>
                            <div class="modal-footer border-top border-secondary border-opacity-25">
                                <button type="button" class="btn btn-glass" data-bs-dismiss="modal">Cancelar</button>
                                <button type="submit" class="btn btn-premium"><i class="fas fa-paper-plane me-1"></i> Enviar Reporte</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>

    <!-- Bootstrap JS para Modales -->
    <script src="../js/bootstrap.bundle.min.js"></script>

    <!-- Lógica del Formulario de Pago -->
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // --- GESTIÓN DE TEMA ---
        const themeBtn = document.getElementById('themeToggleBtn');
        const html = document.documentElement;
        const themeIcon = themeBtn.querySelector('i');

        function updateThemeIcon(theme) {
            if (theme === 'dark') {
                themeIcon.className = 'fas fa-sun';
            } else {
                themeIcon.className = 'fas fa-moon';
            }
        }

        // Sincronizar icono inicial
        updateThemeIcon(html.getAttribute('data-theme'));

        themeBtn.addEventListener('click', function() {
            const currentTheme = html.getAttribute('data-theme');
            const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
            
            html.setAttribute('data-theme', newTheme);
            localStorage.setItem('theme', newTheme);
            updateThemeIcon(newTheme);
        });

        // --- LÓGICA DE PAGOS ---
        const todosLosBancos = <?php echo json_encode($bancosArr); ?>;
        const tasaBcv = <?php echo $tasa_bcv; ?>;

        document.querySelectorAll('.form-reporte-pago').forEach(form => {
            const selectMeses = form.querySelector('.select-meses');
            const displayUsd = form.querySelector('.display-usd');
            const displayBs = form.querySelector('.display-bs');
            const inputHiddenUsd = form.querySelector('.monto_usd_hidden');
            
            const selectMetodo = form.querySelector('.select-metodo');
            const selectBanco = form.querySelector('.select-banco');
            const infoBox = form.querySelector('.banco-info-box');
            const detallesDiv = form.querySelector('.banco-detalles');

            // 1. Recalcular montos al cambiar los meses a pagar
            selectMeses.addEventListener('change', function() {
                const meses = parseInt(this.value);
                const deuda = parseFloat(this.getAttribute('data-deuda'));
                const mensualidad = parseFloat(this.getAttribute('data-mensualidad'));
                
                let montoTotalUsd = 0;
                if (deuda > 0) {
                    montoTotalUsd = deuda + (mensualidad * meses);
                } else {
                    montoTotalUsd = mensualidad * meses;
                }

                inputHiddenUsd.value = montoTotalUsd.toFixed(2);
                displayUsd.value = montoTotalUsd.toFixed(2);
                
                if (displayBs) {
                    const montoTotalBs = montoTotalUsd * tasaBcv;
                    displayBs.value = montoTotalBs.toLocaleString('es-VE', {minimumFractionDigits: 2, maximumFractionDigits: 2});
                }
            });

            // 2. Filtrar bancos según método de pago
            selectMetodo.addEventListener('change', function() {
                const metodo = this.value;
                selectBanco.innerHTML = '<option value="">Seleccione banco...</option>';
                infoBox.classList.add('d-none'); // Ocultar info box al cambiar

                if (metodo) {
                    const filtrados = todosLosBancos.filter(b => (b.metodos_pago || []).includes(metodo));
                    filtrados.forEach(b => {
                        const opt = document.createElement('option');
                        opt.value = b.id_banco;
                        opt.textContent = b.nombre_banco;
                        selectBanco.appendChild(opt);
                    });
                }
            });

            // 3. Mostrar detalles del banco seleccionado
            selectBanco.addEventListener('change', function() {
                const idBanco = this.value;
                infoBox.classList.add('d-none');
                
                if (idBanco) {
                    const banco = todosLosBancos.find(b => b.id_banco == idBanco);
                    if (banco && banco.numero_cuenta !== '$' && banco.numero_cuenta !== 'Bolivares') {
                        let html = '';
                        if (selectMetodo.value === 'Pago Móvil') {
                            html += `<div><strong>Teléfono:</strong> ${banco.numero_cuenta}</div>`;
                            html += `<div><strong>Cédula/RIF:</strong> ${banco.cedula_propietario}</div>`;
                        } else if (selectMetodo.value === 'Transferencia') {
                            html += `<div><strong>N° Cuenta:</strong> ${banco.numero_cuenta}</div>`;
                            html += `<div><strong>Titular:</strong> ${banco.nombre_propietario}</div>`;
                            html += `<div><strong>RIF:</strong> ${banco.cedula_propietario}</div>`;
                        } else if (selectMetodo.value === 'Zelle') {
                            html += `<div><strong>Correo/Usuario:</strong> ${banco.numero_cuenta}</div>`;
                            html += `<div><strong>Titular:</strong> ${banco.nombre_propietario}</div>`;
                        } else {
                            html += `<div><strong>Detalle:</strong> ${banco.numero_cuenta}</div>`;
                        }
                        
                        if (html) {
                            detallesDiv.innerHTML = html;
                            infoBox.classList.remove('d-none');
                        }
                    }
                }
            });
        });
    });
    </script>
</body>
</html>
