<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reporte Técnico - Wireless</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background-color: #f8f9fa;
        }

        .signature-pad {
            border: 2px dashed #ccc;
            border-radius: 5px;
            width: 100%;
            height: 200px;
            background-color: #fff;
            touch-action: none;
        }

        .section-title {
            background-color: #e9ecef;
            padding: 8px 15px;
            border-left: 4px solid #0d6efd;
            font-weight: bold;
            margin-top: 20px;
            margin-bottom: 15px;
        }
    </style>
</head>

<body>

    <div class="container py-4">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="card shadow-lg border-0">
                    <div class="card-header bg-primary text-white text-center py-3">
                        <h4 class="mb-0"><i class="fas fa-file-contract me-2"></i>Reporte de Visita Técnica</h4>
                    </div>
                    <div class="card-body p-4">
                        <form id="formReporte">

                            <!-- 1. Encabezado -->
                            <div class="row mb-3">
                                <div class="col-md-6 mb-2">
                                    <label class="form-label fw-bold">Fecha</label>
                                    <input type="date" class="form-control" name="fecha"
                                        value="<?php echo date('Y-m-d'); ?>" required>
                                </div>
                                <div class="col-md-6 mb-2">
                                    <label class="form-label fw-bold">Sector</label>
                                    <input type="text" class="form-control" name="sector"
                                        placeholder="Ej. Las Malvinas">
                                </div>
                            </div>

                            <!-- 2. Cliente -->
                            <div class="section-title">Datos del Cliente</div>
                            <div class="mb-3 position-relative">
                                <label class="form-label">Buscar Titular</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-search"></i></span>
                                    <input type="text" class="form-control" id="cliente_search"
                                        placeholder="Buscar por Nombre, ID o Cédula..." autocomplete="off">
                                </div>
                                <input type="hidden" name="id_contrato" id="id_contrato" required>
                                <div id="search_results" class="list-group position-absolute w-100 shadow"
                                    style="z-index: 1000; display: none;"></div>
                                <div id="cliente_seleccionado" class="form-text text-success fw-bold mt-2"></div>
                            </div>

                            <!-- 3. Servicio & Equipos -->
                            <div class="section-title">Detalles del Servicio</div>
                            <div class="row mb-3">
                                <div class="col-6">
                                    <label class="form-label fw-bold">Tipo Servicio</label>
                                    <select class="form-select" name="tipo_servicio">
                                        <option value="FTTH">FTTH (Fibra)</option>
                                        <option value="RADIO">Radio/Antena</option>
                                    </select>
                                </div>
                                <div class="col-6">
                                    <label class="form-label fw-bold">IP Asignada</label>
                                    <input type="text" class="form-control" name="ip" placeholder="0.0.0.0">
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-4">
                                    <label class="form-label text-muted small">Estado ONU</label>
                                    <select class="form-select" name="estado_onu">
                                        <option value="ON">ON (Encendido)</option>
                                        <option value="OFF">OFF (Apagado)</option>
                                        <option value="LOS">LOS (Sin Señal)</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label text-muted small">Estado Router</label>
                                    <select class="form-select" name="estado_router">
                                        <option value="ON">ON (Encendido)</option>
                                        <option value="OFF">OFF (Apagado)</option>
                                        <option value="RESET">Reset fábrica</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label text-muted small">Modelo Router</label>
                                    <input type="text" class="form-control" name="modelo_router"
                                        placeholder="Ej. TpLink WR840N">
                                </div>
                            </div>

                            <!-- 4. Medición -->
                            <div class="section-title">Medición Ancho de Banda</div>
                            <div class="row text-center mb-3">
                                <div class="col-4">
                                    <label class="form-label small">Bajada (MB)</label>
                                    <input type="text" class="form-control" name="bw_bajada" placeholder="00">
                                </div>
                                <div class="col-4">
                                    <label class="form-label small">Subida (MB)</label>
                                    <input type="text" class="form-control" name="bw_subida" placeholder="00">
                                </div>
                                <div class="col-4">
                                    <label class="form-label small">Ping (ms)</label>
                                    <input type="text" class="form-control" name="bw_ping" placeholder="00 ms">
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Dispositivos Conectados</label>
                                <input type="number" class="form-control" name="num_dispositivos"
                                    placeholder="Cantidad">
                            </div>

                            <!-- 5. Antena (Caso Radio) -->
                            <div class="section-title">Estado Antena (Solo Radio)</div>
                            <div class="row">
                                <div class="col-6">
                                    <label class="form-label">Estado</label>
                                    <input type="text" class="form-control" name="estado_antena"
                                        placeholder="Ej. Alineada">
                                </div>
                                <div class="col-6">
                                    <label class="form-label">Valores (dBm)</label>
                                    <input type="text" class="form-control" name="valores_antena" placeholder="Ej. -55">
                                </div>
                            </div>

                            <!-- 6. Observaciones -->
                            <div class="section-title">Diagnóstico y Solución</div>
                            <div class="mb-3">
                                <label class="form-label fw-bold">Tipo de Falla</label>
                                <select class="form-select" name="tipo_falla" id="tipo_falla" required>
                                    <option value="">-- Seleccionar tipo de falla --</option>
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
                                    <option value="Dispositivo del Cliente">Problema en Dispositivo del Cliente</option>
                                    <option value="Saturación de Red">Saturación de Red</option>
                                    <option value="Mantenimiento Preventivo">Mantenimiento Preventivo</option>
                                    <option value="Cambio de Equipo">Cambio de Equipo</option>
                                    <option value="Otro">Otro</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Observaciones Adicionales</label>
                                <textarea class="form-control" name="observaciones" rows="3"
                                    placeholder="Detalles adicionales sobre la falla o solución aplicada..."></textarea>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Sugerencias al Cliente</label>
                                <textarea class="form-control" name="sugerencias" rows="2"
                                    placeholder="Recomendaciones..."></textarea>
                            </div>

                            <div class="form-check form-switch mb-3 p-3 bg-light rounded">
                                <input class="form-check-input" type="checkbox" id="solucion_completada"
                                    name="solucion_completada" checked>
                                <label class="form-check-label fw-bold" for="solucion_completada">¿Solución de Falla
                                    Completada?</label>
                            </div>

                            <!-- Payment Section -->
                            <div class="row mb-3">
                                <div class="col-md-4">
                                    <label class="form-label fw-bold text-danger">Costo de Visita ($)</label>
                                    <input type="number" step="0.01" class="form-control form-control-lg"
                                        id="monto_total" name="monto_total" value="0.00" min="0">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label fw-bold text-success">Monto Pagado ($)</label>
                                    <input type="number" step="0.01" class="form-control form-control-lg"
                                        id="monto_pagado" name="monto_pagado" value="0.00" min="0">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label fw-bold text-warning">Saldo Pendiente ($)</label>
                                    <input type="number" step="0.01" class="form-control form-control-lg bg-light"
                                        id="saldo_pendiente" name="saldo_pendiente" value="0.00" readonly>
                                </div>
                            </div>
                            <div id="payment_warning" class="alert alert-warning d-none" role="alert">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                <strong>Atención:</strong> El cliente quedará registrado en la lista de deudores con un
                                saldo pendiente de $<span id="warning_amount">0.00</span>.
                            </div>

                            <!-- 7. Firmas -->
                            <div class="section-title">Conformidad</div>

                            <div class="mb-4">
                                <label class="form-label fw-bold">Firma del Técnico</label>
                                <canvas id="sigTech" class="signature-pad"></canvas>
                                <button type="button" class="btn btn-sm btn-outline-secondary mt-1"
                                    onclick="clearPad('tech')">Limpiar</button>
                                <input type="hidden" name="firma_tecnico_data" id="firma_tecnico_data">
                            </div>

                            <div class="mb-4">
                                <label class="form-label fw-bold">Firma del Cliente</label>
                                <canvas id="sigCli" class="signature-pad"></canvas>
                                <button type="button" class="btn btn-sm btn-outline-secondary mt-1"
                                    onclick="clearPad('cli')">Limpiar</button>
                                <input type="hidden" name="firma_cliente_data" id="firma_cliente_data">
                            </div>

                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary btn-lg" id="btnGuardar">
                                    <i class="fas fa-save me-2"></i> Guardar Reporte
                                </button>
                            </div>

                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/signature_pad@4.1.5/dist/signature_pad.umd.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        // Inicializar Signature Pads
        const canvasTech = document.getElementById('sigTech');
        const canvasCli = document.getElementById('sigCli');

        // Ajustar tamaño canvas a display
        function resizeCanvas(canvas) {
            var ratio = Math.max(window.devicePixelRatio || 1, 1);
            canvas.width = canvas.offsetWidth * ratio;
            canvas.height = canvas.offsetHeight * ratio;
            canvas.getContext("2d").scale(ratio, ratio);
        }
        window.onresize = function () { resizeCanvas(canvasTech); resizeCanvas(canvasCli); };
        resizeCanvas(canvasTech); resizeCanvas(canvasCli);

        const padTech = new SignaturePad(canvasTech);
        const padCli = new SignaturePad(canvasCli);

        function clearPad(type) {
            if (type === 'tech') padTech.clear();
            if (type === 'cli') padCli.clear();
        }

        // Payment calculation logic
        const montoTotalInput = document.getElementById('monto_total');
        const montoPagadoInput = document.getElementById('monto_pagado');
        const saldoPendienteInput = document.getElementById('saldo_pendiente');
        const paymentWarning = document.getElementById('payment_warning');
        const warningAmount = document.getElementById('warning_amount');

        function calcularSaldo() {
            const total = parseFloat(montoTotalInput.value) || 0;
            const pagado = parseFloat(montoPagadoInput.value) || 0;

            // Validation: paid amount cannot exceed total
            if (pagado > total) {
                montoPagadoInput.value = total.toFixed(2);
                return;
            }

            const saldo = total - pagado;
            saldoPendienteInput.value = saldo.toFixed(2);

            // Show/hide warning
            if (saldo > 0) {
                warningAmount.textContent = saldo.toFixed(2);
                paymentWarning.classList.remove('d-none');
            } else {
                paymentWarning.classList.add('d-none');
            }
        }

        montoTotalInput.addEventListener('input', calcularSaldo);
        montoPagadoInput.addEventListener('input', calcularSaldo);
        montoPagadoInput.addEventListener('blur', function () {
            // Ensure paid amount doesn't exceed total on blur
            const total = parseFloat(montoTotalInput.value) || 0;
            const pagado = parseFloat(this.value) || 0;
            if (pagado > total) {
                this.value = total.toFixed(2);
                calcularSaldo();
            }
        });

        // Buscador AJAX
        const searchInput = document.getElementById('cliente_search');
        const resultsDiv = document.getElementById('search_results');
        const idInput = document.getElementById('id_contrato');
        const selectedDiv = document.getElementById('cliente_seleccionado');

        searchInput.addEventListener('input', function () {
            const term = this.value;
            if (term.length < 3) { resultsDiv.style.display = 'none'; return; }

            fetch(`../principal/buscar_contratos.php?q=${term}`)
                .then(r => r.json())
                .then(data => {
                    resultsDiv.innerHTML = '';
                    if (data.length > 0) {
                        data.forEach(item => {
                            const a = document.createElement('a');
                            a.className = 'list-group-item list-group-item-action';
                            a.innerHTML = `<strong>${item.nombre_completo}</strong><br><small class="text-muted">ID: ${item.id} | Cédula: ${item.cedula}</small>`;
                            a.href = '#';
                            a.onclick = (e) => {
                                e.preventDefault();
                                searchInput.value = item.nombre_completo;
                                idInput.value = item.id;
                                selectedDiv.textContent = 'Cliente Seleccionado: ' + item.nombre_completo;
                                resultsDiv.style.display = 'none';
                            };
                            resultsDiv.appendChild(a);
                        });
                        resultsDiv.style.display = 'block';
                    }
                });
        });

        // Enviar Formulario
        document.getElementById('formReporte').addEventListener('submit', function (e) {
            e.preventDefault();

            if (!idInput.value) {
                Swal.fire('Error', 'Debe buscar y seleccionar un cliente.', 'error');
                return;
            }

            // Guardar firmas en inputs ocultos
            if (!padTech.isEmpty()) document.getElementById('firma_tecnico_data').value = padTech.toDataURL();
            if (!padCli.isEmpty()) document.getElementById('firma_cliente_data').value = padCli.toDataURL();

            const formData = new FormData(this);

            document.getElementById('btnGuardar').disabled = true;
            document.getElementById('btnGuardar').innerHTML = '<i class="fas fa-spinner fa-spin"></i> Guardando...';

            fetch('guardar_reporte_publico.php', {
                method: 'POST',
                body: formData
            })
                .then(r => r.json())
                .then(data => {
                    if (data.status === 'success') {
                        Swal.fire({
                            title: '¡Guardado!',
                            text: 'Reporte registrado con éxito.',
                            icon: 'success'
                        }).then(() => {
                            window.location.reload();
                        });
                    } else {
                        Swal.fire('Error', data.msg, 'error');
                        document.getElementById('btnGuardar').disabled = false;
                        document.getElementById('btnGuardar').textContent = 'Guardar Reporte';
                    }
                })
                .catch(err => {
                    console.error(err);
                    Swal.fire('Error', 'Error de conexión con el servidor', 'error');
                    document.getElementById('btnGuardar').disabled = false;
                });
        });

    </script>

</body>

</html>