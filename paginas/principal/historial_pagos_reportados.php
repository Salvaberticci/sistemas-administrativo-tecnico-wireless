<?php
// historial_pagos_reportados.php - Historial de reportes de pago (Aprobados y Rechazados)
require_once '../conexion.php';

// Configuración Layout
$path_to_root = "../../";
$page_title = "Historial de Reportes de Pago";
$breadcrumb = ["Cobranzas"];
$back_url = "../menu.php";
require_once '../includes/layout_head.php';
require_once '../includes/sidebar.php';

// Filtros
$estado_filtro = isset($_GET['estado']) ? $conn->real_escape_string($_GET['estado']) : '';
$hoy = date('Y-m-d');
$fecha_inicio = isset($_GET['fecha_inicio']) ? $conn->real_escape_string($_GET['fecha_inicio']) : date('Y-m-01');
$fecha_fin = isset($_GET['fecha_fin']) ? $conn->real_escape_string($_GET['fecha_fin']) : date('Y-m-d');

// Validaciones PHP: No exceder hoy
if ($fecha_inicio > $hoy)
    $fecha_inicio = $hoy;
if ($fecha_fin > $hoy)
    $fecha_fin = $hoy;
// Asegurar Desde <= Hasta
if ($fecha_inicio > $fecha_fin)
    $fecha_inicio = $fecha_fin;

// Construcción de la consulta
$where = "pr.estado != 'PENDIENTE'";
if (!empty($estado_filtro)) {
    $where .= " AND pr.estado = '$estado_filtro'";
}
if (!empty($fecha_inicio) && !empty($fecha_fin)) {
    $where .= " AND DATE(pr.fecha_registro) BETWEEN '$fecha_inicio' AND '$fecha_fin'";
}

$sql = "
    SELECT 
        pr.*,
        b.nombre_banco AS banco_destino
    FROM pagos_reportados pr
    LEFT JOIN bancos b ON pr.id_banco_destino = b.id_banco
    WHERE $where
    ORDER BY pr.fecha_registro DESC
";
$resultado = $conn->query($sql);
?>

<main class="main-content">
    <?php include '../includes/header.php'; ?>

    <div class="page-content">
        <div class="row mb-4">
            <div class="col-md-8">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="aprobar_pagos.php">Aprobación de Pagos</a></li>
                        <li class="breadcrumb-item active">Historial</li>
                    </ol>
                </nav>
                <h4 class="fw-bold text-primary">Historial de Reportes de Pago</h4>
                <p class="text-muted">Registro de todos los reportes procesados (Aprobados y Rechazados).</p>
            </div>
            <div class="col-md-4 text-md-end">
                <a href="aprobar_pagos.php" class="btn btn-secondary btn-sm">
                    <i class="fas fa-arrow-left me-1"></i> Volver a Pendientes
                </a>
            </div>
        </div>

        <!-- Filtros -->
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-body">
                <form method="GET" class="row g-3 align-items-end">
                    <div class="col-md-3">
                        <label class="form-label small fw-bold">Desde</label>
                        <input type="date" name="fecha_inicio" id="fecha_inicio" class="form-control form-control-sm"
                            value="<?php echo $fecha_inicio; ?>" max="<?php echo $hoy; ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small fw-bold">Hasta</label>
                        <input type="date" name="fecha_fin" id="fecha_fin" class="form-control form-control-sm"
                            value="<?php echo $fecha_fin; ?>" max="<?php echo $hoy; ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small fw-bold">Estado</label>
                        <select name="estado" class="form-select form-select-sm">
                            <option value="">Todos (Procesados)</option>
                            <option value="APROBADO" <?php echo ($estado_filtro == 'APROBADO') ? 'selected' : ''; ?>>
                                Aprobados</option>
                            <option value="RECHAZADO" <?php echo ($estado_filtro == 'RECHAZADO') ? 'selected' : ''; ?>>
                                Rechazados</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <button type="submit" class="btn btn-primary btn-sm w-100">
                            <i class="fas fa-filter me-1"></i> Filtrar
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <div class="card shadow-sm border-0">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Fecha Procesado</th>
                                <th>Cliente / Cédula</th>
                                <th>Monto / Referencia</th>
                                <th>Meses</th>
                                <th>Estado</th>
                                <th class="text-center">Comprobante</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($resultado && $resultado->num_rows > 0): ?>
                                <?php while ($row = $resultado->fetch_assoc()): ?>
                                    <tr>
                                        <td>
                                            <span class="d-block fw-bold small">
                                                <?php echo date('d/m/Y', strtotime($row['fecha_registro'])); ?>
                                            </span>
                                            <small class="text-muted">
                                                <?php echo date('H:i', strtotime($row['fecha_registro'])); ?>
                                            </small>
                                        </td>
                                        <td>
                                            <span class="d-block fw-bold">
                                                <?php echo htmlspecialchars($row['nombre_titular']); ?>
                                            </span>
                                            <small class="badge bg-secondary">
                                                <?php echo htmlspecialchars($row['cedula_titular']); ?>
                                            </small>
                                        </td>
                                        <td>
                                            <div class="small">
                                                <strong>Ref:</strong>
                                                <?php echo htmlspecialchars($row['referencia'] ?: 'N/A'); ?><br>
                                                <small class="text-muted">
                                                    <?php echo htmlspecialchars($row['metodo_pago']); ?>
                                                </small>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="text-wrap small">
                                                <?php echo htmlspecialchars($row['meses_pagados']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if ($row['estado'] == 'APROBADO'): ?>
                                                <span class="badge bg-success"><i class="fas fa-check-circle me-1"></i>
                                                    Aprobado</span>
                                            <?php else: ?>
                                                <span class="badge bg-danger"><i class="fas fa-times-circle me-1"></i>
                                                    Rechazado</span>
                                                <div class="small text-muted mt-1" style="max-width: 200px; font-size: 0.75rem;">
                                                    <?php
                                                    // Extraer motivo de rechazo si existe en el concepto
                                                    $partes = explode('MOTIVO RECHAZO:', $row['concepto']);
                                                    echo htmlspecialchars(isset($partes[1]) ? trim($partes[1]) : 'Rechazado sin motivo específico.');
                                                    ?>
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center">
                                            <?php if (!empty($row['capture_path']) && file_exists("../../" . $row['capture_path'])): ?>
                                                <a href="../../<?php echo $row['capture_path']; ?>" target="_blank"
                                                    class="btn btn-sm btn-outline-info">
                                                    <i class="fas fa-image"></i>
                                                </a>
                                            <?php else: ?>
                                                <span class="text-muted small italic">Sin foto</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="text-center py-5 text-muted">
                                        No se encontraron reportes procesados en este rango de fechas.
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</main>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const inputDesde = document.getElementById('fecha_inicio');
        const inputHasta = document.getElementById('fecha_fin');

        // Sincronizar límites al cargar
        inputDesde.setAttribute('max', inputHasta.value);
        inputHasta.setAttribute('min', inputDesde.value);

        // Al cambiar "Desde", actualizar el "min" de "Hasta"
        inputDesde.addEventListener('change', function () {
            inputHasta.setAttribute('min', this.value);
            if (inputHasta.value < this.value) {
                inputHasta.value = this.value;
            }
        });

        // Al cambiar "Hasta", actualizar el "max" de "Desde"
        inputHasta.addEventListener('change', function () {
            inputDesde.setAttribute('max', this.value);
            if (inputDesde.value > this.value) {
                inputDesde.value = this.value;
            }
        });
    });
</script>

<?php require_once '../includes/layout_foot.php'; ?>