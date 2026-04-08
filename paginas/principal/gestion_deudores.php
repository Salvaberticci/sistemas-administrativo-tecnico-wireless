<?php
/**
 * Gestión de Clientes Deudores
 */
require_once '../conexion.php';

$path_to_root = "../../";
$page_title = "Clientes Deudores";
$breadcrumb = ["Cobranzas"];
$back_url = "../menu.php";

// Obtener Bancos para Modal de Abonos
$bancos = $conn->query("SELECT id_banco, nombre_banco FROM bancos ORDER BY nombre_banco ASC");
$bancos_data = [];
if ($bancos) {
    while ($row = $bancos->fetch_assoc()) {
        $bancos_data[] = $row;
    }
}

require_once '../includes/layout_head.php';
require_once '../includes/sidebar.php';
?>

<link href="<?php echo $path_to_root; ?>css/datatables.min.css" rel="stylesheet">

<main class="main-content">
    <?php include '../includes/header.php'; ?>

    <div class="page-content">
        <div class="card">
            <div
                class="card-header d-flex justify-content-between align-items-center bg-white border-bottom-0 pt-4 px-4">
                <div>
                    <h5 class="fw-bold text-danger mb-1">Clientes Deudores</h5>
                    <p class="text-muted small mb-0">Listado de clientes con saldos pendientes</p>
                </div>
                <button type="button" class="btn btn-danger shadow-sm px-4 fw-bold" data-bs-toggle="modal" data-bs-target="#modalCrearDeuda">
                    <i class="fas fa-plus-circle me-1"></i> Crear Nueva Deuda
                </button>
            </div>

            <div class="card-body px-4">
                <div class="table-responsive">
                    <table class="display table table-hover w-100" id="tabla_deudores">
                        <thead class="bg-light">
                            <tr>
                                <th class="text-center">ID</th>
                                <th class="text-center">Cliente</th>
                                <th class="text-center">Cédula</th>
                                <th class="text-center">IP</th>
                                <th class="text-center">Monto Total</th>
                                <th class="text-center">Monto Pagado</th>
                                <th class="text-center">Saldo Pendiente</th>
                                <th class="text-center">Fecha Registro</th>
                                <th class="text-center">Estado</th>
                                <th class="text-center" width="15%">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $sql = "SELECT d.*, c.nombre_completo, c.cedula, c.ip_onu
                                    FROM clientes_deudores d
                                    INNER JOIN contratos c ON d.id_contrato = c.id
                                    WHERE d.estado = 'PENDIENTE'
                                    ORDER BY d.fecha_registro DESC";
                            $result = $conn->query($sql);

                            while ($row = $result->fetch_assoc()) {
                                $estado_badge = '<span class="badge bg-danger">PENDIENTE</span>';

                                echo "<tr>
                                    <td class='text-center'>{$row['id']}</td>
                                    <td class='fw-bold text-center'>{$row['nombre_completo']}</td>
                                    <td class='text-center'>{$row['cedula']}</td>
                                    <td class='text-center'><code>{$row['ip_onu']}</code></td>
                                    <td class='text-center'>\${$row['monto_total']}</td>
                                    <td class='text-center'>\${$row['monto_pagado']}</td>
                                    <td class='text-center text-danger fw-bold'>\${$row['saldo_pendiente']}</td>
                                    <td class='text-center'>" . date('d/m/Y', strtotime($row['fecha_registro'])) . "</td>
                                    <td class='text-center'>{$estado_badge}</td>
                                    <td>
                                        <div class='d-flex justify-content-center gap-1 flex-nowrap'>
                                            <button class='btn btn-sm btn-success' onclick='marcarPagado({$row['id']})' title='Marcar como Pagado'>
                                                <i class='fa-solid fa-check'></i> Pagado
                                            </button>
                                            <button class='btn btn-sm btn-info text-white' onclick='abrirModalAbonos({$row['id']}, {$row['saldo_pendiente']})' title='Gestionar Abonos'>
                                                <i class='fa-solid fa-coins'></i> Abonos
                                            </button>
                                            <button onclick='verContrato({$row['id_contrato']})' class='btn btn-sm btn-outline-primary' title='Ver Detalle del Contrato'>
                                                <i class='fa-solid fa-eye'></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>

                <div class="d-flex justify-content-center mt-4">
                    <a href="../../paginas/menu.php" class="btn btn-outline-secondary">
                        <i class="fa-solid fa-arrow-left me-2"></i> Volver al Menú
                    </a>
                </div>
            </div>
        </div>
    </div>
</main>

<!-- Modal Ver Contrato Detalle -->
<div class="modal fade" id="modalVerContrato" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title fw-bold"><i class="fas fa-file-contract me-2"></i>Detalle del Contrato #<span id="vc_id_contrato">---</span></h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-0">
                <!-- Banner Superior -->
                <div class="bg-light p-4 border-bottom">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <h3 class="fw-bold text-dark mb-1" id="vc_nombre_completo">---</h3>
                            <span class="badge bg-primary px-3 py-2" id="vc_estado">ACTIVO</span>
                            <span class="ms-2 text-muted small"><i class="fas fa-id-card me-1"></i><span id="vc_cedula">---</span></span>
                        </div>
                        <div class="text-end">
                            <label class="small text-muted d-block fw-bold">Plan de Internet</label>
                            <h4 class="text-primary fw-bold mb-0" id="vc_plan">---</h4>
                        </div>
                    </div>
                </div>

                <div class="p-4">
                    <div class="row g-4">
                        <!-- Columna Contacto -->
                        <div class="col-md-6">
                            <div class="card bg-white border h-100">
                                <div class="card-body">
                                    <h6 class="fw-bold mb-3 text-secondary border-bottom pb-2"><i class="fas fa-user-circle me-2"></i>Contacto y Ubicación</h6>
                                    <div class="mb-2"><small class="text-muted d-block small fw-bold uppercase">Teléfono</small><span id="vc_telefono">---</span></div>
                                    <div class="mb-2"><small class="text-muted d-block small fw-bold uppercase">Correo</small><span id="vc_correo">---</span></div>
                                    <div class="mb-2"><small class="text-muted d-block small fw-bold uppercase">Dirección</small>
                                        <p class="small mb-0" id="vc_direccion_completa">---</p>
                                    </div>
                                    <div class="mt-2 small text-muted">
                                        <i class="fas fa-map-marker-alt me-1 text-danger"></i> <span id="vc_municipio">---</span>, <span id="vc_parroquia">---</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Columna Conexión -->
                        <div class="col-md-6">
                            <div class="card bg-white border h-100">
                                <div class="card-body">
                                    <h6 class="fw-bold mb-3 text-secondary border-bottom pb-2"><i class="fas fa-network-wired me-2"></i>Información de Red</h6>
                                    <div class="row">
                                        <div class="col-6 mb-2"><small class="text-muted d-block small fw-bold uppercase">IP ONU</small><code id="vc_ip_onu">---</code></div>
                                        <div class="col-6 mb-2"><small class="text-muted d-block small fw-bold uppercase">IP Antena/Router</small><code id="vc_ip">---</code></div>
                                        <div class="col-12 mb-2"><small class="text-muted d-block small fw-bold uppercase">Fecha Instalación</small><span id="vc_fecha_instalacion">---</span></div>
                                        <div class="col-12 mb-2"><small class="text-muted d-block small fw-bold uppercase">Vendedor</small><span id="vc_vendedor">---</span></div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Sección Técnica (FTTH / RADIO) -->
                        <div class="col-12">
                            <div class="card bg-light border-0 shadow-sm" id="vc_tech_section">
                                <div class="card-body">
                                    <h6 class="fw-bold mb-3 text-dark"><i class="fas fa-tools me-2"></i>Detalles Técnicos de <span id="vc_tipo_conexion">---</span></h6>
                                    
                                    <!-- Campos FTTH -->
                                    <div id="vc_tech_ftth" class="row g-3" style="display:none;">
                                        <div class="col-md-3"><small class="text-muted d-block fw-bold small">OLT</small><span id="vc_olt">---</span></div>
                                        <div class="col-md-3"><small class="text-muted d-block fw-bold small">PON</small><span id="vc_pon">---</span></div>
                                        <div class="col-md-3"><small class="text-muted d-block fw-bold small">Caja NAP</small><span id="vc_caja">---</span></div>
                                        <div class="col-md-3"><small class="text-muted d-block fw-bold small">Puerto</small><span id="vc_puerto">---</span></div>
                                        <div class="col-md-3"><small class="text-muted d-block fw-bold small">MAC/Serial</small><code id="vc_mac">---</code></div>
                                        <div class="col-md-3"><small class="text-muted d-block fw-bold small">RX Power</small><span id="vc_rx" class="badge bg-dark">---</span></div>
                                        <div class="col-md-3"><small class="text-muted d-block fw-bold small">Distancia</small><span id="vc_distancia">---</span></div>
                                    </div>

                                    <!-- Campos RADIO -->
                                    <div id="vc_tech_radio" class="row g-3" style="display:none;">
                                        <div class="col-md-6"><small class="text-muted d-block fw-bold small">Punto de Acceso</small><span id="vc_ap">---</span></div>
                                        <div class="col-md-6"><small class="text-muted d-block fw-bold small">Señal (dBm)</small><span id="vc_signal" class="badge bg-dark">---</span></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer bg-light border-top-0">
                <button type="button" class="btn btn-dark shadow-sm px-4" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Gestionar Abonos -->
<div class="modal fade" id="modalGestionarAbonos" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title fw-bold">
                    <i class="fas fa-coins me-2"></i>Gestionar Abonos - Deuda #<span id="abono_id_deuda"></span>
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            
            <div class="modal-body p-0">
                <!-- Info Banner -->
                <div class="bg-light p-3 border-bottom d-flex justify-content-between align-items-center">
                    <div>
                        <span class="small text-muted fw-bold d-block text-uppercase">Saldo Pendiente Actual</span>
                        <h4 class="mb-0 text-danger fw-bold">$<span id="abono_saldo_pendiente_display">0.00</span></h4>
                    </div>
                </div>

                <!-- Tabs Navbar -->
                <ul class="nav nav-tabs px-3 pt-3 bg-white" id="abonoTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active fw-bold" id="tab-registrar" data-bs-toggle="tab" data-bs-target="#pane-registrar" type="button" role="tab" aria-selected="true">
                            <i class="fas fa-plus-circle me-1"></i>Ingresar Abono
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link fw-bold" id="tab-historial" data-bs-toggle="tab" data-bs-target="#pane-historial" type="button" role="tab" aria-selected="false">
                            <i class="fas fa-history me-1"></i>Historial de Pagos
                        </button>
                    </li>
                </ul>

                <!-- Tabs Content -->
                <div class="tab-content" id="abonoTabsContent">
                    <!-- Tab 1: Registrar Abono -->
                    <div class="tab-pane fade show active p-4" id="pane-registrar" role="tabpanel">
                        <form id="formRegistrarAbono" enctype="multipart/form-data">
                            <input type="hidden" name="id_deudor" id="abono_input_id_deudor">
                            <input type="hidden" id="abono_input_max_monto">
                            
                            <div class="row g-3">
                                <!-- Columna Izquierda: Datos del Pago -->
                                <div class="col-md-7 border-end pe-4">
                                    <div class="mb-3">
                                        <label class="form-label fw-bold text-muted small mb-1">Monto a Abonar ($)</label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-light fw-bold text-success">$</span>
                                            <input type="text" class="form-control form-control-lg fw-bold text-success decimal-input" id="abono_monto" name="monto_abono" required placeholder="0.00" inputmode="decimal" autocomplete="off">
                                        </div>
                                        <div class="form-text small text-danger d-none" id="abono_error_monto">El monto excede el saldo pendiente.</div>
                                    </div>
                                    
                                    <div class="row g-3 mb-3">
                                        <div class="col-6">
                                            <label class="form-label fw-bold text-muted small mb-1">Referencia</label>
                                            <input type="text" class="form-control form-control-sm" name="referencia" required autocomplete="off">
                                        </div>
                                        <div class="col-6">
                                            <label class="form-label fw-bold text-muted small mb-1">Banco Receptor</label>
                                            <select class="form-select form-select-sm" name="id_banco" id="abono_select_banco" required>
                                                <option value="">Seleccione...</option>
                                                <!-- Llenado vía JS/PHP -->
                                            </select>
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label fw-bold text-muted small mb-1">Justificación (Opcional)</label>
                                        <input type="text" class="form-control form-control-sm" name="justificacion" placeholder="Ej: Transferencia parcial acordada...">
                                    </div>
                                </div>
                                
                                <!-- Columna Derecha: Capture -->
                                <div class="col-md-5 ps-3">
                                    <label class="form-label fw-bold text-muted small mb-2 d-block">Comprobante (Opcional)</label>
                                    <div class="border rounded p-2 text-center bg-light mb-2">
                                        <input type="file" class="form-control form-control-sm mb-2" id="abono_capture" name="capture_abono" accept="image/*">
                                        <div class="rounded bg-white border" style="height: 150px; overflow: hidden; display: flex; align-items: center; justify-content: center;">
                                            <img id="abono_preview_img" src="" class="img-fluid d-none" style="object-fit: contain; max-height: 100%;">
                                            <i class="fas fa-image fa-3x text-muted opacity-25" id="abono_preview_icon"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="text-end mt-4">
                                <button type="button" class="btn btn-light me-2" data-bs-dismiss="modal">Cancelar</button>
                                <button type="submit" class="btn btn-info text-white fw-bold px-4" id="btn_submit_abono">
                                    <i class="fas fa-save me-1"></i>Registrar Abono
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- Tab 2: Historial -->
                    <div class="tab-pane fade p-3" id="pane-historial" role="tabpanel">
                        <div class="table-responsive" style="max-height: 300px; overflow-y: auto;">
                            <table class="table table-sm table-hover mb-0" style="font-size: 0.85rem;">
                                <thead class="table-light sticky-top">
                                    <tr>
                                        <th>Fecha</th>
                                        <th>Ref / Banco</th>
                                        <th>Justificación</th>
                                        <th class="text-end">Monto ABONADO</th>
                                        <th class="text-center">Capture</th>
                                    </tr>
                                </thead>
                                <tbody id="abono_historial_body">
                                    <tr><td colspan="5" class="text-center text-muted py-4">Cargando historial...</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Crear Nueva Deuda -->
<div class="modal fade" id="modalCrearDeuda" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title fw-bold"><i class="fas fa-hand-holding-dollar me-2"></i>Registrar Nueva Deuda</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="formCrearDeuda">
                <div class="modal-body p-4">
                    <!-- 1. Buscador de Contrato -->
                    <div class="mb-4 position-relative">
                        <label class="form-label fw-bold text-dark small"><i class="fas fa-search me-1 text-danger"></i> 1. Buscar Cliente / Contrato</label>
                        <div class="input-group">
                            <span class="input-group-text bg-white border-end-0"><i class="fas fa-user text-muted"></i></span>
                            <input type="text" id="contrato_search_deuda" class="form-control border-start-0" placeholder="Nombre, Cédula o ID..." autocomplete="off">
                        </div>
                        <input type="hidden" name="id_contrato" id="id_contrato_hidden_deuda" required>
                        <div id="contrato_search_results_deuda" class="list-group position-absolute w-100 shadow-sm" style="z-index: 1050; max-height: 200px; overflow-y: auto;"></div>
                    </div>

                    <!-- 2. Monto de la Deuda -->
                    <div class="mb-4">
                        <label class="form-label fw-bold text-dark small"><i class="fas fa-money-bill-wave me-1 text-success"></i> 2. Monto de la Deuda ($)</label>
                        <div class="input-group input-group-lg">
                            <span class="input-group-text bg-light fw-bold text-success">$</span>
                            <input type="number" step="0.01" min="0.01" name="monto" class="form-control fw-bold" placeholder="0.00" required>
                        </div>
                    </div>

                    <!-- 3. Notas / Justificación -->
                    <div class="mb-2">
                        <label class="form-label fw-bold text-dark small"><i class="fas fa-comment-alt me-1 text-primary"></i> 3. Notas / Justificación</label>
                        <textarea name="notas" class="form-control" rows="3" placeholder="Ej: Saldo pendiente por reposición de equipo ONU..."></textarea>
                    </div>
                </div>
                <div class="modal-footer bg-light border-0">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-danger px-4 fw-bold" id="btn_submit_deuda">
                        <i class="fas fa-save me-1"></i> Guardar Deuda
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="<?php echo $path_to_root; ?>js/jquery.min.js"></script>
<script src="<?php echo $path_to_root; ?>js/datatables.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
    $(document).ready(function () {
        $('#tabla_deudores').DataTable({
            "language": {
                "lengthMenu": "Mostrar _MENU_",
                "zeroRecords": "No hay deudores registrados",
                "info": "_START_ - _END_ de _TOTAL_",
                "search": "Buscar:",
                "paginate": { "next": ">", "previous": "<" }
            },
            "order": [[7, "desc"]] // Ordenar por fecha descendente
        });

        // Cargar bancos para el modal de abonos desde PHP
        const bancosInfo = <?php echo json_encode($bancos_data); ?>;
        const selectBanco = document.getElementById('abono_select_banco');
        if (bancosInfo && bancosInfo.length > 0) {
            bancosInfo.forEach(b => {
                const opt = document.createElement('option');
                opt.value = b.id_banco;
                opt.textContent = b.nombre_banco;
                selectBanco.appendChild(opt);
            });
        }
    });

    async function solicitarClaveAdmin(titulo = 'Confirmar Acción') {
        const focusHandler = (e) => {
            if (e.target.closest(".swal2-container")) {
                e.stopImmediatePropagation();
            }
        };
        document.addEventListener('focusin', focusHandler, true);

        const { value: password } = await Swal.fire({
            title: titulo,
            input: 'password',
            inputLabel: 'Ingrese su clave de usuario para proceder',
            inputPlaceholder: 'Contraseña',
            inputAttributes: {
                autocapitalize: 'off',
                autocorrect: 'off'
            },
            showCancelButton: true,
            confirmButtonText: 'Confirmar',
            cancelButtonText: 'Cancelar',
            reverseButtons: true,
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
            if (data.success) {
                return true;
            }
            Swal.fire('Error', 'Clave incorrecta', 'error');
        }
        return false;
    }

    async function marcarPagado(id) {
        const proceeds = await solicitarClaveAdmin('Confirmar Pago');
        if (!proceeds) return;

        Swal.fire({
            title: '¿Confirmar Pago?',
            text: "Esta acción marcará la deuda como pagada y limpiará el saldo pendiente.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#198754',
            cancelButtonColor: '#343a40',
            confirmButtonText: '<i class="fa-solid fa-check me-2"></i>Sí, confirmar pago',
            cancelButtonText: 'Cancelar',
            reverseButtons: true
        }).then((result) => {
            if (result.isConfirmed) {
                // Mostrar estado de carga
                Swal.fire({
                    title: 'Procesando...',
                    text: 'Actualizando registro en el sistema',
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });

                $.post('marcar_pagado.php', { id: id }, function (resp) {
                    if (resp === 'OK') {
                        Swal.fire({
                            title: '¡Guardado!',
                            text: 'El cliente ha sido marcado como pagado exitosamente.',
                            icon: 'success',
                            confirmButtonColor: '#0d6efd',
                            timer: 1500,
                            showConfirmButton: false
                        }).then(() => {
                            location.reload();
                        });
                    } else {
                        Swal.fire({
                            title: 'Error',
                            text: 'No se pudo actualizar el registro: ' + resp,
                            icon: 'error',
                            confirmButtonColor: '#dc3545'
                        });
                    }
                }).fail(function() {
                    Swal.fire({
                        title: 'Error de Red',
                        text: 'No se pudo comunicar con el servidor.',
                        icon: 'error',
                        confirmButtonColor: '#dc3545'
                    });
                });
            }
        });
    }

    /* =========================================
       LÓGICA DE ABONOS (PAGOS PARCIALES)
       ========================================= */

    function abrirModalAbonos(idDeudor, montoPendiente) {
        // Reset form e UI
        document.getElementById('formRegistrarAbono').reset();
        document.getElementById('abono_preview_img').classList.add('d-none');
        document.getElementById('abono_preview_icon').classList.remove('d-none');
        document.getElementById('abono_error_monto').classList.add('d-none');
        
        // Cargar datos al modal
        document.getElementById('abono_id_deuda').textContent = idDeudor;
        document.getElementById('abono_input_id_deudor').value = idDeudor;
        document.getElementById('abono_saldo_pendiente_display').textContent = parseFloat(montoPendiente).toFixed(2);
        document.getElementById('abono_input_max_monto').value = montoPendiente;
        
        // Activar la primera pestaña y limpiar tabla historial visualmente
        const bsTab = new bootstrap.Tab(document.getElementById('tab-registrar'));
        bsTab.show();
        document.getElementById('abono_historial_body').innerHTML = '<tr><td colspan="5" class="text-center text-muted py-4"><i class="fas fa-spinner fa-spin me-2"></i>Cargando historial...</td></tr>';

        // Mostrar el modal
        const modal = new bootstrap.Modal(document.getElementById('modalGestionarAbonos'));
        modal.show();

        // Cargar historial en background
        cargarHistorialAbonos(idDeudor);
    }

    function cargarHistorialAbonos(idDeudor) {
        fetch(`get_historial_abonos.php?id_deudor=${idDeudor}`)
            .then(r => r.json())
            .then(res => {
                const tbody = document.getElementById('abono_historial_body');
                tbody.innerHTML = '';
                
                if (!res.success) {
                    tbody.innerHTML = `<tr><td colspan="5" class="text-center text-danger py-3">${res.message}</td></tr>`;
                    return;
                }

                if (res.data.length === 0) {
                    tbody.innerHTML = '<tr><td colspan="5" class="text-center text-muted py-4">No se han registrado abonos para esta deuda.</td></tr>';
                } else {
                    res.data.forEach(p => {
                        let captureHtml = p.capture_pago 
                            ? `<a href="../../${p.capture_pago}" target="_blank" class="btn btn-sm btn-light border" title="Ver Capture"><i class="fas fa-image text-primary"></i></a>`
                            : '<span class="text-muted small">N/A</span>';

                        const tr = document.createElement('tr');
                        tr.innerHTML = `
                            <td>${p.fecha_pago || p.fecha_creacion}</td>
                            <td>
                                <strong>Ref:</strong> ${p.referencia_pago || 'N/A'}<br>
                                <span class="small text-muted">${p.nombre_banco || ''}</span>
                            </td>
                            <td>${p.justificacion}</td>
                            <td class="text-end fw-bold text-success">+$${parseFloat(p.monto_cargado).toFixed(2)}</td>
                            <td class="text-center">${captureHtml}</td>
                        `;
                        tbody.appendChild(tr);
                    });
                }
            })
            .catch(err => {
                console.error("Error historial abonos:", err);
                document.getElementById('abono_historial_body').innerHTML = '<tr><td colspan="5" class="text-center text-danger py-4">Error técnico al cargar el historial.</td></tr>';
            });
    }

    // Validación de entrada para el monto a abonar
    document.getElementById('abono_monto').addEventListener('input', function() {
        // Limpiar caracteres no numéricos excepto punto (o coma si se prefiere)
        this.value = this.value.replace(/[^0-9.]/g, '');
        
        const val = parseFloat(this.value) || 0;
        const max = parseFloat(document.getElementById('abono_input_max_monto').value) || 0;
        const btnSubmit = document.getElementById('btn_submit_abono');
        const errorMsg = document.getElementById('abono_error_monto');

        if (val > max && max > 0) { // Permitir validación solo si hay max válido
            errorMsg.classList.remove('d-none');
            this.classList.add('is-invalid', 'border-danger');
            btnSubmit.disabled = true;
        } else {
            errorMsg.classList.add('d-none');
            this.classList.remove('is-invalid', 'border-danger');
            btnSubmit.disabled = false;
        }
    });

    // Vista previa de la imagen al subir
    document.getElementById('abono_capture').addEventListener('change', function(e) {
        const file = e.target.files[0];
        const previewImg = document.getElementById('abono_preview_img');
        const previewIcon = document.getElementById('abono_preview_icon');
        
        if (file && file.type.startsWith('image/')) {
            const reader = new FileReader();
            reader.onload = function(evt) {
                previewImg.src = evt.target.result;
                previewImg.classList.remove('d-none');
                previewIcon.classList.add('d-none');
            };
            reader.readAsDataURL(file);
        } else {
            previewImg.src = '';
            previewImg.classList.add('d-none');
            previewIcon.classList.remove('d-none');
        }
    });

    // Enviar el formulario
    document.getElementById('formRegistrarAbono').addEventListener('submit', async function(e) {
        e.preventDefault();
        
        const val = parseFloat(document.getElementById('abono_monto').value) || 0;
        const max = parseFloat(document.getElementById('abono_input_max_monto').value) || 0;
        
        if (val <= 0 || val > max) {
             Swal.fire('Error', 'El monto a abonar no es válido. Revise el saldo pendiente.', 'error');
             return;
        }

        const proceeds = await solicitarClaveAdmin('Confirmar Abono');
        if (!proceeds) return;

        const btn = document.getElementById('btn_submit_abono');
        const originalText = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Procesando...';

        const fd = new FormData(this);

        fetch('registrar_abono_deudor.php', {
            method: 'POST',
            body: fd
        })
        .then(r => r.json())
        .then(res => {
            if (res.success) {
                // Verificar si saldó la deuda completamente
                if (res.saldado) {
                    Swal.fire({
                        title: '¡Deuda Saldada!',
                        text: 'El abono ha cubierto la totalidad de la deuda. El cliente ya no es considerado deudor.',
                        icon: 'success',
                        confirmButtonColor: '#198754'
                    }).then(() => location.reload());
                } else {
                    Swal.fire({
                        title: '¡Abono Registrado!',
                        text: res.message,
                        icon: 'success',
                        timer: 2000,
                        showConfirmButton: false
                    }).then(() => location.reload());
                }
            } else {
                Swal.fire('Error', res.message, 'error');
                btn.disabled = false;
                btn.innerHTML = originalText;
            }
        })
        .catch(err => {
            console.error(err);
            Swal.fire('Error Técnico', 'No se pudo comunicar con el servidor.', 'error');
            btn.disabled = false;
            btn.innerHTML = originalText;
        });
    });

    function verContrato(id) {
        // Mostrar modal de carga o simplemente reset
        $('#modalVerContrato').modal('show');
        
        // Limpiar campos visualmente
        $('#vc_id_contrato').text(id);
        $('#vc_nombre_completo').text('Cargando...');
        
        $.get('get_contrato_detalle.php', { id: id }, function(data) {
            if(data.error) {
                alert(data.error);
                return;
            }
            
            // Llenar campos
            $('#vc_nombre_completo').text(data.nombre_completo);
            $('#vc_cedula').text(data.cedula);
            $('#vc_estado').text(data.estado).removeClass().addClass('badge px-3 py-2 ' + (data.estado === 'ACTIVO' ? 'bg-success' : 'bg-danger'));
            $('#vc_plan').text(data.nombre_plan || 'N/A');
            $('#vc_telefono').text(data.telefono || '---');
            $('#vc_correo').text(data.correo || '---');
            $('#vc_direccion_completa').text(data.direccion || '---');
            $('#vc_municipio').text(data.municipio_texto || data.nombre_municipio || '---');
            $('#vc_parroquia').text(data.parroquia_texto || data.nombre_parroquia || '---');
            
            $('#vc_ip_onu').text(data.ip_onu || '---');
            $('#vc_ip').text(data.ip || '---');
            $('#vc_fecha_instalacion').text(data.fecha_instalacion || '---');
            $('#vc_vendedor').text(data.vendedor_texto || '---');
            $('#vc_tipo_conexion').text(data.tipo_conexion || 'Técnicos');
            
            // Lógica Técnica
            $('#vc_tech_ftth, #vc_tech_radio').hide();
            if(data.tipo_conexion && data.tipo_conexion.includes('FTTH')) {
                $('#vc_tech_ftth').show();
                $('#vc_olt').text(data.nombre_olt || '---');
                $('#vc_pon').text(data.nombre_pon || '---');
                $('#vc_caja').text(data.ident_caja_nap || '---');
                $('#vc_puerto').text(data.puerto_nap || '---');
                $('#vc_mac').text(data.mac_onu || '---');
                $('#vc_rx').text(data.onu_rx_power || '---');
                $('#vc_distancia').text(data.distancia_drop ? data.distancia_drop + ' m' : '---');
            } else {
                $('#vc_tech_radio').show();
                $('#vc_ap').text(data.punto_acceso || '---');
                $('#vc_signal').text(data.valor_conexion_dbm ? data.valor_conexion_dbm + ' dBm' : '---');
            }
            
        });
    }

    /* =========================================
       NUEVO: REGISTRO MANUAL DE DEUDA
       ========================================= */
    const searchInputDeuda = document.getElementById('contrato_search_deuda');
    const resultsContainerDeuda = document.getElementById('contrato_search_results_deuda');
    const hiddenInputDeuda = document.getElementById('id_contrato_hidden_deuda');

    if (searchInputDeuda) {
        let timer;
        searchInputDeuda.addEventListener('input', function () {
            clearTimeout(timer);
            const q = this.value.trim();
            if (q.length < 3) { resultsContainerDeuda.innerHTML = ''; return; }
            timer = setTimeout(() => {
                fetch(`buscar_contratos.php?q=${encodeURIComponent(q)}`)
                    .then(r => r.json())
                    .then(data => {
                        resultsContainerDeuda.innerHTML = '';
                        if (data.length) {
                            data.forEach(c => {
                                const a = document.createElement('a');
                                a.className = 'list-group-item list-group-item-action';
                                
                                // Reutilizamos lógica de badges de mensualidades para consistencia
                                let nroLabel = (c.total_contratos > 1) ? `<span class="bg-primary text-white px-2 py-0 rounded-pill me-1" style="font-size:0.7rem;">#${c.nro_orden}</span>` : '';
                                let pagoInfoHtml = '';
                                const MESES = ["Enero", "Febrero", "Marzo", "Abril", "Mayo", "Junio", "Julio", "Agosto", "Septiembre", "Octubre", "Noviembre", "Diciembre"];
                                
                                if (c.ultimo_justif) {
                                    const match = c.ultimo_justif.match(/\[(Enero|Febrero|Marzo|Abril|Mayo|Junio|Julio|Agosto|Septiembre|Octubre|Noviembre|Diciembre)\]/i);
                                    if (match) {
                                        const ultimoMes = match[1];
                                        pagoInfoHtml = `<div class="mt-1 small"><span class="text-info">Último Pago: ${ultimoMes}</span></div>`;
                                    }
                                }

                                a.innerHTML = `
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            ${nroLabel} <strong>ID ${c.id}: ${c.nombre_completo}</strong>
                                            <br><small class="text-muted">C.I.: ${c.cedula || 'N/A'}</small>
                                            ${pagoInfoHtml}
                                        </div>
                                        <i class="fas fa-plus text-danger"></i>
                                    </div>
                                `;
                                a.onclick = (e) => {
                                    e.preventDefault();
                                    searchInputDeuda.value = `ID ${c.id}: ${c.nombre_completo}`;
                                    hiddenInputDeuda.value = c.id;
                                    resultsContainerDeuda.innerHTML = '';
                                };
                                resultsContainerDeuda.appendChild(a);
                            });
                        } else {
                            resultsContainerDeuda.innerHTML = '<div class="list-group-item disabled">Sin resultados</div>';
                        }
                    });
            }, 300);
        });
        
        // Cerrar resultados al clickear fuera
        document.addEventListener('click', (e) => {
            if (!searchInputDeuda.contains(e.target) && !resultsContainerDeuda.contains(e.target)) {
                resultsContainerDeuda.innerHTML = '';
            }
        });
    }

    // Envío del formulario de deuda
    document.getElementById('formCrearDeuda').addEventListener('submit', async function(e) {
        e.preventDefault();
        
        if (!hiddenInputDeuda.value) {
            Swal.fire('Atención', 'Por favor, seleccione un cliente válido del buscador.', 'warning');
            return;
        }

        const proceeds = await solicitarClaveAdmin('Confirmar Nueva Deuda');
        if (!proceeds) return;

        const btn = document.getElementById('btn_submit_deuda');
        const originalHtml = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Guardando...';

        const fd = new FormData(this);
        fetch('registrar_deuda_manual.php', {
            method: 'POST',
            body: fd
        })
        .then(r => r.json())
        .then(res => {
            if (res.success) {
                Swal.fire({
                    title: '¡Registrado!',
                    text: res.message,
                    icon: 'success',
                    timer: 2000,
                    showConfirmButton: false
                }).then(() => location.reload());
            } else {
                Swal.fire('Error', res.message, 'error');
                btn.disabled = false;
                btn.innerHTML = originalHtml;
            }
        })
        .catch(err => {
            console.error(err);
            Swal.fire('Error Técnico', 'No se pudo comunicar con el servidor.', 'error');
            btn.disabled = false;
            btn.innerHTML = originalHtml;
        });
    });
</script>

<?php require_once '../includes/layout_foot.php'; ?>