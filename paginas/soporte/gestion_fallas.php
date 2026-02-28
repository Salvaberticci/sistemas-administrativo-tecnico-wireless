<?php
/**
 * Gestión de Fallas Técnicas - Dashboard Principal
 * Módulo completo con estadísticas, filtros y exportación PDF
 */
$path_to_root = "../../";
$page_title = "Gestión de Fallas";
$breadcrumb = ["Soporte", "Gestión de Fallas"];
$back_url = "../menu.php";
require_once $path_to_root . 'paginas/conexion.php';
require_once $path_to_root . 'paginas/includes/layout_head.php';
require_once $path_to_root . 'paginas/includes/sidebar.php';

// Leer filtros para PHP Table Rendering
$filtro_desde = isset($_GET['fecha_desde']) ? $conn->real_escape_string($_GET['fecha_desde']) : date('Y-m-d', strtotime('-1 month'));
$filtro_hasta = isset($_GET['fecha_hasta']) ? $conn->real_escape_string($_GET['fecha_hasta']) : date('Y-m-d');
$filtro_tipo = isset($_GET['tipo_falla']) ? $conn->real_escape_string($_GET['tipo_falla']) : '';
$filtro_tecnico = isset($_GET['tecnico']) ? $conn->real_escape_string($_GET['tecnico']) : '';
$filtro_pago = isset($_GET['estado_pago']) ? $conn->real_escape_string($_GET['estado_pago']) : '';
?>

<!-- Include DataTables CSS inside head if not already included by layout_head.php (removing DT css) -->
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

    /* Estilos para niveles de prioridad */
    .badge-nivel1 {
        background-color: #ffff00;
        color: #000;
    }

    .badge-nivel2 {
        background-color: #fd7e14;
        color: white;
    }

    .badge-nivel3 {
        background-color: #dc3545;
        color: white;
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
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h2 class="h3 fw-bold text-primary mb-1">
                            <i class="fa-solid fa-chart-line me-2"></i>Gestión de Fallas Técnicas
                        </h2>
                        <p class="text-muted">Dashboard de análisis y estadísticas de reportes técnicos</p>
                    </div>
                    <a href="registro_falla.php" class="btn btn-danger">
                        <i class="fa-solid fa-bolt me-1"></i>Registrar Falla
                    </a>
                </div>
            </div>
        </div>



        <!-- Nuevos KPIs Avanzados -->
        <div class="row g-3 mb-4" id="kpiCardsAvanzados">
            <div class="col-md-6 col-lg-3">
                <div class="card stat-card border-0 shadow-sm h-100" style="border-left-color: #17a2b8 !important;">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <p class="text-muted mb-1 small">Tiempo Promedio Respuesta</p>
                                <h3 class="fw-bold mb-0 text-info" id="tiempo_promedio">
                                    <span class="spinner-border spinner-border-sm"></span>
                                </h3>
                                <small class="text-muted">horas</small>
                            </div>
                            <div class="text-info">
                                <i class="fa-solid fa-clock fa-2x opacity-75"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-6 col-lg-3">
                <div class="card stat-card border-0 shadow-sm h-100" style="border-left-color: #dc3545 !important;">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <p class="text-muted mb-1 small">Fallas Críticas Activas</p>
                                <h3 class="fw-bold mb-0 text-danger" id="fallas_criticas_activas">
                                    <span class="spinner-border spinner-border-sm"></span>
                                </h3>
                            </div>
                            <div class="text-danger">
                                <i class="fa-solid fa-fire fa-2x opacity-75"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-6 col-lg-3">
                <div class="card stat-card border-0 shadow-sm h-100" style="border-left-color: #fd7e14 !important;">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <p class="text-muted mb-1 small">Clientes Recurrentes</p>
                                <h3 class="fw-bold mb-0" style="color: #fd7e14;" id="clientes_recurrentes_count">
                                    <span class="spinner-border spinner-border-sm"></span>
                                </h3>
                                <small class="text-muted">>3 fallas/mes</small>
                            </div>
                            <div style="color: #fd7e14;">
                                <i class="fa-solid fa-user-clock fa-2x opacity-75"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-6 col-lg-3">
                <div class="card stat-card border-0 shadow-sm h-100" style="border-left-color: #6f42c1 !important;">
                    <div class="card-body">
                        <div>
                            <p class="text-muted mb-1 small">Zona Más Afectada</p>
                            <h6 class="fw-bold mb-0" style="color: #6f42c1;" id="zona_top">
                                <span class="spinner-border spinner-border-sm"></span>
                            </h6>
                            <small class="text-muted" id="zona_top_count">-- fallas</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Alerta de Caídas Críticas Activas -->
        <div class="alert alert-danger d-none" id="alertCaidasCriticas" role="alert">
            <h6 class="alert-heading fw-bold">
                <i class="fa-solid fa-triangle-exclamation me-2"></i>Caídas Críticas Activas
            </h6>
            <ul id="listaCaidasCriticas" class="mb-0"></ul>
        </div>

        <!-- Filtros -->
        <div class="filter-section">
            <h5 class="fw-bold mb-3"><i class="fa-solid fa-filter me-2"></i>Filtros</h5>
            <form id="formFiltros" method="GET" action="gestion_fallas.php">
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label small">Fecha Desde</label>
                        <input type="date" class="form-control" id="fecha_desde" name="fecha_desde"
                            value="<?php echo htmlspecialchars($filtro_desde); ?>" max="<?php echo date('Y-m-d'); ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small">Fecha Hasta</label>
                        <input type="date" class="form-control" id="fecha_hasta" name="fecha_hasta"
                            value="<?php echo htmlspecialchars($filtro_hasta); ?>" max="<?php echo date('Y-m-d'); ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small">Tipo de Falla</label>
                        <select class="form-select" id="tipo_falla" name="tipo_falla">
                            <option value="">Todos</option>
                            <option value="Sin Señal / LOS" <?php echo $filtro_tipo == 'Sin Señal / LOS' ? 'selected' : ''; ?>>Sin Señal / LOS</option>
                            <option value="Internet Lento" <?php echo $filtro_tipo == 'Internet Lento' ? 'selected' : ''; ?>>Internet Lento</option>
                            <option value="Cortes Intermitentes" <?php echo $filtro_tipo == 'Cortes Intermitentes' ? 'selected' : ''; ?>>Cortes Intermitentes</option>
                            <option value="Router Dañado" <?php echo $filtro_tipo == 'Router Dañado' ? 'selected' : ''; ?>>Router Dañado</option>
                            <option value="ONU Apagada/Dañada" <?php echo $filtro_tipo == 'ONU Apagada/Dañada' ? 'selected' : ''; ?>>ONU Apagada/Dañada</option>
                            <option value="Antena Desalineada" <?php echo $filtro_tipo == 'Antena Desalineada' ? 'selected' : ''; ?>>Antena Desalineada</option>
                            <option value="Cable Dañado" <?php echo $filtro_tipo == 'Cable Dañado' ? 'selected' : ''; ?>>
                                Cable Dañado</option>
                            <option value="Fibra Cortada" <?php echo $filtro_tipo == 'Fibra Cortada' ? 'selected' : ''; ?>>Fibra Cortada</option>
                            <option value="Problema Eléctrico" <?php echo $filtro_tipo == 'Problema Eléctrico' ? 'selected' : ''; ?>>Problema Eléctrico</option>
                            <option value="Configuración Incorrecta" <?php echo $filtro_tipo == 'Configuración Incorrecta' ? 'selected' : ''; ?>>Configuración Incorrecta</option>
                            <option value="Dispositivo del Cliente" <?php echo $filtro_tipo == 'Dispositivo del Cliente' ? 'selected' : ''; ?>>Dispositivo del Cliente</option>
                            <option value="Saturación de Red" <?php echo $filtro_tipo == 'Saturación de Red' ? 'selected' : ''; ?>>Saturación de Red</option>
                            <option value="Mantenimiento Preventivo" <?php echo $filtro_tipo == 'Mantenimiento Preventivo' ? 'selected' : ''; ?>>Mantenimiento Preventivo</option>
                            <option value="Cambio de Equipo" <?php echo $filtro_tipo == 'Cambio de Equipo' ? 'selected' : ''; ?>>Cambio de Equipo</option>
                            <option value="Otro" <?php echo $filtro_tipo == 'Otro' ? 'selected' : ''; ?>>Otro</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small">Técnico</label>
                        <input type="text" class="form-control" id="filtro_tecnico" name="tecnico"
                            placeholder="Nombre del técnico..."
                            value="<?php echo htmlspecialchars($filtro_tecnico); ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small">Estado de Pago</label>
                        <select class="form-select" id="estado_pago" name="estado_pago">
                            <option value="" <?php echo $filtro_pago == '' ? 'selected' : ''; ?>>Todos</option>
                            <option value="PAGADO" <?php echo $filtro_pago == 'PAGADO' ? 'selected' : ''; ?>>Pagado
                            </option>
                            <option value="PENDIENTE" <?php echo $filtro_pago == 'PENDIENTE' ? 'selected' : ''; ?>>
                                Pendiente</option>
                        </select>
                    </div>
                    <div class="col-md-3 d-flex align-items-end gap-2">
                        <button type="submit" class="btn btn-primary flex-fill">
                            <i class="fa-solid fa-search me-1"></i>Filtrar
                        </button>
                        <a href="gestion_fallas.php" class="btn btn-outline-secondary">
                            <i class="fa-solid fa-times"></i>
                        </a>
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
                        <h6 class="mb-0 fw-bold">Fallas por Nivel de Prioridad</h6>
                    </div>
                    <div class="card-body">
                        <div class="chart-container">
                            <canvas id="chartNiveles"></canvas>
                        </div>
                    </div>
                </div>
            </div>


        </div>

        <!-- ☠️ Caídas Críticas Activas -->
        <?php
        $sql_criticas = "SELECT s.id_soporte, DATE_FORMAT(s.fecha_soporte, '%d/%m/%Y') as fecha, c.nombre_completo, s.tipo_falla, s.tecnico_asignado, s.clientes_afectados, s.zona_afectada, s.solucion_completada
            FROM soportes s
            INNER JOIN contratos c ON s.id_contrato = c.id
            WHERE s.es_caida_critica = 1
            ORDER BY s.id_soporte DESC LIMIT 20";
        $res_criticas = $conn->query($sql_criticas);
        $num_criticas = $res_criticas ? $res_criticas->num_rows : 0;
        ?>
        <?php if ($num_criticas > 0): ?>
            <div class="card border-danger border-2 shadow mb-4">
                <div class="card-header bg-danger text-white d-flex align-items-center gap-2">
                    <i class="fa-solid fa-fire fa-shake"></i>
                    <span class="fw-bold">⚠️ Caídas Críticas (<?php echo $num_criticas; ?>)</span>
                    <span class="ms-auto small">Fallas que afectan múltiples clientes o infraestructura crítica</span>
                </div>
                <div class="table-responsive">
                    <table class="table table-bordered mb-0">
                        <thead class="table-danger">
                            <tr>
                                <th>#</th>
                                <th>Fecha</th>
                                <th>Cliente</th>
                                <th>Tipo Falla</th>
                                <th>Técnico</th>
                                <th>Clientes Afectados</th>
                                <th>Zona</th>
                                <th>Estado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($cr = $res_criticas->fetch_assoc()): ?>
                                <tr class="<?php echo $cr['solucion_completada'] ? '' : 'table-warning'; ?>"
                                    style="cursor:pointer;" ondblclick="verDetalles(<?php echo $cr['id_soporte']; ?>)">
                                    <td><span class="badge bg-danger">#<?php echo $cr['id_soporte']; ?></span></td>
                                    <td><?php echo htmlspecialchars($cr['fecha']); ?></td>
                                    <td><?php echo htmlspecialchars($cr['nombre_completo']); ?></td>
                                    <td><?php echo htmlspecialchars($cr['tipo_falla'] ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($cr['tecnico_asignado'] ?? 'Sin asignar'); ?></td>
                                    <td class="text-center fw-bold text-danger"><?php echo intval($cr['clientes_afectados']); ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($cr['zona_afectada'] ?? '—'); ?></td>
                                    <td><?php echo $cr['solucion_completada'] ? '<span class="badge bg-success">Solucionada</span>' : '<span class="badge bg-warning text-dark">Activa</span>'; ?>
                                    </td>
                                    <td>
                                        <button class="btn btn-sm btn-outline-primary"
                                            onclick="verDetalles(<?php echo $cr['id_soporte']; ?>)" title="Ver Detalles">
                                            <i class="fa-solid fa-eye"></i>
                                        </button>
                                        <button class="btn btn-sm btn-warning"
                                            onclick="abrirEditar(<?php echo $cr['id_soporte']; ?>)" title="Editar">
                                            <i class="fa-solid fa-pen"></i>
                                        </button>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
        <?php endif; ?>

        <!-- Tabla de Reportes -->
        <div class="card border-0 shadow-sm">
            <div
                class="card-header bg-white border-bottom d-flex justify-content-between align-items-center flex-wrap gap-2">
                <h6 class="mb-0 fw-bold">Listado de Reportes</h6>
                <div class="d-flex align-items-center gap-2">
                    <div class="input-group input-group-sm" style="width: 250px;">
                        <span class="input-group-text bg-white"><i class="fa-solid fa-search text-muted"></i></span>
                        <input type="text" id="buscadorNativo" class="form-control" placeholder="Buscar en la tabla...">
                    </div>
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
                                    <th>Nivel</th>
                                    <th>Pagado</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $sWhere = "s.fecha_soporte BETWEEN '$filtro_desde' AND '$filtro_hasta'";

                                if (!empty($filtro_tipo))
                                    $sWhere .= " AND s.tipo_falla = '$filtro_tipo'";
                                if (!empty($filtro_tecnico))
                                    $sWhere .= " AND s.tecnico_asignado LIKE '%$filtro_tecnico%'";
                                if (!empty($filtro_pago)) {
                                    if ($filtro_pago == 'PAGADO')
                                        $sWhere .= " AND (s.monto_total - s.monto_pagado) <= 0.01";
                                    else if ($filtro_pago == 'PENDIENTE')
                                        $sWhere .= " AND (s.monto_total - s.monto_pagado) > 0.01";
                                }

                                $sql = "SELECT s.id_soporte, DATE_FORMAT(s.fecha_soporte, '%d/%m/%Y') as fecha_formateada,
                                           c.nombre_completo, COALESCE(s.tipo_falla, 'No especificado') as tipo_falla,
                                           COALESCE(s.tecnico_asignado, 'Sin asignar') as tecnico, s.prioridad,
                                           s.monto_total, s.monto_pagado, (s.monto_total - s.monto_pagado) as saldo_pendiente,
                                           s.solucion_completada, s.es_caida_critica
                                    FROM soportes s
                                    INNER JOIN contratos c ON s.id_contrato = c.id
                                    WHERE $sWhere
                                    ORDER BY s.id_soporte DESC LIMIT 500";
                                $result = $conn->query($sql);

                                if ($result && $result->num_rows > 0) {
                                    if (!function_exists('fix_utf8')) {
                                        function fix_utf8($str)
                                        {
                                            if (empty($str))
                                                return '';
                                            if (!mb_check_encoding($str, 'UTF-8')) {
                                                $str = mb_convert_encoding($str, 'UTF-8', 'ISO-8859-1');
                                            }
                                            return htmlspecialchars($str, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
                                        }
                                    }

                                    while ($row = $result->fetch_assoc()) {
                                        $id = $row['id_soporte'];
                                        $prioridad = $row['prioridad'];

                                        // Nivel badge
                                        $badgePrioridad = '<span class="badge bg-secondary">Normal</span>';
                                        if ($prioridad == 'NIVEL 1')
                                            $badgePrioridad = '<span class="badge" style="background-color: #ffff00; color: #000;">Nivel 1</span>';
                                        elseif ($prioridad == 'NIVEL 2')
                                            $badgePrioridad = '<span class="badge bg-warning text-dark">Nivel 2</span>';
                                        elseif ($prioridad == 'NIVEL 3')
                                            $badgePrioridad = '<span class="badge bg-danger">Nivel 3 🔴</span>';

                                        // Pago badge
                                        $saldo = floatval($row['saldo_pendiente']);
                                        $badgePago = $saldo <= 0.01
                                            ? '<span class="badge bg-success">Pagado</span>'
                                            : '<span class="badge bg-warning text-dark">Pendiente</span>';

                                        // Fila roja si es NIVEL 3 o caída crítica
                                        $rowClass = '';
                                        if ($prioridad == 'NIVEL 3')
                                            $rowClass = 'table-danger';
                                        elseif ($row['es_caida_critica'])
                                            $rowClass = 'table-warning';

                                        echo "<tr class='{$rowClass}' style='cursor:pointer;' ondblclick='verDetalles({$id})'" . ">";
                                        echo "<td>{$id}</td>";
                                        echo "<td>{$row['fecha_formateada']}</td>";
                                        echo "<td>" . fix_utf8($row['nombre_completo']) . "</td>";
                                        echo "<td>" . fix_utf8($row['tipo_falla']) . "</td>";
                                        echo "<td>" . fix_utf8($row['tecnico']) . "</td>";
                                        echo "<td>{$badgePrioridad}</td>";
                                        echo "<td>{$badgePago}</td>";
                                        echo "<td class='text-nowrap'>
                                            <button class='btn btn-sm btn-outline-info me-1' onclick='verDetalles({$id})' title='Ver Detalles'>
                                                <i class='fa-solid fa-eye'></i>
                                            </button>
                                            <button class='btn btn-sm btn-warning me-1' onclick='abrirEditar({$id})' title='Editar'>
                                                <i class='fa-solid fa-pen'></i>
                                            </button>
                                            <a href='generar_pdf_reporte.php?id={$id}' target='_blank' class='btn btn-sm btn-danger' title='PDF'>
                                                <i class='fa-solid fa-file-pdf'></i>
                                            </a>
                                          </td>";
                                        echo "</tr>";
                                    }
                                } else {
                                    echo "<tr><td colspan='8' class='text-center'>No se encontraron reportes con los filtros actuales.</td></tr>";
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Paginación Nativa -->
                    <div class="card-footer bg-white border-top d-flex justify-content-between align-items-center py-3">
                        <span id="infoPaginacion" class="text-muted small">Mostrando 0 a 0 de 0 registros</span>
                        <nav aria-label="Page navigation">
                            <ul class="pagination pagination-sm mb-0" id="contenedorPaginacion">
                                <!-- Los botones se generan con JS -->
                            </ul>
                        </nav>
                    </div>
                </div>
            </div>

</main>

<!-- =============== MODAL: VER DETALLES =============== -->
<div class="modal fade" id="modalVerDetalles" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title"><i class="fa-solid fa-eye me-2"></i>Detalles del Reporte <span id="ver_modal_id"></span></h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="verDetallesBody">
                <div class="text-center py-5"><span class="spinner-border text-info"></span></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                <a id="btnVerPDF" href="#" target="_blank" class="btn btn-danger"><i class="fa-solid fa-file-pdf me-1"></i>Ver PDF</a>
                <button id="btnVerEditar" onclick="" class="btn btn-warning"><i class="fa-solid fa-pen me-1"></i>Editar</button>
            </div>
        </div>
    </div>
</div>

<!-- =============== MODAL: EDITAR SOPORTE =============== -->
<div class="modal fade" id="modalEditar" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <form action="actualizar_soporte.php" method="POST" class="modal-content" id="formEditarSoporte">
            <div class="modal-header bg-warning">
                <h5 class="modal-title fw-bold"><i class="fa-solid fa-pen me-2"></i>Editar Soporte #<span id="edit_modal_id_display"></span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="id_soporte_edit" id="id_soporte_edit">
                <input type="hidden" name="origen" value="gestion_fallas">

                <!-- Encabezado -->
                <div class="row mb-3">
                    <div class="col-md-3 mb-3">
                        <label class="form-label fw-bold">Fecha</label>
                        <input type="date" class="form-control" name="fecha_edit" id="fecha_edit" required>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label fw-bold">Técnico Asignado</label>
                        <input type="text" class="form-control" name="tecnico_edit" id="tecnico_edit" required>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label fw-bold">Sector</label>
                        <input type="text" class="form-control" name="sector" id="sector_edit">
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label fw-bold">Prioridad</label>
                        <select class="form-select" name="prioridad_edit" id="prioridad_edit">
                            <option value="NIVEL 1">NIVEL 1 (WhatsApp)</option>
                            <option value="NIVEL 2">NIVEL 2 (Visita)</option>
                            <option value="NIVEL 3">NIVEL 3 (Red)</option>
                        </select>
                    </div>
                </div>

                <!-- Falla -->
                <div class="p-2 mb-2 bg-light border-start border-danger border-4 fw-bold">Información de Falla</div>
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Tipo de Falla</label>
                        <select class="form-select" name="tipo_falla_edit" id="tipo_falla_edit">
                            <option value="">-- Seleccionar --</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-bold">Tipo Servicio</label>
                        <select class="form-select" name="tipo_servicio" id="tipo_servicio_edit">
                            <option value="FTTH">FTTH (Fibra)</option>
                            <option value="RADIO">Radio/Antena</option>
                        </select>
                    </div>
                    <div class="col-md-3 d-flex align-items-end">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="es_caida_critica_edit" id="es_caida_critica_edit" value="1">
                            <label class="form-check-label text-danger fw-bold" for="es_caida_critica_edit">¿Caída Crítica?</label>
                        </div>
                    </div>
                </div>

                <!-- Detalles Técnicos -->
                <div class="p-2 mb-2 bg-light border-start border-primary border-4 fw-bold">Detalles Técnicos</div>
                <div class="row mb-3">
                    <div class="col-md-3">
                        <label class="form-label text-muted small">IP</label>
                        <input type="text" class="form-control" name="ip" id="ip_edit" placeholder="0.0.0.0">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label text-muted small">Estado ONU</label>
                        <select class="form-select" name="estado_onu" id="estado_onu_edit">
                            <option value="">--</option>
                            <option value="ON">ON</option>
                            <option value="OFF">OFF</option>
                            <option value="LOS">LOS</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label text-muted small">Estado Router</label>
                        <select class="form-select" name="estado_router" id="estado_router_edit">
                            <option value="">--</option>
                            <option value="ON">ON</option>
                            <option value="OFF">OFF</option>
                            <option value="RESET">Reset</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label text-muted small">Modelo Router</label>
                        <input type="text" class="form-control" name="modelo_router" id="modelo_router_edit">
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-2">
                        <label class="form-label small">Dispositivos</label>
                        <input type="number" class="form-control" name="num_dispositivos" id="num_dispositivos_edit">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small">Bajada</label>
                        <input type="text" class="form-control" name="bw_bajada" id="bw_bajada_edit" placeholder="MB">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small">Subida</label>
                        <input type="text" class="form-control" name="bw_subida" id="bw_subida_edit" placeholder="MB">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small">Ping</label>
                        <input type="text" class="form-control" name="bw_ping" id="bw_ping_edit" placeholder="ms">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small">Antena Estado</label>
                        <input type="text" class="form-control" name="estado_antena" id="estado_antena_edit">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small">Valores dBm</label>
                        <input type="text" class="form-control" name="valores_antena" id="valores_antena_edit">
                    </div>
                </div>

                <!-- Diagnóstico y Solución -->
                <div class="p-2 mb-2 bg-light border-start border-primary border-4 fw-bold">Diagnóstico y Solución</div>
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label">Observaciones / Problema</label>
                        <textarea class="form-control" name="descripcion_edit" id="descripcion_edit" rows="3" required></textarea>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Sugerencias al Cliente</label>
                        <textarea class="form-control" name="sugerencias" id="sugerencias_edit" rows="3"></textarea>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Notas Internas</label>
                    <textarea class="form-control" name="notas_internas_edit" id="notas_internas_edit" rows="2"></textarea>
                </div>
                <div class="form-check form-switch mb-3">
                    <input class="form-check-input" type="checkbox" id="solucion_completada_edit" name="solucion_completada">
                    <label class="form-check-label fw-bold" for="solucion_completada_edit">¿Falla Solucionada?</label>
                </div>

                <!-- Costos -->
                <div class="p-2 mb-2 bg-light border-start border-primary border-4 fw-bold">Costos y Facturación</div>
                <div class="mb-3">
                    <label class="form-label fw-bold">Monto Total ($)</label>
                    <input type="number" step="0.01" min="0" class="form-control" name="monto_total_edit" id="monto_total_edit" required>
                </div>

                <!-- Firmas -->
                <div class="p-2 mb-2 bg-light border-start border-primary border-4 fw-bold">Actualizar Firmas (Opcional)</div>
                <p class="text-muted small">Deje los lienzos en blanco para conservar las firmas originales.</p>
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label small fw-bold">Firma Técnico</label>
                        <div class="mb-1 text-center d-none" id="container_firma_tech_edit">
                            <span class="badge bg-info mb-1">Firma Actual</span><br>
                            <img id="imgFirmaTech_edit" src="" style="max-height: 80px; border: 1px dashed #ccc;">
                        </div>
                        <canvas id="sigTechEdit" style="border:1px solid #ccc;width:100%;height:120px;border-radius:4px;background:#fcfcfc;"></canvas>
                        <button type="button" class="btn btn-sm btn-outline-secondary mt-1" onclick="clearPadEdit('tech')">Limpiar</button>
                        <input type="hidden" name="firma_tecnico_data" id="firma_tecnico_data_edit">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-bold">Firma Cliente</label>
                        <div class="mb-1 text-center d-none" id="container_firma_cli_edit">
                            <span class="badge bg-info mb-1">Firma Actual</span><br>
                            <img id="imgFirmaCli_edit" src="" style="max-height: 80px; border: 1px dashed #ccc;">
                        </div>
                        <canvas id="sigCliEdit" style="border:1px solid #ccc;width:100%;height:120px;border-radius:4px;background:#fcfcfc;"></canvas>
                        <button type="button" class="btn btn-sm btn-outline-secondary mt-1" onclick="clearPadEdit('cli')">Limpiar</button>
                        <input type="hidden" name="firma_cliente_data" id="firma_cliente_data_edit">
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-warning fw-bold" id="btnGuardarEdicion"><i class="fa-solid fa-save me-1"></i>Guardar Cambios</button>
            </div>
        </form>
    </div>
</div>

<script src="<?php echo $path_to_root; ?>js/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/signature_pad@4.1.5/dist/signature_pad.umd.min.js"></script>

<script>
    let charts = {};
    let padTechEdit = null, padCliEdit = null;
    let _currentEditId = null;

    // ---- Ver Detalles (Modal AJAX) ----
    function verDetalles(id) {
        _currentEditId = id;
        $('#ver_modal_id').text('#' + id);
        $('#verDetallesBody').html('<div class="text-center py-5"><span class="spinner-border text-info"></span></div>');
        $('#btnVerPDF').attr('href', 'generar_pdf_reporte.php?id=' + id);
        $('#btnVerEditar').attr('onclick', 'verAEditar(' + id + ')');
        new bootstrap.Modal(document.getElementById('modalVerDetalles')).show();

        fetch('get_soporte_detalle.php?id=' + id)
            .then(r => r.json())
            .then(d => {
                if (d.error) { $('#verDetallesBody').html('<div class="alert alert-danger">' + d.error + '</div>'); return; }
                const f = (v) => v ? v : '—';
                const badge = (p) => {
                    if (p === 'NIVEL 1') return '<span class="badge" style="background:#ffff00;color:#000">NIVEL 1</span>';
                    if (p === 'NIVEL 2') return '<span class="badge bg-warning text-dark">NIVEL 2</span>';
                    if (p === 'NIVEL 3') return '<span class="badge bg-danger">NIVEL 3</span>';
                    return '<span class="badge bg-secondary">' + f(p) + '</span>';
                };
                const path_root = '../../';
                let html = `
                <div class="row">
                    <div class="col-md-6">
                        <div class="card mb-3 border-0 shadow-sm">
                            <div class="card-header bg-primary text-white"><i class="fa-solid fa-user me-2"></i>Cliente</div>
                            <div class="card-body">
                                <p class="mb-1"><strong>Nombre:</strong> ${f(d.nombre_completo)}</p>
                                <p class="mb-1"><strong>Cédula:</strong> ${f(d.cedula)}</p>
                                <p class="mb-1"><strong>IP:</strong> <code>${f(d.ip_address)}</code></p>
                                <p class="mb-0"><strong>Teléfono:</strong> ${f(d.telefono)}</p>
                            </div>
                        </div>
                        <div class="card mb-3 border-0 shadow-sm">
                            <div class="card-header bg-info text-white"><i class="fa-solid fa-calendar-check me-2"></i>Visita</div>
                            <div class="card-body">
                                <p class="mb-1"><strong>Fecha:</strong> ${f(d.fecha_soporte_form)}</p>
                                <p class="mb-1"><strong>Técnico:</strong> ${f(d.tecnico_asignado)}</p>
                                <p class="mb-1"><strong>Sector:</strong> ${f(d.sector)}</p>
                                <p class="mb-1"><strong>Tipo Falla:</strong> ${f(d.tipo_falla)}</p>
                                <p class="mb-1"><strong>Prioridad:</strong> ${badge(d.prioridad)}</p>
                                <p class="mb-0"><strong>Caída Crítica:</strong> ${d.es_caida_critica == 1 ? '<span class=\"badge bg-danger\">Sí ('+f(d.clientes_afectados)+' clientes)</span>' : 'No'}</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card mb-3 border-0 shadow-sm">
                            <div class="card-header bg-warning"><i class="fa-solid fa-tools me-2"></i>Diagnóstico</div>
                            <div class="card-body">
                                <p class="mb-1"><strong>ONU:</strong> ${f(d.estado_onu)} | <strong>Router:</strong> ${f(d.estado_router)}</p>
                                <p class="mb-1"><strong>Modelo:</strong> ${f(d.modelo_router)}</p>
                                <p class="mb-1"><strong>BW:</strong> ↓${f(d.bw_bajada)} / ↑${f(d.bw_subida)} / Ping:${f(d.bw_ping)}</p>
                                <p class="mb-1"><strong>Observaciones:</strong> ${f(d.observaciones)}</p>
                                <p class="mb-0"><strong>Solucionada:</strong> ${d.solucion_completada == 1 ? '<span class=\"badge bg-success\">Sí</span>' : '<span class=\"badge bg-warning text-dark\">No</span>'}</p>
                            </div>
                        </div>
                        <div class="card mb-3 border-0 shadow-sm">
                            <div class="card-header bg-success text-white"><i class="fa-solid fa-dollar-sign me-2"></i>Financiero</div>
                            <div class="card-body">
                                <p class="mb-1"><strong>Total:</strong> $${parseFloat(d.monto_total||0).toFixed(2)}</p>
                                <p class="mb-1"><strong>Pagado:</strong> $${parseFloat(d.monto_pagado||0).toFixed(2)}</p>
                                <p class="mb-0"><strong>Saldo:</strong> <span class="${(d.monto_total-d.monto_pagado)>0.01?'text-danger fw-bold':'text-success fw-bold'}">$${(parseFloat(d.monto_total||0)-parseFloat(d.monto_pagado||0)).toFixed(2)}</span></p>
                            </div>
                        </div>
                    </div>
                </div>`;
                // Firmas
                if (d.firma_tecnico || d.firma_cliente) {
                    html += '<div class="row mt-2">';
                    if (d.firma_tecnico) html += `<div class="col-6 text-center"><small class="text-muted">Firma Técnico</small><br><img src="${path_root}uploads/firmas/${d.firma_tecnico}" class="img-fluid border" style="max-height:100px;"></div>`;
                    if (d.firma_cliente) html += `<div class="col-6 text-center"><small class="text-muted">Firma Cliente</small><br><img src="${path_root}uploads/firmas/${d.firma_cliente}" class="img-fluid border" style="max-height:100px;"></div>`;
                    html += '</div>';
                }
                $('#verDetallesBody').html(html);
            })
            .catch(() => $('#verDetallesBody').html('<div class="alert alert-danger">Error al cargar datos.</div>'));
    }

    function verAEditar(id) {
        bootstrap.Modal.getInstance(document.getElementById('modalVerDetalles'))?.hide();
        setTimeout(() => abrirEditar(id), 300);
    }

    // ---- Abrir Editar Modal ----
    function abrirEditar(id) {
        _currentEditId = id;
        $('#edit_modal_id_display').text(id);
        $('#id_soporte_edit').val(id);
        // Reset firmas
        $('#container_firma_tech_edit, #container_firma_cli_edit').addClass('d-none');

        // Cargar opciones de falla antes de mostrar
        cargarOpcionesFallaEdit(() => {
            fetch('get_soporte_detalle.php?id=' + id)
                .then(r => r.json())
                .then(d => {
                    if (d.error) { alert('Error: ' + d.error); return; }
                    $('#fecha_edit').val(d.fecha_soporte_form);
                    $('#tecnico_edit').val(d.tecnico_asignado || '');
                    $('#sector_edit').val(d.sector || '');
                    $('#prioridad_edit').val(d.prioridad || 'NIVEL 1');
                    $('#tipo_falla_edit').val(d.tipo_falla || '');
                    $('#tipo_servicio_edit').val(d.tipo_servicio || 'FTTH');
                    $('#es_caida_critica_edit').prop('checked', d.es_caida_critica == 1);
                    $('#ip_edit').val(d.ip_address || '');
                    $('#estado_onu_edit').val(d.estado_onu || '');
                    $('#estado_router_edit').val(d.estado_router || '');
                    $('#modelo_router_edit').val(d.modelo_router || '');
                    $('#num_dispositivos_edit').val(d.num_dispositivos || '');
                    $('#bw_bajada_edit').val(d.bw_bajada || '');
                    $('#bw_subida_edit').val(d.bw_subida || '');
                    $('#bw_ping_edit').val(d.bw_ping || '');
                    $('#estado_antena_edit').val(d.estado_antena || '');
                    $('#valores_antena_edit').val(d.valores_antena || '');
                    $('#descripcion_edit').val(d.observaciones || '');
                    $('#sugerencias_edit').val(d.sugerencias || '');
                    $('#notas_internas_edit').val(d.notas_internas || '');
                    $('#solucion_completada_edit').prop('checked', d.solucion_completada == 1);
                    $('#monto_total_edit').val(parseFloat(d.monto_total || 0).toFixed(2));

                    // Firmas actuales
                    const pathRoot = '../../';
                    if (d.firma_tecnico) {
                        $('#imgFirmaTech_edit').attr('src', pathRoot + 'uploads/firmas/' + d.firma_tecnico);
                        $('#container_firma_tech_edit').removeClass('d-none');
                    }
                    if (d.firma_cliente) {
                        $('#imgFirmaCli_edit').attr('src', pathRoot + 'uploads/firmas/' + d.firma_cliente);
                        $('#container_firma_cli_edit').removeClass('d-none');
                    }

                    new bootstrap.Modal(document.getElementById('modalEditar')).show();
                })
                .catch(() => alert('Error al cargar datos del soporte.'));
        });
    }

    // ---- Cargar opciones de falla ----
    function cargarOpcionesFallaEdit(callback) {
        fetch('admin_opciones.php?accion=listar&tipo=tipos_falla')
            .then(r => r.json())
            .then(data => {
                const sel = document.getElementById('tipo_falla_edit');
                const current = sel.value;
                sel.innerHTML = '<option value="">-- Seleccionar --</option>';
                (data.tipos_falla || []).forEach(op => {
                    const o = document.createElement('option');
                    o.value = op; o.textContent = op;
                    sel.appendChild(o);
                });
                if (current) sel.value = current;
                if (callback) callback();
            })
            .catch(() => { if (callback) callback(); });
    }

    // ---- SignaturePad para modal de edición ----
    $('#modalEditar').on('shown.bs.modal', function () {
        const canvasTech = document.getElementById('sigTechEdit');
        const canvasCli = document.getElementById('sigCliEdit');
        function resizeCanvas(canvas) {
            var ratio = Math.max(window.devicePixelRatio || 1, 1);
            canvas.width = canvas.offsetWidth * ratio;
            canvas.height = canvas.offsetHeight * ratio;
            canvas.getContext("2d").scale(ratio, ratio);
        }
        resizeCanvas(canvasTech);
        resizeCanvas(canvasCli);
        if (!padTechEdit) padTechEdit = new SignaturePad(canvasTech);
        else padTechEdit.clear();
        if (!padCliEdit) padCliEdit = new SignaturePad(canvasCli);
        else padCliEdit.clear();
    });

    function clearPadEdit(type) {
        if (type === 'tech' && padTechEdit) padTechEdit.clear();
        if (type === 'cli' && padCliEdit) padCliEdit.clear();
    }

    // ---- On Edit Form Submit ----
    document.getElementById('formEditarSoporte').addEventListener('submit', function (e) {
        if (padTechEdit && !padTechEdit.isEmpty()) {
            document.getElementById('firma_tecnico_data_edit').value = padTechEdit.toDataURL();
        }
        if (padCliEdit && !padCliEdit.isEmpty()) {
            document.getElementById('firma_cliente_data_edit').value = padCliEdit.toDataURL();
        }
        const btn = document.getElementById('btnGuardarEdicion');
        btn.disabled = true;
        btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-1"></i>Guardando...';
    });

    $(document).ready(function () {
        cargarEstadisticas();
        // DataTables initialization removed as per instruction
    });

    function cargarEstadisticas() {
        const fechaDesde = $('#fecha_desde').val();
        const fechaHasta = $('#fecha_hasta').val();
        const tipoFalla = $('#tipo_falla').val();
        const tecnico = $('#filtro_tecnico').val();
        const estadoPago = $('#estado_pago').val();

        // Cargar estadísticas estándar
        $.ajax({
            url: 'obtener_estadisticas.php',
            data: {
                fecha_desde: fechaDesde,
                fecha_hasta: fechaHasta,
                tipo_falla: tipoFalla,
                tecnico: tecnico,
                estado_pago: estadoPago
            },
            dataType: 'json',
            success: function (data) {
                if (data && data.success) {
                    // Update main cards 
                    $('#tiempo_promedio').text((data.tiempo_respuesta?.promedio_respuesta || 0) + 'h');

                    // The rest are updated by actualizarKPIsAvanzados, but wait, those were in the other ajax call.
                    // Let's call the chart function
                    crearGraficos(data);
                } else {
                    console.error("Estadisticas response error: ", data);
                }
            },
            error: function (xhr, status, error) {
                console.error('AJAX Error:', error, xhr.responseText);
            }
        });

        // Cargar métricas avanzadas (con try/catch)
        $.ajax({
            url: 'obtener_metricas_avanzadas.php',
            data: {
                fecha_desde: fechaDesde,
                fecha_hasta: fechaHasta,
                tipo_falla: tipoFalla,
                tecnico: tecnico,
                estado_pago: estadoPago
            },
            dataType: 'json',
            success: function (data) {
                if (data && data.success) {
                    actualizarKPIsAvanzados(data);
                }
            },
            error: function (xhr, status, error) {
                console.error("AJAX Avanzadas Error: ", error, xhr.responseText);
            }
        });
    }

    function actualizarKPIsAvanzados(data) {
        // Tiempo promedio de respuesta
        const tiempoPromedio = data.tiempo_respuesta?.promedio_respuesta || 0;
        $('#tiempo_promedio').text(tiempoPromedio);

        // Fallas críticas activas
        const fallasActivas = data.fallas_criticas?.activas || 0;
        $('#fallas_criticas_activas').text(fallasActivas);

        // Clientes recurrentes
        const clientesRecurrentes = data.clientes_recurrentes?.length || 0;
        $('#clientes_recurrentes_count').text(clientesRecurrentes);

        // Zona más afectada
        const zonas = data.zonas_afectadas || {};
        const zonasArray = Object.entries(zonas).map(([zona, info]) => ({
            zona: zona,
            total: info.total
        })).sort((a, b) => b.total - a.total);

        if (zonasArray.length > 0) {
            $('#zona_top').text(zonasArray[0].zona);
            $('#zona_top_count').text(zonasArray[0].total + ' fallas');
        } else {
            $('#zona_top').text('N/A');
            $('#zona_top_count').text('');
        }

        // Mostrar alerta de caídas críticas si hay activas
        const caidasActivas = data.caidas_recientes?.filter(c => c.estado === 'Activa') || [];
        if (caidasActivas.length > 0) {
            $('#alertCaidasCriticas').removeClass('d-none');
            const lista = caidasActivas.map(caida =>
                `<li><strong>Ticket #${caida.id}</strong> - ${caida.tipo} en ${caida.zona} 
                 (${caida.clientes_afectados} clientes, ${caida.horas_caida}h)</li>`
            ).join('');
            $('#listaCaidasCriticas').html(lista);
        } else {
            $('#alertCaidasCriticas').addClass('d-none');
        }
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

        // 4. Gráfico de Niveles de Prioridad
        const ctxNiveles = document.getElementById('chartNiveles').getContext('2d');
        const nivelesLabels = Object.keys(data.fallas_por_nivel || {});
        const nivelesData = Object.values(data.fallas_por_nivel || {});
        const totalFallas = nivelesData.reduce((a, b) => a + b, 0);

        charts.niveles = new Chart(ctxNiveles, {
            type: 'doughnut',
            data: {
                labels: nivelesLabels,
                datasets: [{
                    data: nivelesData,
                    backgroundColor: [
                        'rgba(220, 53, 69, 0.8)',   // Rojo para Nivel 3
                        'rgba(253, 126, 20, 0.8)',  // Naranja para Nivel 2
                        'rgba(255, 193, 7, 0.8)',   // Amarillo para Nivel 1
                        'rgba(108, 117, 125, 0.8)'  // Gris para Otros/No Asignado
                    ]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    tooltip: {
                        callbacks: {
                            label: function (context) {
                                let label = context.label || '';
                                if (label) {
                                    label += ': ';
                                }
                                const value = context.parsed || 0;
                                const percentage = totalFallas > 0 ? ((value / totalFallas) * 100).toFixed(1) : 0;
                                label += value + ' reportes (' + percentage + '%)';
                                return label;
                            }
                        }
                    }
                }
            }
        });
    }

    function exportarPDF() {
        const params = new URLSearchParams({
            fecha_desde: $('#fecha_desde').val(),
            fecha_hasta: $('#fecha_hasta').val(),
            tipo_falla: $('#tipo_falla').val(),
            tecnico: $('#filtro_tecnico').val(),
            estado_pago: $('#estado_pago').val()
        });
        window.open('generar_pdf_consolidado.php?' + params.toString(), '_blank');
    }

    // --- Paginación y Búsqueda Nativa ---
    const filasPorPagina = 10;
    let paginaActual = 1;

    $(document).ready(function () {
        // Validaciones de fechas
        const inputDesde = document.getElementById('fecha_desde');
        const inputHasta = document.getElementById('fecha_hasta');

        if (inputDesde && inputHasta) {
            inputDesde.addEventListener('change', function () {
                if (this.value && inputHasta.value && this.value > inputHasta.value) {
                    inputHasta.value = this.value;
                }
            });
            inputHasta.addEventListener('change', function () {
                if (this.value && inputDesde.value && this.value < inputDesde.value) {
                    inputDesde.value = this.value;
                }
            });
        }

        // Inicializar búsqueda
        $('#buscadorNativo').on('keyup', function () {
            paginaActual = 1; // reset a pág 1 al buscar
            actualizarTabla();
        });

        // Inicializar tabla si hay datos
        if ($('#tablaReportes tbody tr').length > 0 && !$('#tablaReportes tbody tr td').hasClass('text-center')) {
            actualizarTabla();
        }
    });

    function actualizarTabla() {
        const buscador = $('#buscadorNativo').val().toLowerCase();
        let todasLasFilas = $('#tablaReportes tbody tr');
        let filasFiltradas = [];

        // Ignorar la fila de "No se encontraron reportes"
        if (todasLasFilas.length === 1 && todasLasFilas.find('.text-center').length > 0) return;

        // 1. Filtrar filas
        todasLasFilas.each(function () {
            let fila = $(this);
            let textoFila = fila.text().toLowerCase();

            if (textoFila.includes(buscador)) {
                filasFiltradas.push(fila);
                fila.removeClass('d-none'); // Mostrar temporalmente
            } else {
                fila.addClass('d-none'); // Ocultar las que no coinciden
            }
        });

        const totalFiltradas = filasFiltradas.length;
        const totalPaginas = Math.ceil(totalFiltradas / filasPorPagina);

        // Evitar página vacía si se borran datos en la última página
        if (paginaActual > totalPaginas && totalPaginas > 0) {
            paginaActual = totalPaginas;
        } else if (paginaActual < 1) {
            paginaActual = 1;
        }

        // 2. Aplicar paginación (ocultar lo que no va en esta página)
        const inicio = (paginaActual - 1) * filasPorPagina;
        const fin = inicio + filasPorPagina;

        for (let i = 0; i < totalFiltradas; i++) {
            if (i >= inicio && i < fin) {
                filasFiltradas[i].removeClass('d-none');
            } else {
                filasFiltradas[i].addClass('d-none');
            }
        }

        // 3. Actualizar texto de información
        const numInicioDisp = totalFiltradas === 0 ? 0 : inicio + 1;
        const numFinDisp = (fin > totalFiltradas) ? totalFiltradas : fin;
        $('#infoPaginacion').text(`Mostrando ${numInicioDisp} a ${numFinDisp} de ${totalFiltradas} registros`);

        // 4. Renderizar botones de paginación
        renderizarControlesPaginacion(totalPaginas);
    }

    function renderizarControlesPaginacion(totalPaginas) {
        const contenedor = $('#contenedorPaginacion');
        contenedor.empty();

        if (totalPaginas <= 1) return; // Solo 1 página o ninguna, no mostrar botones

        // Botón Anterior
        const deshabilitarAnterior = paginaActual === 1 ? 'disabled' : '';
        contenedor.append(`<li class="page-item ${deshabilitarAnterior}">
            <a class="page-link" href="javascript:void(0)" onclick="cambiarPagina(${paginaActual - 1})">Anterior</a>
        </li>`);

        // Lógica para botones numéricos (mostrar máx 5)
        let maxBotones = 5;
        let startPage = Math.max(1, paginaActual - Math.floor(maxBotones / 2));
        let endPage = startPage + maxBotones - 1;

        if (endPage > totalPaginas) {
            endPage = totalPaginas;
            startPage = Math.max(1, endPage - maxBotones + 1);
        }

        for (let i = startPage; i <= endPage; i++) {
            const activo = i === paginaActual ? 'active' : '';
            contenedor.append(`<li class="page-item ${activo}">
                <a class="page-link" href="javascript:void(0)" onclick="cambiarPagina(${i})">${i}</a>
            </li>`);
        }

        // Botón Siguiente
        const deshabilitarSiguiente = paginaActual === totalPaginas ? 'disabled' : '';
        contenedor.append(`<li class="page-item ${deshabilitarSiguiente}">
            <a class="page-link" href="javascript:void(0)" onclick="cambiarPagina(${paginaActual + 1})">Siguiente</a>
        </li>`);
    }

    function cambiarPagina(nuevaPagina) {
        paginaActual = nuevaPagina;
        actualizarTabla();
    }
</script>

<?php require_once $path_to_root . 'paginas/includes/layout_foot.php'; ?>