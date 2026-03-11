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
                                <th>Plan</th>
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
                        <input type="number" step="0.01" min="0" class="form-control form-control-lg"
                            id="input_monto_pagar" required>
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
                    
                    <!-- Escáner Inteligente OCR -->
                    <div class="mb-3 p-3 border rounded bg-light border-primary border-opacity-25" style="border-style: dashed !important;">
                        <label class="form-label fw-bold text-primary small mb-1"><i class="fas fa-camera"></i> Escaneo Automático de Comprobante (Opcional)</label>
                        <p class="text-muted small mb-0" style="font-size: 0.8rem;">Sube el capture temporalmente para rellenar Monto, Referencia y Banco (BETA).</p>
                        <input class="form-control form-control-sm mt-2" type="file" id="capture_upload" accept="image/*">
                        <div id="ocr_status" class="mt-2 small text-info fw-bold d-none">
                            <i class="fas fa-spinner fa-spin"></i> Analizando comprobante, por favor espera...
                        </div>
                    </div>

                    <!-- Datos Globales del Pago -->
                    <div class="row bg-light rounded p-3 mb-3 border">
                        <div class="col-md-6 mb-2">
                            <label class="form-label fw-semibold text-secondary small">Referencia de Pago</label>
                            <input type="text" class="form-control" name="referencia_pago" required placeholder="N° de Transferencia/Pago">
                        </div>
                        <div class="col-md-6 mb-2">
                            <label class="form-label fw-semibold text-secondary small">Banco Destino (Dónde pagó el cliente)</label>
                            <select class="form-select" name="id_banco_pago" id="select_banco_cobro" required>
                                <option value="">Seleccione...</option>
                                <!-- Llenado por JS -->
                            </select>
                        </div>
                    </div>

                    <!-- DESGLOSE DEL PAGO -->
                    <h6 class="fw-bold text-muted small mb-3 border-bottom pb-2">Desglose del Pago (Debe coincidir con la sumatoria)</h6>
                    
                    <!-- Switch 1: Mensualidad -->
                    <div class="d-flex align-items-center mb-2 bg-light p-2 rounded">
                        <div class="form-check form-switch me-3 mb-0">
                            <input class="form-check-input desglose-switch" type="checkbox" id="switch_mensualidad" name="desglose_mensualidad_activado" value="1">
                        </div>
                        <label class="form-check-label fw-bold text-dark me-auto" for="switch_mensualidad" style="min-width: 100px;">Mensualidad</label>
                        
                        <div class="d-none desglose-fields d-flex gap-2 w-100" id="fields_mensualidad">
                            <input type="number" step="0.01" min="0" class="form-control form-control-sm desglose-monto" name="monto_mensualidad" placeholder="Monto">
                            <input type="number" step="1" min="1" class="form-control form-control-sm" name="meses_mensualidad" placeholder="Cant. Meses">
                        </div>
                    </div>

                    <!-- Switch 2: Instalación -->
                    <div class="d-flex align-items-center mb-2 bg-light p-2 rounded">
                        <div class="form-check form-switch me-3 mb-0">
                            <input class="form-check-input desglose-switch" type="checkbox" id="switch_instalacion" name="desglose_instalacion_activado" value="1">
                        </div>
                        <label class="form-check-label fw-bold text-dark me-auto" for="switch_instalacion" style="min-width: 100px;">Instalación</label>
                        
                        <div class="d-none desglose-fields w-100" id="fields_instalacion">
                            <input type="number" step="0.01" min="0" class="form-control form-control-sm desglose-monto" name="monto_instalacion" placeholder="Monto Instalación">
                        </div>
                    </div>

                    <!-- Switch 3: Prorrateo -->
                    <div class="d-flex align-items-center mb-2 bg-light p-2 rounded">
                        <div class="form-check form-switch me-3 mb-0">
                            <input class="form-check-input desglose-switch" type="checkbox" id="switch_prorrateo" name="desglose_prorrateo_activado" value="1">
                        </div>
                        <label class="form-check-label fw-bold text-dark me-auto" for="switch_prorrateo" style="min-width: 100px;">Prorrateo</label>
                        
                        <div class="d-none desglose-fields w-100" id="fields_prorrateo">
                            <input type="number" step="0.01" min="0" class="form-control form-control-sm desglose-monto" name="monto_prorrateo" placeholder="Monto Prorrateo">
                        </div>
                    </div>

                    <!-- Switch 4: Abono -->
                    <div class="d-flex align-items-center mb-2 bg-light p-2 rounded">
                        <div class="form-check form-switch me-3 mb-0">
                            <input class="form-check-input desglose-switch" type="checkbox" id="switch_abono" name="desglose_abono_activado" value="1">
                        </div>
                        <label class="form-check-label fw-bold text-dark me-auto" for="switch_abono" style="min-width: 100px;">Abono</label>
                        
                        <div class="d-none desglose-fields w-100" id="fields_abono">
                            <input type="number" step="0.01" min="0" class="form-control form-control-sm desglose-monto" name="monto_abono" placeholder="Monto Abono">
                        </div>
                    </div>

                    <!-- Switch 5: Equipo -->
                    <div class="d-flex align-items-center mb-2 bg-light p-2 rounded">
                        <div class="form-check form-switch me-3 mb-0">
                            <input class="form-check-input desglose-switch" type="checkbox" id="switch_equipo" name="desglose_equipo_activado" value="1">
                        </div>
                        <label class="form-check-label fw-bold text-dark me-auto" for="switch_equipo" style="min-width: 100px;">Equipo</label>
                        
                        <div class="d-none desglose-fields w-100" id="fields_equipo">
                            <input type="number" step="0.01" min="0" class="form-control form-control-sm desglose-monto" name="monto_equipo" placeholder="Monto por Equipo">
                        </div>
                    </div>

                    <!-- Switch 6: Mensualidad Extra (Otros Contratos) -->
                    <div class="mb-4 bg-light p-2 rounded border border-info">
                        <div class="d-flex align-items-center mb-2">
                            <div class="form-check form-switch me-3 mb-0">
                                <input class="form-check-input desglose-switch" type="checkbox" id="switch_extra" name="desglose_extra_activado" value="1">
                            </div>
                            <label class="form-check-label fw-bold text-info me-auto" for="switch_extra">Mensualidad Extra (Otros Usuarios)</label>
                        </div>
                        
                        <div class="d-none desglose-fields w-100" id="fields_extra">
                            <div id="contenedor_extras">
                                <!-- Primera fila por defecto -->
                                <div class="row g-2 mb-2 fila-extra align-items-end">
                                    <div class="col-5 position-relative">
                                        <input type="text" class="form-control form-control-sm extra-search" placeholder="ID, Nombre o CI" autocomplete="off">
                                        <input type="hidden" name="extra_contrato[]" class="extra-hidden">
                                        <div class="list-group shadow-sm position-absolute w-100 extra-results" style="z-index: 1050; max-height: 150px; overflow-y: auto;"></div>
                                    </div>
                                    <div class="col-3">
                                        <input type="number" step="0.01" min="0" class="form-control form-control-sm desglose-monto" name="extra_monto[]" placeholder="Monto">
                                    </div>
                                    <div class="col-3">
                                        <input type="number" step="1" min="1" class="form-control form-control-sm" name="extra_meses[]" placeholder="Meses">
                                    </div>
                                    <div class="col-1 text-end">
                                        <button type="button" class="btn btn-sm btn-outline-danger btn-remove-extra" disabled><i class="fas fa-times"></i></button>
                                    </div>
                                </div>
                            </div>
                            <button type="button" class="btn btn-sm btn-info text-white mt-1 w-100" id="btn_add_extra">
                                <i class="fas fa-plus"></i> Añadir otra Mensualidad Extra
                            </button>
                        </div>
                    </div>

                    <!-- Totales y sumatoria Validación -->
                    <div class="alert alert-secondary d-flex justify-content-between align-items-center py-2 mb-3">
                        <span class="fw-bold small">Verificación de Desglose:</span>
                        <div class="text-end">
                            <span class="d-block small text-muted">Monto Superior Declarado: <strong id="val_monto_total">$0.00</strong></span>
                            <span class="d-block small text-muted">Suma del Desglose: <strong id="val_suma_desglose" class="text-danger">$0.00</strong></span>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold text-secondary small">Autorizado por</label>
                        <div class="input-group">
                            <input type="text" class="form-control" name="autorizado_por" id="input_autorizado_por"
                                required placeholder="Escriba un nombre...">
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
                <form id="formEliminar" method="POST" action="elimina_cobro.php">
                    <input type="hidden" name="id" id="id_cobro_eliminar">
                    <p class="text-muted small mb-2">Se eliminará el cobro #<strong id="id_display_eliminar"></strong>
                        de <strong id="cliente_nombre_eliminar"></strong></p>

                    <div class="mb-3 text-start">
                        <label for="delete_password" class="form-label small fw-bold">Confirme su Contraseña</label>
                        <input type="password" name="clave" class="form-control form-control-sm" id="delete_password"
                            placeholder="Ingrese su clave" required>
                    </div>

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
                { "data": 0 }, { "data": 1 }, { "data": 2 }, { "data": 3 }, { "data": 4 }, { "data": 5 }, { "data": 6 }, { "data": 7 }, { "data": 8 }, { "data": 9 },
                { "data": 10, "orderable": false, "searchable": false, "className": "text-end" }
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

        // === MODAL GENERAR COBRO (NUEVO DESGLOSE) LÓGICA ===
        const btnSubmitCobro = document.querySelector('#modalGenerarCobro form button[type="submit"]');
        const switchesDesglose = document.querySelectorAll('.desglose-switch');
        const inputsMontoDesglose = document.querySelectorAll('.desglose-monto');
        
        function validarSumatoriaDesglose() {
            let totalDeclarado = parseFloat(document.getElementById('monto_cobro_hidden').value) || 0;
            let sumatoriaDesglose = 0;

            // Recorrer todos los switches activados
            switchesDesglose.forEach(sw => {
                if (sw.checked) {
                    // Buscar los inputs de monto dentro del contenedor hermano
                    const container = sw.closest('.rounded').querySelector('.desglose-fields');
                    if (container) {
                        const montos = container.querySelectorAll('.desglose-monto');
                        montos.forEach(m => {
                            sumatoriaDesglose += parseFloat(m.value) || 0;
                        });
                    }
                }
            });

            // Actualizar UI
            document.getElementById('val_monto_total').textContent = `$${totalDeclarado.toFixed(2)}`;
            const spanSumatoria = document.getElementById('val_suma_desglose');
            spanSumatoria.textContent = `$${sumatoriaDesglose.toFixed(2)}`;

            // Validar
            // Usamos un pequeño epsilon para evitar problemas de precisión en JS (0.1 + 0.2 != 0.3)
            if (Math.abs(totalDeclarado - sumatoriaDesglose) < 0.01 && totalDeclarado > 0) {
                spanSumatoria.className = 'text-success';
                btnSubmitCobro.disabled = false;
            } else {
                spanSumatoria.className = 'text-danger fw-bold';
                btnSubmitCobro.disabled = true;
            }
        }

        // Eventos para recalcular
        switchesDesglose.forEach(sw => {
            sw.addEventListener('change', function() {
                const container = this.closest('.rounded').querySelector('.desglose-fields');
                if (this.checked) {
                    container.classList.remove('d-none');
                    // Hacer inputs 'required'
                    container.querySelectorAll('input').forEach(inp => {
                        if (inp.type !== 'hidden' && !inp.classList.contains('extra-search')) {
                          inp.setAttribute('required', 'required');
                        }
                    });
                } else {
                    container.classList.add('d-none');
                    // Quitar 'required' y vaciar
                    container.querySelectorAll('input').forEach(inp => {
                        inp.removeAttribute('required');
                        if (inp.type !== 'hidden') inp.value = '';
                    });
                }
                validarSumatoriaDesglose();
            });
        });

        // Recalcular cuando se escribe en los montos del desglose
        document.addEventListener('input', function(e) {
            if(e.target.classList.contains('desglose-monto') || e.target.id === 'input_monto_cobro') {
                setTimeout(validarSumatoriaDesglose, 50); // Dar tiempo a que el hidden se asiente si es el principal
            }
        });

        // Dinamismo "Mensualidades Extra"
        const btnAddExtra = document.getElementById('btn_add_extra');
        const containerExtras = document.getElementById('contenedor_extras');
        if (btnAddExtra) {
            btnAddExtra.addEventListener('click', function() {
                const filaOriginal = containerExtras.querySelector('.fila-extra');
                const nuevaFila = filaOriginal.cloneNode(true);
                
                // Limpiar valores
                nuevaFila.querySelectorAll('input').forEach(inp => inp.value = '');
                nuevaFila.querySelector('.extra-results').innerHTML = '';
                
                // Habilitar botón eliminar
                const btnRemove = nuevaFila.querySelector('.btn-remove-extra');
                btnRemove.disabled = false;
                btnRemove.addEventListener('click', function() {
                    nuevaFila.remove();
                    validarSumatoriaDesglose();
                });

                // Attach autocomplete to new row
                attachAutocompleteToRow(nuevaFila);

                containerExtras.appendChild(nuevaFila);
            });
        }

        // Attach autocomplete a la primera fila original
        if (containerExtras) {
            attachAutocompleteToRow(containerExtras.querySelector('.fila-extra'));
        }

        function attachAutocompleteToRow(row) {
            const searchInput = row.querySelector('.extra-search');
            const hiddenInput = row.querySelector('.extra-hidden');
            const resultsContainer = row.querySelector('.extra-results');
            const montoInput = row.querySelector('.desglose-monto');

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
                                    a.className = 'list-group-item list-group-item-action py-1 px-2 small';
                                    let extraPlanInfo = c.nombre_plan ? ` | <span class="badge bg-success-subtle text-success">$${parseFloat(c.monto_plan).toFixed(2)}</span>` : '';
                                    a.innerHTML = `<strong>ID ${c.id}</strong>: ${c.nombre_completo} <br><small class="text-muted">C.I.: ${c.cedula || 'N/A'}${extraPlanInfo}</small>`;
                                    a.onclick = (e) => {
                                        e.preventDefault();
                                        searchInput.value = `ID ${c.id}: ${c.nombre_completo}`;
                                        hiddenInput.value = c.id;
                                        resultsContainer.innerHTML = '';
                                        
                                        // Auto-rellenar monto si se tiene precio de plan
                                        if (c.monto_plan && parseFloat(c.monto_plan) > 0) {
                                            montoInput.value = parseFloat(c.monto_plan).toFixed(2);
                                            // Activar validacion
                                            const eventInput = new Event('input', { bubbles: true });
                                            montoInput.dispatchEvent(eventInput);
                                        }
                                    };
                                    resultsContainer.appendChild(a);
                                });
                            } else {
                                resultsContainer.innerHTML = '<div class="list-group-item disabled py-1 px-2 small">Sin resultados</div>';
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

        // === MODAL ELIMINAR LÓGICA ===
        $('#modalEliminar').on('show.bs.modal', function (event) {
            var button = $(event.relatedTarget);
            var modal = $(this);
            modal.find('#id_cobro_eliminar').val(button.data('id'));
            modal.find('#id_display_eliminar').text(button.data('id'));
            modal.find('#cliente_nombre_eliminar').text(button.data('nombre'));
            $('#delete_password').val(''); // Limpiar contraseña al abrir
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
                                    let extraPlanInfo = c.nombre_plan ? ` | <span class="badge bg-success-subtle text-success">$${parseFloat(c.monto_plan).toFixed(2)}</span>` : '';
                                    a.innerHTML = `<strong>ID ${c.id}</strong>: ${c.nombre_completo} <br><small class="text-muted">C.I.: ${c.cedula || 'N/A'}${extraPlanInfo}</small>`;
                                    a.onclick = (e) => {
                                        e.preventDefault();
                                        searchInput.value = `ID ${c.id}: ${c.nombre_completo}`;
                                        hiddenInput.value = c.id;
                                        resultsContainer.innerHTML = '';
                                        
                                        // Auto-seleccionar Mensualidad basado en el plan del cliente (Monto Sugerido)
                                        if (c.monto_plan && parseFloat(c.monto_plan) > 0) {
                                            const switchMensualidad = document.getElementById('switch_mensualidad');
                                            if (!switchMensualidad.checked) {
                                                switchMensualidad.checked = true;
                                                switchMensualidad.dispatchEvent(new Event('change'));
                                            }
                                            document.querySelector('[name="monto_mensualidad"]').value = parseFloat(c.monto_plan).toFixed(2);
                                            document.querySelector('[name="meses_mensualidad"]').value = 1;
                                            
                                            // Activar validacion de sumatoria
                                            const eventInput = new Event('input', { bubbles: true });
                                            document.querySelector('[name="monto_mensualidad"]').dispatchEvent(eventInput);
                                        }
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

        // Toggle Pago Inmediato ya no existe, se removió.
        // Forzar validación inicial para que el botón arranque bloqueado
        setTimeout(validarSumatoriaDesglose, 200);

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
        fetch('json_bancos_api.php?action=get&limit=100') // Solicitar más límite para traer todos en el modal
            .then(r => r.json())
            .then(result => {
                const bancosArray = result.data || [];
                const filtro = document.getElementById('filtro_cuenta');
                const modalSelect = document.getElementById('select_banco_modal');
                const cobroSelect = document.getElementById('select_banco_cobro');

                const valFiltro = filtro ? filtro.value : '';
                const valModal = modalSelect ? modalSelect.value : '';
                const valCobro = cobroSelect ? cobroSelect.value : '';

                if (filtro) filtro.innerHTML = '<option value="">Todas las Cuentas</option>';
                if (modalSelect) modalSelect.innerHTML = '<option value="">Seleccione...</option>';
                if (cobroSelect) cobroSelect.innerHTML = '<option value="">Seleccione...</option>';

                bancosArray.forEach(b => {
                    const nombreLabel = b.nombre_banco + (b.numero_cuenta ? ' (' + b.numero_cuenta.slice(-4) + ')' : '');
                    if (filtro) filtro.add(new Option(nombreLabel, b.id_banco));
                    if (modalSelect) modalSelect.add(new Option(nombreLabel, b.id_banco));
                    if (cobroSelect) cobroSelect.add(new Option(nombreLabel, b.id_banco));
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


    // === OCR TESSERACT.JS LOGIC ===
    const scriptTesseract = document.createElement('script');
    scriptTesseract.src = "https://cdn.jsdelivr.net/npm/tesseract.js@5/dist/tesseract.min.js";
    document.head.appendChild(scriptTesseract);

    document.getElementById('capture_upload').addEventListener('change', async function(e) {
        const file = e.target.files[0];
        if (!file) return;

        const statusDiv = document.getElementById('ocr_status');
        statusDiv.classList.remove('d-none');
        statusDiv.classList.replace('text-success', 'text-info');
        statusDiv.classList.replace('text-danger', 'text-info');
        statusDiv.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Analizando comprobante OCR, por favor espera...';

        try {
            const worker = await Tesseract.createWorker('spa'); 
            const ret = await worker.recognize(file);
            const text = ret.data.text;
            console.log("OCR TEXT:", text);
            
            let foundMonto = false;
            let foundRef = false;
            let foundBanco = false;

            // 1. Regex de Monto (Busca numero seguido o precedido de Bs)
            const montoRegex = /([\d\.\,]+)\s*Bs/i;
            const montoMatch = text.match(montoRegex);
            if (montoMatch) {
                let numStr = montoMatch[1].replace(/\./g, '').replace(',', '.');
                let amt = parseFloat(numStr);
                if (!isNaN(amt)) {
                    document.getElementById('moneda_cobro_bs').checked = true;
                    document.getElementById('moneda_cobro_bs').dispatchEvent(new Event('change'));
                    
                    document.getElementById('input_monto_cobro').value = amt;
                    document.getElementById('input_monto_cobro').dispatchEvent(new Event('input', { bubbles: true }));
                    foundMonto = true;
                }
            }

            // 2. Regex de Referencia Avanzado (Ajustado según captures reales)
            // Keywords prioritarias: Nro. de referencia, Número de operación, Referencia, Operación
            const keywords = [
                'Nro\\.?\\s+de\\s+referencia',
                'N[uú]mero\\s+de\\s+referencia',
                'Nro\\.?\\s+de\\s+operaci[oó]n',
                'N[uú]mero\\s+de\\s+operaci[oó]n',
                'Referencia',
                'Operaci[oó]n',
                'Operacion', // Sin tilde (común en OCR)
                'Confirmaci[oó]n',
                'Aprobaci[oó]n',
                'Ref\\.',
                'Ref\\s'
            ].join('|');
            
            // Buscamos la keyword seguida de ":" o espacio, y luego el número (aceptando ceros a la izquierda)
            // Agregamos [^0-9\n]* para saltar iconos de "copiar" o decoraciones antes del número
            const refRegex = new RegExp(`(?:${keywords})(?:\\s*:)?\\s*[^0-9\\n]*(\\d{6,})`, 'i');
            const refMatch = text.match(refRegex);
            
            if (refMatch) {
                // Limpiar espacios y asegurar que capturamos ceros a la izquierda (Provincial usa 0000...)
                let cleanRef = refMatch[1].trim().replace(/\s/g, '');
                document.querySelector('[name="referencia_pago"]').value = cleanRef;
                foundRef = true;
            } else {
                // Intento B: Buscar el número más largo que no sea el monto
                const flatText = text.replace(/\n/g, ' '); 
                const allNumbers = flatText.match(/\b\d{6,25}\b/g);
                if (allNumbers && allNumbers.length > 0) {
                    allNumbers.sort((a,b) => b.length - a.length);
                    document.querySelector('[name="referencia_pago"]').value = allNumbers[0];
                    foundRef = true;
                }
            }

            // 3. Regex Banco Destino (Super Flexible)
            const selectBanco = document.getElementById('select_banco_cobro');
            
            // a) Buscar por los últimos 4 dígitos de la cuenta que recibe
            const destinoRegex = /Destino[^\d]*(\d{4})/i;
            const destMatch = text.match(destinoRegex);
            
            if (destMatch && selectBanco) {
                let codBanco = destMatch[1]; // ej. 9811
                for (let i = 0; i < selectBanco.options.length; i++) {
                    if (selectBanco.options[i].text.includes(codBanco)) {
                        selectBanco.selectedIndex = i;
                        foundBanco = true;
                        break;
                    }
                }
            }

            // b) Buscar por nombre (Mapeo Inteligente ignorando espacios)
            if (!foundBanco && selectBanco) {
                let textLimpio = text.toUpperCase().replace(/\s+/g, ''); // Quitamos espacios al capture
                
                for (let i = 0; i < selectBanco.options.length; i++) {
                    let optTarget = selectBanco.options[i].text.toUpperCase().replace(/\s+/g, ''); // Ej: BANCAAMIGA(0000)
                    
                    let targetKeywords = [];
                    if (optTarget.includes('BANCAMIGA') || optTarget.includes('BANCAAMIGA') || optTarget.includes('AMIGA')) targetKeywords.push('BANCAMIGA', 'BANCAAMIGA');
                    if (optTarget.includes('BANESCO')) targetKeywords.push('BANESCO');
                    if (optTarget.includes('MERCANTIL')) targetKeywords.push('MERCANTIL');
                    if (optTarget.includes('PROVINCIAL')) targetKeywords.push('PROVINCIAL');
                    if (optTarget.includes('VENEZUELA')) targetKeywords.push('VENEZUELA', 'BDV');
                    if (optTarget.includes('BNC') || optTarget.includes('CREDITO')) targetKeywords.push('BNC', 'NACIONALDECREDITO');
                    
                    for (let kw of targetKeywords) {
                        if (textLimpio.includes(kw)) {
                            selectBanco.selectedIndex = i;
                            foundBanco = true;
                            break; // Rompe búsqueda de keywords
                        }
                    }
                    if (foundBanco) break; // Rompe loop de opciones
                }
            }

            let resultMsg = "OCR Finalizado. ";
            resultMsg += foundMonto ? "Monto OK. " : "";
            resultMsg += foundRef ? "Ref OK. " : "";
            resultMsg += foundBanco ? "Banco OK. " : "";

            statusDiv.innerHTML = '<i class="fas fa-check-circle"></i> ' + resultMsg;
            statusDiv.classList.replace('text-info', 'text-success');

            await worker.terminate();

        } catch (err) {
            console.error(err);
            statusDiv.innerHTML = '<i class="fas fa-exclamation-triangle"></i> Error al analizar imagen.';
            statusDiv.classList.replace('text-info', 'text-danger');
        }
    });

    window.confirmarEdicionCobro = async function (id) {
        const proceeds = await solicitarClaveAdmin('Modificar Cobro');
        if (proceeds) {
            window.location.href = 'modifica_cobro1.php?id=' + id;
        }
    };
</script>

<?php require_once '../includes/layout_foot.php'; ?>