<?php
/**
 * Página principal que muestra la tabla de registros
 */
require '../conexion.php';

$path_to_root = "../../"; 
$page_title = "Gestión de Contratos";
require_once '../includes/layout_head.php';
require_once '../includes/sidebar.php';
?>

<!-- DataTables CSS -->
<link href="<?php echo $path_to_root; ?>css/datatables.min.css" rel="stylesheet">
<style>
    /* Estilos para tabla ancha */
    .dataTables_wrapper {
        width: 100%;
        overflow-x: auto;
    }
    
    /* Headers mas compactos */
    #mitabla thead th {
        font-size: 0.8rem;
        white-space: nowrap;
        vertical-align: middle;
        text-align: center;
        background-color: #f8f9fa;
        color: #495057;
    }
    
    /* Celdas mas compactas */
    #mitabla tbody td {
        font-size: 0.85rem;
        vertical-align: middle;
        white-space: nowrap; /* Evita saltos de linea largos */
        padding: 4px 8px;
    }

    /* Estilo Editable Excel */
    .editable-cell {
        cursor: text;
        transition: background-color 0.2s, border-color 0.2s;
        padding: 4px;
        border-radius: 4px;
    }
    .editable-cell:hover {
        background-color: #f1f3f5;
        border: 1px solid #ced4da;
    }
    .editable-cell:focus {
        background-color: #fff;
        outline: 2px solid #86b7fe;
        color: #212529;
    }

    /* Scrollbar personalizado si se desea */
    .table-responsive::-webkit-scrollbar {
        height: 10px;
    }
    .table-responsive::-webkit-scrollbar-thumb {
        background-color: #ccc;
        border-radius: 5px;
    }
    
    /* Grupos de Headers (opcional, por ahora simple) */
</style>

<main class="main-content">
    <?php include '../includes/header.php'; ?>

    <div class="page-content">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center bg-white border-bottom-0 pt-4 px-4">
                <div>
                    <h5 class="fw-bold text-primary mb-1">Contratos</h5>
                    <p class="text-muted small mb-0">Gestión integral de contratos y servicios</p>
                </div>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-outline-success d-flex align-items-center gap-2" data-bs-toggle="modal" data-bs-target="#modalStats" id="btnOpenStats">
                        <i class="fa-solid fa-chart-pie"></i> <span class="d-none d-md-inline">Estadísticas</span>
                    </button>
                    <button type="button" class="btn btn-outline-info d-flex align-items-center gap-2" data-bs-toggle="modal" data-bs-target="#modalTipos">
                        <i class="fa-solid fa-tags"></i> <span class="d-none d-md-inline">Editar Tipos de Instalacion</span>
                    </button>
                    <button type="button" class="btn btn-outline-primary d-flex align-items-center gap-2" data-bs-toggle="modal" data-bs-target="#modalUbicaciones">
                        <i class="fa-solid fa-map-location-dot"></i> <span class="d-none d-md-inline">Editar Ubicaciones</span>
                    </button>
                    <a href="nuevo.php" class="btn btn-primary d-flex align-items-center gap-2">
                        <i class="fa-solid fa-plus"></i> <span class="d-none d-md-inline">Nuevo Contrato</span>
                    </a>
                </div>
            </div>

            <div class="card-body px-4">
                <!-- Dashboard Cards -->
                <div class="row mb-4">
                    <div class="col-md-6 mb-3 mb-md-0">
                        <div class="card bg-primary text-white h-100 shadow-sm border-0">
                            <div class="card-body d-flex align-items-center">
                                <div class="rounded-circle bg-white bg-opacity-25 p-3 me-3">
                                    <i class="fa-solid fa-users fa-2x"></i>
                                </div>
                                <div>
                                    <h6 class="mb-0 text-white-50">Total Clientes</h6>
                                    <h2 class="mb-0 fw-bold" id="statTotalClients">...</h2>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card bg-success text-white h-100 shadow-sm border-0">
                            <div class="card-body d-flex align-items-center">
                                <div class="rounded-circle bg-white bg-opacity-25 p-3 me-3">
                                    <i class="fa-solid fa-file-contract fa-2x"></i>
                                </div>
                                <div>
                                    <h6 class="mb-0 text-white-50">Total Contratos</h6>
                                    <h2 class="mb-0 fw-bold" id="statTotalContracts">...</h2>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Wrapper responsive para scroll horizontal -->
                <div class="table-responsive"> 
                    <table class="display table table-hover w-100" id="mitabla">
                        <thead>
                            <tr>
                                <!-- 0 ID (Hidden) -->
                                <th>ID</th>
                                
                                <!-- Info Cliente -->
                                <th title="Marca temporal de registro">SAR</th>
                                <th>Cédula</th>
                                <th>Cliente</th>
                                <th>Municipio</th>
                                <th>Parroquia</th>
                                <th>Dirección</th>
                                <th>Telf. 1</th>
                                <th>Telf. 2</th>
                                <th>Correo</th>
                                <th>Correo (Alt)</th>
                                
                                <!-- Instalacion y Cobro -->
                                <th>F. Instalación</th>
                                <th>Medio Pago</th>
                                <th>Monto Pagar</th>
                                <th>Monto Pagado</th>
                                <th>Días Prorrateo</th>
                                <th>Monto Prorr. ($)</th>
                                <th title="Observaciones">Observ.</th>
                                
                                <!-- Tecnicos -->
                                <th>Tipo Conex.</th>
                                <th>Num. ONU</th>
                                <th>MAC/Serial</th>
                                <th>IP ONU</th>
                                <th>Caja NAP</th>
                                <th>Puerto NAP</th>
                                <th>NAP TX (dBm)</th>
                                <th>ONU RX (dBm)</th>
                                <th>Dist. Drop (m)</th>
                                <th>Instalador</th>
                                <th>IP Servicio</th>
                                <th>Punto Acceso</th>
                                <th>Val. Conex. (dBm)</th>
                                
                                <!-- Cierre -->
                                <th title="Instalador (Cierre)">Instalador (C)</th>
                                <th title="Evidencia de Fibra">Evidencia Fibra</th>
                                <th title="Sugerencias/Observaciones">Sugerencias</th>
                                <th>Precinto ODN</th>
                                <th>Foto</th>
                                <th>Firma Cliente</th>
                                <th>Firma Técnico</th>
                                
                                <!-- EXTRAS -->
                                <th class="table-info">Vendedor (Edit)</th>
                                <th class="table-info">SAE Plus (Edit)</th>
                                <th>Plan</th>
                                <th>OLT</th>
                                <th>PON</th>
                                <th>Estado</th>
                                
                                <!-- Acciones -->
                                <th class="text-end">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
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
    <!-- MODAL GESTION TIPOS INSTALACION -->
    <div class="modal fade" id="modalTipos" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold"><i class="fa-solid fa-tags me-2"></i>Tipos de Instalación</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="list-group mb-3" id="listTipos" style="max-height: 400px; overflow-y: auto;">
                        <!-- Items generados por JS -->
                    </div>
                    <div class="input-group">
                        <input type="text" class="form-control" id="newTipo" placeholder="Nuevo Tipo (Ej. FIBRA EXTRA)">
                        <button class="btn btn-success" type="button" id="btnAddTipo"><i class="fa-solid fa-plus"></i></button>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                     <small class="text-muted me-auto">Cambios guardados en JSON.</small>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- MODAL GESTION UBICACIONES -->
    <div class="modal fade" id="modalUbicaciones" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold"><i class="fa-solid fa-map-location-dot me-2"></i>Gestionar Ubicaciones (JSON)</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <!-- COLUMNA MUNICIPIOS -->
                        <div class="col-md-5 border-end">
                            <h6 class="text-primary fw-bold mb-3">Municipios</h6>
                            <div class="list-group mb-3" id="listMunicipios" style="max-height: 300px; overflow-y: auto;">
                                <!-- Items generados por JS -->
                            </div>
                            <div class="input-group">
                                <input type="text" class="form-control" id="newMunicipio" placeholder="Nuevo Municipio">
                                <button class="btn btn-success" type="button" id="btnAddMunicipio"><i class="fa-solid fa-plus"></i></button>
                            </div>
                        </div>
                        
                        <!-- COLUMNA PARROQUIAS -->
                        <div class="col-md-7">
                            <h6 class="text-info fw-bold mb-3" id="titleParroquias">Parroquias (Seleccione un Municipio)</h6>
                            <div class="list-group mb-3" id="listParroquias" style="max-height: 300px; overflow-y: auto;">
                                <!-- Items generados por JS -->
                                <div class="text-center text-muted p-3">Seleccione un municipio para ver sus parroquias</div>
                            </div>
                            <div class="input-group">
                                <input type="text" class="form-control" id="newParroquia" placeholder="Nueva Parroquia" disabled>
                                <button class="btn btn-success" type="button" id="btnAddParroquia" disabled><i class="fa-solid fa-plus"></i></button>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                     <small class="text-muted me-auto">Los cambios se guardan automáticamente en el archivo JSON.</small>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- MODAL ESTADÍSTICAS -->
    <div class="modal fade" id="modalStats" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title fw-bold"><i class="fa-solid fa-chart-pie me-2"></i>Estadísticas y Reportes</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <!-- Filtros -->
                    <div class="card bg-light border-0 mb-4">
                        <div class="card-body">
                            <h6 class="fw-bold mb-3">Filtros de Búsqueda</h6>
                            <div class="row g-3">
                                <div class="col-md-3">
                                    <label class="form-label small">Fecha Inicio</label>
                                    <input type="date" class="form-control" id="statStartDate">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label small">Fecha Fin</label>
                                    <input type="date" class="form-control" id="statEndDate">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label small">Instalador</label>
                                    <select class="form-select" id="statInstaller">
                                        <option value="">Todos</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label small">Vendedor (ID)</label>
                                    <select class="form-select" id="statVendor">
                                        <option value="">Todos</option>
                                    </select>
                                </div>
                                <div class="col-md-12 text-end">
                                    <button class="btn btn-primary" id="btnFilterStats"><i class="fa-solid fa-filter me-2"></i>Aplicar Filtros</button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Resultados -->
                    <div class="row">
                        <div class="col-md-6">
                            <h6 class="fw-bold text-primary">Instalaciones por Instalador</h6>
                            <div class="table-responsive border rounded bg-white p-2" style="max-height: 300px; overflow-y: auto;">
                                <table class="table table-sm table-striped mb-0" id="tableStatsInstaller">
                                    <thead class="table-light sticky-top">
                                        <tr>
                                            <th>Instalador</th>
                                            <th class="text-center">Total Instalaciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr><td colspan="2" class="text-center text-muted">Aplica filtros para ver datos</td></tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <h6 class="fw-bold text-success">Ventas por Vendedor</h6>
                            <div class="table-responsive border rounded bg-white p-2" style="max-height: 300px; overflow-y: auto;">
                                <table class="table table-sm table-striped mb-0" id="tableStatsVendor">
                                    <thead class="table-light sticky-top">
                                        <tr>
                                            <th>ID Vendedor</th>
                                            <th class="text-center">Total Contratos</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr><td colspan="2" class="text-center text-muted">Aplica filtros para ver datos</td></tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-danger" id="btnExportPDF"><i class="fa-solid fa-file-pdf me-2"></i>Exportar PDF</button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts para PDF Export (Usando PHP Backend) -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Cargar Dashboard Stats
            fetchStatsDashboard();

            // Cargar Listas para Filtros al abrir modal
            var modalStats = document.getElementById('modalStats');
            modalStats.addEventListener('show.bs.modal', function () {
                fetchStatsLists();
            });

            // Botón Filtrar
            document.getElementById('btnFilterStats').addEventListener('click', function() {
                fetchModalStats();
            });

            // Botón Exportar PDF
            document.getElementById('btnExportPDF').addEventListener('click', function() {
                exportStatsPDF();
            });
        });

        function fetchStatsDashboard() {
            fetch('get_contract_stats.php?action=dashboard')
                .then(response => response.json())
                .then(data => {
                    document.getElementById('statTotalContracts').textContent = data.total_contracts;
                    document.getElementById('statTotalClients').textContent = data.total_clients;
                })
                .catch(error => console.error('Error fetching dashboard stats:', error));
        }

        function fetchStatsLists() {
            fetch('get_contract_stats.php?action=get_lists')
                .then(response => response.json())
                .then(data => {
                    const selInst = document.getElementById('statInstaller');
                    const selVend = document.getElementById('statVendor');
                    
                    // Solo llenar si están vacíos (o resetear siempre)
                    if(selInst.options.length <= 1) {
                         data.installers.forEach(inst => {
                            let opt = new Option(inst, inst);
                            selInst.add(opt);
                        });
                    }
                   
                    if(selVend.options.length <= 1) {
                        data.vendors.forEach(vend => {
                            let opt = new Option('Vendedor ' + vend, vend);
                            selVend.add(opt);
                        });
                    }
                });
        }

        function fetchModalStats() {
            const start = document.getElementById('statStartDate').value;
            const end = document.getElementById('statEndDate').value;
            const inst = document.getElementById('statInstaller').value;
            const vend = document.getElementById('statVendor').value;

            const params = new URLSearchParams({
                action: 'modal_stats',
                start: start,
                end: end,
                installer: inst,
                vendor: vend
            });

            fetch('get_contract_stats.php?' + params.toString())
                .then(response => response.json())
                .then(data => {
                    renderStatsTable('tableStatsInstaller', data.by_installer, 'Instalador', 'nombre');
                    renderStatsTable('tableStatsVendor', data.by_vendor, 'Vendedor', 'id_vendedor');
                });
        }

        function renderStatsTable(tableId, data, labelCol, keyCol) {
            const tbody = document.querySelector('#' + tableId + ' tbody');
            tbody.innerHTML = '';

            if (data.length === 0) {
                tbody.innerHTML = '<tr><td colspan="2" class="text-center text-muted">No se encontraron resultados</td></tr>';
                return;
            }

            let totalSum = 0;
            data.forEach(row => {
                const tr = document.createElement('tr');
                tr.innerHTML = `<td>${row[keyCol]}</td><td class="text-center fw-bold">${row.total}</td>`;
                tbody.appendChild(tr);
                totalSum += parseInt(row.total);
            });
            
             // Fila Total
            const trTotal = document.createElement('tr');
            trTotal.classList.add('table-dark');
            trTotal.innerHTML = `<td><strong>TOTAL</strong></td><td class="text-center fw-bold">${totalSum}</td>`;
            tbody.appendChild(trTotal);
        }

        function exportStatsPDF() {
            const start = document.getElementById('statStartDate').value;
            const end = document.getElementById('statEndDate').value;
            const inst = document.getElementById('statInstaller').value;
            const vend = document.getElementById('statVendor').value;

            const params = new URLSearchParams({
                start: start,
                end: end,
                installer: inst,
                vendor: vend
            });

            // Abrir en nueva pestaña
            window.open('../../paginas/reportes_pdf/generar_estadisticas_pdf.php?' + params.toString(), '_blank');
        }
    </script>

</main>

<!-- Modals -->
<div class="modal fade" id="eliminaModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-sm modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-body text-center p-4">
                <div class="mb-3 text-danger"><i class="fa-solid fa-trash-can fa-3x"></i></div>
                <h5 class="fw-bold">Eliminar Contrato</h5>
                <p class="text-muted small">¿Confirma eliminar este registro permanentemente?</p>
                <div class="d-flex justify-content-center gap-2 mt-3">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                    <a class="btn btn-danger btn-ok text-white">Eliminar</a>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalDireccion" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered"> 
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-primary text-white"> 
                <h5 class="modal-title fs-6"><i class="fa-solid fa-map-location-dot me-2"></i> Dirección</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div class="d-flex align-items-center mb-3">
                    <div class="flex-shrink-0">
                        <div class="bg-primary bg-opacity-10 p-3 rounded-circle text-primary"><i class="fa-solid fa-user fa-lg"></i></div>
                    </div>
                    <div class="flex-grow-1 ms-3">
                        <h6 class="mb-0 fw-bold" id="md_nombre">Cliente</h6>
                        <small class="text-muted">IP: <span id="md_ip"></span></small>
                    </div>
                </div>
                <div class="bg-light p-3 rounded border">
                    <small class="text-uppercase text-muted fw-bold" style="font-size: 0.7rem;">Ubicación Exacta</small>
                    <p id="md_direccion" class="mb-0 mt-1 text-dark small"></p> 
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/layout_foot.php'; ?>

<script src="<?php echo $path_to_root; ?>js/jquery.min.js"></script>
<script src="<?php echo $path_to_root; ?>js/datatables.min.js"></script>

<script>
    $(document).ready(function() {
        var table = $('#mitabla').DataTable({
            "scrollX": true,      // Habilitar scroll horizontal nativo de DataTables
            "fixedColumns": {     // Si se quisiera fijar columnas (requiere extension FixedColumns, probalo basico primero)
               // leftColumns: 2 
            },
            "order": [[1, "desc"]], // Ordenar por SAR (Fecha Registro) descendente por defecto
            "language": {
                "lengthMenu": "Ver _MENU_",
                "zeroRecords": "No hay datos",
                "info": "_START_ - _END_ de _TOTAL_",
                "infoEmpty": "0 reg.",
                "infoFiltered": "(filtrado de _MAX_)",
                "search": "Buscar:",
                "paginate": { "next": ">", "previous": "<" }
            },
            "bProcessing": true,
            "bServerSide": true,
            "sAjaxSource": "server_process.php",
            "aoColumnDefs": [
                { "bVisible": false, "aTargets": [0] }, // Ocultar ID
                { "className": "text-center", "aTargets": "_all" } // Centrar todo por defecto
            ],
            // Callback tras dibujar la tabla (para bindings si fuera necesario, pero delegamos eventos al tbody)
        });

        // --- Inline Edit Logic ---
        $('#mitabla tbody').on('blur', '.editable-cell', function() {
            var cell = $(this);
            var id = cell.data('id');
            var field = cell.data('field');
            var value = cell.text(); // text() gets plain text, html() usually not needed for simple inputs
            
            // Basic UI feedback 'saving'
            cell.css('color', '#6c757d'); 

            $.post('actualizar_contrato_inline.php', {
                id: id,
                field: field,
                value: value
            }, function(resp) {
                // Success
                cell.css('color', '#198754'); // Green text
                cell.addClass('bg-success bg-opacity-10');
                setTimeout(() => { 
                    cell.removeClass('bg-success bg-opacity-10'); 
                    cell.css('color', '#212529'); // Reset color
                }, 1500);
            }).fail(function() {
                // Fail
                cell.css('color', '#dc3545'); // Red text
                alert("Error al guardar cambios. Verifique su conexión.");
            });
        });

        // Enter key to blur (save)
        $('#mitabla tbody').on('keydown', '.editable-cell', function(e) {
            if (e.which === 13) {
                e.preventDefault();
                $(this).blur();
            }
        });

        // Modal Logic
        window.verDireccion = function(dir, nom, ip) {
            $('#md_direccion').text(dir);
            $('#md_nombre').text(nom);
            $('#md_ip').text(ip);
            // Mostrar modal manualmente si usamos onclick en vez de data-bs-toggle o para asegurar
            // $('#modalDireccion').modal('show'); // Bootstrap 5 data attributes work fine usually
        };
        
        window.confirmarEliminar = function(id) {
            var url = 'elimina.php?id=' + id;
            var modalEl = document.getElementById('eliminaModal');
            modalEl.querySelector('.btn-ok').href = url;
            var modal = new bootstrap.Modal(modalEl);
            modal.show();
        };
    });
</script>

<!-- SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
    // ==========================================
    // LÓGICA GESTIÓN TIPOS DE INSTALACIÓN (JSON)
    // ==========================================
    let tiposData = [];

    function loadTipos() {
        $.get('api_tipos_instalacion.php', function(data) {
            tiposData = data;
            renderTipos();
        });
    }

    // Exponer globalmente
    window.renderTipos = function() {
        const list = $('#listTipos');
        list.empty();
        tiposData.forEach((t, index) => {
            const item = `
                <div class="list-group-item d-flex justify-content-between align-items-center">
                    <span>${t}</span>
                    <button class="btn btn-sm btn-danger py-0 px-2" onclick="deleteTipo(${index})" title="Eliminar"><i class="fa-solid fa-times"></i></button>
                </div>
            `;
            list.append(item);
        });
    };

    window.deleteTipo = function(index) {
        Swal.fire({
            title: '¿Eliminar Tipo?',
            text: `Se eliminará "${tiposData[index]}".`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            confirmButtonText: 'Sí, eliminar'
        }).then((result) => {
            if (result.isConfirmed) {
                tiposData.splice(index, 1);
                saveTipos();
                renderTipos();
            }
        });
    };

    function saveTipos() {
        $.ajax({
            url: 'api_tipos_instalacion.php',
            type: 'POST',
            contentType: 'application/json',
            data: JSON.stringify(tiposData),
            success: function(response) {},
            error: function() {
                Swal.fire('Error', 'No se pudo guardar los cambios', 'error');
            }
        });
    }

    // ==========================================
    // LÓGICA GESTIÓN UBICACIONES (JSON)
    // ==========================================
    let ubicacionesData = [];
    let selectedMunicipioIndex = -1;

    function loadUbicaciones() {
        $.get('api_ubicaciones.php', function(data) {
            ubicacionesData = data;
            renderMunicipios();
            // Reset selección
            selectedMunicipioIndex = -1;
            $('#listParroquias').html('<div class="text-center text-muted p-3">Seleccione un municipio para ver sus parroquias</div>');
            $('#titleParroquias').text('Parroquias (Seleccione un Municipio)');
            $('#newParroquia, #btnAddParroquia').prop('disabled', true);
        });
    }

    // Exponer globalmente
    window.renderMunicipios = function() {
        const list = $('#listMunicipios');
        list.empty();
        ubicacionesData.forEach((m, index) => {
            const activeClass = (index === selectedMunicipioIndex) ? 'active' : '';
            const item = `
                <div class="list-group-item list-group-item-action d-flex justify-content-between align-items-center ${activeClass}" 
                     onclick="selectMunicipio(${index})" style="cursor: pointer;">
                    <span>${m.municipio}</span>
                    <button class="btn btn-sm btn-danger py-0 px-2" onclick="deleteMunicipio(event, ${index})" title="Eliminar"><i class="fa-solid fa-times"></i></button>
                </div>
            `;
            list.append(item);
        });
    };

    window.selectMunicipio = function(index) {
        selectedMunicipioIndex = index;
        renderMunicipios(); // Para actualizar clase active
        renderParroquias();
        
        $('#titleParroquias').text(`Parroquias de: ${ubicacionesData[index].municipio}`);
        $('#newParroquia, #btnAddParroquia').prop('disabled', false);
    };

    window.renderParroquias = function() {
        if(selectedMunicipioIndex === -1) return;
        
        const list = $('#listParroquias');
        list.empty();
        const parroquias = ubicacionesData[selectedMunicipioIndex].parroquias;
        
        if(parroquias.length === 0) {
           list.html('<div class="text-center text-muted p-2">Sin parroquias registradas</div>');
           return; 
        }

        parroquias.forEach((p, pIndex) => {
            const item = `
                <div class="list-group-item d-flex justify-content-between align-items-center">
                    <span>${p}</span>
                    <button class="btn btn-sm btn-outline-danger py-0 px-2" onclick="deleteParroquia(${pIndex})" title="Eliminar"><i class="fa-solid fa-trash-can"></i></button>
                </div>
            `;
            list.append(item);
        });
    };

    window.deleteMunicipio = function(e, index) {
        e.stopPropagation(); // Evitar seleccionar al borrar
        Swal.fire({
            title: '¿Eliminar Municipio?',
            text: `Se eliminará "${ubicacionesData[index].municipio}" y todas sus parroquias.`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            confirmButtonText: 'Sí, eliminar'
        }).then((result) => {
            if (result.isConfirmed) {
                ubicacionesData.splice(index, 1);
                if(selectedMunicipioIndex === index) {
                    selectedMunicipioIndex = -1;
                    $('#listParroquias').empty();
                    $('#newParroquia, #btnAddParroquia').prop('disabled', true);
                } else if(selectedMunicipioIndex > index) {
                    selectedMunicipioIndex--;
                }
                saveData();
                renderMunicipios();
            }
        });
    };

    window.deleteParroquia = function(pIndex) {
        ubicacionesData[selectedMunicipioIndex].parroquias.splice(pIndex, 1);
        saveData();
        renderParroquias();
    };

    function saveData() {
        $.ajax({
            url: 'api_ubicaciones.php',
            type: 'POST',
            contentType: 'application/json',
            data: JSON.stringify(ubicacionesData),
            success: function(response) {},
            error: function() {
                Swal.fire('Error', 'No se pudo guardar los cambios', 'error');
            }
        });
    }

    // ==========================================
    // EVENTS ON READY
    // ==========================================
    $(document).ready(function() {
        
        // --- TIPOS ---
        $('#modalTipos').on('show.bs.modal', function() {
            loadTipos();
        });

        $('#btnAddTipo').click(function() {
            const nombre = $('#newTipo').val().trim().toUpperCase(); 
            if(nombre) {
                if(tiposData.includes(nombre)) {
                    Swal.fire('Error', 'El tipo ya existe', 'warning');
                    return;
                }
                tiposData.push(nombre);
                $('#newTipo').val('');
                saveTipos();
                renderTipos();
            }
        });

        // --- UBICACIONES ---
        $('#modalUbicaciones').on('show.bs.modal', function() {
            loadUbicaciones();
        });

        $('#btnAddMunicipio').click(function() {
            const nombre = $('#newMunicipio').val().trim();
            if(nombre) {
                if(ubicacionesData.some(m => m.municipio.toLowerCase() === nombre.toLowerCase())) {
                    Swal.fire('Error', 'El municipio ya existe', 'warning');
                    return;
                }
                ubicacionesData.push({ municipio: nombre, parroquias: [] });
                $('#newMunicipio').val('');
                saveData();
                renderMunicipios();
            }
        });

        $('#btnAddParroquia').click(function() {
            const nombre = $('#newParroquia').val().trim();
            if(nombre && selectedMunicipioIndex !== -1) {
                if(ubicacionesData[selectedMunicipioIndex].parroquias.some(p => p.toLowerCase() === nombre.toLowerCase())) {
                    Swal.fire('Error', 'La parroquia ya existe en este municipio', 'warning');
                    return;
                }
                ubicacionesData[selectedMunicipioIndex].parroquias.push(nombre);
                $('#newParroquia').val('');
                saveData();
                renderParroquias();
            }
        });
    });
</script>