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
        white-space: nowrap;
        /* Evita saltos de linea largos */
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
            <div
                class="card-header d-flex justify-content-between align-items-center bg-white border-bottom-0 pt-4 px-4">
                <div>
                    <h5 class="fw-bold text-primary mb-1">Contratos</h5>
                    <p class="text-muted small mb-0">Gestión integral de contratos y servicios</p>
                </div>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-outline-success d-flex align-items-center gap-2"
                        data-bs-toggle="modal" data-bs-target="#modalStats" id="btnOpenStats">
                        <i class="fa-solid fa-chart-pie"></i> <span class="d-none d-md-inline">Estadísticas</span>
                    </button>
                    <button type="button" class="btn btn-outline-info d-flex align-items-center gap-2"
                        data-bs-toggle="modal" data-bs-target="#modalTipos">
                        <i class="fa-solid fa-tags"></i> <span class="d-none d-md-inline">Editar Tipos de
                            Conexión</span>
                    </button>
                    <!-- BOTONES INSTALADORES / VENDEDORES -->
                    <button type="button" class="btn btn-outline-secondary d-flex align-items-center gap-2"
                        data-bs-toggle="modal" data-bs-target="#modalInstaladores">
                        <i class="fa-solid fa-helmet-safety"></i> <span class="d-none d-md-inline">Instaladores</span>
                    </button>
                    <button type="button" class="btn btn-outline-secondary d-flex align-items-center gap-2"
                        data-bs-toggle="modal" data-bs-target="#modalVendedores">
                        <i class="fa-solid fa-user-tie"></i> <span class="d-none d-md-inline">Vendedores</span>
                    </button>
                    <!-- BOTON PLANES PRORRATEO -->
                    <button type="button" class="btn btn-outline-warning d-flex align-items-center gap-2"
                        data-bs-toggle="modal" data-bs-target="#modalProrrateo">
                        <i class="fa-solid fa-file-invoice-dollar"></i> <span class="d-none d-md-inline">Planes
                            Prorrateo</span>
                    </button>

                    <button type="button" class="btn btn-outline-primary d-flex align-items-center gap-2"
                        data-bs-toggle="modal" data-bs-target="#modalUbicaciones">
                        <i class="fa-solid fa-map-location-dot"></i> <span class="d-none d-md-inline">Editar
                            Ubicaciones</span>
                    </button>
                    <a href="nuevo.php" class="btn btn-primary d-flex align-items-center gap-2">
                        <i class="fa-solid fa-plus"></i> <span class="d-none d-md-inline">Nuevo Contrato</span>
                    </a>
                </div>
            </div>

            <div class="card-body px-4">
                <!-- Filtros Extra (Poc) -->
                <div class="row mb-3">
                    <div class="col-md-3">
                        <div class="input-group">
                            <span class="input-group-text bg-light"><i
                                    class="fa-solid fa-filter text-primary"></i></span>
                            <select id="filter_empty" class="form-select border-primary">
                                <option value="">Todos los registros</option>
                                <option value="1">Vacío: SAR (Fecha)</option>
                                <option value="2">Vacío: Cédula</option>
                                <option value="3">Vacío: Cliente</option>
                                <option value="4">Vacío: Municipio</option>
                                <option value="5">Vacío: Parroquia</option>
                                <option value="6">Vacío: Dirección</option>
                                <option value="7">Vacío: Telf. 1</option>
                                <option value="8">Vacío: Telf. 2</option>
                                <option value="9">Vacío: Correo</option>
                                <option value="10">Vacío: Correo (Alt)</option>
                                <option value="11">Vacío: F. Instalación</option>
                                <option value="12">Vacío: Medio Pago</option>
                                <option value="13">Vacío: Monto Pagar</option>
                                <option value="14">Vacío: Monto Pagado</option>
                                <option value="15">Vacío: Días Prorrateo</option>
                                <option value="16">Vacío: Monto Prorr. ($)</option>
                                <option value="17">Vacío: Observaciones</option>
                                <option value="18">Vacío: Tipo Conex.</option>
                                <option value="19">Vacío: Num. ONU</option>
                                <option value="20">Vacío: MAC/Serial</option>
                                <option value="21">Vacío: IP ONU</option>
                                <option value="22">Vacío: Caja NAP</option>
                                <option value="23">Vacío: Puerto NAP</option>
                                <option value="24">Vacío: NAP TX (dBm)</option>
                                <option value="25">Vacío: ONU RX (dBm)</option>
                                <option value="26">Vacío: Dist. Drop (m)</option>
                                <option value="27">Vacío: Instalador</option>
                                <option value="28">Vacío: Evidencia Fibra</option>
                                <option value="29">Vacío: IP Servicio</option>
                                <option value="30">Vacío: Punto Acceso</option>
                                <option value="31">Vacío: Val. Conex. (dBm)</option>
                                <option value="32">Vacío: Precinto ODN</option>
                                <option value="33">Vacío: Foto</option>
                                <option value="34">Vacío: Firma Cliente</option>
                                <option value="35">Vacío: Firma Técnico</option>
                                <option value="36">Vacío: Vendedor (Edit)</option>
                                <option value="37">Vacío: SAE Plus (Edit)</option>
                                <option value="38">Vacío: Plan</option>
                                <option value="39">Vacío: OLT</option>
                                <option value="40">Vacío: PON</option>
                                <option value="41">Vacío: Estado</option>
                            </select>
                        </div>
                    </div>
                </div>
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
                <div class="modal-header bg-info text-dark">
                    <h5 class="modal-title fw-bold"><i class="fa-solid fa-tags me-2"></i>Tipos de Conexión</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="list-group mb-3" id="listTipos" style="max-height: 400px; overflow-y: auto;">
                        <!-- Items generados por JS -->
                    </div>
                    <div class="input-group">
                        <input type="text" class="form-control" id="newTipo" placeholder="Nuevo Tipo (Ej. FTTH, RADIO)">
                        <button class="btn btn-success" type="button" id="btnAddTipo"><i
                                class="fa-solid fa-plus"></i></button>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <small class="text-muted me-auto">Cambios guardados en JSON.</small>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- MODAL GESTION INSTALADORES -->
    <div class="modal fade" id="modalInstaladores" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-secondary text-white">
                    <h5 class="modal-title fw-bold"><i class="fa-solid fa-helmet-safety me-2"></i>Gestionar Instaladores
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="list-group mb-3" id="listInstaladores" style="max-height: 400px; overflow-y: auto;">
                    </div>
                    <div class="input-group">
                        <input type="text" class="form-control" id="newInstalador" placeholder="Nombre Instalador">
                        <button class="btn btn-success" type="button" onclick="addPersonal('instalador')"><i
                                class="fa-solid fa-plus"></i></button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- MODAL GESTION VENDEDORES -->
    <div class="modal fade" id="modalVendedores" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-secondary text-white">
                    <h5 class="modal-title fw-bold"><i class="fa-solid fa-user-tie me-2"></i>Gestionar Vendedores</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="list-group mb-3" id="listVendedores" style="max-height: 400px; overflow-y: auto;"></div>
                    <div class="input-group">
                        <input type="text" class="form-control" id="newVendedor" placeholder="Nombre Vendedor">
                        <button class="btn btn-success" type="button" onclick="addPersonal('vendedor')"><i
                                class="fa-solid fa-plus"></i></button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- MODAL GESTION PLANES PRORRATEO -->
    <div class="modal fade" id="modalProrrateo" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-warning text-dark">
                    <h5 class="modal-title fw-bold"><i class="fa-solid fa-file-invoice-dollar me-2"></i>Planes Prorrateo
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="list-group mb-3" id="listProrrateo" style="max-height: 400px; overflow-y: auto;"></div>
                    <div class="input-group">
                        <input type="text" class="form-control" id="newPlanNombre" placeholder="Nombre (Ej. 100 Mbps)">
                        <input type="number" step="0.01" class="form-control" id="newPlanPrecio"
                            placeholder="Precio ($)">
                        <button class="btn btn-success" type="button" onclick="addPlanProrrateo()"><i
                                class="fa-solid fa-plus"></i></button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- MODAL GESTION UBICACIONES -->
    <div class="modal fade" id="modalUbicaciones" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold"><i class="fa-solid fa-map-location-dot me-2"></i>Gestionar
                        Ubicaciones (JSON)</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <!-- COLUMNA MUNICIPIOS -->
                        <div class="col-md-5 border-end">
                            <h6 class="text-primary fw-bold mb-3">Municipios</h6>
                            <div class="list-group mb-3" id="listMunicipios"
                                style="max-height: 300px; overflow-y: auto;">
                                <!-- Items generados por JS -->
                            </div>
                            <div class="input-group">
                                <input type="text" class="form-control" id="newMunicipio" placeholder="Nuevo Municipio">
                                <button class="btn btn-success" type="button" id="btnAddMunicipio"><i
                                        class="fa-solid fa-plus"></i></button>
                            </div>
                        </div>

                        <!-- COLUMNA PARROQUIAS -->
                        <div class="col-md-7">
                            <h6 class="text-info fw-bold mb-3" id="titleParroquias">Parroquias (Seleccione un Municipio)
                            </h6>
                            <div class="list-group mb-3" id="listParroquias"
                                style="max-height: 300px; overflow-y: auto;">
                                <!-- Items generados por JS -->
                                <div class="text-center text-muted p-3">Seleccione un municipio para ver sus parroquias
                                </div>
                            </div>
                            <div class="input-group">
                                <input type="text" class="form-control" id="newParroquia" placeholder="Nueva Parroquia"
                                    disabled>
                                <button class="btn btn-success" type="button" id="btnAddParroquia" disabled><i
                                        class="fa-solid fa-plus"></i></button>
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
                    <h5 class="modal-title fw-bold"><i class="fa-solid fa-chart-bar me-2"></i>Estadísticas [VERSIÓN 3 -
                        BARRAS]
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <!-- Filtros -->
                    <div class="card bg-light border-0 mb-4">
                        <div class="card-body">
                            <h6 class="fw-bold mb-3">Filtros de Búsqueda</h6>
                            <div class="row g-3">
                                <div class="col-md-2">
                                    <label class="form-label small">Fecha Inicio</label>
                                    <input type="date" class="form-control" id="statStartDate">
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label small">Fecha Fin</label>
                                    <input type="date" class="form-control" id="statEndDate">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label small">Instalador</label>
                                    <select class="form-select" id="statInstaller">
                                        <option value="">Todos</option>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label small">Vendedor</label>
                                    <select class="form-select" id="statVendor">
                                        <option value="">Todos</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label small">Tipo de Conexión</label>
                                    <select class="form-select" id="statContractType">
                                        <option value="">Todos</option>
                                    </select>
                                </div>
                                <div class="col-md-12 text-end">
                                    <button class="btn btn-primary" id="btnFilterStats"><i
                                            class="fa-solid fa-filter me-2"></i>Aplicar Filtros</button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Resultados Gráficas -->
                    <div class="row g-4">
                        <div class="col-md-4">
                            <h6 class="fw-bold text-primary text-center">Instalaciones por Instalador</h6>
                            <div class="chart-container" style="position: relative; height:300px; width:100%">
                                <canvas id="chartInstaller"></canvas>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <h6 class="fw-bold text-success text-center">Ventas por Vendedor</h6>
                            <div class="chart-container" style="position: relative; height:300px; width:100%">
                                <canvas id="chartVendor"></canvas>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <h6 class="fw-bold text-info text-center">Contratos por Ubicación</h6>
                            <div class="chart-container" style="position: relative; height:300px; width:100%">
                                <canvas id="chartLocation"></canvas>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <h6 class="fw-bold text-warning text-center">Tipo de Instalación</h6>
                            <div class="chart-container" style="position: relative; height:300px; width:100%">
                                <canvas id="chartType"></canvas>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <h6 class="fw-bold text-danger text-center">Instalaciones Mensuales</h6>
                            <div class="chart-container" style="position: relative; height:300px; width:100%">
                                <canvas id="chartMonthly"></canvas>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <h6 class="fw-bold text-secondary text-center">Tipo de Conexión</h6>
                            <div class="chart-container" style="position: relative; height:300px; width:100%">
                                <canvas id="chartConnection"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-danger" id="btnExportPDF"><i
                            class="fa-solid fa-file-pdf me-2"></i>Exportar PDF</button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts para PDF Export (Usando PHP Backend) -->
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // Cargar Dashboard Stats
            fetchStatsDashboard();

            // Cargar Listas para Filtros al abrir modal
            var modalStats = document.getElementById('modalStats');
            modalStats.addEventListener('show.bs.modal', function () {
                fetchStatsLists();
                fetchModalStats(); // Load charts immediately
            });

            // Botón Filtrar
            document.getElementById('btnFilterStats').addEventListener('click', function () {
                fetchModalStats();
            });

            // Botón Exportar PDF
            document.getElementById('btnExportPDF').addEventListener('click', function () {
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
            Promise.all([
                // Fetch Instaladores form JSON API (Same as Management Modal)
                fetch('json_personal_api.php?action=get_instaladores').then(r => r.json()),
                // Fetch Vendedores from JSON API
                fetch('json_personal_api.php?action=get_vendedores').then(r => r.json()),
                // Fetch Tipos from Types API
                fetch('api_tipos_instalacion.php').then(r => r.json())
            ]).then(([installers, vendors, types]) => {
                const selInst = document.getElementById('statInstaller');
                const selVend = document.getElementById('statVendor');
                const selType = document.getElementById('statContractType');

                // Fill Instaladores
                if (selInst.options.length <= 1) {
                    installers.forEach(inst => selInst.add(new Option(inst, inst)));
                }

                // Fill Vendedores
                if (selVend.options.length <= 1) {
                    vendors.forEach(vend => selVend.add(new Option(vend, vend)));
                }

                // Fill Tipos
                if (selType.options.length <= 1) {
                    types.forEach(t => selType.add(new Option(t, t)));
                }
            }).catch(err => console.error('Error loading lists:', err));
        }

        // Chart Instances
        let chartInstances = {};

        function fetchModalStats() {
            const start = document.getElementById('statStartDate').value;
            const end = document.getElementById('statEndDate').value;
            const inst = document.getElementById('statInstaller').value;
            const vend = document.getElementById('statVendor').value;
            const type = document.getElementById('statContractType').value;

            const params = new URLSearchParams({
                action: 'modal_stats',
                start: start,
                end: end,
                installer: inst,
                vendor: vend,
                type: type,
                _t: new Date().getTime() // Cache busting
            });

            fetch('get_contract_stats.php?' + params.toString())
                .then(response => response.json())
                .then(data => {
                    // 1. Zonas de Ventas (Vertical Blue)
                    renderStatsChartV4('chartLocation', data.by_location, 'ubicacion', 'Zonas de Ventas', false, '#3a7bd5');

                    // 2. Instalaciones por Tipo (Vertical Blue)
                    renderStatsChartV4('chartType', data.by_type, 'tipo', 'Instalaciones por Tipo', false, '#3a7bd5');

                    // 3. Instalaciones (Monthly Vertical Blue)
                    renderStatsChartV4('chartMonthly', data.by_month, 'mes', 'Instalaciones', false, '#3a7bd5');

                    // 4. Tipo de Instalación (Horizontal Multi-color)
                    renderStatsChartV4('chartConnection', data.by_connection, 'conexion', 'Tipos de Conexión', true, null);

                    // 5. Instaladores (Horizontal Blue)
                    renderStatsChartV4('chartInstaller', data.by_installer, 'nombre', 'Instaladores', true, '#3a7bd5');

                    // 6. Vendedor (Horizontal Blue)
                    renderStatsChartV4('chartVendor', data.by_vendor, 'nombre_vendedor', 'Ventas', true, '#3a7bd5');
                });
        }

        function renderStatsChartV4(canvasId, data, labelKey, title, isHorizontal = false, customColor = null) {
            console.log('Rendering V4 Chart:', canvasId, title);
            const ctx = document.getElementById(canvasId).getContext('2d');

            if (chartInstances[canvasId]) {
                chartInstances[canvasId].destroy();
            }

            if (!data || data.length === 0) {
                chartInstances[canvasId] = new Chart(ctx, {
                    type: 'bar',
                    data: { labels: ['Sin Datos'], datasets: [{ data: [0], backgroundColor: ['#e9ecef'] }] },
                    options: {
                        indexAxis: isHorizontal ? 'y' : 'x',
                        plugins: { legend: { display: false }, title: { display: true, text: 'Sin Resultados' } }
                    }
                });
                return;
            }

            const labels = data.map(item => item[labelKey]);
            const values = data.map(item => item.total);
            const colors = customColor ? new Array(data.length).fill(customColor) : generateColors(data.length);

            chartInstances[canvasId] = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Cantidad',
                        data: values,
                        backgroundColor: colors,
                        borderColor: colors.map(c => c.startsWith('hsl') ? c.replace('60%', '50%') : c),
                        borderWidth: 1,
                        barPercentage: 0.8
                    }]
                },
                options: {
                    indexAxis: isHorizontal ? 'y' : 'x',
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                        title: { display: true, text: title, font: { size: 14, weight: 'bold' } }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: { stepSize: 1 },
                            grid: { display: false }
                        },
                        x: {
                            ticks: {
                                autoSkip: false,
                                maxRotation: isHorizontal ? 0 : 90,
                                minRotation: isHorizontal ? 0 : 45
                            },
                            grid: { display: false }
                        }
                    }
                }
            });
        }

        function generateColors(count) {
            const colors = [];
            for (let i = 0; i < count; i++) {
                const hue = (i * 137.508) % 360; // Golden angle approx
                colors.push(`hsl(${hue}, 70%, 60%)`);
            }
            return colors;
        }

        function exportStatsPDF() {
            const start = document.getElementById('statStartDate').value;
            const end = document.getElementById('statEndDate').value;
            const inst = document.getElementById('statInstaller').value;
            const vend = document.getElementById('statVendor').value;
            const type = document.getElementById('statContractType').value;

            // Capture 6 Charts
            const fields = {
                start: start,
                end: end,
                installer: inst,
                vendor: vend,
                type: type,
                img_installer: document.getElementById('chartInstaller').toDataURL('image/png'),
                img_vendor: document.getElementById('chartVendor').toDataURL('image/png'),
                img_location: document.getElementById('chartLocation').toDataURL('image/png'),
                img_type: document.getElementById('chartType').toDataURL('image/png'),
                img_monthly: document.getElementById('chartMonthly').toDataURL('image/png'),
                img_connection: document.getElementById('chartConnection').toDataURL('image/png')
            };

            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '../../paginas/reportes_pdf/generar_estadisticas_pdf.php';
            form.target = '_blank';

            for (const key in fields) {
                if (fields.hasOwnProperty(key)) {
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = key;
                    input.value = fields[key];
                    form.appendChild(input);
                }
            }

            document.body.appendChild(form);
            form.submit();
            document.body.removeChild(form);
        }
    </script>

</main>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

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


<?php require_once '../includes/layout_foot.php'; ?>

<script src="<?php echo $path_to_root; ?>js/jquery.min.js"></script>
<script src="<?php echo $path_to_root; ?>js/datatables.min.js"></script>

<script>
    $(document).ready(function () {
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
            "fnServerParams": function (aoData) {
                aoData.push({ "name": "empty_filter", "value": $('#filter_empty').val() });
            },
            "aoColumnDefs": [
                { "bVisible": false, "aTargets": [0] }, // Ocultar ID
                { "className": "text-center", "aTargets": "_all" } // Centrar todo por defecto
            ],
            // Callback tras dibujar la tabla (para bindings si fuera necesario, pero delegamos eventos al tbody)
        });

        // --- Inline Edit Logic ---
        $('#mitabla tbody').on('blur', '.editable-cell', function () {
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
            }, function (resp) {
                // Success
                cell.css('color', '#198754'); // Green text
                cell.addClass('bg-success bg-opacity-10');
                setTimeout(() => {
                    cell.removeClass('bg-success bg-opacity-10');
                    cell.css('color', '#212529'); // Reset color
                }, 1500);
            }).fail(function () {
                // Fail
                cell.css('color', '#dc3545'); // Red text
                alert("Error al guardar cambios. Verifique su conexión.");
            });
        });

        // Enter key to blur (save)
        $('#mitabla tbody').on('keydown', '.editable-cell', function (e) {
            if (e.which === 13) {
                e.preventDefault();
                $(this).blur();
            }
        });

        // Modal Logic

        window.confirmarEliminar = function (id) {
            var url = 'elimina.php?id=' + id;
            var modalEl = document.getElementById('eliminaModal');
            modalEl.querySelector('.btn-ok').href = url;
            var modal = new bootstrap.Modal(modalEl);
            modal.show();
        };

        // Escuchar cambio en filtro de vacios
        $('#filter_empty').on('change', function () {
            table.draw();
        });
    });
</script>

<!-- SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
    // ==========================================
    // GESTIÓN GENÉRICA LISTAS (Instaladores / Vendedores / Tipos)
    // ==========================================

    // --- INSTALADORES ---
    let instaladoresData = [];
    function loadInstaladores() {
        $.get('json_personal_api.php?action=get_instaladores', function (data) {
            instaladoresData = data || [];
            renderPersonalList('listInstaladores', instaladoresData, 'Instalador');
        });
    }

    // --- VENDEDORES ---
    let vendedoresData = [];
    function loadVendedores() {
        $.get('json_personal_api.php?action=get_vendedores', function (data) {
            vendedoresData = data || [];
            renderPersonalList('listVendedores', vendedoresData, 'Vendedor');
        });
    }

    // --- PLANES PRORRATEO ---
    let prorrateoData = [];
    function loadPlanesProrrateo() {
        $.get('json_personal_api.php?action=get_planes_prorrateo', function (data) {
            prorrateoData = data || [];
            renderProrrateoList();
        });
    }

    function renderProrrateoList() {
        const list = $('#listProrrateo');
        list.empty();
        if (prorrateoData.length === 0) {
            list.html('<div class="text-center text-muted p-2">Sin registros</div>');
            return;
        }
        prorrateoData.forEach((item, index) => {
            const row = `
                <div class="list-group-item d-flex justify-content-between align-items-center">
                    <span>${item.nombre} - $${item.precio}</span>
                    <button class="btn btn-sm btn-danger py-0 px-2" onclick="deletePlanProrrateo(${index})" title="Eliminar"><i class="fa-solid fa-times"></i></button>
                </div>`;
            list.append(row);
        });
    }

    window.addPlanProrrateo = function () {
        const nombre = $('#newPlanNombre').val().trim();
        const precio = $('#newPlanPrecio').val().trim();

        if (!nombre || !precio) {
            Swal.fire('Error', 'Ingrese nombre y precio', 'warning');
            return;
        }

        prorrateoData.push({ nombre: nombre, precio: precio });
        $('#newPlanNombre').val('');
        $('#newPlanPrecio').val('');

        savePlanesProrrateo();
        renderProrrateoList();
    };

    window.deletePlanProrrateo = function (index) {
        Swal.fire({
            title: '¿Eliminar Plan?',
            text: `Se eliminará este plan.`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            confirmButtonText: 'Sí, eliminar'
        }).then((result) => {
            if (result.isConfirmed) {
                prorrateoData.splice(index, 1);
                savePlanesProrrateo();
                renderProrrateoList();
            }
        });
    };

    function savePlanesProrrateo() {
        $.ajax({
            url: 'json_personal_api.php?action=save_planes_prorrateo',
            type: 'POST',
            contentType: 'application/json',
            data: JSON.stringify(prorrateoData),
            error: function () { Swal.fire('Error', 'No se pudo guardar', 'error'); }
        });
    }

    // Funciones Genéricas UI
    function renderPersonalList(listId, dataArr, typeLabel) {
        const list = $('#' + listId);
        list.empty();
        if (dataArr.length === 0) {
            list.html('<div class="text-center text-muted p-2">Sin registros</div>');
            return;
        }
        dataArr.forEach((item, index) => {
            const row = `
                <div class="list-group-item d-flex justify-content-between align-items-center">
                    <span>${item}</span>
                    <button class="btn btn-sm btn-danger py-0 px-2" onclick="deletePersonal('${listId}', ${index})" title="Eliminar"><i class="fa-solid fa-times"></i></button>
                </div>`;
            list.append(row);
        });
    }

    window.addPersonal = function (type) {
        let inputId = (type === 'instalador') ? 'newInstalador' : 'newVendedor';
        let listId = (type === 'instalador') ? 'listInstaladores' : 'listVendedores';
        let dataArr = (type === 'instalador') ? instaladoresData : vendedoresData;

        // Obtener ID real del array porque lo pasamos por referencia
        if (type === 'instalador') dataArr = instaladoresData;
        else dataArr = vendedoresData;

        const val = $('#' + inputId).val().trim().toUpperCase();
        if (!val) return;

        if (dataArr.includes(val)) {
            Swal.fire('Atención', 'Este registro ya existe', 'warning');
            return;
        }

        dataArr.push(val);
        $('#' + inputId).val('');

        savePersonal((type === 'instalador'));
        renderPersonalList(listId, dataArr, '');
    };

    window.deletePersonal = function (listId, index) {
        let isInstalador = (listId === 'listInstaladores');
        let label = isInstalador ? 'Instalador' : 'Vendedor';
        let dataArr = isInstalador ? instaladoresData : vendedoresData;

        Swal.fire({
            title: '¿Eliminar?',
            text: `Se eliminará este ${label}.`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            confirmButtonText: 'Sí, eliminar'
        }).then((result) => {
            if (result.isConfirmed) {
                // Actualizar el array correcto
                if (isInstalador) instaladoresData.splice(index, 1);
                else vendedoresData.splice(index, 1);

                savePersonal(isInstalador);

                // Re-render
                if (isInstalador) renderPersonalList('listInstaladores', instaladoresData, 'Instalador');
                else renderPersonalList('listVendedores', vendedoresData, 'Vendedor');
            }
        });
    };

    function savePersonal(isInstalador) {
        const action = isInstalador ? 'save_instaladores' : 'save_vendedores';
        const dataPayload = isInstalador ? instaladoresData : vendedoresData;

        $.ajax({
            url: 'json_personal_api.php?action=' + action,
            type: 'POST',
            contentType: 'application/json',
            data: JSON.stringify(dataPayload),
            error: function () { Swal.fire('Error', 'No se pudo guardar', 'error'); }
        });
    }

    // --- TIPOS (Mantenido Original) ---
    let tiposData = [];
    function loadTipos() {
        $.get('api_tipos_instalacion.php', function (data) {
            tiposData = data;
            renderTipos();
        });
    }

    window.renderTipos = function () {
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

    window.deleteTipo = function (index) {
        Swal.fire({
            title: '¿Eliminar Tipo de Conexión?',
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
    // ... resto script tipos ...


    function saveTipos() {
        $.ajax({
            url: 'api_tipos_instalacion.php',
            type: 'POST',
            contentType: 'application/json',
            data: JSON.stringify(tiposData),
            success: function (response) { },
            error: function () {
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
        $.get('api_ubicaciones.php', function (data) {
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
    window.renderMunicipios = function () {
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

    window.selectMunicipio = function (index) {
        selectedMunicipioIndex = index;
        renderMunicipios(); // Para actualizar clase active
        renderParroquias();

        $('#titleParroquias').text(`Parroquias de: ${ubicacionesData[index].municipio}`);
        $('#newParroquia, #btnAddParroquia').prop('disabled', false);
    };

    window.renderParroquias = function () {
        if (selectedMunicipioIndex === -1) return;

        const list = $('#listParroquias');
        list.empty();
        const parroquias = ubicacionesData[selectedMunicipioIndex].parroquias;

        if (parroquias.length === 0) {
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

    window.deleteMunicipio = function (e, index) {
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
                if (selectedMunicipioIndex === index) {
                    selectedMunicipioIndex = -1;
                    $('#listParroquias').empty();
                    $('#newParroquia, #btnAddParroquia').prop('disabled', true);
                } else if (selectedMunicipioIndex > index) {
                    selectedMunicipioIndex--;
                }
                saveData();
                renderMunicipios();
            }
        });
    };

    window.deleteParroquia = function (pIndex) {
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
            success: function (response) { },
            error: function () {
                Swal.fire('Error', 'No se pudo guardar los cambios', 'error');
            }
        });
    }

    // ==========================================
    // EVENTS ON READY
    // ==========================================
    $(document).ready(function () {

        // --- TIPOS ---
        $('#modalTipos').on('show.bs.modal', function () {
            loadTipos();
        });

        // --- INSTALADORES / VENDEDORES ---
        $('#modalInstaladores').on('show.bs.modal', function () { loadInstaladores(); });
        $('#modalVendedores').on('show.bs.modal', function () { loadVendedores(); });
        $('#modalProrrateo').on('show.bs.modal', function () { loadPlanesProrrateo(); });

        $('#btnAddTipo').click(function () {
            const nombre = $('#newTipo').val().trim().toUpperCase();
            if (nombre) {
                if (tiposData.includes(nombre)) {
                    Swal.fire('Error', 'El tipo de conexión ya existe', 'warning');
                    return;
                }
                tiposData.push(nombre);
                $('#newTipo').val('');
                saveTipos();
                renderTipos();
            }
        });

        // --- UBICACIONES ---
        $('#modalUbicaciones').on('show.bs.modal', function () {
            loadUbicaciones();
        });

        $('#btnAddMunicipio').click(function () {
            const nombre = $('#newMunicipio').val().trim();
            if (nombre) {
                if (ubicacionesData.some(m => m.municipio.toLowerCase() === nombre.toLowerCase())) {
                    Swal.fire('Error', 'El municipio ya existe', 'warning');
                    return;
                }
                ubicacionesData.push({ municipio: nombre, parroquias: [] });
                $('#newMunicipio').val('');
                saveData();
                renderMunicipios();
            }
        });

        $('#btnAddParroquia').click(function () {
            const nombre = $('#newParroquia').val().trim();
            if (nombre && selectedMunicipioIndex !== -1) {
                if (ubicacionesData[selectedMunicipioIndex].parroquias.some(p => p.toLowerCase() === nombre.toLowerCase())) {
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