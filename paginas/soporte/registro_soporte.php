<?php
// paginas/soporte/registro_soporte.php
// Formulario de Registro de Soporte Técnico (Vista Admin)

$path_to_root = "../../";
include_once $path_to_root . 'paginas/conexion.php';
include $path_to_root . 'paginas/includes/layout_head.php';
include $path_to_root . 'paginas/includes/sidebar.php';
include $path_to_root . 'paginas/includes/header.php';
?>

<style>
    .signature-pad {
        border: 2px dashed #ccc;
        border-radius: 5px;
        width: 100%;
        height: 150px;
        background-color: #f8f9fa;
        touch-action: none;
    }
    .section-title {
        background-color: #f1f3f5;
        padding: 10px;
        font-weight: bold;
        border-left: 4px solid #0d6efd;
        margin-top: 20px;
        margin-bottom: 15px;
        border-radius: 0 4px 4px 0;
    }
</style>

<main class="main-content">
    <div class="page-content">
        <div class="container-fluid">
            <!-- Header -->
            <div class="row mb-4">
                <div class="col-12">
                    <h2 class="h4 fw-bold mb-1 text-primary">Registrar Soporte Técnico</h2>
                    <p class="text-muted mb-0">Complete la ficha técnica del servicio.</p>
                </div>
            </div>

            <!-- Alertas -->
            <?php if (isset($_GET['status'])): ?>
                <div class="alert alert-<?php echo $_GET['status'] == 'success' ? 'success' : 'danger'; ?> alert-dismissible fade show">
                    <?php echo isset($_GET['msg']) ? htmlspecialchars($_GET['msg']) : ''; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <form action="guardar_soporte.php" method="POST" id="formSoporteAdmin">
                        
                        <!-- 1. Encabezado -->
                        <div class="row mb-3">
                            <div class="col-md-4 mb-3">
                                <label class="form-label fw-bold">Fecha</label>
                                <input type="date" class="form-control" name="fecha_soporte" value="<?php echo date('Y-m-d'); ?>" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label fw-bold">Técnico Asignado</label>
                                <input type="text" class="form-control" name="tecnico" placeholder="Nombre del técnico" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label fw-bold">Sector</label>
                                <input type="text" class="form-control" name="sector" placeholder="Ej. Las Malvinas">
                            </div>
                        </div>

                        <!-- 2. Cliente -->
                        <div class="section-title">Datos del Cliente</div>
                        <div class="mb-3 position-relative">
                            <label class="form-label">Buscar Cliente (Nombre o ID)</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-search"></i></span>
                                <input type="text" class="form-control" id="cliente_search" placeholder="Escriba 3 letras para buscar..." autocomplete="off">
                            </div>
                            <!-- ID del contrato seleccionado -->
                            <input type="hidden" name="id_contrato" id="id_contrato" required>
                            
                            <!-- Lista de resultados -->
                            <div id="search_results" class="list-group position-absolute w-100 shadow" style="z-index: 1000; display: none;"></div>
                            
                            <!-- Selección visual -->
                            <div id="cliente_seleccionado" class="mt-2 p-2 bg-light border rounded d-none">
                                <i class="fas fa-check-circle text-success me-2"></i> <span id="nombre_cliente_sel" class="fw-bold"></span>
                            </div>
                        </div>

                        <!-- 3. Detalles de Servicio -->
                        <div class="section-title">Detalles Técnicos</div>
                        <div class="row mb-3">
                            <div class="col-md-3">
                                <label class="form-label fw-bold">Tipo Servicio</label>
                                <select class="form-select" name="tipo_servicio">
                                    <option value="FTTH">FTTH (Fibra)</option>
                                    <option value="RADIO">Radio/Antena</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-bold">IP Asignada</label>
                                <input type="text" class="form-control" name="ip" placeholder="0.0.0.0">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label text-muted small">Estado ONU</label>
                                <select class="form-select" name="estado_onu">
                                    <option value="ON">ON</option>
                                    <option value="OFF">OFF</option>
                                    <option value="LOS">LOS</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label text-muted small">Estado Router</label>
                                <select class="form-select" name="estado_router">
                                    <option value="ON">ON</option>
                                    <option value="OFF">OFF</option>
                                    <option value="RESET">Reset</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                             <div class="col-md-4">
                                <label class="form-label small">Modelo Router</label>
                                <input type="text" class="form-control" name="modelo_router" placeholder="Ej. TPLink">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label small">Dispositivos</label>
                                <input type="number" class="form-control" name="num_dispositivos" placeholder="Cant.">
                            </div>
                             <div class="col-md-6">
                                <div class="row">
                                    <div class="col-4">
                                        <label class="form-label small">Bajada</label>
                                        <input type="text" class="form-control" name="bw_bajada" placeholder="MB">
                                    </div>
                                    <div class="col-4">
                                        <label class="form-label small">Subida</label>
                                        <input type="text" class="form-control" name="bw_subida" placeholder="MB">
                                    </div>
                                    <div class="col-4">
                                        <label class="form-label small">Ping</label>
                                        <input type="text" class="form-control" name="bw_ping" placeholder="ms">
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row mb-3 bg-light p-2 rounded mx-1">
                            <div class="col-12"><small class="text-muted fw-bold">Solo Radio / Antena:</small></div>
                            <div class="col-md-6">
                                <label class="form-label small">Estado Antena</label>
                                <input type="text" class="form-control" name="estado_antena" placeholder="Ej. Alineada">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small">Valores dBm</label>
                                <input type="text" class="form-control" name="valores_antena" placeholder="Ej. -55">
                            </div>
                        </div>

                        <!-- 4. Observaciones -->
                        <div class="section-title">Diagnóstico y Solución</div>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Observaciones / Problema</label>
                                <textarea class="form-control" name="descripcion" rows="3" placeholder="Describa la falla detalladamente..."></textarea>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Sugerencias al Cliente</label>
                                <textarea class="form-control" name="sugerencias" rows="3" placeholder="Recomendaciones..."></textarea>
                            </div>
                        </div>
                        
                        <div class="form-check form-switch mb-3">
                            <input class="form-check-input" type="checkbox" id="solucion_completada" name="solucion_completada" checked>
                            <label class="form-check-label fw-bold" for="solucion_completada">¿Falla Solucionada?</label>
                        </div>
                        
                        <!-- 5. Costos -->
                        <div class="section-title">Costos y Facturación</div>
                        <div class="row mb-3 align-items-end">
                            <div class="col-md-4">
                                <label class="form-label fw-bold">Costo Total ($)</label>
                                <input type="number" step="0.01" min="0" class="form-control form-control-lg" name="monto_total" id="monto_total" value="0.00" oninput="calcularDeuda()">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold">Monto Abonado/Pagado ($)</label>
                                <input type="number" step="0.01" min="0" class="form-control form-control-lg" name="monto_pagado" id="monto_pagado" value="0.00" oninput="calcularDeuda()">
                            </div>
                            <div class="col-md-4">
                                <div class="alert alert-info py-2 mb-0 text-center">
                                    <small>Saldo Pendiente:</small><br>
                                    <span class="fs-4 fw-bold" id="deuda_pendiente">$0.00</span>
                                </div>
                            </div>
                        </div>
                        
                         <!-- 6. Firmas -->
                        <div class="section-title">Firmas Digitales (Opcional)</div>
                         <div class="row mb-4">
                            <div class="col-md-6">
                                <label class="form-label small fw-bold">Firma Técnico</label>
                                <canvas id="sigTech" class="signature-pad"></canvas>
                                <button type="button" class="btn btn-sm btn-outline-secondary mt-1" onclick="clearPad('tech')">Limpiar</button>
                                <input type="hidden" name="firma_tecnico_data" id="firma_tecnico_data">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-bold">Firma Cliente</label>
                                <canvas id="sigCli" class="signature-pad"></canvas>
                                <button type="button" class="btn btn-sm btn-outline-secondary mt-1" onclick="clearPad('cli')">Limpiar</button>
                                <input type="hidden" name="firma_cliente_data" id="firma_cliente_data">
                            </div>
                         </div>

                        <hr>
                        <div class="d-flex justify-content-end gap-2">
                             <a href="historial_soportes.php" class="btn btn-secondary">Cancelar</a>
                            <button type="submit" class="btn btn-primary btn-lg" id="btnGuardar">
                                <i class="fas fa-save me-2"></i> Guardar Soporte
                            </button>
                        </div>
                    </form>
                </div>
            </div>

        </div>
    </div>
</main>

<script src="<?php echo $path_to_root; ?>js/bootstrap.bundle.min.js"></script>
<script src="<?php echo $path_to_root; ?>js/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/signature_pad@4.1.5/dist/signature_pad.umd.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
    // --- Lógica de Búsqueda de Cliente ---
    const searchInput = document.getElementById('cliente_search');
    const resultsDiv = document.getElementById('search_results');
    const idInput = document.getElementById('id_contrato');
    const selectedDiv = document.getElementById('cliente_seleccionado');
    const nameSpan = document.getElementById('nombre_cliente_sel');

    searchInput.addEventListener('input', function() {
        const term = this.value;
        if(term.length < 3) { resultsDiv.style.display = 'none'; return; }
        
        fetch(`../principal/buscar_contratos.php?q=${term}`)
            .then(r => r.json())
            .then(data => {
                resultsDiv.innerHTML = '';
                if(data.length > 0) {
                    data.forEach(item => {
                        const a = document.createElement('a');
                        a.className = 'list-group-item list-group-item-action';
                        a.innerHTML = `<strong>${item.nombre_completo}</strong> (ID: ${item.id})`;
                        a.href = '#';
                        a.onclick = (e) => {
                            e.preventDefault();
                            searchInput.value = ''; // Limpiar buscador
                            idInput.value = item.id;
                            nameSpan.textContent = item.nombre_completo + ' (ID: ' + item.id + ')';
                            selectedDiv.classList.remove('d-none');
                            resultsDiv.style.display = 'none';
                        };
                        resultsDiv.appendChild(a);
                    });
                    resultsDiv.style.display = 'block';
                } else {
                     resultsDiv.style.display = 'none';
                }
            });
    });

    // --- Lógica de Costos ---
    function calcularDeuda() {
        let total = parseFloat(document.getElementById('monto_total').value) || 0;
        let pagado = parseFloat(document.getElementById('monto_pagado').value) || 0;
        let deuda = total - pagado;
        if(deuda < 0) deuda = 0;
        document.getElementById('deuda_pendiente').textContent = '$' + deuda.toFixed(2);
    }

    // --- Firmas Digitales ---
    const canvasTech = document.getElementById('sigTech');
    const canvasCli = document.getElementById('sigCli');
    let padTech, padCli;

    function resizeCanvas(canvas) {
        var ratio =  Math.max(window.devicePixelRatio || 1, 1);
        canvas.width = canvas.offsetWidth * ratio;
        canvas.height = canvas.offsetHeight * ratio;
        canvas.getContext("2d").scale(ratio, ratio);
    }
    
    // Inicializar al cargar solo si existen (por si acaso el browser cachea)
    window.onload = function() {
        resizeCanvas(canvasTech); 
        resizeCanvas(canvasCli);
        padTech = new SignaturePad(canvasTech);
        padCli = new SignaturePad(canvasCli);
    };

    function clearPad(type) {
        if(type === 'tech') padTech.clear();
        if(type === 'cli') padCli.clear();
    }
    
    // --- Envío del Formulario ---
    document.getElementById('formSoporteAdmin').addEventListener('submit', function(e) {
        if(!idInput.value) {
            e.preventDefault();
            Swal.fire('Error', 'Debe seleccionar un cliente obligatorio.', 'warning');
            return;
        }
        
        // Guardar firmas en hidden inputs si no están vacías
        if(padTech && !padTech.isEmpty()) document.getElementById('firma_tecnico_data').value = padTech.toDataURL();
        if(padCli && !padCli.isEmpty()) document.getElementById('firma_cliente_data').value = padCli.toDataURL();
        
        // No prevenimos el default aqui, dejamos que haga submit POST normal a guardar_soporte.php
        // Pero podríamos bloquear el botón
        document.getElementById('btnGuardar').disabled = true;
        document.getElementById('btnGuardar').innerHTML = '<i class="fas fa-spinner fa-spin"></i> Guardando...';
    });

</script>
</body>
</html>
