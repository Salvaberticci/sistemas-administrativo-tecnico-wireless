<?php
/**
 * Gestión de Fallas Técnicas - Dashboard Principal
 * Módulo completo con estadísticas, filtros y exportación PDF
 */
$path_to_root = "../../";
$page_title = "Gestión de Fallas Técnicas";
require_once $path_to_root . 'paginas/conexion.php';
require_once $path_to_root . 'paginas/includes/layout_head.php';
require_once $path_to_root . 'paginas/includes/sidebar.php';
?>

<link href="<?php echo $path_to_root; ?>css/datatables.min.css" rel="stylesheet">
<style>
    .stat-card {
        border-left: 4px solid;
        transition: transform 0.2s;
    }

    .stat-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 16px rgba(0, 0, 0, 0.1);
    }

    .stat-card.primary {
        border-left-color: #0d6efd;
    }

    .stat-card.warning {
        border-left-color: #ffc107;
    }

    .stat-card.success {
        border-left-color: #198754;
    }

    .stat-card.danger {
        border-left-color: #dc3545;
    }

    .filter-section {
        background: #f8f9fa;
        border-radius: 8px;
        padding: 20px;
        margin-bottom: 20px;
    }

    .chart-container {
        position: relative;
        height: 300px;
        margin-bottom: 20px;
    }
</style>

<main class="main-content">
    <?php include $path_to_root . 'paginas/includes/header.php'; ?>

    <div class="page-content">
        <!-- Header -->
        <div class="row mb-4">
            <div class="col-12">
                <h2 class="h3 fw-bold text-primary mb-1">
                    <i class="fa-solid fa-chart-line me-2"></i>Gestión de Fallas Técnicas
                </h2>
                <p class="text-muted">Dashboard de análisis y estadísticas de reportes técnicos</p>
            </div>
        </div>

        <!-- KPI Cards -->
        <div class="row g-3 mb-4" id="kpiCards">
            <div class="col-md-6 col-lg-3">
                <div class="card stat-card primary border-0 shadow-sm h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <p class="text-muted mb-1 small">Total Reportes</p>
                                <h3 class="fw-bold mb-0" id="total_reportes">
                                    <span class="spinner-border spinner-border-sm"></span>
                                </h3>
                            </div>
                            <div class="text-primary">
                                <i class="fa-solid fa-file-lines fa-2x opacity-75"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-6 col-lg-3">
                <div class="card stat-card warning border-0 shadow-sm h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <p class="text-muted mb-1 small">Pendientes Pago</p>
                                <h3 class="fw-bold mb-0" id="reportes_pendientes">
                                    <span class="spinner-border spinner-border-sm"></span>
                                </h3>
                            </div>
                            <div class="text-warning">
                                <i class="fa-solid fa-clock fa-2x opacity-75"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-6 col-lg-3">
                <div class="card stat-card success border-0 shadow-sm h-100">
                    <div class="card-body">
                        <div>
                            <p class="text-muted mb-1 small">Total Facturado</p>
                            <h4 class="fw-bold mb-0 text-success" id="total_facturado">
                                <span class="spinner-border spinner-border-sm"></span>
                            </h4>
                            <p class="text-muted small mb-0">Cobrado: <span class="fw-bold" id="total_cobrado">$0</span>
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-6 col-lg-3">
                <div class="card stat-card danger border-0 shadow-sm h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <p class="text-muted mb-1 small">Saldo Pendiente</p>
                                <h4 class="fw-bold mb-0 text-danger" id="saldo_pendiente">
                                    <span class="spinner-border spinner-border-sm"></span>
                                </h4>
                            </div>
                            <div class="text-danger">
                                <i class="fa-solid fa-exclamation-triangle fa-2x opacity-75"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filtros -->
        <div class="filter-section">
            <h5 class="fw-bold mb-3"><i class="fa-solid fa-filter me-2"></i>Filtros</h5>
            <form id="formFiltros">
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label small">Fecha Desde</label>
                        <input type="date" class="form-control" id="fecha_desde" name="fecha_desde"
                            value="<?php echo date('Y-m-d', strtotime('-1 month')); ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small">Fecha Hasta</label>
                        <input type="date" class="form-control" id="fecha_hasta" name="fecha_hasta"
                            value="<?php echo date('Y-m-d'); ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small">Tipo de Falla</label>
                        <select class="form-select" id="tipo_falla" name="tipo_falla">
                            <option value="">Todos</option>
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
                    </div>
                    <div class="col-md-3 d-flex align-items-end gap-2">
                        <button type="button" class="btn btn-primary flex-fill" onclick="aplicarFiltros()">
                            <i class="fa-solid fa-search me-1"></i>Filtrar
                        </button>
                        <button type="button" class="btn btn-outline-secondary" onclick="limpiarFiltros()">
                            <i class="fa-solid fa-times"></i>
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <!-- Gráficos -->
        <div class="row g-3 mb-4">
            <div class="col-lg-6">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white border-bottom">
                        <h6 class="mb-0 fw-bold">Top 10 Tipos de Falla</h6>
                    </div>
                    <div class="card-body">
                        <div class="chart-container">
                            <canvas id="chartFallasTipo"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-6">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white border-bottom">
                        <h6 class="mb-0 fw-bold">Reportes por Mes</h6>
                    </div>
                    <div class="card-body">
                        <div class="chart-container">
                            <canvas id="chartReportesMes"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-6">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white border-bottom">
                        <h6 class="mb-0 fw-bold">Top 10 Técnicos</h6>
                    </div>
                    <div class="card-body">
                        <div class="chart-container">
                            <canvas id="chartTecnicos"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-6">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white border-bottom">
                        <h6 class="mb-0 fw-bold">Ingresos Mensuales</h6>
                    </div>
                    <div class="card-body">
                        <div class="chart-container">
                            <canvas id="chartIngresos"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tabla de Reportes -->
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-bottom d-flex justify-content-between align-items-center">
                <h6 class="mb-0 fw-bold">Listado de Reportes</h6>
                <button class="btn btn-sm btn-danger" onclick="exportarPDF()">
                    <i class="fa-solid fa-file-pdf me-1"></i>Exportar PDF
                </button>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive p-3">
                    <table id="tablaReportes" class="display table table-striped table-bordered w-100">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Fecha</th>
                                <th>Cliente</th>
                                <th>Tipo Falla</th>
                                <th>Técnico</th>
                                <th>Total</th>
                                <th>Pagado</th>
                                <th>Estado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>
</main>

<script src="<?php echo $path_to_root; ?>js/jquery.min.js"></script>
<script src="<?php echo $path_to_root; ?>js/bootstrap.bundle.min.js"></script>
<script src="<?php echo $path_to_root; ?>js/datatables.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

<script>
    let charts = {};
    let dataTable;

    $(document).ready(function () {
        cargarEstadisticas();
        inicializarTabla();
    });

    function cargarEstadisticas() {
        const fechaDesde = $('#fecha_desde').val();
        const fechaHasta = $('#fecha_hasta').val();

        $.ajax({
            url: 'obtener_estadisticas.php',
            data: { fecha_desde: fechaDesde, fecha_hasta: fechaHasta },
            success: function (data) {
                if (data.success) {
                    actualizarKPIs(data.general);
                    crearGraficos(data);
                }
            },
            error: function () {
                alert('Error al cargar estadísticas');
            }
        });
    }

    function actualizarKPIs(general) {
        $('#total_reportes').text(general.total_reportes);
        $('#reportes_pendientes').text(general.reportes_pendientes);
        $('#total_facturado').text('$' + parseFloat(general.total_facturado).toFixed(2));
        $('#total_cobrado').text('$' + parseFloat(general.total_cobrado).toFixed(2));
        $('#saldo_pendiente').text('$' + parseFloat(general.saldo_pendiente).toFixed(2));
    }

    function crearGraficos(data) {
        // Destruir gráficos anteriores
        Object.values(charts).forEach(chart => chart.destroy());

        // 1. Gráfico de fallas por tipo
        const ctxTipo = document.getElementById('chartFallasTipo').getContext('2d');
        const tipoLabels = Object.keys(data.fallas_por_tipo).slice(0, 10);
        const tipoData = Object.values(data.fallas_por_tipo).slice(0, 10);

        charts.tipo = new Chart(ctxTipo, {
            type: 'bar',
            data: {
                labels: tipoLabels,
                datasets: [{
                    label: 'Cantidad de Reportes',
                    data: tipoData,
                    backgroundColor: 'rgba(13, 110, 253, 0.7)',
                    borderColor: 'rgba(13, 110, 253, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                indexAxis: 'y',
                plugins: {
                    legend: { display: false }
                }
            }
        });

        // 2. Gráfico de reportes por mes
        const ctxMes = document.getElementById('chartReportesMes').getContext('2d');
        const mesLabels = data.meses_labels || [];
        const mesData = Object.values(data.reportes_por_mes);

        charts.mes = new Chart(ctxMes, {
            type: 'line',
            data: {
                labels: mesLabels,
                datasets: [{
                    label: 'Reportes',
                    data: mesData,
                    borderColor: 'rgba(25, 135, 84, 1)',
                    backgroundColor: 'rgba(25, 135, 84, 0.1)',
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false
            }
        });

        // 3. Gráfico de técnicos
        const ctxTecnico = document.getElementById('chartTecnicos').getContext('2d');
        const tecnicoLabels = Object.keys(data.reportes_por_tecnico);
        const tecnicoData = tecnicoLabels.map(t => data.reportes_por_tecnico[t].cantidad);

        charts.tecnico = new Chart(ctxTecnico, {
            type: 'doughnut',
            data: {
                labels: tecnicoLabels,
                datasets: [{
                    data: tecnicoData,
                    backgroundColor: [
                        'rgba(13, 110, 253, 0.7)',
                        'rgba(25, 135, 84, 0.7)',
                        'rgba(255, 193, 7, 0.7)',
                        'rgba(220, 53, 69, 0.7)',
                        'rgba(13, 202, 240, 0.7)',
                        'rgba(108, 117, 125, 0.7)',
                        'rgba(111, 66, 193, 0.7)',
                        'rgba(253, 126, 20, 0.7)',
                        'rgba(32, 201, 151, 0.7)',
                        'rgba(214, 51, 132, 0.7)'
                    ]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false
            }
        });

        // 4. Gráfico de ingresos
        const ctxIngresos = document.getElementById('chartIngresos').getContext('2d');
        const ingresosLabels = data.meses_labels || [];
        const ingresosTotal = Object.values(data.ingresos_por_mes).map(i => i.total);
        const ingresosPagado = Object.values(data.ingresos_por_mes).map(i => i.pagado);

        charts.ingresos = new Chart(ctxIngresos, {
            type: 'bar',
            data: {
                labels: ingresosLabels,
                datasets: [
                    {
                        label: 'Facturado',
                        data: ingresosTotal,
                        backgroundColor: 'rgba(13, 110, 253, 0.7)'
                    },
                    {
                        label: 'Cobrado',
                        data: ingresosPagado,
                        backgroundColor: 'rgba(25, 135, 84, 0.7)'
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false
            }
        });
    }

    function inicializarTabla() {
        dataTable = $('#tablaReportes').DataTable({
            "order": [[0, "desc"]],
            "bProcessing": true,
            "bServerSide": true,
            "sAjaxSource": "filtrar_reportes.php",
            "language": {
                "url": "//cdn.datatables.net/plug-ins/1.13.4/i18n/es-ES.json"
            },
            "fnServerParams": function (aoData) {
                aoData.push(
                    { "name": "fecha_desde", "value": $('#fecha_desde').val() },
                    { "name": "fecha_hasta", "value": $('#fecha_hasta').val() },
                    { "name": "tipo_falla", "value": $('#tipo_falla').val() }
                );
            },
            "aoColumnDefs": [
                { "mData": 0, "aTargets": [0] },
                { "mData": 1, "aTargets": [1] },
                { "mData": 2, "aTargets": [2] },
                { "mData": 3, "aTargets": [3] },
                { "mData": 4, "aTargets": [4] },
                { "mData": 5, "aTargets": [5], "mRender": d => '$' + parseFloat(d).toFixed(2) },
                { "mData": 6, "aTargets": [6], "mRender": d => '$' + parseFloat(d).toFixed(2) },
                {
                    "mData": 7, "aTargets": [7],
                    "mRender": function (d, t, row) {
                        const total = parseFloat(row[5]);
                        const pagado = parseFloat(row[6]);
                        return pagado >= (total - 0.01)
                            ? '<span class="badge bg-success">Pagado</span>'
                            : '<span class="badge bg-danger">Pendiente</span>';
                    }
                },
                {
                    "mData": null, "aTargets": [8], "bSortable": false,
                    "mRender": function (d, t, row) {
                        return `<button class="btn btn-sm btn-info" onclick="verDetalles(${row[0]})">
                                <i class="fa-solid fa-eye"></i> Ver
                            </button>`;
                    }
                }
            ]
        });
    }

    function aplicarFiltros() {
        cargarEstadisticas();
        dataTable.ajax.reload();
    }

    function limpiarFiltros() {
        $('#formFiltros')[0].reset();
        $('#fecha_desde').val('<?php echo date('Y-m-d', strtotime('-1 month')); ?>');
        $('#fecha_hasta').val('<?php echo date('Y-m-d'); ?>');
        aplicarFiltros();
    }

    function verDetalles(id) {
        window.location.href = 'ver_detalles_reporte.php?id=' + id;
    }

    function exportarPDF() {
        const params = new URLSearchParams({
            fecha_desde: $('#fecha_desde').val(),
            fecha_hasta: $('#fecha_hasta').val(),
            tipo_falla: $('#tipo_falla').val()
        });
        window.open('generar_pdf_consolidado.php?' + params.toString(), '_blank');
    }
</script>

<?php require_once $path_to_root . 'paginas/includes/layout_foot.php'; ?>