<?php
/**
 * Registro Rápido de Fallas
 * Formulario simplificado para registrar fallas antes de la visita técnica
 */
$path_to_root = "../../";
$page_title = "Registro de Falla Masiva (NIVEL 3)";
$breadcrumb = ["Soporte", "Falla Masiva"];
$back_url = "gestion_fallas.php";
require_once $path_to_root . 'paginas/conexion.php';
require_once $path_to_root . 'paginas/includes/layout_head.php';

// Obtener OLTs
$olts = [];
$res_olt = $conn->query("SELECT id_olt, nombre_olt FROM olt ORDER BY nombre_olt ASC");
while ($row = $res_olt->fetch_assoc()) {
    $olts[] = $row;
}
?>

<style>
    .priority-badge {
        display: inline-block;
        padding: 10px 20px;
        border-radius: 8px;
        font-weight: bold;
        cursor: pointer;
        transition: all 0.3s ease;
        border: 2px solid transparent;
        opacity: 0.6;
    }

    .priority-badge:hover {
        transform: translateY(-2px);
        opacity: 1;
    }

    .priority-badge.active {
        border-color: #000;
        box-shadow: 0 0 15px rgba(0, 0, 0, 0.3);
        transform: scale(1.1);
        opacity: 1;
    }

    .priority-nivel1 {
        background-color: #ffff00;
        color: #000;
    }

    .priority-nivel2 {
        background-color: #fd7e14;
        color: white;
    }

    .priority-nivel3 {
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

    .signature-pad {
        border: 2px dashed #ccc;
        border-radius: 5px;
        width: 100%;
        height: 150px;
        background-color: #f8f9fa;
        touch-action: none;
    }

    .section-title {
        background-color: #f1f3f5;
        padding: 10px;
        font-weight: bold;
        border-left: 4px solid #0d6efd;
        margin-top: 20px;
        margin-bottom: 15px;
        border-radius: 0 4px 4px 0;
    }

    .btn-cancel-custom {
        background-color: #adb5bd !important;
        color: #212529 !important;
        border: 1px solid #999 !important;
        transition: all 0.3s ease !important;
    }

    .btn-cancel-custom:hover {
        background-color: #6c757d !important;
        color: white !important;
        border-color: #5a6268 !important;
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
                            <i class="fa-solid fa-network-wired me-2"></i>Registro de Falla Masiva (NIVEL 3)
                        </h2>
                        <p class="text-muted">Reporte de caídas de red, OLTs o zonas completas</p>
                    </div>
                    <a href="gestion_fallas.php" class="btn btn-outline-secondary">
                        <i class="fa-solid fa-arrow-left me-1"></i>Volver
                    </a>
                </div>
            </div>
        </div>

        <form id="formRegistroFalla" class="needs-validation" novalidate>
            <!-- Sección 1: Información de Referencia -->
            <div class="form-section">
                <h5 class="fw-bold mb-3"><i class="fa-solid fa-user me-2"></i>Cliente Reportante (Referencia)</h5>
                <div class="row g-3">
                    <div class="col-12">
                        <label class="form-label">Buscar un cliente afectado como referencia <span class="text-danger">*</span></label>
                        <div class="input-group position-relative">
                            <span class="input-group-text"><i class="fa-solid fa-search"></i></span>
                            <input type="text" class="form-control" id="cliente_search"
                                placeholder="Nombre, ID o Cédula..." autocomplete="off">
                        </div>
                        <input type="hidden" name="id_contrato" id="id_contrato" required>
                        <div id="search_results" class="list-group position-absolute w-100 shadow mt-1"
                            style="z-index: 1000; display: none; max-height: 300px; overflow-y: auto;"></div>
                        <div id="cliente_seleccionado" class="form-text text-success fw-bold mt-2"></div>
                        <div class="invalid-feedback">Debe seleccionar al menos un cliente de referencia</div>
                    </div>
                </div>
            </div>


            <!-- Sección 2: Clasificación de la Falla -->
            <div class="form-section">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="fw-bold mb-0"><i class="fa-solid fa-tag me-2"></i>Clasificación de la Falla</h5>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-12 text-center py-2 bg-danger text-white rounded shadow-sm">
                        <h4 class="fw-bold mb-0"><i class="fa-solid fa-triangle-exclamation me-2"></i>PRIORIDAD: NIVEL 3 (FALLA MASIVA)</h4>
                        <input type="hidden" id="prioridad" name="prioridad" value="NIVEL 3" required>
                    </div>
                </div>

                <div class="row g-3 mt-2">
                    <div class="col-md-6">
                        <label class="form-label fw-bold">OLT Afectada <span class="text-danger">*</span></label>
                        <select class="form-select border-primary" id="id_olt" name="id_olt" required>
                            <option value="">Seleccione OLT...</option>
                            <?php foreach ($olts as $olt): ?>
                                <option value="<?php echo $olt['id_olt']; ?>"><?php echo htmlspecialchars($olt['nombre_olt']); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <div class="invalid-feedback">Debe seleccionar la OLT afectada</div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold">PON Afectado (Opcional)</label>
                        <select class="form-select border-primary" id="id_pon" name="id_pon">
                            <option value="">Primero seleccione OLT...</option>
                        </select>
                        <small class="text-muted">Deja en blanco si es caída total de la OLT</small>
                    </div>
                </div>
                <div class="row g-3">
                    <div class="col-12">
                        <label class="form-label">Tipo de Falla <span class="text-danger">*</span></label>
                        <select class="form-select border-primary" id="tipo_falla" name="tipo_falla" required>
                            <option value="">Cargando opciones...</option>
                        </select>
                        <div class="invalid-feedback">Debe seleccionar un tipo de falla</div>
                    </div>
                </div>
                <div class="row g-3 mt-3">
                    <div class="col-md-6">
                        <div class="p-3 border rounded bg-white shadow-sm h-100">
                            <label class="form-label fw-bold text-danger"><i class="fa-solid fa-users me-2"></i>Estimación de Clientes Afectados</label>
                            <input type="number" class="form-control form-control-lg border-danger" id="clientes_afectados" name="clientes_afectados"
                                min="1" value="50">
                            <small class="text-muted">Introduce una cifra estimada (Ej. 100, 200...)</small>
                        </div>
                        <input type="hidden" name="es_caida_critica" value="1">
                    </div>
                    <div class="col-md-6">
                        <div class="critical-alert h-100 mb-0 shadow-sm border-danger" style="display: block;">
                            <h6 class="fw-bold mb-2">
                                <i class="fa-solid fa-triangle-exclamation me-2"></i>Reporte de Gravedad
                            </h6>
                            <p class="mb-0 small">
                                Las fallas de Nivel 3 activan alertas inmediatas para el equipo de infraestructura.
                                Asegúrese de que la OLT esté correctamente seleccionada.
                            </p>
                        </div>
                    </div>
                </div>
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
                        <input type="text" class="form-control" id="direccion" name="direccion">
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
            <div class="row mt-4">
                <div class="col-12">
                    <div class="d-flex gap-2 justify-content-end p-3 bg-light rounded shadow-sm">
                        <a href="gestion_fallas.php" class="btn btn-cancel-custom px-4">
                            <i class="fa-solid fa-times me-1"></i>Cancelar
                        </a>
                        <button type="submit" class="btn btn-danger btn-lg px-5 fw-bold shadow">
                            <i class="fa-solid fa-save me-1"></i>REGISTRAR FALLA MASIVA
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
        // Cargar PONs cuando cambie la OLT
        $('#id_olt').change(function() {
            const id_olt = $(this).val();
            const ponSelect = $('#id_pon');
            
            ponSelect.empty().append('<option value="">Cargando...</option>');
            
            if (!id_olt) {
                ponSelect.html('<option value="">Primero seleccione OLT...</option>');
                return;
            }

            $.ajax({
                url: 'get_pons_ajax.php',
                data: { id_olt: id_olt },
                dataType: 'json',
                success: function(data) {
                    ponSelect.empty().append('<option value="">Toda la OLT / Seleccione PON...</option>');
                    data.forEach(function(pon) {
                        ponSelect.append(`<option value="${pon.id_pon}">${pon.nombre_pon}</option>`);
                    });
                },
                error: function() {
                    ponSelect.html('<option value="">Error al cargar PONs</option>');
                }
            });
        });

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

        // Manejo de selección de prioridad (kept for consistency, though fixed to NIVEL 3)
        $('.priority-badge').click(function () {
            $('.priority-badge').removeClass('active');
            $(this).addClass('active');
            $('#prioridad').val($(this).data('priority'));
        });

        // Cargar opciones de falla Nivel 3
        function cargarOpcionesFalla() {
            fetch('admin_opciones.php?action=read')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const select = document.getElementById('tipo_falla');
                        select.innerHTML = '<option value="">-- Seleccionar tipo de falla Nivel 3 --</option>';
                        
                        const opciones = data.data['NIVEL 3'] || [];
                        opciones.forEach(opcion => {
                            const option = document.createElement('option');
                            option.value = opcion;
                            option.textContent = opcion;
                            select.appendChild(option);
                        });
                    }
                })
                .catch(error => console.error('Error cargando opciones:', error));
        }
        cargarOpcionesFalla();

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
                es_caida_critica: 1, // Siempre es crítica en este formulario
                clientes_afectados: $('#clientes_afectados').val() || 50,
                sector: $('#sector').val(),
                zona_afectada: $('#zona_afectada').val(),
                id_olt: $('#id_olt').val(),
                id_pon: $('#id_pon').val(),
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
                error: function (xhr, status, error) {
                    console.error('Error Detalles:', { xhr, status, error });
                    Swal.fire({
                        icon: 'error',
                        title: 'Error de Conexión',
                        html: `<p>No se pudo conectar con el servidor.</p>
                               <small class="text-muted">Estado: ${xhr.status} ${status}<br>Error: ${error}</small>`
                    });
                }
            });
        });
    });
</script>
<script src="https://cdn.jsdelivr.net/npm/signature_pad@4.1.5/dist/signature_pad.umd.min.js"></script>

<?php require_once $path_to_root . 'paginas/includes/layout_foot.php'; ?>