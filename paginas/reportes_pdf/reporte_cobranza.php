<?php
require_once '../conexion.php';

// 1. CAPTURA Y SANEO DE PARÁMETROS DE FILTRO
// Fechas: Por defecto, un mes atrás hasta hoy.
$fecha_inicio = isset($_GET['fecha_inicio']) ? $_GET['fecha_inicio'] : date('Y-m-d', strtotime('-30 days'));
$fecha_fin = isset($_GET['fecha_fin']) ? $_GET['fecha_fin'] : date('Y-m-d');
$estado_filtro = isset($_GET['estado']) ? $_GET['estado'] : 'TODOS'; // Filtro de estado
$cobros = [];
$total_monto = 0;
$total_vencido = 0;

// 2. CONSTRUCCIÓN DINÁMICA DE LA CLÁUSULA WHERE
$where_clause = " WHERE 1=1 "; // Inicializamos la cláusula WHERE
$params = []; // Array para almacenar los parámetros de la consulta preparada
$types = ''; // Cadena para almacenar los tipos de datos (s, i, d...)

// Filtro por Estado (Si no es 'TODOS')
if ($estado_filtro !== 'TODOS') {
    $where_clause .= " AND cxc.estado = ? ";
    $params[] = $estado_filtro;
    $types .= 's';
}

// Filtro por Rango de Fechas (Aplicado a la fecha de emisión)
if (!empty($fecha_inicio) && !empty($fecha_fin)) {
    // Usamos la fecha de emisión para que el reporte sea sobre la creación del cobro
    $where_clause .= " AND cxc.fecha_emision >= ? AND cxc.fecha_emision <= ? ";
    $params[] = $fecha_inicio;
    $params[] = $fecha_fin;
    $types .= 'ss';
}

// 3. CONSULTA SQL BASE
$sql = "
    SELECT 
        cxc.id_cobro, 
        cxc.fecha_emision, 
        cxc.fecha_vencimiento, 
        cxc.monto_total, 
        cxc.estado,
        co.nombre_completo AS cliente,
        co.ip,
        DATEDIFF(CURRENT_DATE(), cxc.fecha_vencimiento) AS dias_vencido
    FROM contratos cxc_main
    RIGHT JOIN cuentas_por_cobrar cxc ON cxc.id_contrato = cxc_main.id 
    JOIN contratos co ON cxc.id_contrato = co.id
    " . $where_clause . "
    ORDER BY cxc.fecha_emision DESC
";
// Fixed join logic slightly to ensure we target table correctly, though original query was:
// FROM cuentas_por_cobrar cxc JOIN contratos co ON cxc.id_contrato = co.id
// Restoring original simple join for safety unless I saw an error.
$sql = "
    SELECT 
        cxc.id_cobro, 
        cxc.fecha_emision, 
        cxc.fecha_vencimiento, 
        cxc.monto_total, 
        cxc.estado,
        co.nombre_completo AS cliente,
        co.ip,
        DATEDIFF(CURRENT_DATE(), cxc.fecha_vencimiento) AS dias_vencido
    FROM cuentas_por_cobrar cxc
    JOIN contratos co ON cxc.id_contrato = co.id
    " . $where_clause . "
    ORDER BY cxc.fecha_emision DESC
";

$stmt = $conn->prepare($sql);

if ($stmt) {
    // 4. ENLAZAR PARÁMETROS Y EJECUTAR
    if (!empty($params)) {
        // La función call_user_func_array es necesaria para bind_param cuando se usan parámetros dinámicos
        $stmt->bind_param($types, ...$params);
    }

    $stmt->execute();
    $resultado = $stmt->get_result();

    while ($fila = $resultado->fetch_assoc()) {
        $cobros[] = $fila;
        $total_monto += $fila['monto_total'];

        // Sumamos el total vencido si la factura está pendiente y vencida
        if ($fila['estado'] !== 'PAGADO' && $fila['dias_vencido'] > 0) {
            $total_vencido += $fila['monto_total'];
        }
    }
    $stmt->close();
} else {
    $error = "Error al preparar la consulta: " . $conn->error;
}

// --- TEMPLATE START ---
$path_to_root = "../../";
$page_title = "Reporte de Cobranzas";
include $path_to_root . 'paginas/includes/layout_head.php';
?>

<main class="main-content">
    <?php include $path_to_root . 'paginas/includes/header.php'; ?>

    <div class="page-content">
        <!-- Header Section -->
        <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
            <div>
                <h2 class="h4 fw-bold mb-1 text-primary">Reporte Dinámico de Cobranzas</h2>
                <p class="text-muted mb-0">Análisis financiero y estado de cuentas</p>
            </div>
            <a href="../menu.php" class="btn btn-outline-secondary">
                <i class="fa-solid fa-arrow-left me-2"></i>Volver al Menú
            </a>
        </div>

        <!-- Filtros -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white border-bottom">
                <h6 class="mb-0 fw-bold text-primary"><i class="fa-solid fa-filter me-2"></i>Filtros de Búsqueda</h6>
            </div>
            <div class="card-body p-4 bg-light">
                <form action="reporte_cobranza.php" method="GET" class="row g-3 align-items-end">

                    <div class="col-md-3">
                        <label for="estado"
                            class="form-label fw-semibold text-secondary small text-uppercase">Estado</label>
                        <select class="form-select bg-white" name="estado" id="estado">
                            <option value="TODOS" <?php echo ($estado_filtro == 'TODOS') ? 'selected' : ''; ?>>TODOS
                            </option>
                            <option value="PENDIENTE" <?php echo ($estado_filtro == 'PENDIENTE') ? 'selected' : ''; ?>>
                                PENDIENTE</option>
                            <option value="VENCIDO" <?php echo ($estado_filtro == 'VENCIDO') ? 'selected' : ''; ?>>VENCIDO
                            </option>
                            <option value="PAGADO" <?php echo ($estado_filtro == 'PAGADO') ? 'selected' : ''; ?>>PAGADO
                            </option>
                            <option value="CANCELADO" <?php echo ($estado_filtro == 'CANCELADO') ? 'selected' : ''; ?>>
                                CANCELADO</option>
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label for="fecha_inicio"
                            class="form-label fw-semibold text-secondary small text-uppercase">Fecha Desde</label>
                        <input type="date" class="form-control" name="fecha_inicio"
                            value="<?php echo htmlspecialchars($fecha_inicio); ?>">
                    </div>
                    <div class="col-md-3">
                        <label for="fecha_fin" class="form-label fw-semibold text-secondary small text-uppercase">Fecha
                            Hasta</label>
                        <input type="date" class="form-control" name="fecha_fin"
                            value="<?php echo htmlspecialchars($fecha_fin); ?>">
                    </div>

                    <div class="col-md-3 d-flex gap-2">
                        <button type="submit" class="btn btn-primary flex-grow-1">
                            <i class="fa-solid fa-magnifying-glass me-2"></i>Aplicar
                        </button>

                        <?php
                        $export_params = http_build_query($_GET);
                        ?>
                        <a href="exportar_cuentas_por_cobrar.php?<?php echo $export_params; ?>" class="btn btn-danger"
                            target="_blank" title="Exportar a PDF">
                            <i class="fa-solid fa-file-pdf"></i>
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <?php if (isset($error)): ?>
            <div class="alert alert-danger shadow-sm border-0">
                <i class="fa-solid fa-circle-exclamation me-2"></i><?php echo $error; ?>
            </div>
        <?php elseif (empty($cobros)): ?>
            <div class="alert alert-info shadow-sm border-0 text-center py-4">
                <i class="fa-solid fa-circle-info fa-2x mb-3 text-info d-block"></i>
                <h5 class="fw-bold text-dark">No se encontraron resultados</h5>
                <p class="text-muted mb-0">Intenta ajustar los filtros de búsqueda.</p>
            </div>
        <?php else: ?>

            <!-- KPIs -->
            <div class="row mb-4">
                <!-- Total Card -->
                <div class="col-md-6 mb-3 mb-md-0">
                    <div class="card border-0 shadow-sm h-100 bg-primary text-white overflow-hidden position-relative">
                        <div class="card-body p-4">
                            <h6 class="text-white-50 text-uppercase fw-bold mb-2">Total Reportado</h6>
                            <div class="d-flex align-items-center mb-0">
                                <h2 class="display-6 fw-bold mb-0 me-3">$<?php echo number_format($total_monto, 2); ?></h2>
                                <i class="fa-solid fa-sack-dollar fa-2x opacity-25 ms-auto"></i>
                            </div>
                            <p class="text-white-50 small mb-0 mt-2">Suma total de facturas en este reporte</p>
                        </div>
                    </div>
                </div>

                <!-- Vencido Card -->
                <div class="col-md-6">
                    <div class="card border-0 shadow-sm h-100 bg-danger text-white overflow-hidden position-relative">
                        <div class="card-body p-4">
                            <h6 class="text-white-50 text-uppercase fw-bold mb-2">Deuda Vencida</h6>
                            <div class="d-flex align-items-center mb-0">
                                <h2 class="display-6 fw-bold mb-0 me-3">$<?php echo number_format($total_vencido, 2); ?>
                                </h2>
                                <i class="fa-solid fa-clock-rotate-left fa-2x opacity-25 ms-auto"></i>
                            </div>
                            <p class="text-white-50 small mb-0 mt-2">Monto pendiente con fecha pasada</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tabla de Resultados -->
            <div class="card border-0 shadow-lg">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-light">
                                <tr>
                                    <th class="ps-4 text-secondary text-uppercase small fw-bold">ID</th>
                                    <th class="text-secondary text-uppercase small fw-bold">Cliente</th>
                                    <th class="text-secondary text-uppercase small fw-bold">Emisión</th>
                                    <th class="text-secondary text-uppercase small fw-bold">Vencimiento</th>
                                    <th class="text-center text-secondary text-uppercase small fw-bold">Días Vencido</th>
                                    <th class="text-end text-secondary text-uppercase small fw-bold">Monto</th>
                                    <th class="text-center text-secondary text-uppercase small fw-bold">Estado</th>
                                    <th class="text-end pe-4 text-secondary text-uppercase small fw-bold">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($cobros as $fila):
                                    $is_vencido = ($fila['estado'] !== 'PAGADO' && $fila['dias_vencido'] > 0);
                                    $row_bg = $is_vencido ? 'bg-danger bg-opacity-10' : '';
                                    ?>
                                    <tr class="<?php echo $row_bg; ?>">
                                        <td class="ps-4 fw-medium text-muted">
                                            #<?php echo htmlspecialchars($fila['id_cobro']); ?></td>
                                        <td class="fw-bold text-dark"><?php echo htmlspecialchars($fila['cliente']); ?></td>
                                        <td><?php echo htmlspecialchars($fila['fecha_emision']); ?></td>
                                        <td class="<?php echo $is_vencido ? 'text-danger fw-bold' : ''; ?>">
                                            <?php echo htmlspecialchars($fila['fecha_vencimiento']); ?></td>
                                        <td class="text-center">
                                            <?php
                                            if ($fila['estado'] !== 'PAGADO' && $fila['dias_vencido'] > 0) {
                                                echo "<span class='badge bg-danger'>{$fila['dias_vencido']} días</span>";
                                            } elseif ($fila['estado'] == 'PAGADO') {
                                                echo "<span class='badge bg-light text-muted border'>0</span>";
                                            } else {
                                                echo "<span class='badge bg-success'>A tiempo</span>";
                                            }
                                            ?>
                                        </td>
                                        <td class="text-end fw-bold">$<?php echo number_format($fila['monto_total'], 2); ?></td>
                                        <td class="text-center">
                                            <?php
                                            $badge_class = 'secondary';
                                            switch ($fila['estado']) {
                                                case 'PAGADO':
                                                    $badge_class = 'success';
                                                    break;
                                                case 'VENCIDO':
                                                    $badge_class = 'danger';
                                                    break;
                                                case 'PENDIENTE':
                                                    $badge_class = 'warning text-dark';
                                                    break;
                                            }
                                            ?>
                                            <span
                                                class="badge bg-<?php echo $badge_class; ?>"><?php echo htmlspecialchars($fila['estado']); ?></span>
                                        </td>
                                        <td class="text-end pe-4">
                                            <a href="../principal/modifica_cobro.php?id=<?php echo $fila['id_cobro']; ?>"
                                                class="btn btn-sm btn-outline-primary rounded-2">
                                                <i class="fa-solid fa-eye me-1"></i>Detalles
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</main>

<?php include $path_to_root . 'paginas/includes/layout_foot.php'; ?>