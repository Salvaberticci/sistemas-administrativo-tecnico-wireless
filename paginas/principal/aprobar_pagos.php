<?php
// aprobar_pagos.php - Panel administrativo para revisar pagos reportados por clientes
require_once '../conexion.php';

// Configuración Layout
$path_to_root = "../../";
$page_title = "Aprobar Reportes de Pago";
$breadcrumb = ["Cobranzas"];
$back_url = "../menu.php";
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



<script src="<?php echo $path_to_root; ?>js/jquery.min.js"></script>
<script src="https://unpkg.com/tesseract.js@5.0.3/dist/tesseract.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

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
                    <div class="col-auto">
                        <a href="historial_pagos_reportados.php" class="btn btn-outline-primary btn-sm">
                            <i class="fas fa-history me-1"></i> Ver Historial
                        </a>
                    </div>
                </div>
            </div>

            <?php if (isset($_GET['message'])): ?>
                <div class="px-4 pt-3">
                    <div class="alert alert-<?php echo $_GET['class'] ?? 'info'; ?> alert-dismissible fade show"
                        role="alert">
                        <?php echo htmlspecialchars($_GET['message']); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                </div>
            <?php endif; ?>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover align-middle" id="tablaAprobacion">
                        <thead class="table-light">
                            <tr>
                                <th>Fecha Reporte</th>
                                <th>Cliente / Cédula</th>
                                <th>Detalle Pago</th>
                                <th>Monto (Bs)</th>
                                <th>Meses</th>
                                <th>Comprobante</th>
                                <th class="text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody id="tbodyReportes">
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
                                            <span class="badge bg-primary fs-6">
                                                Bs. <?php echo number_format($row['monto_bs'], 2, ',', '.'); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php
                                            $meses_array = array_filter(explode(',', $row['meses_pagados']));
                                            $cant_meses = count($meses_array);
                                            ?>
                                            <span class="badge bg-info text-dark mb-1 d-inline-block">
                                                <?php echo $cant_meses; ?> mes<?php echo $cant_meses != 1 ? 'es' : ''; ?>
                                            </span>
                                            <div class="text-wrap small text-muted">
                                                <?php echo htmlspecialchars($row['meses_pagados']); ?>
                                            </div>
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
                                                <button type="button" class="btn btn-sm btn-outline-danger"
                                                    onclick="confirmarEliminacion(<?php echo $row['id_reporte']; ?>)"
                                                    title="Eliminar Reporte Permanentemente">
                                                    <i class="fas fa-trash"></i>
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
                        <input type="hidden" name="accion" value="APROBAR">

                        <div class="alert alert-info py-2 small d-flex justify-content-between align-items-center">
                            <span>Al aprobar, se creará un registro en el historial de mensualidades como
                                <strong>PAGADO</strong>.</span>
                            <div class="text-end">
                                <div class="fw-bold text-dark">Monto ingresado por Cliente: <span id="val_monto_usuario"
                                        class="badge bg-primary">0,00 Bs.</span></div>
                                <div class="fw-bold text-dark">Monto detectado en Capture: <span id="val_monto_ocr"
                                        class="badge bg-secondary">Esperando...</span></div>
                            </div>
                        </div>

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Contrato Asociado</label>
                                <select name="id_contrato" id="ap_id_contrato" class="form-select" required>
                                    <option value="">Seleccione contrato...</option>
                                    <!-- Se llenará con AJAX o JS si ya se detectó -->
                                </select>
                                <div class="small text-muted mt-1">Si la Cédula no coincide, busque el contrato
                                    correcto.</div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Monto a Registrar ($)</label>
                                <input type="number" step="0.01" name="monto_total" id="ap_monto_total"
                                    class="form-control fw-bold" required placeholder="Detectando...">
                                <div id="ocrStatus" class="small mt-1">
                                    <div class="spinner-border spinner-border-sm text-primary" role="status"></div>
                                    <span class="text-primary ms-1" id="ocrText">Analizando capture con OCR...</span>
                                </div>
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
                        <h5 class="mb-3">Â¿Seguro que desea rechazar este reporte?</h5>
                        <p class="text-muted">Esta acción no registrará el pago y marcará el reporte como rechazado.</p>
                        <div class="modal-footer border-top-0 pt-0">
                            <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                            <button type="submit" class="btn btn-danger px-4">RECHAZAR PAGO</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Eliminar Reporte -->
    <div class="modal fade" id="modalEliminarReporte" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-sm">
            <div class="modal-content">
                <div class="modal-header bg-dark text-white">
                    <h5 class="modal-title fw-bold">Eliminar Reporte</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form action="procesar_aprobacion_admin.php" method="POST">
                    <div class="modal-body text-center p-4">
                        <input type="hidden" name="id_reporte" id="el_id_reporte">
                        <input type="hidden" name="accion" value="ELIMINAR">
                        <i class="fas fa-trash-alt fa-3x text-danger mb-3"></i>
                        <h6 class="fw-bold">Â¿Eliminar permanentemente?</h6>
                        <p class="text-muted small">Esto borrará el registro y su imagen del servidor.</p>
                    </div>
                    <div class="modal-footer border-top-0 pt-0">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">No</button>
                        <button type="submit" class="btn btn-danger px-4">Sí, Eliminar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

</main>

<script>
    // Global variables for modals and OCR
    let modalAprobar, modalRechazar;
    let currentCapturePath = '';

    $(document).ready(function () {
        // Initialize modals safely after Bootstrap is loaded from layout_foot.php
        const elAprobar = document.getElementById('modalConfirmarAprobacion');
        const elRechazar = document.getElementById('modalRechazar');

        if (elAprobar) modalAprobar = new bootstrap.Modal(elAprobar);
        if (elRechazar) modalRechazar = new bootstrap.Modal(elRechazar);

        // AUTO-REFRESH cada 5 segundos
        let isModalOpen = false;

        // Detectar si hay algún modal abierto para pausar el refresco
        $(document).on('show.bs.modal', '.modal', function () { isModalOpen = true; });
        $(document).on('hidden.bs.modal', '.modal', function () { isModalOpen = false; });

        setInterval(function () {
            if (!isModalOpen) {
                console.log("Refrescando tabla de reportes...");
                fetch('get_reportes_pendientes_ajax.php')
                    .then(r => r.text())
                    .then(html => {
                        const container = document.getElementById('tbodyReportes');
                        if (container) container.innerHTML = html;
                    })
                    .catch(err => console.error("Error en auto-refresh:", err));
            }
        }, 5000);
    });

    window.prepararAprobacion = function (data) {
        currentCapturePath = data.capture_path; // Guardar path para OCR
        document.getElementById('ap_id_reporte').value = data.id_reporte;
        document.getElementById('ap_fecha_pago').value = data.fecha_pago;
        document.getElementById('ap_referencia').value = data.referencia;
        document.getElementById('ap_id_banco').value = data.id_banco_destino || '';
        document.getElementById('ap_monto_total').value = ''; // Limpiar previo

        // Mostrar monto reportado por el usuario
        const montoBs = parseFloat(data.monto_bs || 0);
        document.getElementById('val_monto_usuario').innerText = montoBs.toLocaleString('es-VE', { minimumFractionDigits: 2 }) + ' Bs.';
        document.getElementById('val_monto_ocr').innerText = 'Procesando...';
        document.getElementById('val_monto_ocr').className = 'badge bg-secondary';

        document.getElementById('ap_meses_notas').innerHTML = `<strong>Meses reportados:</strong> ${data.meses_pagados}<br><strong>Justificación:</strong> ${data.concepto || 'N/A'}`;

        // Reset OCR UI
        document.getElementById('ocrStatus').style.display = 'block';
        document.getElementById('ocrText').innerText = 'Analizando capture con OCR...';

        console.log("Preparando aprobación para reporte:", data);

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

        if (modalAprobar) {
            modalAprobar.show();
        } else {
            modalAprobar = new bootstrap.Modal(document.getElementById('modalConfirmarAprobacion'));
            modalAprobar.show();
        }

        // Auto-trigger OCR after short delay to allow modal animation
        setTimeout(() => ejecutarOCR(), 500);
    }

    window.confirmarRechazo = function (id) {
        document.getElementById('rej_id_reporte').value = id;
        if (modalRechazar) {
            modalRechazar.show();
        } else {
            modalRechazar = new bootstrap.Modal(document.getElementById('modalRechazar'));
            modalRechazar.show();
        }
    }

    window.confirmarEliminacion = function (id) {
        const modalEliminar = new bootstrap.Modal(document.getElementById('modalEliminarReporte'));
        document.getElementById('el_id_reporte').value = id;
        modalEliminar.show();
    }

    // --- LÓGICA OCR (Auto-ejecuta al abrir modal) ---
    async function ejecutarOCR() {
        if (!currentCapturePath) {
            document.getElementById('ocrStatus').style.display = 'none';
            return;
        }

        const status = document.getElementById('ocrStatus');
        const statusText = document.getElementById('ocrText');
        const inputMonto = document.getElementById('ap_monto_total');

        status.style.display = 'block';
        statusText.innerText = 'Inicializando OCR...';

        try {
            // Path absoluto para Tesseract (considerando que estamos en paginas/principal/)
            const imageUrl = `../../${currentCapturePath}`;

            const result = await Tesseract.recognize(
                imageUrl,
                'spa',
                {
                    logger: m => {
                        if (m.status === 'recognizing text') {
                            let prog = Math.round(m.progress * 100);
                            statusText.innerText = `Leyendo... ${prog}%`;
                        }
                    }
                }
            );

            const text = result.data.text;
            console.log("OCR Text extracted:", text);

            // Regex mejorada: Busca patrones como "Bs. 1.234,56", "Monto: 500.00" o "1.234,56 Bs"
            // Caso 1: Etiqueta -> Numero (Bs. 1.000)
            const regexLabelNum = /(?:Bs|VES|Monto|Pagado|Importe|Total)[.:\s]*([\d.]+,\d{2}|[\d,]+.\d{2}|[\d.,]+)/gi;
            // Caso 2: Numero -> Etiqueta (1.000 Bs)
            const regexNumLabel = /([\d.]+,\d{2}|[\d,]+.\d{2}|[\d.,]+)[.:\s]*(?:Bs|VES)/gi;

            let detectedVal = null;

            // Intentar Caso 1
            let m1 = [...text.matchAll(regexLabelNum)];
            for (const match of m1) {
                let clean = match[1].trim();
                let val = parseOCRNumber(clean);
                if (val > 0) { detectedVal = val; break; }
            }

            // Intentar Caso 2 si no se detectó nada
            if (!detectedVal) {
                let m2 = [...text.matchAll(regexNumLabel)];
                for (const match of m2) {
                    let clean = match[1].trim();
                    let val = parseOCRNumber(clean);
                    if (val > 0) { detectedVal = val; break; }
                }
            }

            function parseOCRNumber(str) {
                // Normalizar formato (quitar puntos de miles, cambiar coma a punto decimal)
                let clean = str;
                if (clean.includes(',') && clean.includes('.')) {
                    // Formato XXX.XXX,XX o XXX,XXX.XX (depende del OCR)
                    // Asumimos el más común en Venezuela: . miles , decimal
                    if (clean.lastIndexOf(',') > clean.lastIndexOf('.')) {
                        clean = clean.replace(/\./g, '').replace(',', '.');
                    } else {
                        clean = clean.replace(/,/g, '');
                    }
                } else if (clean.includes(',')) {
                    clean = clean.replace(',', '.');
                }
                return parseFloat(clean);
            }

            if (detectedVal) {
                inputMonto.value = detectedVal.toFixed(2);
                const displayVal = detectedVal.toLocaleString('es-VE', { minimumFractionDigits: 2 }) + ' Bs.';
                statusText.innerHTML = '<span class="text-success"><i class="fas fa-check"></i> Monto detectado en capture: <strong>Bs. ' + displayVal + '</strong></span>';

                // Actualizar comparativa en el modal
                const valOcrEl = document.getElementById('val_monto_ocr');
                valOcrEl.innerText = displayVal;
                valOcrEl.className = 'badge bg-success';
            } else {
                statusText.innerHTML = '<span class="text-warning"><i class="fas fa-exclamation-triangle"></i> No detectado en capture — ingrese manualmente</span>';
                document.getElementById('val_monto_ocr').innerText = 'No detectado';
                document.getElementById('val_monto_ocr').className = 'badge bg-warning text-dark';
            }

        } catch (err) {
            console.error("OCR Error:", err);
            statusText.innerHTML = '<span class="text-muted"><i class="fas fa-times"></i> OCR no disponible</span>';
        }
    }
</script>

<?php require_once '../includes/layout_foot.php'; ?>