<?php
/**
 * Registro Rápido de Fallas
 * Formulario simplificado para registrar fallas antes de la visita técnica
 */
$path_to_root = "../../";
$page_title = "Registro Rápido de Falla";
require_once $path_to_root . 'paginas/conexion.php';
require_once $path_to_root . 'paginas/includes/layout_head.php';
?>

<style>
    .priority-badge {
        display: inline-block;
        padding: 8px 16px;
        border-radius: 6px;
        font-weight: bold;
        cursor: pointer;
        transition: all 0.2s;
        border: 2px solid transparent;
    }

    .priority-badge:hover {
        transform: scale(1.05);
    }

    .priority-badge.active {
        border-color: #000;
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
    }

    .priority-baja {
        background-color: #d1ecf1;
        color: #0c5460;
    }

    .priority-media {
        background-color: #fff3cd;
        color: #856404;
    }

    .priority-alta {
        background-color: #f8d7da;
        color: #721c24;
    }

    .priority-critica {
        background-color: #dc3545;
        color: white;
    }

    .form-section {
        background: #f8f9fa;
        padding: 20px;
        border-radius: 8px;
        margin-bottom: 20px;
        border-left: 4px solid #0d6efd;
    }

    .critical-alert {
        background: #fff3cd;
        border: 2px solid #ffc107;
        padding: 15px;
        border-radius: 8px;
        margin-bottom: 20px;
    }
</style>

<main class="main-content">
    <?php include $path_to_root . 'paginas/includes/header.php'; ?>

    <div class="page-content">
        <!-- Header -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h2 class="h3 fw-bold text-danger mb-1">
                            <i class="fa-solid fa-bolt me-2"></i>Registro Rápido de Falla
                        </h2>
                        <p class="text-muted">Registra incidencias antes de la visita técnica</p>
                    </div>
                    <a href="gestion_fallas.php" class="btn btn-outline-secondary">
                        <i class="fa-solid fa-arrow-left me-1"></i>Volver
                    </a>
                </div>
            </div>
        </div>

        <form id="formRegistroFalla" class="needs-validation" novalidate>
            <!-- Sección 1: Información del Cliente -->
            <div class="form-section">
                <h5 class="fw-bold mb-3"><i class="fa-solid fa-user me-2"></i>Información del Cliente</h5>
                <div class="row g-3">
                    <div class="col-md-8">
                        <label class="form-label">Buscar Cliente <span class="text-danger">*</span></label>
                        <div class="input-group position-relative">
                            <span class="input-group-text"><i class="fa-solid fa-search"></i></span>
                            <input type="text" class="form-control" id="cliente_search"
                                placeholder="Buscar por Nombre, ID o Cédula..." autocomplete="off">
                        </div>
                        <input type="hidden" name="id_contrato" id="id_contrato" required>
                        <div id="search_results" class="list-group position-absolute w-100 shadow mt-1"
                            style="z-index: 1000; display: none; max-height: 300px; overflow-y: auto;"></div>
                        <div id="cliente_seleccionado" class="form-text text-success fw-bold mt-2"></div>
                        <div class="invalid-feedback">Debe seleccionar un cliente</div>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Teléfono</label>
                        <input type="text" class="form-control" id="telefono_cliente" readonly>
                    </div>
                </div>
            </div>

            <!-- Sección 2: Clasificación de la Falla -->
            <div class="form-section">
                <h5 class="fw-bold mb-3"><i class="fa-solid fa-tag me-2"></i>Clasificación de la Falla</h5>

                <div class="row g-3 mb-3">
                    <div class="col-12">
                        <label class="form-label">Prioridad <span class="text-danger">*</span></label>
                        <div class="d-flex gap-2 flex-wrap">
                            <span class="priority-badge priority-baja" data-priority="BAJA">
                                <i class="fa-solid fa-circle-info me-1"></i>BAJA
                            </span>
                            <span class="priority-badge priority-media active" data-priority="MEDIA">
                                <i class="fa-solid fa-exclamation me-1"></i>MEDIA
                            </span>
                            <span class="priority-badge priority-alta" data-priority="ALTA">
                                <i class="fa-solid fa-triangle-exclamation me-1"></i>ALTA
                            </span>
                            <span class="priority-badge priority-critica" data-priority="CRITICA">
                                <i class="fa-solid fa-fire me-1"></i>CRÍTICA
                            </span>
                        </div>
                        <input type="hidden" id="prioridad" name="prioridad" value="MEDIA" required>
                    </div>
                </div>

                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Tipo de Falla <span class="text-danger">*</span></label>
                        <select class="form-select" id="tipo_falla" name="tipo_falla" required>
                            <option value="">Seleccione...</option>
                            <option value="Sin Señal / LOS">Sin Señal / LOS</option>
                            <option value="Internet Lento">Internet Lento</option>
                            <option value="Cortes Intermitentes">Cortes Intermitentes</option>
                            <option value="Router Dañado">Router Dañado</option>
                            <option value="ONU Apagada/Dañada">ONU Apagada/Dañada</option>
                            <option value="Antena Desalineada">Antena Desalineada</option>
                            <option value="Cable Dañado">Cable Dañado</option>
                            <option value="Fibra Cortada">Fibra Cortada</option>
                            <option value="Problema Eléctrico">Problema Eléctrico</option>
                            <option value="Configuración Incorrecta">Configuración Incorrecta</option>
                            <option value="Dispositivo del Cliente">Dispositivo del Cliente</option>
                            <option value="Saturación de Red">Saturación de Red</option>
                            <option value="Mantenimiento Preventivo">Mantenimiento Preventivo</option>
                            <option value="Cambio de Equipo">Cambio de Equipo</option>
                            <option value="Otro">Otro</option>
                        </select>
                        <div class="invalid-feedback">Debe seleccionar un tipo de falla</div>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Tipo de Servicio</label>
                        <select class="form-select" id="tipo_servicio" name="tipo_servicio">
                            <option value="Fibra Óptica">Fibra Óptica</option>
                            <option value="Radio Enlace">Radio Enlace</option>
                            <option value="Mixto">Mixto</option>
                        </select>
                    </div>
                </div>

                <div class="row g-3 mt-2">
                    <div class="col-md-6">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="es_caida_critica"
                                name="es_caida_critica">
                            <label class="form-check-label fw-bold text-danger" for="es_caida_critica">
                                <i class="fa-solid fa-exclamation-triangle me-1"></i>¿Es una caída crítica?
                            </label>
                            <small class="d-block text-muted">Marca si afecta a múltiples clientes o infraestructura
                                crítica</small>
                        </div>
                    </div>
                    <div class="col-md-6" id="clientesAfectadosContainer" style="display: none;">
                        <label class="form-label">Clientes Afectados</label>
                        <input type="number" class="form-control" id="clientes_afectados" name="clientes_afectados"
                            min="1" value="1">
                    </div>
                </div>
            </div>

            <!-- Alerta de Caída Crítica -->
            <div class="critical-alert" id="criticalAlert" style="display: none;">
                <h6 class="fw-bold mb-2">
                    <i class="fa-solid fa-triangle-exclamation me-2"></i>Caída Crítica Detectada
                </h6>
                <p class="mb-0 small">
                    Esta falla afecta a múltiples clientes. Se notificará al equipo de gestión y se priorizará su
                    atención.
                </p>
            </div>

            <!-- Sección 3: Ubicación -->
            <div class="form-section">
                <h5 class="fw-bold mb-3"><i class="fa-solid fa-map-marker-alt me-2"></i>Ubicación y Zona</h5>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Sector</label>
                        <input type="text" class="form-control" id="sector" name="sector">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Zona Afectada</label>
                        <input type="text" class="form-control" id="zona_afectada" name="zona_afectada"
                            placeholder="Ej: Urbanización Los Pinos, Calle Principal">
                        <small class="text-muted">Especifica la zona geográfica para análisis</small>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Dirección</label>
                        <input type="text" class="form-control" id="direccion" name="direccion" readonly>
                    </div>
                </div>
            </div>

            <!-- Sección 4: Descripción -->
            <div class="form-section">
                <h5 class="fw-bold mb-3"><i class="fa-solid fa-file-lines me-2"></i>Descripción de la Falla</h5>
                <div class="row g-3">
                    <div class="col-12">
                        <label class="form-label">Síntomas Reportados <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="observaciones" name="observaciones" rows="4" required
                            minlength="10" placeholder="Describe los síntomas reportados por el cliente..."></textarea>
                        <div class="invalid-feedback">Debe describir los síntomas (mínimo 10 caracteres)</div>
                    </div>

                    <div class="col-12">
                        <label class="form-label">Equipos Potencialmente Afectados</label>
                        <div class="d-flex gap-3 flex-wrap">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="afecta_onu" value="ONU">
                                <label class="form-check-label" for="afecta_onu">ONU</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="afecta_router" value="Router">
                                <label class="form-check-label" for="afecta_router">Router</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="afecta_antena" value="Antena">
                                <label class="form-check-label" for="afecta_antena">Antena</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="afecta_fibra" value="Fibra">
                                <label class="form-check-label" for="afecta_fibra">Fibra</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="afecta_cable" value="Cable">
                                <label class="form-check-label" for="afecta_cable">Cable</label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sección 5: Asignación -->
            <div class="form-section">
                <h5 class="fw-bold mb-3"><i class="fa-solid fa-user-gear me-2"></i>Asignación</h5>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Técnico Asignado <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="tecnico_asignado" name="tecnico_asignado" required>
                        <div class="invalid-feedback">Debe asignar un técnico</div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Fecha/Hora de Reporte</label>
                        <input type="datetime-local" class="form-control" id="fecha_reporte" name="fecha_reporte"
                            value="<?php echo date('Y-m-d\TH:i'); ?>" readonly>
                        <small class="text-muted">Se registra automáticamente</small>
                    </div>
                </div>
            </div>

            <!-- Sección 6: Notas Internas -->
            <div class="form-section">
                <h5 class="fw-bold mb-3"><i class="fa-solid fa-note-sticky me-2"></i>Notas Internas</h5>
                <div class="row g-3">
                    <div class="col-12">
                        <label class="form-label">Notas del Operador</label>
                        <textarea class="form-control" id="notas_internas" name="notas_internas" rows="3"
                            placeholder="Notas internas que no serán visibles en el reporte al cliente..."></textarea>
                        <small class="text-muted">Estas notas son solo para uso interno</small>
                    </div>
                </div>
            </div>

            <!-- Botones -->
            <div class="row">
                <div class="col-12">
                    <div class="d-flex gap-2 justify-content-end">
                        <a href="gestion_fallas.php" class="btn btn-outline-secondary">
                            <i class="fa-solid fa-times me-1"></i>Cancelar
                        </a>
                        <button type="submit" class="btn btn-danger btn-lg">
                            <i class="fa-solid fa-save me-1"></i>Registrar Falla
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>
</main>

<script src="<?php echo $path_to_root; ?>js/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
    $(document).ready(function () {
        // Buscador AJAX de clientes (igual que en reporte_tecnico.php)
        const searchInput = document.getElementById('cliente_search');
        const resultsDiv = document.getElementById('search_results');
        const idInput = document.getElementById('id_contrato');
        const selectedDiv = document.getElementById('cliente_seleccionado');
        let clienteData = null;

        searchInput.addEventListener('input', function () {
            const term = this.value;
            if (term.length < 3) {
                resultsDiv.style.display = 'none';
                return;
            }

            fetch(`../principal/buscar_contratos.php?q=${term}`)
                .then(r => r.json())
                .then(data => {
                    resultsDiv.innerHTML = '';
                    if (data.length > 0) {
                        data.forEach(item => {
                            const a = document.createElement('a');
                            a.className = 'list-group-item list-group-item-action';
                            a.innerHTML = `<strong>${item.nombre_completo}</strong><br><small class="text-muted">ID: ${item.id} | Cédula: ${item.cedula} | IP: ${item.ip}</small>`;
                            a.href = '#';
                            a.onclick = (e) => {
                                e.preventDefault();
                                searchInput.value = item.nombre_completo;
                                idInput.value = item.id;
                                selectedDiv.textContent = '✓ Cliente Seleccionado: ' + item.nombre_completo;
                                resultsDiv.style.display = 'none';

                                // Guardar datos del cliente
                                clienteData = item;
                                $('#telefono_cliente').val(item.telefono || '');
                                $('#direccion').val(item.direccion || '');
                                $('#sector').val(item.sector || '');
                            };
                            resultsDiv.appendChild(a);
                        });
                        resultsDiv.style.display = 'block';
                    } else {
                        resultsDiv.innerHTML = '<div class="list-group-item text-muted">No se encontraron resultados</div>';
                        resultsDiv.style.display = 'block';
                    }
                })
                .catch(err => {
                    console.error('Error en búsqueda:', err);
                    resultsDiv.innerHTML = '<div class="list-group-item text-danger">Error al buscar</div>';
                    resultsDiv.style.display = 'block';
                });
        });

        // Ocultar resultados al hacer clic fuera
        document.addEventListener('click', function (e) {
            if (!searchInput.contains(e.target) && !resultsDiv.contains(e.target)) {
                resultsDiv.style.display = 'none';
            }
        });

        // Manejo de selección de prioridad
        $('.priority-badge').click(function () {
            $('.priority-badge').removeClass('active');
            $(this).addClass('active');
            $('#prioridad').val($(this).data('priority'));
        });

        // Mostrar/ocultar campo de clientes afectados
        $('#es_caida_critica').change(function () {
            if ($(this).is(':checked')) {
                $('#clientesAfectadosContainer').show();
                $('#criticalAlert').show();
                $('#clientes_afectados').attr('required', true);
                $('#clientes_afectados').val(2);

                // Auto-seleccionar prioridad CRÍTICA
                $('.priority-badge[data-priority="CRITICA"]').click();
            } else {
                $('#clientesAfectadosContainer').hide();
                $('#criticalAlert').hide();
                $('#clientes_afectados').attr('required', false);
                $('#clientes_afectados').val(1);
            }
        });

        // Validación y envío del formulario
        $('#formRegistroFalla').submit(function (e) {
            e.preventDefault();

            if (!this.checkValidity()) {
                e.stopPropagation();
                $(this).addClass('was-validated');
                return;
            }

            // Recopilar equipos afectados
            const equiposAfectados = [];
            $('input[type="checkbox"]:checked').each(function () {
                if ($(this).val()) {
                    equiposAfectados.push($(this).val());
                }
            });

            const formData = {
                id_contrato: $('#id_contrato').val(),
                prioridad: $('#prioridad').val(),
                tipo_falla: $('#tipo_falla').val(),
                tipo_servicio: $('#tipo_servicio').val(),
                es_caida_critica: $('#es_caida_critica').is(':checked') ? 1 : 0,
                clientes_afectados: $('#clientes_afectados').val() || 1,
                sector: $('#sector').val(),
                zona_afectada: $('#zona_afectada').val(),
                observaciones: $('#observaciones').val(),
                equipos_afectados: equiposAfectados.join(', '),
                tecnico_asignado: $('#tecnico_asignado').val(),
                notas_internas: $('#notas_internas').val(),
                fecha_reporte: $('#fecha_reporte').val()
            };

            // Enviar datos
            $.ajax({
                url: 'guardar_falla.php',
                method: 'POST',
                data: formData,
                dataType: 'json',
                success: function (response) {
                    if (response.success) {
                        Swal.fire({
                            icon: 'success',
                            title: '¡Falla Registrada!',
                            html: `<p>Ticket #<strong>${response.id_soporte}</strong> creado exitosamente</p>
                               <p class="text-muted small">Técnico asignado: ${formData.tecnico_asignado}</p>`,
                            confirmButtonText: 'Ver Gestión de Fallas',
                            showCancelButton: true,
                            cancelButtonText: 'Registrar Otra'
                        }).then((result) => {
                            if (result.isConfirmed) {
                                window.location.href = 'gestion_fallas.php';
                            } else {
                                location.reload();
                            }
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: response.message || 'No se pudo registrar la falla'
                        });
                    }
                },
                error: function () {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error de Conexión',
                        text: 'No se pudo conectar con el servidor'
                    });
                }
            });
        });
    });
</script>

<?php require_once $path_to_root . 'paginas/includes/layout_foot.php'; ?>