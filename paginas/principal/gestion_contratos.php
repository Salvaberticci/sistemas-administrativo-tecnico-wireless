<?php
/**
 * Página principal que muestra la tabla de registros
 */
require '../conexion.php';

$path_to_root = "../../";
$page_title = "Gestión de Contratos";
$breadcrumb = ["Admin"];
$back_url = "../menu.php";
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

    /* Nuevos estilos para Signature Pad y secciones */
    .section-title {
        background-color: #f8f9fa;
        padding: 10px 15px;
        border-radius: 4px;
        font-size: 0.9rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .signature-pad {
        border: 2px dashed #ccc;
        border-radius: 5px;
        width: 100%;
        height: 180px;
        background-color: #fff;
        touch-action: none;
    }

    .signature-prev {
        max-height: 120px;
        border: 1px solid #dee2e6;
        padding: 5px;
        border-radius: 4px;
        background: #f8f9fa;
        display: block;
        margin-bottom: 10px;
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
                    <!-- BOTONES EXCEL NUEVOS -->
                    <div class="vr mx-1"></div>
                    <button type="button" class="btn btn-success d-flex align-items-center gap-2 shadow-sm"
                        onclick="exportExcel()">
                        <i class="fa-solid fa-file-excel"></i> <span class="d-none d-md-inline">Exportar</span>
                    </button>
                    <button type="button" class="btn btn-outline-success d-flex align-items-center gap-2 shadow-sm"
                        data-bs-toggle="modal" data-bs-target="#modalImportExcel">
                        <i class="fa-solid fa-file-import"></i> <span class="d-none d-md-inline">Importar</span>
                    </button>
                    <div class="vr mx-1"></div>

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
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-body text-center p-4">
                <div class="mb-3 text-danger"><i class="fa-solid fa-trash-can fa-3x"></i></div>
                <h5 class="fw-bold">Eliminar Contrato</h5>
                <p class="text-muted small">¿Confirma eliminar este registro permanentemente? Esta acción no se puede
                    deshacer.</p>

                <div class="mb-3 text-start">
                    <label for="delete_password" class="form-label small fw-bold">Confirme su Contraseña</label>
                    <input type="password" class="form-control" id="delete_password"
                        placeholder="Ingrese su contraseña administrativa">
                </div>

                <div class="d-flex justify-content-center gap-2 mt-3">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-danger" id="btnConfirmarEliminar">Eliminar</button>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- Modal Importar Excel -->
<div class="modal fade" id="modalImportExcel" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title fw-bold"><i class="fa-solid fa-file-excel me-2"></i>Importar Contratos</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div class="alert alert-info border-0 shadow-sm small">
                    <i class="fa-solid fa-circle-info me-2"></i>
                    Sube un archivo Excel (.xlsx) con los datos de los contratos.
                    Asegúrate de que las columnas coincidan con el formato de exportación.
                </div>

                <form id="formImportExcel" action="importar_excel_contratos.php" method="POST"
                    enctype="multipart/form-data">
                    <div class="mb-4">
                        <label class="form-label fw-bold small text-uppercase text-muted">Archivo Excel</label>
                        <input type="file" name="archivo_excel" class="form-control" accept=".xlsx, .xls" required>
                    </div>
                    <div class="d-grid">
                        <button type="submit" class="btn btn-success shadow-sm">
                            <i class="fa-solid fa-upload me-2"></i> Cargar y Procesar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- ===== MODAL EDITAR CONTRATO ===== -->
<div class="modal fade" id="modalEditarContrato" tabindex="-1" aria-labelledby="modalEditarContratoLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title fw-bold" id="modalEditarContratoLabel">
                    <i class="fa-solid fa-pen me-2"></i>Editar Contrato #<span id="editContratoId">-</span>
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">

                <!-- Alert for errors/success -->
                <div id="editContratoAlert" class="alert d-none mb-3" role="alert"></div>

                <!-- Spinner -->
                <div id="editContratoSpinner" class="text-center py-5">
                    <div class="spinner-border text-primary" role="status"></div>
                    <p class="mt-2 text-muted small">Cargando datos...</p>
                </div>

                <form id="formEditarContrato" class="d-none">
                    <input type="hidden" id="edit_id" name="id">

                    <!-- SECCIÓN: INFO CLIENTE -->
                    <div class="section-title bg-light p-2 fw-bold border-start border-primary border-4 mb-3">
                        <i class="fa-solid fa-user me-2 text-primary"></i>Información del Cliente
                    </div>
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Cédula <span class="text-danger">*</span></label>
                            <input type="text" class="form-control form-control-sm" id="edit_cedula" name="cedula"
                                required pattern="[VJEGPvjegp][0-9]+" placeholder="V12345678"
                                style="text-transform: uppercase;">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Estado <span class="text-danger">*</span></label>
                            <select class="form-select form-select-sm" id="edit_estado" name="estado" required>
                                <option value="ACTIVO">ACTIVO</option>
                                <option value="INACTIVO">INACTIVO</option>
                                <option value="SUSPENDIDO">SUSPENDIDO</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Nombre Completo <span
                                    class="text-danger">*</span></label>
                            <input type="text" class="form-control form-control-sm" id="edit_nombre"
                                name="nombre_completo" required pattern="[A-Za-zñÑáéíóúÁÉÍÓÚ\s]+">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-bold">Teléfono</label>
                            <input type="text" class="form-control form-control-sm" id="edit_telefono" name="telefono"
                                inputmode="tel" pattern="[0-9-+\s]{7,15}" placeholder="0424-1234567">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-bold">Teléfono (Alt)</label>
                            <input type="text" class="form-control form-control-sm" id="edit_telefono2"
                                name="telefono_secundario" inputmode="tel" pattern="[0-9-+\s]{7,15}"
                                placeholder="0414-7654321">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Correo</label>
                            <input type="email" class="form-control form-control-sm" id="edit_correo" name="correo">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Correo (Alt)</label>
                            <input type="email" class="form-control form-control-sm" id="edit_correo2"
                                name="correo_adicional">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Fecha de Instalación</label>
                            <input type="date" class="form-control form-control-sm" id="edit_fecha"
                                name="fecha_instalacion">
                        </div>
                        <div class="col-md-12">
                            <label class="form-label small fw-bold">Dirección</label>
                            <textarea class="form-control form-control-sm" id="edit_direccion" name="direccion"
                                rows="2"></textarea>
                        </div>
                    </div>

                    <!-- SECCIÓN: UBICACIÓN -->
                    <div class="section-title bg-light p-2 fw-bold border-start border-success border-4 mb-3">
                        <i class="fa-solid fa-map-location-dot me-2 text-success"></i>Ubicación
                    </div>
                    <div class="row g-3 mb-4">
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Municipio</label>
                            <select class="form-select form-select-sm" id="edit_municipio" name="id_municipio">
                                <option value="">Cargando municipios...</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Parroquia</label>
                            <select class="form-select form-select-sm" id="edit_parroquia" name="id_parroquia" disabled>
                                <option value="">-- Seleccione municipio --</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Comunidad</label>
                            <select class="form-select form-select-sm" id="edit_comunidad" name="id_comunidad" disabled>
                                <option value="">-- Seleccione parroquia --</option>
                            </select>
                        </div>
                    </div>

                    <!-- SECCIÓN: CONTRATO / PLAN -->
                    <div class="section-title bg-light p-2 fw-bold border-start border-warning border-4 mb-3">
                        <i class="fa-solid fa-file-contract me-2 text-warning"></i>Plan y Comercial
                    </div>
                    <div class="row g-3 mb-4">
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Plan</label>
                            <select class="form-select form-select-sm" id="edit_plan" name="id_plan">
                                <option value="">-- Seleccione --</option>
                                <?php
                                $sql_planes = "SELECT id_plan, nombre_plan FROM planes ORDER BY nombre_plan ASC";
                                $res_planes = $conn->query($sql_planes);
                                while ($p = $res_planes->fetch_assoc()) {
                                    echo '<option value="' . $p['id_plan'] . '">' . htmlspecialchars($p['nombre_plan']) . '</option>';
                                }
                                ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Vendedor</label>
                            <select class="form-select form-select-sm" id="edit_vendedor" name="id_vendedor">
                                <option value="">-- Seleccione --</option>
                                <?php
                                $sql_vends = "SELECT id_vendedor, nombre_vendedor FROM vendedores ORDER BY nombre_vendedor ASC";
                                $res_vends = $conn->query($sql_vends);
                                while ($v = $res_vends->fetch_assoc()) {
                                    echo '<option value="' . $v['id_vendedor'] . '">' . htmlspecialchars($v['nombre_vendedor']) . '</option>';
                                }
                                ?>
                            </select>
                        </div>
                    </div>

                    <!-- SECCIÓN: RED -->
                    <div class="section-title bg-light p-2 fw-bold border-start border-info border-4 mb-3">
                        <i class="fa-solid fa-network-wired me-2 text-info"></i>Red y NAP
                    </div>
                    <div class="row g-3 mb-4">
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">OLT</label>
                            <select class="form-select form-select-sm" id="edit_olt" name="id_olt">
                                <option value="">-- Seleccione --</option>
                                <?php
                                $sql_olts = "SELECT id_olt, nombre_olt FROM olt ORDER BY nombre_olt ASC";
                                $res_olts = $conn->query($sql_olts);
                                while ($o = $res_olts->fetch_assoc()) {
                                    echo '<option value="' . $o['id_olt'] . '">' . htmlspecialchars($o['nombre_olt']) . '</option>';
                                }
                                ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">PON</label>
                            <select class="form-select form-select-sm" id="edit_pon" name="id_pon" disabled>
                                <option value="">-- Seleccione OLT --</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Tipo de Conexión</label>
                            <select class="form-select form-select-sm" id="edit_tipo_conexion" name="tipo_conexion">
                                <option value="">-- Seleccione --</option>
                                <?php
                                $jsonTipos = 'data/tipos_instalacion.json';
                                if (file_exists($jsonTipos)) {
                                    $tipos = json_decode(file_get_contents($jsonTipos), true);
                                    foreach ($tipos as $t)
                                        echo '<option value="' . $t . '">' . $t . '</option>';
                                } else {
                                    echo '<option value="FTTH">FTTH</option><option value="RADIO">RADIO</option>';
                                }
                                ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Caja NAP</label>
                            <input type="text" class="form-control form-control-sm" id="edit_nap" name="ident_caja_nap">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Puerto NAP</label>
                            <input type="text" class="form-control form-control-sm" id="edit_puerto_nap"
                                name="puerto_nap">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Precinto ODN</label>
                            <input type="text" class="form-control form-control-sm" id="edit_odn"
                                name="num_presinto_odn">
                        </div>
                    </div>

                    <!-- SECCIÓN: TÉCNICO FTTH/RADIO -->
                    <div class="section-title bg-light p-2 fw-bold border-start border-secondary border-4 mb-3">
                        <i class="fa-solid fa-screwdriver-wrench me-2 text-secondary"></i>Datos Técnicos
                    </div>
                    <div class="row g-3 mb-4" id="edit_campos_ftth">
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">MAC/Serial ONU</label>
                            <input type="text" class="form-control form-control-sm" id="edit_mac" name="mac_onu"
                                pattern="[A-Fa-f0-9:.\-]{8,20}" placeholder="FABBCC112233">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">IP ONU</label>
                            <input type="text" class="form-control form-control-sm" id="edit_ip_onu" name="ip_onu"
                                pattern="^(?:[0-9]{1,3}\.){3}[0-9]{1,3}$" placeholder="192.168.1.1">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">NAP TX Power</label>
                            <input type="text" class="form-control form-control-sm" id="edit_nap_tx" name="nap_tx_power"
                                pattern="-?[0-9.]+" placeholder="-25.5">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">ONU RX Power</label>
                            <input type="text" class="form-control form-control-sm" id="edit_onu_rx" name="onu_rx_power"
                                pattern="-?[0-9.]+" placeholder="-27.5">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Distancia Drop (m)</label>
                            <input type="number" step="1" class="form-control form-control-sm" id="edit_drop"
                                name="distancia_drop" placeholder="50">
                        </div>
                    </div>
                    <div class="row g-3 mb-4" id="edit_campos_radio">
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Punto de Acceso</label>
                            <input type="text" class="form-control form-control-sm" id="edit_pa" name="punto_acceso">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Valor Conexión (dBm)</label>
                            <input type="text" class="form-control form-control-sm" id="edit_dbm"
                                name="valor_conexion_dbm" pattern="-?[0-9.]+" placeholder="-55.0">
                        </div>
                    </div>

                    <!-- SECCIÓN: FIRMAS -->
                    <div class="section-title bg-light p-2 fw-bold border-start border-danger border-4 mb-3 mt-4">
                        <i class="fa-solid fa-signature me-2 text-danger"></i>Firmas Digitales
                    </div>
                    <div class="row g-4 mb-2">
                        <!-- Firma Cliente -->
                        <div class="col-md-6">
                            <label class="form-label small fw-bold d-block">Firma del Cliente</label>
                            <div id="prev_firma_cliente_div" class="mb-2">
                                <img id="prev_firma_cliente" src="" alt="Firma Cliente" class="signature-prev d-none">
                                <span id="no_firma_cliente" class="badge bg-secondary d-none">Sin firma</span>
                            </div>

                            <div id="pad_firma_cliente_div" class="d-none">
                                <canvas id="edit_sigCliente" class="signature-pad"></canvas>
                                <button type="button" class="btn btn-sm btn-outline-secondary mt-1"
                                    onclick="clearEditPad('cliente')">
                                    <i class="fa-solid fa-eraser me-1"></i>Limpiar Pad
                                </button>
                                <input type="hidden" name="firma_cliente_data" id="edit_firma_cliente_data">
                            </div>

                            <button type="button" class="btn btn-sm btn-outline-primary mt-1"
                                id="btnCambiarFirmaCliente">
                                <i class="fa-solid fa-pen me-1"></i>Cambiar Firma
                            </button>
                        </div>

                        <!-- Firma Técnico -->
                        <div class="col-md-6">
                            <label class="form-label small fw-bold d-block">Firma del Técnico</label>
                            <div id="prev_firma_tecnico_div" class="mb-2">
                                <img id="prev_firma_tecnico" src="" alt="Firma Técnico" class="signature-prev d-none">
                                <span id="no_firma_tecnico" class="badge bg-secondary d-none">Sin firma</span>
                            </div>

                            <div id="pad_firma_tecnico_div" class="d-none">
                                <canvas id="edit_sigTecnico" class="signature-pad"></canvas>
                                <button type="button" class="btn btn-sm btn-outline-secondary mt-1"
                                    onclick="clearEditPad('tecnico')">
                                    <i class="fa-solid fa-eraser me-1"></i>Limpiar Pad
                                </button>
                                <input type="hidden" name="firma_tecnico_data" id="edit_firma_tecnico_data">
                            </div>

                            <button type="button" class="btn btn-sm btn-outline-primary mt-1"
                                id="btnCambiarFirmaTecnico">
                                <i class="fa-solid fa-pen me-1"></i>Cambiar Firma
                            </button>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" id="btnGuardarContrato" disabled>
                    <i class="fa-solid fa-floppy-disk me-1"></i>Guardar Cambios
                </button>
            </div>
        </div>
    </div>
</div>
<!-- ===== FIN MODAL EDITAR CONTRATO ===== -->


<script src="<?php echo $path_to_root; ?>js/jquery.min.js"></script>
<script src="<?php echo $path_to_root; ?>js/datatables.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/exceljs/4.3.0/exceljs.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/signature_pad@4.1.5/dist/signature_pad.umd.min.js"></script>

<script>
    // === LÓGICA DE FIRMAS PARA EL MODAL DE EDICIÓN ===
    let padEditCliente, padEditTecnico;

    function resizeEditPad(canvas, pad) {
        if (!canvas || !pad) return;
        var ratio = Math.max(window.devicePixelRatio || 1, 1);
        canvas.width = canvas.offsetWidth * ratio;
        canvas.height = canvas.offsetHeight * ratio;
        canvas.getContext("2d").scale(ratio, ratio);
        pad.clear(); // SignaturePad needs clearing after resize to reset its internal state
    }

    function initEditSignaturePads() {
        const canvasCliente = document.getElementById('edit_sigCliente');
        const canvasTecnico = document.getElementById('edit_sigTecnico');

        if (!canvasCliente || !canvasTecnico) return;

        if (!padEditCliente) padEditCliente = new SignaturePad(canvasCliente);
        if (!padEditTecnico) padEditTecnico = new SignaturePad(canvasTecnico);
    }

    function clearEditPad(type) {
        if (type === 'cliente') padEditCliente.clear();
        if (type === 'tecnico') padEditTecnico.clear();
    }

    function resetEditPadsWorkflows() {
        // Ocultar pads y mostrar previas
        $('#pad_firma_cliente_div').addClass('d-none');
        $('#prev_firma_cliente_div').removeClass('d-none');
        $('#btnCambiarFirmaCliente').removeClass('d-none');
        $('#edit_firma_cliente_data').val('');

        $('#pad_firma_tecnico_div').addClass('d-none');
        $('#prev_firma_tecnico_div').removeClass('d-none');
        $('#btnCambiarFirmaTecnico').removeClass('d-none');
        $('#edit_firma_tecnico_data').val('');

        if (padEditCliente) padEditCliente.clear();
        if  (padEditTecnico) padEditTecnico.clear();
    }

    $(document).ready(function () {
        // Inicializar al abrir el modal 
        $('#modalEditarContrato').on('shown.bs.modal', function () {
            initEditSignaturePads();
        });

        $('#btnCambiarFirmaCliente').on('click', function () {
            $('#prev_firma_cliente_div').addClass('d-none');
            $('#pad_firma_cliente_div').removeClass('d-none');
            $(this).addClass('d-none');
            
            // Forzar resize una vez visible
            const canvas = document.getElementById('edit_sigCliente');
            resizeEditPad(canvas, padEditCliente);
        });

        $('#btnCambiarFirmaTecnico').on('click', function () {
            $('#prev_firma_tecnico_div').addClass('d-none');
            $('#pad_firma_tecnico_div').removeClass('d-none');
            $(this).addClass('d-none');
            
            // Forzar resize una vez visible
            const canvas = document.getElementById('edit_sigTecnico');
            resizeEditPad(canvas, padEditTecnico);
        });
    });

    // Modificar el submit para capturar la data
    function prepareSignaturesForSubmit() {
        if (padEditCliente && !padEditCliente.isEmpty() && !$('#pad_firma_cliente_div').hasClass('d-none')) {
            $('#edit_firma_cliente_data').val(padEditCliente.toDataURL());
        }
        if (padEditTecnico && !padEditTecnico.isEmpty() && !$('#pad_firma_tecnico_div').hasClass('d-none')) {
            $('#edit_firma_tecnico_data').val(padEditTecnico.toDataURL());
        }
    }
</script>

<script>
    // --- EXPORTAR EXCEL ---
    async function exportExcel() {
        // Mostrar mensaje de carga
        Swal.fire({
            title: 'Generando Excel...',
            html: 'Obteniendo datos del servidor...',
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });

        try {
            // 1. Obtener TODOS los datos crudosdesde el backend
            const response = await fetch('get_all_contratos_json.php');
            if (!response.ok) throw new Error('Error de red al obtener datos');
            const data = await response.json();

            if (!data || data.length === 0) {
                throw new Error('No hay datos para exportar.');
            }

            // 2. Crear Workbook
            const workbook = new ExcelJS.Workbook();
            const worksheet = workbook.addWorksheet('Contratos');

            // 3. Definir Columnas y Encabezados (Mismo orden que el array $colMap en importación)
            worksheet.columns = [
                { header: 'ID', key: 'id_contrato', width: 10 },
                { header: 'SAR', key: 'fecha_registro', width: 15 },
                { header: 'Cédula', key: 'cedula_cliente', width: 15 },
                { header: 'Cliente', key: 'nombre_cliente', width: 25 },
                { header: 'Municipio', key: 'nombre_municipio', width: 15 },
                { header: 'Parroquia', key: 'nombre_parroquia', width: 15 },
                { header: 'Dirección', key: 'direccion_instalacion', width: 30 },
                { header: 'Telf. 1', key: 'telefono_cliente', width: 15 },
                { header: 'Telf. 2', key: 'telefono_extra', width: 15 },
                { header: 'Correo', key: 'email_cliente', width: 25 },
                { header: 'Correo (Alt)', key: 'email_extra', width: 25 },
                { header: 'F. Instalación', key: 'fecha_instalacion', width: 15 },
                { header: 'Medio Pago', key: 'metodo_pago', width: 15 },
                { header: 'Monto Pagar', key: 'costo_instalacion', width: 15 },
                { header: 'Monto Pagado', key: 'monto_pagado', width: 15 },
                { header: 'Días Prorrateo', key: 'dias_prorrateo', width: 10 },
                { header: 'Monto Prorr. ($)', key: 'monto_prorrateo', width: 15 },
                { header: 'Observ.', key: 'observaciones', width: 30 },
                { header: 'Tipo Conex.', key: 'tipo_conexion', width: 15 },
                { header: 'Num. ONU', key: 'numero_onu', width: 15 },
                { header: 'MAC/Serial', key: 'mac_serial', width: 20 },
                { header: 'IP ONU', key: 'ip_onu', width: 15 },
                { header: 'Caja NAP', key: 'caja_nap', width: 10 },
                { header: 'Puerto NAP', key: 'puerto_nap', width: 10 },
                { header: 'NAP TX (dBm)', key: 'potencia_nap_tx', width: 10 },
                { header: 'ONU RX (dBm)', key: 'potencia_onu_rx', width: 10 },
                { header: 'Dist. Drop (m)', key: 'distancia_drop', width: 10 },
                { header: 'Instalador', key: 'id_instalador', width: 15 },
                { header: 'Evidencia Fibra', key: 'evidencia_foto_fibra', width: 20 },
                { header: 'IP Servicio', key: 'ip_servicio', width: 15 },
                { header: 'Punto Acceso', key: 'punto_acceso', width: 15 },
                { header: 'Val. Conex. (dBm)', key: 'valor_conexion', width: 10 },
                { header: 'Precinto ODN', key: 'precinto_odn', width: 15 },
                { header: 'Vendedor (Edit)', key: 'id_vendedor', width: 15 },
                { header: 'SAE Plus (Edit)', key: 'codigo_sae_plus', width: 15 },
                { header: 'Plan', key: 'nombre_plan', width: 15 },
                { header: 'OLT', key: 'nombre_olt', width: 15 },
                { header: 'PON', key: 'nombre_pon', width: 10 },
                { header: 'Estado', key: 'status', width: 10 }
            ];

            // Estilo Header
            const headerRow = worksheet.getRow(1);
            headerRow.eachCell((cell) => {
                cell.fill = {
                    type: 'pattern',
                    pattern: 'solid',
                    fgColor: { argb: '003366' }
                };
                cell.font = {
                    color: { argb: 'FFFFFF' },
                    bold: true
                };
                cell.alignment = { horizontal: 'center' };
            });

            // 4. Agregar filas directamente (ya vienen limpias del SQL/JSON)
            // Solo necesitamos asegurarnos que las keys coincidan
            worksheet.addRows(data);

            // Generar archivo
            const buffer = await workbook.xlsx.writeBuffer();
            const blob = new Blob([buffer], { type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' });
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = 'Contratos_Wireless_' + new Date().toISOString().split('T')[0] + '.xlsx';
            a.click();
            window.URL.revokeObjectURL(url);

            Swal.close();

        } catch (error) {
            console.error("Error exportando Excel:", error);
            Swal.fire('Error', 'Hubo un problema al generar el Excel: ' + error.message, 'error');
        }
    }

    // Configuración DataTables original
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
        let idEliminar = null;
        window.confirmarEliminar = function (id) {
            idEliminar = id;
            $('#delete_password').val(''); // Limpiar anterior
            var modal = new bootstrap.Modal(document.getElementById('eliminaModal'));
            modal.show();
        };

        $('#btnConfirmarEliminar').on('click', function () {
            const password = $('#delete_password').val();
            if (!password) {
                Swal.fire('Atención', 'Debe ingresar su contraseña para confirmar la eliminación.', 'warning');
                return;
            }

            Swal.fire({
                title: 'Eliminando...',
                allowOutsideClick: false,
                didOpen: () => { Swal.showLoading(); }
            });

            $.ajax({
                url: 'elimina.php',
                type: 'POST',
                data: { id: idEliminar, clave: password },
                dataType: 'json',
                success: function (resp) {
                    if (resp.success) {
                        Swal.fire('Eliminado', resp.message, 'success');
                        $('#eliminaModal').modal('hide');
                        table.ajax.reload(null, false); // Recargar sin resetear paginación
                    } else {
                        Swal.fire('Error', resp.message, 'error');
                    }
                },
                error: function () {
                    Swal.fire('Error', 'Hubo un problema de conexión con el servidor.', 'error');
                }
            });
        });

        // Escuchar cambio en filtro de vacios
        $('#filter_empty').on('change', function () {
            table.draw();
        });

        // ========================================================
        // MODAL EDITAR CONTRATO
        // ========================================================

        // Helper Location Logic JSON
        let editUbicacionesData = [];
        $.get('api_ubicaciones.php', function (data) {
            editUbicacionesData = data;
            let options = '<option value="">-- Seleccione Municipio --</option>';
            editUbicacionesData.forEach(function (m) {
                options += `<option value="${m.municipio}">${m.municipio}</option>`;
            });
            $('#edit_municipio').html(options);
        });

        // Helper: load parroquias into #edit_parroquia
        function editLoadParroquias(munName, selectedParName, onDone) {
            var $par = $('#edit_parroquia');
            $par.html('<option value="">Cargando...</option>').prop('disabled', true);
            $('#edit_comunidad').html('<option value="">-- Seleccione parroquia --</option>').prop('disabled', true);

            if (!munName) {
                $par.html('<option value="">-- Seleccione municipio --</option>');
                if (onDone) onDone();
                return;
            }

            let options = '<option value="">-- Seleccione parroquia --</option>';
            const munObj = editUbicacionesData.find(m => m.municipio === munName);
            if (munObj && munObj.parroquias) {
                munObj.parroquias.forEach(p => {
                    options += `<option value="${p.nombre}">${p.nombre}</option>`;
                });
            }
            $par.html(options).prop('disabled', false);

            if (selectedParName) {
                $par.val(selectedParName);
                editLoadComunidades(selectedParName, null, onDone);
            } else if (onDone) onDone();
        }

        // Helper: load comunidades into #edit_comunidad
        function editLoadComunidades(parName, selectedComName, onDone) {
            var $com = $('#edit_comunidad');
            $com.html('<option value="">Cargando...</option>').prop('disabled', true);

            if (!parName) {
                $com.html('<option value="">-- Seleccione parroquia --</option>');
                if (onDone) onDone();
                return;
            }

            const munName = $('#edit_municipio').val();
            let options = '<option value="">-- Seleccione comunidad --</option>';
            const munObj = editUbicacionesData.find(m => m.municipio === munName);
            if (munObj && munObj.parroquias) {
                const parObj = munObj.parroquias.find(p => p.nombre === parName);
                if (parObj && parObj.comunidades) {
                    parObj.comunidades.forEach(c => {
                        options += `<option value="${c}">${c}</option>`;
                    });
                }
            }
            $com.html(options).prop('disabled', false);

            if (selectedComName) $com.val(selectedComName);
            if (onDone) onDone();
        }

        // Helper: load PONs into #edit_pon
        function editLoadPons(oltId, selectedPonId, onDone) {
            var $pon = $('#edit_pon');
            $pon.html('<option value="">Cargando...</option>').prop('disabled', true);
            if (!oltId) { $pon.html('<option value="">-- Seleccione OLT --</option>'); if (onDone) onDone(); return; }
            $.get('gets_pon_by_olt.php', { id_olt: oltId }, function (resp) {
                $pon.html('<option value="">-- Seleccione PON --</option>').prop('disabled', false);
                if (resp.pons) {
                    $.each(resp.pons, function (i, p) {
                        $pon.append('<option value="' + p.id_pon + '">' + p.nombre_pon + '</option>');
                    });
                }
                if (selectedPonId) $pon.val(selectedPonId);
                if (onDone) onDone();
            }, 'json').fail(function () { $pon.html('<option value="">Error al cargar</option>'); if (onDone) onDone(); });
        }

        // Cascading selects: Municipio -> Parroquia -> Comunidad
        $('#edit_municipio').on('change', function () {
            editLoadParroquias($(this).val(), null, null);
        });
        $('#edit_parroquia').on('change', function () {
            editLoadComunidades($(this).val(), null, null);
        });
        $('#edit_olt').on('change', function () {
            editLoadPons($(this).val(), null, null);
        });

        // Tipo conexion visibility
        $('#edit_tipo_conexion').on('change', function () {
            var t = $(this).val();
            $('#edit_campos_ftth, #edit_campos_radio').hide();
            if (t && t.includes('FTTH')) $('#edit_campos_ftth').show();
            else if (t && t.includes('RADIO')) $('#edit_campos_radio').show();
        });

        // Open modal and populate with data
        window.abrirModalEdicion = function (id) {
            // Reset state
            $('#editContratoAlert').addClass('d-none').removeClass('alert-danger alert-success').html('');
            $('#editContratoSpinner').show();
            $('#formEditarContrato').addClass('d-none');
            $('#btnGuardarContrato').prop('disabled', true);
            $('#editContratoId').text(id);

            var modal = new bootstrap.Modal(document.getElementById('modalEditarContrato'));
            modal.show();

            $.getJSON('get_contrato_detalle.php?id=' + id, function (d) {
                if (d.error) {
                    $('#editContratoSpinner').hide();
                    $('#editContratoAlert').removeClass('d-none').addClass('alert-danger').html('Error: ' + d.error);
                    return;
                }

                // Populate simple fields
                $('#edit_id').val(d.id);
                $('#edit_cedula').val(d.cedula);
                $('#edit_nombre').val(d.nombre_completo);
                $('#edit_telefono').val(d.telefono);
                $('#edit_telefono2').val(d.telefono_secundario);
                $('#edit_correo').val(d.correo);
                $('#edit_correo2').val(d.correo_adicional);
                $('#edit_fecha').val(d.fecha_instalacion);
                $('#edit_direccion').val(d.direccion);
                $('#edit_estado').val(d.estado);
                $('#edit_obs').val(d.observaciones);

                // --- FIRMAS ---
                resetEditPadsWorkflows();

                // Cliente
                if (d.firma_cliente) {
                    $('#prev_firma_cliente').attr('src', '../../uploads/firmas/' + d.firma_cliente).removeClass('d-none');
                    $('#no_firma_cliente').addClass('d-none');
                } else {
                    $('#prev_firma_cliente').addClass('d-none');
                    $('#no_firma_cliente').removeClass('d-none');
                }

                // Técnico
                if (d.firma_tecnico) {
                    $('#prev_firma_tecnico').attr('src', '../../uploads/firmas/' + d.firma_tecnico).removeClass('d-none');
                    $('#no_firma_tecnico').addClass('d-none');
                } else {
                    $('#prev_firma_tecnico').addClass('d-none');
                    $('#no_firma_tecnico').removeClass('d-none');
                }
                $('#edit_plan').val(d.id_plan);
                $('#edit_vendedor').val(d.id_vendedor);
                $('#edit_nap').val(d.ident_caja_nap);
                $('#edit_puerto_nap').val(d.puerto_nap);
                $('#edit_odn').val(d.num_presinto_odn);
                $('#edit_mac').val(d.mac_onu);
                $('#edit_ip_onu').val(d.ip_onu);
                $('#edit_nap_tx').val(d.nap_tx_power);
                $('#edit_onu_rx').val(d.onu_rx_power);
                $('#edit_drop').val(d.distancia_drop);
                $('#edit_pa').val(d.punto_acceso);
                $('#edit_dbm').val(d.valor_conexion_dbm);
                $('#edit_obs').val(d.observaciones);

                // Tipo conexion
                $('#edit_tipo_conexion').val(d.tipo_conexion).trigger('change');

                // Cascading location (Using Text Names from Join)
                $('#edit_municipio').val(d.nombre_municipio);
                editLoadParroquias(d.nombre_municipio, d.nombre_parroquia, function () {
                    if (d.nombre_comunidad) {
                        editLoadComunidades(d.nombre_parroquia, d.nombre_comunidad, function () {
                            // Finally OLT
                            finishLoadingModal();
                        });
                    } else {
                        finishLoadingModal();
                    }
                });

                function finishLoadingModal() {
                    $('#edit_olt').val(d.id_olt);
                    editLoadPons(d.id_olt, d.id_pon, function () {
                        $('#editContratoSpinner').hide();
                        $('#formEditarContrato').removeClass('d-none');
                        $('#btnGuardarContrato').prop('disabled', false);
                    });
                }
            }).fail(function () {
                $('#editContratoSpinner').hide();
                $('#editContratoAlert').removeClass('d-none').addClass('alert-danger').html('Error al cargar los datos del contrato.');
            });
        };

        // Double-click on table row to open edit modal
        $('#mitabla tbody').on('dblclick', 'tr', function () {
            var rowData = table.row(this).data();
            if (rowData && rowData[0]) {
                abrirModalEdicion(rowData[0]);
            }
        });

        // ========================================================
        // VALIDACIÓN DE CAMPOS EN MODAL (RERQUERIMIENTO USUARIO)
        // ========================================================
        $('#edit_cedula').on('input', function () {
            let val = $(this).val().toUpperCase().replace(/[^VJEGP0-9]/g, '');
            $(this).val(val);
        });

        $('#edit_ip, #edit_ip_onu').on('input', function () {
            let val = $(this).val().replace(/[^0-9.]/g, '');
            $(this).val(val);
        });

        $('#edit_nombre').on('input', function () {
            let val = $(this).val().replace(/[^A-Za-zñÑáéíóúÁÉÍÓÚ\s]/g, '');
            $(this).val(val);
        });

        $('#edit_telefono, #edit_telefono2').on('input', function () {
            let val = $(this).val().replace(/[^0-9-+\s]/g, '');
            $(this).val(val);
        });

        $('#edit_mac').on('input', function () {
            let val = $(this).val().toUpperCase().replace(/[^A-F0-9:.-]/g, '');
            $(this).val(val);
        });

        $('#edit_nap_tx, #edit_onu_rx, #edit_dbm').on('input', function () {
            let val = $(this).val().replace(/[^0-9.-]/g, '');
            // Only allow one '-' at the beginning
            if (val.indexOf('-') > 0) val = val.substring(0, val.indexOf('-')) + val.substring(val.indexOf('-') + 1);
            $(this).val(val);
        });

        // Save via AJAX
        $('#btnGuardarContrato').on('click', function () {
            var $btn = $(this).prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span>Guardando...');
            prepareSignaturesForSubmit();
            const formData = new FormData(document.getElementById('formEditarContrato'));

            $.ajax({
                url: 'actualizar_contrato_ajax.php',
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                dataType: 'json',
                success: function (res) {
                    var $alert = $('#editContratoAlert').removeClass('d-none alert-danger alert-success');
                    if (res.success) {
                        $alert.addClass('alert-success').html('<i class="fa-solid fa-check-circle me-1"></i>' + res.message);
                        table.ajax.reload(null, false); // Reload table without pagination reset
                        setTimeout(() => { bootstrap.Modal.getInstance(document.getElementById('modalEditarContrato')).hide(); }, 1200);
                    } else {
                        $alert.addClass('alert-danger').html('<i class="fa-solid fa-circle-exclamation me-1"></i>' + res.message);
                    }
                },
                error: function () {
                    $('#editContratoAlert').removeClass('d-none').addClass('alert-danger').html('Error de conexión con el servidor.');
                },
                complete: function () {
                    $('#btnGuardarContrato').prop('disabled', false).html('<i class="fa-solid fa-floppy-disk me-1"></i>Guardar Cambios');
                }
            });
        });

        // ========================================================
        // GESTIÓN DE FIRMA REMOTA
        // ========================================================
        window.gestionarFirma = function (id, token, estado) {
            const baseUrl = window.location.origin + '/sistemas-administrativo-tecnico-wireless/paginas/soporte/firmar_remoto.php';

            if (token && estado === 'PENDIENTE') {
                const link = `${baseUrl}?token=${token}&type=contrato`;

                Swal.fire({
                    title: 'Enlace de Firma Pendiente',
                    html: `
                    <p>Existe un proceso de firma activo para este contrato.</p>
                    <div class="input-group mb-3">
                        <input type="text" id="linkFirma" class="form-control" value="${link}" readonly>
                        <button class="btn btn-outline-primary" onclick="copiarLinkFirma()">
                            <i class="fa-solid fa-copy"></i> Copiar
                        </button>
                    </div>
                    <p class="small text-muted">Envía este link al cliente por WhatsApp.</p>
                `,
                    showCancelButton: true,
                    confirmButtonText: '<i class="fa-solid fa-rotate"></i> Regenerar Link',
                    confirmButtonColor: '#ffc107',
                    cancelButtonText: 'Cerrar'
                }).then((result) => {
                    if (result.isConfirmed) {
                        solicitarNuevoToken(id);
                    }
                });
            } else {
                const msj = (estado === 'COMPLETADO') ? 'Este contrato ya ha sido firmado.' : 'No existe un link de firma activo para este contrato.';

                Swal.fire({
                    title: 'Gestión de Firma',
                    text: msj,
                    icon: (estado === 'COMPLETADO') ? 'success' : 'info',
                    showCancelButton: true,
                    confirmButtonText: '<i class="fa-solid fa-plus"></i> Generar Nuevo Link',
                    cancelButtonText: 'Cerrar'
                }).then((result) => {
                    if (result.isConfirmed) {
                        solicitarNuevoToken(id);
                    }
                });
            }
        };

        window.solicitarNuevoToken = function (id) {
            Swal.fire({
                title: 'Generando...',
                allowOutsideClick: false,
                didOpen: () => { Swal.showLoading(); }
            });

            $.post('generar_token_firma.php', { id: id }, function (resp) {
                if (resp.success) {
                    const baseUrl = window.location.origin + '/sistemas-administrativo-tecnico-wireless/paginas/soporte/firmar_remoto.php';
                    const link = `${baseUrl}?token=${resp.token}&type=contrato`;

                    Swal.fire({
                        title: '¡Link Generado!',
                        html: `
                        <p>Se ha creado un nuevo enlace de firma:</p>
                        <div class="input-group mb-3">
                            <input type="text" id="linkFirma" class="form-control" value="${link}" readonly>
                            <button class="btn btn-outline-primary" onclick="copiarLinkFirma()">
                                <i class="fa-solid fa-copy"></i> Copiar
                            </button>
                        </div>
                    `,
                        icon: 'success',
                        confirmButtonText: 'Listo'
                    }).then(() => {
                        // Recargar tabla para que el nuevo token se refleje en el botón
                        $('#mitabla').DataTable().ajax.reload(null, false);
                    });
                } else {
                    Swal.fire('Error', resp.message, 'error');
                }
            }, 'json').fail(function () {
                Swal.fire('Error', 'Error de comunicación con el servidor', 'error');
            });
        };

        window.copiarLinkFirma = function () {
            const input = document.getElementById('linkFirma');
            if (!input) return;
            input.select();
            input.setSelectionRange(0, 99999);
            navigator.clipboard.writeText(input.value).then(() => {
                Swal.fire({
                    toast: true,
                    position: 'top-end',
                    icon: 'success',
                    title: 'Copiado al portapapeles',
                    showConfirmButton: false,
                    timer: 2000
                });
            });
        };

    });
</script>

<?php require_once '../includes/layout_foot.php'; ?>

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
                    <div class="d-flex gap-1">
                        <button class="btn btn-sm btn-outline-primary py-0 px-2" onclick="editMunicipio(event, ${index})" title="Editar"><i class="fa-solid fa-pencil"></i></button>
                        <button class="btn btn-sm btn-danger py-0 px-2" onclick="deleteMunicipio(event, ${index})" title="Eliminar"><i class="fa-solid fa-times"></i></button>
                    </div>
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
                    <div class="d-flex gap-1">
                        <button class="btn btn-sm btn-outline-primary py-0 px-2" onclick="editParroquia(${pIndex})" title="Editar"><i class="fa-solid fa-pencil"></i></button>
                        <button class="btn btn-sm btn-outline-danger py-0 px-2" onclick="deleteParroquia(${pIndex})" title="Eliminar"><i class="fa-solid fa-trash-can"></i></button>
                    </div>
                </div>
            `;
            list.append(item);
        });
    };

    // Helper: verify password before sensitive action
    async function verificarClave(nombreElemento) {
        console.log("Iniciando verificación para:", nombreElemento);
        const { value: clave, isConfirmed } = await Swal.fire({
            target: document.getElementById('modalUbicaciones'),
            title: `Eliminar "${nombreElemento}"`,
            html: '<p class="text-muted small mb-2">Ingrese su contraseña administrativa para confirmar.</p>'
                + '<input id="swal-clave" type="password" class="swal2-input" placeholder="Contraseña">',
            icon: 'warning',
            showCancelButton: true,
            cancelButtonText: 'Cancelar',
            confirmButtonColor: '#d33',
            confirmButtonText: 'Eliminar',
            focusConfirm: false,
            preConfirm: () => {
                const c = document.getElementById('swal-clave').value;
                if (!c) {
                    Swal.showValidationMessage('La contraseña es requerida');
                    return false;
                }
                return c;
            }
        });

        console.log("Swal result:", { isConfirmed, hasClave: !!clave });
        if (!isConfirmed || !clave) return false;

        try {
            console.log("Enviando petición a verificar_clave.php...");
            const resp = await fetch('verificar_clave.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'clave=' + encodeURIComponent(clave)
            });
            const data = await resp.json();
            console.log("Respuesta servidor:", data);
            if (!data.success) {
                Swal.fire({ target: document.getElementById('modalUbicaciones'), title: 'Error', text: data.message, icon: 'error' });
                return false;
            }
            return true;
        } catch (err) {
            console.error("Error en verificarClave:", err);
            Swal.fire({ target: document.getElementById('modalUbicaciones'), title: 'Error', text: 'Error al verificar la contraseña: ' + err.message, icon: 'error' });
            return false;
        }
    }

    window.deleteMunicipio = async function (e, index) {
        e.stopPropagation();
        const nombre = ubicacionesData[index].municipio;

        // 1. Check usage first
        try {
            const usageResp = await fetch(`verificar_uso_ubicacion.php?tipo=municipio&nombre=${encodeURIComponent(nombre)}`);
            const usageData = await usageResp.json();
            if (usageData.usage > 0) {
                const { isConfirmed } = await Swal.fire({
                    target: document.getElementById('modalUbicaciones'),
                    title: '¿Eliminar Municipio en Uso?',
                    text: `El municipio "${nombre}" está asignado a ${usageData.usage} contratos. Al eliminarlo de aquí, ya no podrá seleccionarse en nuevos contratos ni ediciones, pero los registros existentes mantendrán su dato actual. ¿Desea continuar?`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Continuar',
                    cancelButtonText: 'Cancelar',
                    confirmButtonColor: '#f89406'
                });
                if (!isConfirmed) return;
            }
        } catch (err) { console.error("Error validando uso:", err); }

        const ok = await verificarClave(nombre + ' y todas sus parroquias');
        if (!ok) return;

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
        Swal.fire({ target: document.getElementById('modalUbicaciones'), title: 'Eliminado', icon: 'success', timer: 1200, showConfirmButton: false });
    };

    window.deleteParroquia = async function (pIndex) {
        const nombre = ubicacionesData[selectedMunicipioIndex].parroquias[pIndex];

        // 1. Check usage first
        try {
            const usageResp = await fetch(`verificar_uso_ubicacion.php?tipo=parroquia&nombre=${encodeURIComponent(nombre)}`);
            const usageData = await usageResp.json();
            if (usageData.usage > 0) {
                const { isConfirmed } = await Swal.fire({
                    target: document.getElementById('modalUbicaciones'),
                    title: '¿Eliminar Parroquia en Uso?',
                    text: `La parroquia "${nombre}" está asignada a ${usageData.usage} contratos. ¿Desea continuar con la eliminación de esta opción?`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Continuar',
                    cancelButtonText: 'Cancelar',
                    confirmButtonColor: '#f89406'
                });
                if (!isConfirmed) return;
            }
        } catch (err) { console.error("Error validando uso:", err); }

        const ok = await verificarClave(nombre);
        if (!ok) return;

        ubicacionesData[selectedMunicipioIndex].parroquias.splice(pIndex, 1);
        saveData();
        renderParroquias();
        Swal.fire({ target: document.getElementById('modalUbicaciones'), title: 'Eliminado', icon: 'success', timer: 1200, showConfirmButton: false });
    };

    window.editMunicipio = async function (e, index) {
        e.stopPropagation();
        const { value: nuevoNombre, isConfirmed } = await Swal.fire({
            target: document.getElementById('modalUbicaciones'),
            title: 'Editar Municipio',
            input: 'text',
            inputLabel: 'Nuevo nombre del municipio',
            inputValue: ubicacionesData[index].municipio,
            showCancelButton: true,
            cancelButtonText: 'Cancelar',
            confirmButtonText: 'Guardar',
            inputValidator: (v) => { if (!v || !v.trim()) return 'El nombre no puede estar vacío'; }
        });
        if (!isConfirmed || !nuevoNombre) return;
        ubicacionesData[index].municipio = nuevoNombre.trim();
        saveData();
        renderMunicipios();
        Swal.fire({ target: document.getElementById('modalUbicaciones'), title: 'Actualizado', icon: 'success', timer: 1000, showConfirmButton: false });
    };

    window.editParroquia = async function (pIndex) {
        const nombre = ubicacionesData[selectedMunicipioIndex].parroquias[pIndex];
        const { value: nuevoNombre, isConfirmed } = await Swal.fire({
            target: document.getElementById('modalUbicaciones'),
            title: 'Editar Parroquia',
            input: 'text',
            inputLabel: 'Nuevo nombre de la parroquia',
            inputValue: nombre,
            showCancelButton: true,
            cancelButtonText: 'Cancelar',
            confirmButtonText: 'Guardar',
            inputValidator: (v) => { if (!v || !v.trim()) return 'El nombre no puede estar vacío'; }
        });
        if (!isConfirmed || !nuevoNombre) return;
        ubicacionesData[selectedMunicipioIndex].parroquias[pIndex] = nuevoNombre.trim();
        saveData();
        renderParroquias();
        Swal.fire({ target: document.getElementById('modalUbicaciones'), title: 'Actualizado', icon: 'success', timer: 1000, showConfirmButton: false });
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

        // --- IMPORTAR EXCEL (AJAX) ---
        $('#formImportExcel').on('submit', function (e) {
            e.preventDefault();

            // Mostrar loading
            Swal.fire({
                title: 'Importando...',
                html: 'Procesando archivo, por favor espere.',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            const formData = new FormData(this);

            fetch('importar_excel_contratos.php', {
                method: 'POST',
                body: formData
            })
                .then(response => response.json())
                .then(data => {
                    Swal.close();
                    if (data.success) {
                        Swal.fire({
                            title: 'Importación Exitosa',
                            html: `<p>${data.message}</p>
                               <ul class="text-start">
                                   <li>Actualizados: <b>${data.stats.updated}</b></li>
                                   <li>Nuevos: <b>${data.stats.inserted}</b></li>
                                   <li>Errores: <b class="text-danger">${data.stats.errors}</b></li>
                               </ul>`,
                            icon: 'success'
                        }).then(() => {
                            // Recargar la tabla o la página para ver cambios
                            location.reload();
                        });
                        $('#modalImportExcel').modal('hide');
                        $('#formImportExcel')[0].reset();
                    } else {
                        Swal.fire('Error', data.message, 'error');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    Swal.fire('Error', 'Error de conexión al importar.', 'error');
                });
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