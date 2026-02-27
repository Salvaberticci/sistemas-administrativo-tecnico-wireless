<?php
/**
 * Gestión de Mensualidades y Pagos
 * Consolida la Gestión de Cobros (Pendientes) y el Historial de Pagos
 */
require_once '../conexion.php';

// Redirección si no ha pasado mantenimiento (copiado de gestion_cobros.php)
if (!isset($_GET['maintenance_done'])) {
    header('Location: actualizacion_info.php');
    exit();
}

// Lógica de mensajes
$message = isset($_GET['message']) ? htmlspecialchars($_GET['message']) : '';
$message_class = isset($_GET['class']) ? htmlspecialchars($_GET['class']) : '';
$hoy = date('Y-m-d');

// Obtener Bancos para Modal
$bancos = $conn->query("SELECT id_banco, nombre_banco FROM bancos ORDER BY nombre_banco ASC");
$bancos_data = [];
if ($bancos) {
    while ($row = $bancos->fetch_assoc()) {
        $bancos_data[] = $row;
    }
}

$path_to_root = "../../";
$page_title = "Gestión de Mensualidades y Pagos";
$breadcrumb = ["Cobranzas"];
$back_url = "../menu.php";
require_once '../includes/layout_head.php';
require_once '../includes/sidebar.php';
?>
<!-- Estilos DataTables -->
<link href="<?php echo $path_to_root; ?>css/datatables.min.css" rel="stylesheet">
<style>
    /* Estilos específicos */
    .badge.bg-warning {
        background-color: #ffc107 !important;
        color: #000;
    }

    .badge.bg-danger {
        background-color: #dc3545 !important;
    }

    .badge.bg-success {
        background-color: #198754 !important;
    }

    #contrato_search_results_modal {
        max-height: 200px;
        overflow-y: auto;
    }

    @keyframes pulse-warning {
        0% {
            transform: scale(1);
            box-shadow: 0 0 0 0 rgba(255, 193, 7, 0.7);
        }

        70% {
            transform: scale(1.05);
            box-shadow: 0 0 0 10px rgba(255, 193, 7, 0);
        }

        100% {
            transform: scale(1);
            box-shadow: 0 0 0 0 rgba(255, 193, 7, 0);
        }
    }

    .pulse-warning {
        animation: pulse-warning 2s infinite;
    }
</style>

<main class="main-content">
    <?php include '../includes/header.php'; ?>

    <div class="page-content">
        <!-- Header Página -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h4 class="fw-bold text-primary mb-1">Lista de Mensualidades y Pagos</h4>
                <p class="text-muted small mb-0">Gestión unificada de mensualidades, cargos y pagos manuales.</p>
            </div>
            <div class="d-flex gap-2 align-items-center">
                <div class="bg-white p-2 rounded shadow-sm border me-2">
                    <span id="tasa_display" class="text-secondary small">Cargando tasa...</span>
                </div>
                <button type="button" class="btn btn-secondary shadow-sm" data-bs-toggle="modal"
                    data-bs-target="#modalGestionBancos" title="Gestionar Cuentas Bancarias">
                    <i class="fa-solid fa-university me-1"></i> Ctas.
                </button>
                <?php
                // Contar reportes pendientes
                $res_pend = $conn->query("SELECT COUNT(*) FROM pagos_reportados WHERE estado = 'PENDIENTE'");
                $cant_pend = $res_pend ? $res_pend->fetch_array()[0] : 0;
                if ($cant_pend > 0):
                    ?>
                    <a href="aprobar_pagos.php" class="btn btn-warning shadow-sm me-2 fw-bold pulse-warning">
                        <i class="fas fa-bell me-2"></i> <?php echo $cant_pend; ?> Pagos Pendientes
                    </a>
                <?php endif; ?>
                <button type="button" class="btn btn-success shadow-sm" data-bs-toggle="modal"
                    data-bs-target="#modalGenerarCobro">
                    <i class="fas fa-plus-circle me-1"></i> Generar Cobro
                </button>
            </div>
        </div>

        <!-- Alertas -->
        <?php if ($message): ?>
            <div class="alert alert-<?php echo $message_class; ?> alert-dismissible fade show" role="alert">
                <?php echo $message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <!-- Filtros -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body p-3">
                <div class="row g-2 align-items-end">
                    <div class="col-md-2">
                        <label class="form-label small fw-bold text-muted mb-1">Desde</label>
                        <input type="date" class="form-control form-control-sm" id="fecha_inicio"
                            value="<?php echo date('Y-m-01'); ?>" max="<?php echo $hoy; ?>">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small fw-bold text-muted mb-1">Hasta</label>
                        <input type="date" class="form-control form-control-sm" id="fecha_fin"
                            value="<?php echo $hoy; ?>" max="<?php echo $hoy; ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small fw-bold text-muted mb-1">Cuenta/Banco</label>
                        <select class="form-select form-select-sm" id="filtro_cuenta">
                            <option value="">Todas las Cuentas</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small fw-bold text-muted mb-1">Estado SAE Plus</label>
                        <select class="form-select form-select-sm" id="filtro_sae">
                            <option value="">Cualquier Estado</option>
                            <option value="NO CARGADO">No Cargado</option>
                            <option value="CARGADO">Cargado</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <div class="btn-group w-100 shadow-sm">
                            <button class="btn btn-primary btn-sm" onclick="exportarExcel('filtrado')"
                                title="Exportar con filtros actuales">
                                <i class="fa-solid fa-filter me-1"></i> Exportar
                            </button>
                            <button class="btn btn-primary btn-sm dropdown-toggle dropdown-toggle-split"
                                data-bs-toggle="dropdown"></button>
                            <ul class="dropdown-menu dropdown-menu-end shadow border-0">
                                <li><a class="dropdown-item small" href="javascript:void(0)"
                                        onclick="exportarExcel('todos')"><i
                                            class="fa-solid fa-globe text-success me-2"></i> Global</a></li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tabla Unificada -->
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="display table table-striped table-hover w-100" id="tabla_mensualidades_unica">
                        <thead class="bg-light">
                            <tr>
                                <th>Fecha de registro</th>
                                <th>Referencia</th>
                                <th>Cliente</th>
                                <th>Concepto</th>
                                <th>Monto</th>
                                <th>Cuenta</th>
                                <th>Estado</th>
                                <th>Origen</th>
                                <th>Estado SAE Plus</th>
                                <th class="text-end">Acciones</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</main>

<!-- ================= MODALES (Migrados de gestión de cobros) ================= -->

<!-- Modal Gestionar Bancos (NUEVO) -->
<div class="modal fade" id="modalGestionBancos" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-secondary text-white">
                <h5 class="modal-title fw-bold">Gestionar Cuentas Bancarias</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <!-- Form Agregar -->
                <form id="formAgregarBanco" class="mb-4">
                    <label class="form-label fw-bold small text-muted">Agregar Nueva Cuenta</label>
                    <div class="input-group">
                        <input type="text" class="form-control" name="nombre_banco" placeholder="Nombre Banco" required>
                        <button class="btn btn-success" type="submit"><i class="fas fa-plus"></i></button>
                    </div>
                    <div class="row g-2 mt-1">
                        <div class="col-12">
                            <input type="text" class="form-control form-control-sm" name="numero_cuenta"
                                placeholder="Nro Cuenta">
                        </div>
                    </div>
                    <div class="row g-2 mt-1">
                        <div class="col-6">
                            <input type="text" class="form-control form-control-sm" name="titular_cuenta"
                                placeholder="Titular">
                        </div>
                        <div class="col-6">
                            <input type="text" class="form-control form-control-sm" name="cedula_propietario"
                                placeholder="Cédula Titular" id="cedula_propietario_banco">
                        </div>
                    </div>
                </form>

                <!-- Lista Bancos -->
                <h6 class="fw-bold small text-muted mb-2">Cuentas Existentes</h6>
                <ul class="list-group" id="lista_bancos_gestion">
                    <!-- Items por JS -->
                </ul>
            </div>
        </div>
    </div>
</div>

<!-- Modal Pagar -->
<div class="modal fade" id="modalPagar" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title fw-bold">Registrar Pago</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="procesar_pago.php" method="POST">
                <div class="modal-body p-4">
                    <input type="hidden" name="id_cobro" id="id_cobro_modal">

                    <div class="mb-4 text-center">
                        <h6 class="text-muted text-uppercase small fw-bold mb-1">Monto Pendiente</h6>
                        <h2 class="text-success fw-bold display-6" id="monto_display_modal"></h2>
                        <p class="text-muted mb-0">Cliente: <strong id="cliente_nombre_modal"
                                class="text-dark"></strong></p>
                    </div>

                    <!-- Selección de Moneda -->
                    <div class="mb-3 text-center">
                        <div class="btn-group" role="group">
                            <input type="radio" class="btn-check" name="moneda_pagar" id="moneda_pagar_usd" value="usd"
                                checked>
                            <label class="btn btn-outline-success" for="moneda_pagar_usd"><i
                                    class="fas fa-dollar-sign"></i> USD</label>

                            <input type="radio" class="btn-check" name="moneda_pagar" id="moneda_pagar_bs" value="bs">
                            <label class="btn btn-outline-primary" for="moneda_pagar_bs">Bs (Bolívares)</label>
                        </div>
                    </div>

                    <div class="mb-2">
                        <label class="form-label fw-semibold text-secondary small">Monto a Pagar</label>
                        <input type="number" step="0.01" min="0" class="form-control form-control-lg" id="input_monto_pagar"
                            required>
                        <input type="hidden" name="monto_pagado" id="monto_pagado_hidden"> <!-- Valor final en USD -->
                        <div id="equiv_pagar" class="form-text text-end fw-bold text-primary mt-1"></div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold text-secondary small">Referencia</label>
                        <input type="text" class="form-control" name="referencia_pago" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold text-secondary small">Banco / Cuenta</label>
                        <select class="form-select" name="id_banco" id="select_banco_modal" required>
                            <option value="">Seleccione...</option>
                            <!-- Llenado por JS -->
                        </select>
                    </div>
                </div>
                <div class="modal-footer bg-light border-top-0">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                    <button type="submit" class="btn btn-success">Confirmar Pago</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Generar Cobro -->
<div class="modal fade" id="modalGenerarCobro" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title fw-bold">Generar Cargo Manual</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="generar_cobro_manual.php" method="POST">
                <div class="modal-body p-4">
                    <div class="mb-3 position-relative">
                        <label class="form-label fw-semibold text-secondary small">Buscar Contrato</label>
                        <div class="input-group">
                            <span class="input-group-text bg-white border-end-0 text-muted"><i
                                    class="fas fa-search"></i></span>
                            <input type="text" class="form-control border-start-0 ps-0" id="contrato_search_modal"
                                placeholder="ID, Nombre o Cédula" required autocomplete="off">
                        </div>
                        <input type="hidden" name="id_contrato" id="id_contrato_hidden_modal" required>
                        <div id="contrato_search_results_modal" class="list-group shadow-sm position-absolute w-100"
                            style="z-index: 1050;"></div>
                    </div>

                    <div class="row">
                        <div class="col-md-12 mb-3">
                            <label class="form-label fw-semibold text-secondary small d-block">Monto del Cargo</label>

                            <div class="btn-group w-100 mb-2" role="group">
                                <input type="radio" class="btn-check" name="moneda_cobro" id="moneda_cobro_usd"
                                    value="usd" checked>
                                <label class="btn btn-outline-success btn-sm" for="moneda_cobro_usd"><i
                                        class="fas fa-dollar-sign"></i> USD</label>

                                <input type="radio" class="btn-check" name="moneda_cobro" id="moneda_cobro_bs"
                                    value="bs">
                                <label class="btn btn-outline-primary btn-sm" for="moneda_cobro_bs">Bs</label>
                            </div>

                            <input type="number" step="0.01" min="0.01" class="form-control" id="input_monto_cobro"
                                required>
                            <input type="hidden" name="monto" id="monto_cobro_hidden"> <!-- Valor final en USD -->
                            <div id="equiv_cobro" class="form-text fw-bold text-primary mt-1"></div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="check_pago_inmediato"
                                name="pago_inmediato" value="1">
                            <label class="form-check-label fw-bold text-success" for="check_pago_inmediato">¿Pagado
                                Inmediatamente?</label>
                        </div>
                    </div>

                    <!-- Campos de Pago Directo (Ocultos por defecto) -->
                    <div id="campos_pago_directo" class="border rounded p-3 bg-light mb-3" style="display:none;">
                        <h6 class="fw-bold text-muted small mb-3">Detalles del Pago</h6>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold text-secondary small">¿Cuánto paga hoy? ($)</label>
                                <input type="number" step="0.01" min="0" class="form-control fw-bold text-success"
                                    name="monto_pagado_hoy" id="monto_pagado_hoy">
                                <div id="equiv_pago_hoy" class="form-text small fw-bold text-primary mt-1"></div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold text-secondary small">Fecha de Registro</label>
                                <input type="date" class="form-control" name="fecha_pago"
                                    value="<?php echo date('Y-m-d'); ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold text-secondary small">Cuenta Bancaria</label>
                                <select class="form-select" name="id_banco_pago" id="select_banco_cobro">
                                    <option value="">Seleccione...</option>
                                    <!-- Llenado por JS -->
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold text-secondary small">Número de Referencia</label>
                                <input type="text" class="form-control" name="referencia_pago">
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold text-secondary small">Autorizado por</label>
                        <div class="input-group">
                            <input type="text" class="form-control" name="autorizado_por" id="input_autorizado_por" required placeholder="Escriba un nombre...">
                            <button class="btn btn-outline-primary" type="button" 
                                onclick="document.getElementById('input_autorizado_por').value = '<?php echo addslashes($user_name); ?>'">
                                <i class="fas fa-user-check me-1"></i> Usar mi usuario
                            </button>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold text-secondary small">Justificación</label>
                        <textarea class="form-control" name="justificacion" rows="2" required></textarea>
                    </div>
                </div>
                <div class="modal-footer bg-light border-top-0">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-success">Guardar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Eliminar -->
<div class="modal fade" id="modalEliminar" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-sm modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-body p-4 text-center">
                <div class="mb-3 text-danger"><i class="fas fa-exclamation-triangle fa-3x"></i></div>
                <h5 class="fw-bold">¿Eliminar Cobro?</h5>
                <form id="formEliminar" method="GET" action="elimina_cobro.php">
                    <input type="hidden" name="id" id="id_cobro_eliminar">
                    <p class="text-muted small mb-4">Se eliminará el cobro #<strong id="id_display_eliminar"></strong>
                        de <strong id="cliente_nombre_eliminar"></strong></p>
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-danger fw-medium">Sí, Eliminar</button>
                        <button type="button" class="btn btn-light text-secondary fw-medium"
                            data-bs-dismiss="modal">Cancelar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Modal Exito -->
<div class="modal fade" id="modalExito" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-body p-4 text-center">
                <div class="mb-3 text-success"><i class="fas fa-check-circle fa-4x"></i></div>
                <h5 class="fw-bold mb-2" id="modalExitoLabel">Operación Exitosa</h5>
                <p class="text-muted mb-4" id="modal_ex_mensaje_principal">Acción completada.</p>
                <button type="button" class="btn btn-success w-100" data-bs-dismiss="modal">Aceptar</button>
            </div>
        </div>
    </div>
</div>


<!-- Scripts JS -->
<script src="<?php echo $path_to_root; ?>js/jquery.min.js"></script>
<script src="<?php echo $path_to_root; ?>js/datatables.min.js"></script>
<!-- SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    $(function () {
        // === TASA DE CAMBIO ===
        let TASA_BCV = 0;

        // Obtener Tasa al Cargar
        fetch('get_tasa_dolar.php')
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    TASA_BCV = parseFloat(data.promedio);
                    $('#tasa_display').html(`<strong>Tasa BCV/Ref:</strong> Bs. ${TASA_BCV.toFixed(2)}`);
                } else {
                    $('#tasa_display').html(`<span class="text-danger">Error Tasa</span>`);
                }
            });

        // === VALIDACIÓN DE FECHAS ===
        const inputDesde = document.getElementById('fecha_inicio');
        const inputHasta = document.getElementById('fecha_fin');

        const syncFechas = () => {
            if (inputDesde && inputHasta) {
                inputDesde.setAttribute('max', inputHasta.value);
                inputHasta.setAttribute('min', inputDesde.value);
            }
        };

        if (inputDesde && inputHasta) {
            inputDesde.addEventListener('change', function () {
                if (this.value > inputHasta.value) {
                    inputHasta.value = this.value;
                    $(inputHasta).trigger('change');
                }
                syncFechas();
            });

            inputHasta.addEventListener('change', function () {
                if (this.value < inputDesde.value) {
                    inputDesde.value = this.value;
                    $(inputDesde).trigger('change');
                }
                syncFechas();
            });

            syncFechas(); // Inicializar limites
        }

        // === VALIDACIÓN DE MONTOS (SOLO NÚMEROS Y POSITIVOS) ===
        const amountInputs = ['input_monto_pagar', 'input_monto_cobro', 'monto_pagado_hoy'];
        amountInputs.forEach(id => {
            const input = document.getElementById(id);
            if (input) {
                // 1. Bloquear teclas no numéricas al escribir
                input.addEventListener('keydown', function (e) {
                    // Permitir: backspace, delete, tab, escape, enter, dot, comma
                    if ([46, 8, 9, 27, 13, 110, 190, 188].indexOf(e.keyCode) !== -1 ||
                        // Permitir: Ctrl+A, Ctrl+C, Ctrl+V, Ctrl+X, Ctrl+R
                        (e.ctrlKey === true && [65, 67, 86, 88, 82].indexOf(e.keyCode) !== -1) ||
                        // Permitir: home, end, left, right
                        (e.keyCode >= 35 && e.keyCode <= 39)) {
                        return;
                    }
                    // Bloquear si no es un número
                    if ((e.shiftKey || (e.keyCode < 48 || e.keyCode > 57)) && (e.keyCode < 96 || e.keyCode > 105)) {
                        e.preventDefault();
                    }
                });

                // 2. Limpiar pegado de texto no numérico
                input.addEventListener('paste', function (e) {
                    const pasteData = (e.clipboardData || window.clipboardData).getData('text');
                    if (/[^0-9,.]/.test(pasteData)) {
                        e.preventDefault();
                        const cleanedData = pasteData.replace(/[^0-9,.]/g, '');
                        // Insertar texto limpio manualmente si es necesario o simplemente avisar
                        document.execCommand('insertText', false, cleanedData);
                    }
                });

                // 3. Asegurar positivo al final
                input.addEventListener('input', function () {
                    // Por si pasó algún carácter raro (como 'e')
                    this.value = this.value.replace(/[^0-9.,]/g, '');
                    
                    if (parseFloat(this.value) < 0) {
                        this.value = 0;
                    }
                    $(this).trigger('input'); // Disparar cálculos dependientes (conversión Bs/$)
                });
            }
        });


        // === CONVERSIÓN EN MODAL GENERAR COBRO ===
        const inputMontoCobro = document.getElementById('input_monto_cobro');
        const inputMontoCobroHidden = document.getElementById('monto_cobro_hidden');
        const displayEquivCobro = document.getElementById('equiv_cobro');
        const radiosMonedaCobro = document.getElementsByName('moneda_cobro');

        // Nuevo: Monto Pagado Hoy
        const inputPagoHoy = document.getElementById('monto_pagado_hoy');
        const displayEquivPagoHoy = document.getElementById('equiv_pago_hoy');

        function calcCobro() {
            if (!TASA_BCV) return;
            let val = parseFloat(inputMontoCobro.value) || 0;
            let esBs = document.getElementById('moneda_cobro_bs').checked;

            let usd = 0;
            let bs = 0;

            if (esBs) {
                usd = val / TASA_BCV;
                bs = val;
                inputMontoCobroHidden.value = usd.toFixed(2);
                displayEquivCobro.textContent = `Equivalente: $${usd.toFixed(2)}`;
            } else {
                usd = val;
                bs = val * TASA_BCV;
                inputMontoCobroHidden.value = val.toFixed(2);
                displayEquivCobro.textContent = `Equivalente: Bs. ${bs.toFixed(2)}`;
            }

            // Auto-llenar monto pagado hoy
            if (inputPagoHoy && !inputPagoHoy.dataset.manuallyChanged) {
                inputPagoHoy.value = usd.toFixed(2);
                if (displayEquivPagoHoy) {
                    displayEquivPagoHoy.textContent = `Equivalente: Bs. ${(usd * TASA_BCV).toFixed(2)}`;
                }
            }
        }

        if (inputMontoCobro) {
            inputMontoCobro.addEventListener('input', (e) => {
                if (inputPagoHoy) inputPagoHoy.dataset.manuallyChanged = ''; // Reset flag if total changes
                calcCobro();
            });
            radiosMonedaCobro.forEach(r => r.addEventListener('change', calcCobro));
        }

        if (inputPagoHoy) {
            inputPagoHoy.addEventListener('input', function () {
                this.dataset.manuallyChanged = 'true';
                let val = parseFloat(this.value) || 0;
                if (displayEquivPagoHoy) {
                    displayEquivPagoHoy.textContent = `Equivalente: Bs. ${(val * TASA_BCV).toFixed(2)}`;
                }
            });
        }


        // === CONVERSIÓN EN MODAL PAGAR ===
        const inputMontoPagar = document.getElementById('input_monto_pagar');
        const inputMontoPagarHidden = document.getElementById('monto_pagado_hidden');
        const displayEquivPagar = document.getElementById('equiv_pagar');
        const radiosMonedaPagar = document.getElementsByName('moneda_pagar');

        $('#modalPagar').on('shown.bs.modal', function () {
            calcPagar();
        });

        function calcPagar() {
            if (!TASA_BCV) return;
            let val = parseFloat(inputMontoPagar.value) || 0;
            let esBs = document.getElementById('moneda_pagar_bs').checked;

            if (esBs) {
                let usd = val / TASA_BCV;
                if (inputMontoPagarHidden) inputMontoPagarHidden.value = usd.toFixed(2);
                displayEquivPagar.textContent = `Equivalente: $${usd.toFixed(2)}`;
            } else {
                if (inputMontoPagarHidden) inputMontoPagarHidden.value = val.toFixed(2);
                let bs = val * TASA_BCV;
                displayEquivPagar.textContent = `Equivalente: Bs. ${bs.toFixed(2)}`;
            }
        }

        if (inputMontoPagar) {
            inputMontoPagar.addEventListener('input', calcPagar);
            radiosMonedaPagar.forEach(r => r.addEventListener('change', calcPagar));
        }

        // === TABLA UNIFICADA (server_process_mensualidades.php) ===
        var tablaUnica = $('#tabla_mensualidades_unica').DataTable({
            "order": [[0, "desc"]],
            "pageLength": 10,
            "language": {
                "sProcessing": "Procesando...",
                "sLengthMenu": "Mostrar _MENU_ registros",
                "sZeroRecords": "No se encontraron resultados",
                "sEmptyTable": "Ningún dato disponible en esta tabla",
                "sInfo": "Mostrando registros del _START_ al _END_ de un total de _TOTAL_ registros",
                "sInfoEmpty": "Mostrando registros del 0 al 0 de un total de 0 registros",
                "sInfoFiltered": "(filtrado de un total de _MAX_ registros)",
                "sSearch": "Buscar:",
                "oPaginate": {
                    "sFirst": "Primero",
                    "sLast": "Último",
                    "sNext": "Siguiente",
                    "sPrevious": "Anterior"
                }
            },
            "processing": true,
            "serverSide": true,
            "ajax": {
                "url": "server_process_mensualidades.php",
                "type": "POST",
                "data": function (d) {
                    d.fecha_inicio = $('#fecha_inicio').val();
                    d.fecha_fin = $('#fecha_fin').val();
                    d.id_banco = $('#filtro_cuenta').val();
                    d.origen = $('#filtro_origen').val();
                    d.estado_sae = $('#filtro_sae').val();
                    d.sSearch = d.search.value; // Map modern search to legacy param
                }
            },
            "columns": [
                { "data": 0 }, { "data": 1 }, { "data": 2 }, { "data": 3 }, { "data": 4 }, { "data": 5 }, { "data": 6 }, { "data": 7 }, { "data": 8 },
                { "data": 9, "orderable": false, "searchable": false, "className": "text-end" }
            ],
            "dom": '<"d-flex justify-content-between mb-3"lf>rt<"d-flex justify-content-between mt-3"ip>'
        });

        $('#fecha_inicio, #fecha_fin, #filtro_cuenta, #filtro_origen, #filtro_sae').on('change', function () {
            tablaUnica.ajax.reload();
        });

        // Handler para cambio de estado SAE Plus
        $(document).on('change', '.sae-status-select', function () {
            const select = $(this);
            const id = select.data('id');
            const nuevoEstado = select.val();

            select.prop('disabled', true);

            fetch('actualizar_estado_sae.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `id_cobro=${id}&estado_sae_plus=${nuevoEstado}`
            })
                .then(r => r.json())
                .then(res => {
                    if (res.success) {
                        // Cambiar clase para feedback visual
                        if (nuevoEstado === 'CARGADO') {
                            select.removeClass('text-danger').addClass('text-success fw-bold');
                        } else {
                            select.removeClass('text-success fw-bold').addClass('text-danger');
                        }
                    } else {
                        alert('Error al actualizar estado: ' + res.message);
                    }
                })
                .catch(err => {
                    alert('Error técnico: ' + err);
                })
                .finally(() => {
                    select.prop('disabled', false);
                });
        });

        // === MODAL PAGAR LÓGICA ===
        $('#modalPagar').on('show.bs.modal', function (event) {
            var button = $(event.relatedTarget);
            var modal = $(this);
            modal.find('#id_cobro_modal').val(button.data('id'));
            document.getElementById('moneda_pagar_usd').checked = true;
            document.getElementById('input_monto_pagar').value = button.data('monto');
            if (inputMontoPagarHidden) inputMontoPagarHidden.value = button.data('monto');
            modal.find('#monto_display_modal').text('$' + parseFloat(button.data('monto')).toFixed(2));
            modal.find('#cliente_nombre_modal').text(button.data('nombre'));
            setTimeout(calcPagar, 100);
        });

        // === MODAL ELIMINAR LÓGICA ===
        $('#modalEliminar').on('show.bs.modal', function (event) {
            var button = $(event.relatedTarget);
            var modal = $(this);
            modal.find('#id_cobro_eliminar').val(button.data('id'));
            modal.find('#id_display_eliminar').text(button.data('id'));
            modal.find('#cliente_nombre_eliminar').text(button.data('nombre'));
        });

        // === BUSCADOR AUTOCOMPLETE CONTRATOS ===
        const searchInput = document.getElementById('contrato_search_modal');
        const resultsContainer = document.getElementById('contrato_search_results_modal');
        const hiddenInput = document.getElementById('id_contrato_hidden_modal');

        if (searchInput) {
            let timer;
            searchInput.addEventListener('input', function () {
                clearTimeout(timer);
                const q = this.value.trim();
                if (q.length < 3) { resultsContainer.innerHTML = ''; return; }
                timer = setTimeout(() => {
                    fetch(`buscar_contratos.php?q=${encodeURIComponent(q)}`)
                        .then(r => r.json())
                        .then(data => {
                            resultsContainer.innerHTML = '';
                            if (data.length) {
                                data.forEach(c => {
                                    const a = document.createElement('a');
                                    a.className = 'list-group-item list-group-item-action';
                                    a.innerHTML = `<strong>ID ${c.id}</strong>: ${c.nombre_completo} <br><small class="text-muted">C.I.: ${c.cedula || 'N/A'}</small>`;
                                    a.onclick = (e) => {
                                        e.preventDefault();
                                        searchInput.value = `ID ${c.id}: ${c.nombre_completo}`;
                                        hiddenInput.value = c.id;
                                        resultsContainer.innerHTML = '';
                                    };
                                    resultsContainer.appendChild(a);
                                });
                            } else {
                                resultsContainer.innerHTML = '<div class="list-group-item disabled">Sin resultados</div>';
                            }
                        });
                }, 300);
            });
            document.addEventListener('click', function (e) {
                if (!searchInput.contains(e.target) && !resultsContainer.contains(e.target)) {
                    resultsContainer.innerHTML = '';
                }
            });
        }

        // === ÉXITO PARAMS ===
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.has('message') || urlParams.has('pago_exitoso') || urlParams.has('eliminacion_exitosa')) {
            var exitoModal = new bootstrap.Modal(document.getElementById('modalExito'));
            document.getElementById('modal_ex_mensaje_principal').textContent = urlParams.get('message') || 'Operación realizada con éxito.';
            exitoModal.show();
            if (history.replaceState) {
                var newUrl = window.location.protocol + "//" + window.location.host + window.location.pathname + '?maintenance_done=1';
                window.history.replaceState({ path: newUrl }, '', newUrl);
            }
        }

        // === TOGGLE PAGO INMEDIATO ===
        const checkPago = document.getElementById('check_pago_inmediato');
        const boxPago = document.getElementById('campos_pago_directo');
        if (checkPago && boxPago) {
            checkPago.addEventListener('change', function () {
                if (this.checked) {
                    boxPago.style.display = 'block';
                    document.querySelector('[name="id_banco_pago"]').setAttribute('required', 'required');
                    document.querySelector('[name="referencia_pago"]').setAttribute('required', 'required');
                } else {
                    boxPago.style.display = 'none';
                    document.querySelector('[name="id_banco_pago"]').removeAttribute('required');
                    document.querySelector('[name="referencia_pago"]').removeAttribute('required');
                }
            });
        }

        cargarBancos();
    });

    // === FUNCIONES GLOBALES ===
    function exportarExcel(tipo) {
        var fecha_inicio = $('#fecha_inicio').val();
        var fecha_fin = $('#fecha_fin').val();
        var id_banco = $('#filtro_cuenta').val();
        var url = 'exportar_mensualidades.php?tipo=' + tipo;
        if (fecha_inicio && fecha_fin) {
            url += '&fecha_inicio=' + encodeURIComponent(fecha_inicio) + '&fecha_fin=' + encodeURIComponent(fecha_fin);
        }
        if (tipo === 'filtrado' && id_banco) {
            url += '&id_banco=' + encodeURIComponent(id_banco);
        }
        window.open(url, '_blank');
    }

    function cargarBancos() {
        fetch('json_bancos_api.php?action=get')
            .then(r => r.json())
            .then(data => {
                const filtro = document.getElementById('filtro_cuenta');
                const modalSelect = document.getElementById('select_banco_modal');
                const cobroSelect = document.getElementById('select_banco_cobro');
                const lista = document.getElementById('lista_bancos_gestion');

                const valFiltro = filtro ? filtro.value : '';
                const valModal = modalSelect ? modalSelect.value : '';
                const valCobro = cobroSelect ? cobroSelect.value : '';

                if (filtro) filtro.innerHTML = '<option value="">Todas las Cuentas</option>';
                if (modalSelect) modalSelect.innerHTML = '<option value="">Seleccione...</option>';
                if (cobroSelect) cobroSelect.innerHTML = '<option value="">Seleccione...</option>';
                if (lista) lista.innerHTML = '';

                data.forEach(b => {
                    if (filtro) filtro.add(new Option(b.nombre_banco, b.id_banco));
                    if (modalSelect) modalSelect.add(new Option(b.nombre_banco, b.id_banco));
                    if (cobroSelect) cobroSelect.add(new Option(b.nombre_banco, b.id_banco));
                    if (lista) {
                        const li = document.createElement('li');
                        li.className = 'list-group-item d-flex justify-content-between align-items-center';
                        li.innerHTML = `<div><strong>${b.nombre_banco}</strong><small class="d-block text-muted" style="font-size:0.75rem">${b.numero_cuenta || ''} ${b.nombre_propietario ? '- ' + b.nombre_propietario : ''} ${b.cedula_propietario ? '(' + b.cedula_propietario + ')' : ''}</small></div>
                        <button class="btn btn-sm btn-outline-danger" onclick="eliminarBanco('${b.id_banco}')"><i class="fas fa-trash"></i></button>`;
                        lista.appendChild(li);
                    }
                });
                if (valFiltro && filtro) filtro.value = valFiltro;
                if (valModal && modalSelect) modalSelect.value = valModal;
                if (valCobro && cobroSelect) cobroSelect.value = valCobro;
            });
    }

    async function solicitarClaveAdmin(titulo = 'Confirmar Acción') {
        // Fix for Bootstrap modal focus trap
        const focusHandler = (e) => {
            if (e.target.closest(".swal2-container")) {
                e.stopImmediatePropagation();
            }
        };
        document.addEventListener('focusin', focusHandler, true);

        const { value: password } = await Swal.fire({
            title: titulo,
            input: 'password',
            inputLabel: 'Ingrese la clave de administrador para proceder',
            inputPlaceholder: 'Clave de seguridad',
            inputAttributes: {
                autocapitalize: 'off',
                autocorrect: 'off'
            },
            showCancelButton: true,
            confirmButtonText: 'Confirmar',
            cancelButtonText: 'Cancelar',
            didClose: () => {
                document.removeEventListener('focusin', focusHandler, true);
            }
        });

        if (password) {
            const resp = await fetch('verificar_clave.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'clave=' + encodeURIComponent(password)
            });
            const data = await resp.json();
            if (data.success) return true;
            Swal.fire('Error', 'Clave incorrecta', 'error');
        }
        return false;
    }

    const formAgregar = document.getElementById('formAgregarBanco');
    if (formAgregar) {
        formAgregar.addEventListener('submit', async function (e) {
            e.preventDefault();

            const proceeds = await solicitarClaveAdmin('Agregar Nuevo Banco');
            if (!proceeds) return;

            fetch('json_bancos_api.php?action=add', { method: 'POST', body: new FormData(this) })
                .then(r => r.json())
                .then(res => {
                    if (res.success) {
                        this.reset();
                        cargarBancos();
                        Swal.fire('Éxito', 'Banco agregado correctamente', 'success');
                    } else {
                        alert('Error: ' + res.message);
                    }
                });
        });
    }

    window.eliminarBanco = async function (id) {
        const proceeds = await solicitarClaveAdmin('Eliminar Banco');
        if (!proceeds) return;

        const form = new FormData();
        form.append('id', id);
        fetch('json_bancos_api.php?action=delete', { method: 'POST', body: form })
            .then(r => r.json())
            .then(res => {
                if (res.success) {
                    cargarBancos();
                    Swal.fire('Eliminado', 'La cuenta ha sido eliminada', 'success');
                } else {
                    alert('Error al eliminar');
                }
            });
    };
</script>

<?php require_once '../includes/layout_foot.php'; ?>