<?php
// aprobar_pagos.php - Panel administrativo para revisar pagos reportados por clientes
require_once '../conexion.php';

// Configuración Layout
$path_to_root = "../../";
$page_title = "Aprebar Reportes de Pago";
require_once '../includes/layout_head.php';
require_once '../includes/sidebar.php';

// Consulta de pagos pendientes
$sql = "
    SELECT 
        pr.*,
        b.nombre_banco AS banco_destino
    FROM pagos_reportados pr
    LEFT JOIN bancos b ON pr.id_banco_destino = b.id_banco
    WHERE pr.estado = 'PENDIENTE'
    ORDER BY pr.fecha_registro DESC
";
$resultado = $conn->query($sql);
?>

<main class="main-content">
    <?php include '../includes/header.php'; ?>

    <div class="page-content">
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-white py-3">
                <div class="row align-items-center">
                    <div class="col">
                        <h5 class="fw-bold text-primary mb-0">Reportes de Pago Pendientes</h5>
                        <p class="text-muted small mb-0">Revisión manual de reportes enviados por clientes vía link
                            público</p>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover align-middle" id="tablaAprobacion">
                        <thead class="table-light">
                            <tr>
                                <th>Fecha Reporte</th>
                                <th>Cliente / Cédula</th>
                                <th>Detalle Pago</th>
                                <th>Meses</th>
                                <th>Comprobante</th>
                                <th class="text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($resultado && $resultado->num_rows > 0): ?>
                                <?php while ($row = $resultado->fetch_assoc()): ?>
                                    <tr>
                                        <td>
                                            <span class="d-block fw-bold">
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
                                            <div class="text-muted small">
                                                <?php echo htmlspecialchars($row['telefono_titular']); ?>
                                            </div>
                                            <?php if ($row['id_contrato_asociado']): ?>
                                                <div class="text-success small mt-1"><i class="fas fa-link"></i> Contrato detectado
                                                    #
                                                    <?php echo $row['id_contrato_asociado']; ?>
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="small">
                                                <strong>Monto:</strong> (Revisar Capture)<br>
                                                <strong>Método:</strong>
                                                <?php echo htmlspecialchars($row['metodo_pago']); ?><br>
                                                <?php if ($row['referencia']): ?>
                                                    <strong>Ref:</strong>
                                                    <?php echo htmlspecialchars($row['referencia']); ?><br>
                                                <?php endif; ?>
                                                <?php if ($row['banco_destino']): ?>
                                                    <strong>Banco:</strong>
                                                    <?php echo htmlspecialchars($row['banco_destino']); ?>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="text-wrap small">
                                                <?php echo htmlspecialchars($row['meses_pagados']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <a href="../../<?php echo $row['capture_path']; ?>" target="_blank"
                                                class="btn btn-sm btn-outline-info">
                                                <i class="fas fa-image me-1"></i> Ver Foto
                                            </a>
                                        </td>
                                        <td class="text-center">
                                            <div class="btn-group">
                                                <button type="button" class="btn btn-sm btn-success"
                                                    onclick="prepararAprobacion(<?php echo htmlspecialchars(json_encode($row)); ?>)"
                                                    title="Aprobar y Registrar">
                                                    <i class="fas fa-check"></i>
                                                </button>
                                                <button type="button" class="btn btn-sm btn-danger"
                                                    onclick="confirmarRechazo(<?php echo $row['id_reporte']; ?>)"
                                                    title="Rechazar">
                                                    <i class="fas fa-times"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="text-center py-5">
                                        <i class="fas fa-check-circle fa-3x text-light mb-3"></i>
                                        <p class="text-muted">No hay reportes de pago pendientes por revisar.</p>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Aprobar Pago -->
    <div class="modal fade" id="modalConfirmarAprobacion" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title fw-bold">Confirmar Aprobación de Pago</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form action="procesar_aprobacion_admin.php" method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="id_reporte" id="ap_id_reporte">

                        <div class="alert alert-info py-2 small">
                            Al aprobar, se creará un registro en el historial de mensualidades como
                            <strong>PAGADO</strong> con origen <strong>LINK</strong>.
                        </div>

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Contrato Asociado</label>
                                <select name="id_contrato" id="ap_id_contrato" class="form-select" required>
                                    <option value="">Seleccione contrato...</option>
                                    <!-- Se llenará con AJAX o JS si ya se detectó -->
                                </select>
                                <div class="small text-muted mt-1">Si la cédula no coincide, busque el contrato
                                    correcto.</div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Monto a Registrar ($)</label>
                                <input type="number" step="0.01" name="monto_total" class="form-control" required
                                    placeholder="Verificar en el capture">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold">Fecha Pago</label>
                                <input type="date" name="fecha_pago" id="ap_fecha_pago" class="form-control" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold">Referencia</label>
                                <input type="text" name="referencia" id="ap_referencia" class="form-control">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold">Banco Receptor</label>
                                <select name="id_banco" id="ap_id_banco" class="form-select" required>
                                    <?php
                                    // Recargar bancos para el modal
                                    $res_banks = $conn->query("SELECT id_banco, nombre_banco FROM bancos");
                                    while ($b = $res_banks->fetch_assoc()) {
                                        echo "<option value='" . $b['id_banco'] . "'>" . htmlspecialchars($b['nombre_banco']) . "</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                            <div class="col-12">
                                <label class="form-label fw-bold">Notas del Reporte</label>
                                <div id="ap_meses_notas" class="alert alert-light border small"></div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                        <button type="submit" class="btn btn-success fw-bold px-4">APROBAR Y REGISTRAR</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Rechazar -->
    <div class="modal fade" id="modalRechazar" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title fw-bold">Rechazar Reporte de Pago</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form action="procesar_aprobacion_admin.php" method="POST">
                    <div class="modal-body text-center p-4">
                        <input type="hidden" name="id_reporte" id="rej_id_reporte">
                        <input type="hidden" name="accion" value="RECHAZAR">
                        <i class="fas fa-exclamation-circle fa-4x text-danger mb-3"></i>
                        <h5 class="mb-3">¿Seguro que desea rechazar este reporte?</h5>
                        <p class="text-muted">Esta acción no registrará el pago y marcará el reporte como rechazado.</p>
                        <div class="text-start mt-3">
                            <label class="form-label fw-bold small">Motivo del rechazo (Opcional)</label>
                            <textarea class="form-control" name="motivo" rows="2"
                                placeholder="Referencia inválida, capture ilegible, etc."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer border-top-0 pt-0">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-danger px-4">RECHAZAR PAGO</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

</main>

<?php require_once '../includes/layout_foot.php'; ?>

<script>
    const modalAprobar = new bootstrap.Modal(document.getElementById('modalConfirmarAprobacion'));
    const modalRechazar = new bootstrap.Modal(document.getElementById('modalRechazar'));

    function prepararAprobacion(data) {
        document.getElementById('ap_id_reporte').value = data.id_reporte;
        document.getElementById('ap_fecha_pago').value = data.fecha_pago;
        document.getElementById('ap_referencia').value = data.referencia;
        document.getElementById('ap_id_banco').value = data.id_banco_destino || '';
        document.getElementById('ap_meses_notas').innerHTML = `<strong>Meses reportados:</strong> ${data.meses_pagados}<br><strong>Justificación:</strong> ${data.concepto || 'N/A'}`;

        // Cargar contratos dinámicamente o seleccionar el detectado
        const select = document.getElementById('ap_id_contrato');
        select.innerHTML = '<option value="">Cargando contratos...</option>';

        fetch(`buscar_contratos.php?q=${data.cedula_titular}&limit=10`)
            .then(r => r.json())
            .then(contratos => {
                select.innerHTML = '<option value="">Seleccione contrato...</option>';
                contratos.forEach(c => {
                    const selected = (c.id == data.id_contrato_asociado) ? 'selected' : '';
                    select.innerHTML += `<option value="${c.id}" ${selected}>#${c.id} - ${c.nombre_completo} (${c.cedula})</option>`;
                });
            });

        modalAprobar.show();
    }

    function confirmarRechazo(id) {
        document.getElementById('rej_id_reporte').value = id;
        modalRechazar.show();
    }
</script>