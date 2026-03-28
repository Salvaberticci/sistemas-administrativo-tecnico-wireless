<?php
require_once '../conexion.php';

// 1. CAPTURA Y SANEO DE PARÁMETROS DE FILTRO
$fecha_inicio = isset($_GET['fecha_inicio']) ? $_GET['fecha_inicio'] : date('Y-m-d', strtotime('-30 days'));
$fecha_fin = isset($_GET['fecha_fin']) ? $_GET['fecha_fin'] : date('Y-m-d');
$estado_filtro = isset($_GET['estado']) ? $_GET['estado'] : 'TODOS';
$banco_filtro = isset($_GET['id_banco']) ? $_GET['id_banco'] : '';
$origen_filtro = isset($_GET['origen']) ? $_GET['origen'] : '';
$ref_filtro = isset($_GET['referencia']) ? $_GET['referencia'] : '';

$cobros = [];
$total_cobrado = 0; // Solo PAGADO
$deuda_clientes = 0; // PENDIENTE (incluye lo que antes era VENCIDO)

// 2. CONSTRUCCIÓN DINÁMICA DE LA CLÁUSULA WHERE
$where_clause = " WHERE 1=1 ";
$params = [];
$types = '';

if ($estado_filtro !== 'TODOS') {
    $where_clause .= " AND cxc.estado = ? ";
    $params[] = $estado_filtro;
    $types .= 's';
}

if (!empty($fecha_inicio) && !empty($fecha_fin)) {
    $where_clause .= " AND cxc.fecha_emision >= ? AND cxc.fecha_emision <= ? ";
    $params[] = $fecha_inicio;
    $params[] = $fecha_fin;
    $types .= 'ss';
}

if (!empty($banco_filtro)) {
    $where_clause .= " AND cxc.id_banco = ? ";
    $params[] = $banco_filtro;
    $types .= 'i';
}

if (!empty($origen_filtro)) {
    $where_clause .= " AND cxc.origen = ? ";
    $params[] = $origen_filtro;
    $types .= 's';
}

if (!empty($ref_filtro)) {
    $where_clause .= " AND cxc.referencia_pago LIKE ? ";
    $params[] = "%$ref_filtro%";
    $types .= 's';
}

// 3. CONSULTA SQL
$sql = "
    SELECT 
        cxc.id_cobro, 
        cxc.fecha_emision, 
        cxc.fecha_vencimiento, 
        cxc.monto_total, 
        cxc.estado,
        cxc.referencia_pago,
        cxc.origen,
        co.nombre_completo AS cliente,
        co.ip_onu,
        DATEDIFF(CURRENT_DATE(), cxc.fecha_vencimiento) AS dias_vencido,
        b.nombre_banco
    FROM cuentas_por_cobrar cxc
    JOIN contratos co ON cxc.id_contrato = co.id
    LEFT JOIN bancos b ON cxc.id_banco = b.id_banco
    " . $where_clause . "
    ORDER BY cxc.fecha_emision DESC
";

$stmt = $conn->prepare($sql);

if ($stmt) {
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }

    $stmt->execute();
    $resultado = $stmt->get_result();

    while ($fila = $resultado->fetch_assoc()) {
        $cobros[] = $fila;
        
        if ($fila['estado'] === 'PAGADO') {
            $total_cobrado += $fila['monto_total'];
        } else if ($fila['estado'] === 'PENDIENTE') {
            $deuda_clientes += $fila['monto_total'];
        }
    }
    $stmt->close();
} else {
    $error = "Error al preparar la consulta: " . $conn->error;
}

// Obtener bancos para el filtro
$bancos_res = $conn->query("SELECT id_banco, nombre_banco FROM bancos ORDER BY nombre_banco ASC");
$lista_bancos = [];
if ($bancos_res) {
    while($b = $bancos_res->fetch_assoc()) $lista_bancos[] = $b;
}

// --- TEMPLATE START ---
$path_to_root = "../../";
$page_title = "Reporte de Cobranzas";
$breadcrumb = ["Reportes"];
$back_url = "../menu.php";
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

                    <div class="col-md-2">
                        <label for="estado"
                            class="form-label fw-semibold text-secondary small text-uppercase">Estado</label>
                        <select class="form-select bg-white" name="estado" id="estado">
                            <option value="TODOS" <?php echo ($estado_filtro == 'TODOS') ? 'selected' : ''; ?>>TODOS</option>
                            <option value="PENDIENTE" <?php echo ($estado_filtro == 'PENDIENTE') ? 'selected' : ''; ?>>PENDIENTE</option>
                            <option value="PAGADO" <?php echo ($estado_filtro == 'PAGADO') ? 'selected' : ''; ?>>PAGADO</option>
                            <option value="RECHAZADO" <?php echo ($estado_filtro == 'RECHAZADO') ? 'selected' : ''; ?>>RECHAZADO</option>
                        </select>
                    </div>

                    <div class="col-md-2">
                        <label for="id_banco" class="form-label fw-semibold text-secondary small text-uppercase">Banco</label>
                        <select class="form-select bg-white" name="id_banco" id="id_banco">
                            <option value="">TODOS</option>
                            <?php foreach ($lista_bancos as $b): ?>
                                <option value="<?php echo $b['id_banco']; ?>" <?php echo ($banco_filtro == $b['id_banco']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($b['nombre_banco']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-2">
                        <label for="origen" class="form-label fw-semibold text-secondary small text-uppercase">Origen</label>
                        <select class="form-select bg-white" name="origen" id="origen">
                            <option value="">TODOS</option>
                            <option value="SISTEMA" <?php echo ($origen_filtro == 'SISTEMA') ? 'selected' : ''; ?>>SISTEMA</option>
                            <option value="LINK" <?php echo ($origen_filtro == 'LINK') ? 'selected' : ''; ?>>LINK</option>
                        </select>
                    </div>

                    <div class="col-md-2">
                        <label for="referencia" class="form-label fw-semibold text-secondary small text-uppercase">Referencia</label>
                        <input type="text" class="form-control" name="referencia" id="referencia" 
                               value="<?php echo htmlspecialchars($ref_filtro); ?>" placeholder="Ej: 1234...">
                    </div>

                    <div class="col-md-2">
                        <label for="fecha_inicio"
                            class="form-label fw-semibold text-secondary small text-uppercase">Fecha Desde</label>
                        <input type="date" class="form-control" name="fecha_inicio"
                            value="<?php echo htmlspecialchars($fecha_inicio); ?>">
                    </div>
                    <div class="col-md-2">
                        <label for="fecha_fin" class="form-label fw-semibold text-secondary small text-uppercase">Fecha
                            Hasta</label>
                        <input type="date" class="form-control" name="fecha_fin"
                            value="<?php echo htmlspecialchars($fecha_fin); ?>">
                    </div>

                    <div class="col-md-12 d-flex gap-2 mt-2">
                        <button type="submit" class="btn btn-primary px-4">
                            <i class="fa-solid fa-magnifying-glass me-2"></i>Filtrar Reporte
                        </button>

                        <?php
                        $export_params = http_build_query($_GET);
                        ?>
                        <a href="exportar_cuentas_por_cobrar.php?<?php echo $export_params; ?>" class="btn btn-danger"
                            target="_blank" title="Exportar a PDF">
                            <i class="fa-solid fa-file-pdf"></i> PDF
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
                            <h6 class="text-white-50 text-uppercase fw-bold mb-2">Total Cobrado (PAGADO)</h6>
                            <div class="d-flex align-items-center mb-0">
                                <h2 class="display-6 fw-bold mb-0 me-3">$<?php echo number_format($total_cobrado, 2); ?></h2>
                                <i class="fa-solid fa-sack-dollar fa-2x opacity-25 ms-auto"></i>
                            </div>
                            <p class="text-white-50 small mb-0 mt-2">Monto recolectado efectivamente</p>
                        </div>
                    </div>
                </div>

                <!-- Vencido Card -->
                <div class="col-md-6">
                    <div class="card border-0 shadow-sm h-100 bg-danger text-white overflow-hidden position-relative">
                        <div class="card-body p-4">
                            <h6 class="text-white-50 text-uppercase fw-bold mb-2">Deuda Total Clientes</h6>
                            <div class="d-flex align-items-center mb-0">
                                <h2 class="display-6 fw-bold mb-0 me-3">$<?php echo number_format($deuda_clientes, 2); ?></h2>
                                <i class="fa-solid fa-clock-rotate-left fa-2x opacity-25 ms-auto"></i>
                            </div>
                            <p class="text-white-50 small mb-0 mt-2">Suma de todos los cargos pendientes</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tabla de Resultados -->
            <div class="card border-0 shadow-lg">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0" id="tabla_reporte_cobranza">
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
                                            <?php echo htmlspecialchars($fila['fecha_vencimiento']); ?>
                                        </td>
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
                                                case 'PENDIENTE':
                                                    $badge_class = 'warning text-dark';
                                                    break;
                                                case 'RECHAZADO':
                                                    $badge_class = 'secondary';
                                                    break;
                                            }
                                            ?>
                                            <span
                                                class="badge bg-<?php echo $badge_class; ?>"><?php echo htmlspecialchars($fila['estado']); ?></span>
                                        </td>
                                        <td class="text-end pe-4">
                                            <button type="button" onclick="verDetallesCobro(<?php echo $fila['id_cobro']; ?>)"
                                                    class="btn btn-sm btn-outline-primary rounded-2 shadow-sm">
                                                <i class="fa-solid fa-circle-info me-1"></i>Ver Detalles
                                            </button>
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

<!-- Modal Detalles Cobro (Justificación) -->
<div class="modal fade" id="modalJustificacion" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-dark text-white border-bottom-0 p-3">
                <div class="d-flex align-items-center">
                    <div class="bg-primary rounded-circle p-2 me-3 d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                        <i class="fas fa-file-invoice-dollar text-white"></i>
                    </div>
                    <div>
                        <h5 class="modal-title fw-bold mb-0 text-white">Detalles del Cobro</h5>
                        <p class="text-white-50 small mb-0">Información técnica y desglose del pago</p>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-0">
                <!-- Loader -->
                <div id="justif_loader" class="text-center py-5">
                    <div class="spinner-border text-primary" role="status"></div>
                    <p class="text-muted mt-2">Cargando detalles...</p>
                </div>
                
                <!-- Content -->
                <div id="justif_content" class="d-none">
                    <div class="p-4 border-bottom bg-light">
                        <div class="row g-3">
                            <div class="col-md-7">
                                <label class="text-uppercase small fw-bold text-muted d-block mb-1">Cliente / Titular</label>
                                <h5 id="justif_cliente_nombre" class="fw-bold text-dark mb-1">-</h5>
                                <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2">ID Contrato: #<span id="justif_id_contrato">-</span></span>
                            </div>
                            <div class="col-md-5 text-md-end">
                                <label class="text-uppercase small fw-bold text-muted d-block mb-1">Monto Total Reportado</label>
                                <h3 id="justif_monto" class="fw-bold text-primary mb-0">$0.00</h3>
                                <small class="text-muted">ID Cobro: #<span id="justif_id_cobro">-</span></small>
                            </div>
                        </div>
                    </div>

                    <div class="p-4">
                        <div class="row g-4 mb-4">
                            <div class="col-md-4">
                                <div class="d-flex align-items-center mb-3">
                                    <i class="fas fa-calendar-alt text-muted me-2"></i>
                                    <div>
                                        <small class="text-uppercase text-muted fw-bold d-block" style="font-size: 0.7rem;">Fecha Operación</small>
                                        <span id="justif_fecha_creacion" class="fw-medium text-dark">-</span>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="d-flex align-items-center mb-3">
                                    <i class="fas fa-hashtag text-muted me-2"></i>
                                    <div>
                                        <small class="text-uppercase text-muted fw-bold d-block" style="font-size: 0.7rem;">Referencia</small>
                                        <span id="justif_referencia" class="fw-medium text-dark">-</span>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="d-flex align-items-center mb-3">
                                    <i class="fas fa-university text-muted me-2"></i>
                                    <div>
                                        <small class="text-uppercase text-muted fw-bold d-block" style="font-size: 0.7rem;">Banco Destino</small>
                                        <span id="justif_banco" class="fw-medium text-dark">-</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="card border bg-light-subtle mb-4">
                            <div class="card-header bg-white py-2 small fw-bold text-muted text-uppercase">Desglose de Conceptos</div>
                            <div class="card-body p-0">
                                <table class="table table-sm table-borderless align-middle mb-0">
                                    <thead class="bg-light">
                                        <tr>
                                            <th class="ps-3 small text-muted">ID Cargo</th>
                                            <th class="small text-muted">Concepto/Descripción</th>
                                            <th class="text-end pe-3 small text-muted">Monto</th>
                                        </tr>
                                    </thead>
                                    <tbody id="justif_conceptos_body" class="small">
                                        <!-- Dinámico -->
                                    </tbody>
                                    <tfoot class="border-top">
                                        <tr>
                                            <th colspan="2" class="ps-3 py-2 text-dark">TOTAL PAGADO</th>
                                            <th id="justif_total_pagado" class="text-end pe-3 py-2 text-primary fw-bold">$0.00</th>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>

                        <div class="row g-4">
                            <div class="col-md-6">
                                <div class="p-3 rounded border bg-white h-100">
                                    <label class="text-uppercase small fw-bold text-muted d-block mb-2"><i class="fas fa-user-shield me-1"></i> Auditoría</label>
                                    <div class="mb-3">
                                        <small class="text-muted d-block">Registrado por:</small>
                                        <span id="justif_autorizado" class="fw-bold text-dark">-</span>
                                    </div>
                                    <div class="bg-light p-2 rounded small text-muted" id="justif_texto">
                                        -
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="p-3 rounded border bg-white h-100">
                                    <label class="text-uppercase small fw-bold text-muted d-block mb-2"><i class="fas fa-image me-1"></i> Comprobante</label>
                                    <div id="justif_capture_container" class="text-center d-none">
                                        <a href="#" id="justif_capture_link" target="_blank">
                                            <img src="" id="justif_capture_img" class="img-fluid rounded shadow-sm border" style="max-height: 150px;" title="Clic para ampliar">
                                        </a>
                                        <p class="small text-muted mt-1 mb-0">Clic para ver en tamaño completo</p>
                                    </div>
                                    <div id="justif_no_capture" class="text-center p-4 bg-light rounded text-muted small border-dashed">
                                        <i class="fas fa-times-circle d-block mb-1"></i>
                                        Sin imagen de comprobante
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer bg-light border-top-0">
                <button type="button" class="btn btn-secondary px-4 shadow-sm" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<?php 
include $path_to_root . 'paginas/includes/layout_foot.php'; 
?>

<script>
$(document).ready(function() {
    // Inicializar DataTable
    $('#tabla_reporte_cobranza').DataTable({
        "order": [[2, "desc"]], // Ordenar por Emisión (columna 2) desc
        "pageLength": 25,
        "language": {
            "sProcessing": "Procesando...",
            "sLengthMenu": "Mostrar _MENU_ registros",
            "sZeroRecords": "No se encontraron resultados",
            "sEmptyTable": "Ningún dato disponible en esta tabla",
            "sInfo": "Mostrando del _START_ al _END_ de _TOTAL_",
            "sInfoEmpty": "Mostrando 0 al 0 de 0",
            "sInfoFiltered": "(filtrado de _MAX_)",
            "sSearch": "Buscar en resultados:",
            "oPaginate": {
                "sFirst": "Primero",
                "sLast": "Último",
                "sNext": "Sig.",
                "sPrevious": "Ant."
            }
        }
    });
});

function verDetallesCobro(idCobro) {
    const loader = document.getElementById('justif_loader');
    const content = document.getElementById('justif_content');
    
    loader.classList.remove('d-none');
    content.classList.add('d-none');

    var modal = new bootstrap.Modal(document.getElementById('modalJustificacion'));
    modal.show();

    fetch(`../principal/get_justificacion_data.php?id_cobro=${idCobro}`)
        .then(r => r.json())
        .then(res => {
            loader.classList.add('d-none');
            if (res.success) {
                const d = res.data;
                document.getElementById('justif_cliente_nombre').textContent = d.nombre_cliente;
                document.getElementById('justif_id_contrato').textContent = d.id_contrato;
                document.getElementById('justif_id_cobro').textContent = idCobro;
                document.getElementById('justif_monto').textContent = '$' + parseFloat(d.monto_cargado).toFixed(2);
                document.getElementById('justif_fecha_creacion').textContent = new Date(d.fecha_creacion).toLocaleString();
                document.getElementById('justif_referencia').textContent = d.referencia_pago || 'N/A';
                document.getElementById('justif_banco').textContent = d.nombre_banco || 'No especificado';
                document.getElementById('justif_autorizado').textContent = d.autorizado_por;
                document.getElementById('justif_texto').innerHTML = (d.justificacion || '').replace(/\n/g, '<br>');
                
                // Renderizar tabla de conceptos
                const tbody = document.getElementById('justif_conceptos_body');
                tbody.innerHTML = '';
                let totalAcumulado = 0;
                
                if (res.all_concepts && res.all_concepts.length > 0) {
                    res.all_concepts.forEach(c => {
                        const montoVal = parseFloat(c.monto_cargado);
                        totalAcumulado += montoVal;
                        const tr = document.createElement('tr');
                        tr.innerHTML = `
                            <td>${c.id_cobro}</td>
                            <td>${(c.justificacion || 'Cobro').split(' - ')[0]}</td>
                            <td class="text-end fw-bold">$${montoVal.toFixed(2)}</td>
                        `;
                        tbody.appendChild(tr);
                    });
                }
                document.getElementById('justif_total_pagado').textContent = '$' + totalAcumulado.toFixed(2);

                // Manejo del Capture
                const img = document.getElementById('justif_capture_img');
                const link = document.getElementById('justif_capture_link');
                const imgContainer = document.getElementById('justif_capture_container');
                const noCapContainer = document.getElementById('justif_no_capture');

                if (d.capture_pago) {
                    let path = d.capture_pago;
                    if (path.startsWith('../../')) path = path.replace('../../', '');
                    
                    img.src = '../../' + path;
                    link.href = '../../' + path;
                    imgContainer.classList.remove('d-none');
                    noCapContainer.classList.add('d-none');
                } else {
                    imgContainer.classList.add('d-none');
                    noCapContainer.classList.remove('d-none');
                }

                content.classList.remove('d-none');
            } else {
                Swal.fire('Error', res.message, 'error');
                modal.hide();
            }
        })
        .catch(err => {
            console.error(err);
            Swal.fire('Error', 'No se pudo conectar con el servidor.', 'error');
            modal.hide();
        });
}
</script>