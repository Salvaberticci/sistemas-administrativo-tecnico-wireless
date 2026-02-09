<?php
// conciliacion.php
require_once '../conexion.php';

$path_to_root = "../../";
$page_title = "Conciliación Bancaria (OCR)";
require_once '../includes/layout_head.php';
require_once '../includes/sidebar.php';
?>

<style>
    .drop-zone {
        border: 2px dashed #0d6efd;
        border-radius: 10px;
        padding: 40px;
        text-align: center;
        background: #f8fbff;
        cursor: pointer;
        transition: all 0.3s;
    }

    .drop-zone:hover {
        background: #eef5ff;
        border-color: #0a58ca;
    }

    #pdf-preview-container {
        max-height: 400px;
        overflow-y: auto;
        border: 1px solid #dee2e6;
        display: none;
    }

    .status-badge {
        width: 10px;
        height: 10px;
        display: inline-block;
        border-radius: 50%;
        margin-right: 5px;
    }
</style>

<main class="main-content">
    <?php include '../includes/header.php'; ?>

    <div class="page-content">
        <div class="container-fluid">
            <!-- Header -->
            <div class="row mb-4">
                <div class="col-12">
                    <h3 class="fw-bold text-primary"><i class="fa-solid fa-scale-balanced me-2"></i>Conciliación
                        Bancaria Inteligente</h3>
                    <p class="text-muted">Carga el PDF del estado de cuenta para extraer y validar referencias
                        bancarias.</p>
                </div>
            </div>

            <div class="row g-4">
                <!-- Columna de Carga -->
                <div class="col-lg-4">
                    <div class="card shadow-sm h-100">
                        <div class="card-header bg-white fw-bold">1. Cargar Estado de Cuenta</div>
                        <div class="card-body">
                            <div id="drop-zone" class="drop-zone mb-3">
                                <i class="fa-solid fa-file-pdf fa-3x text-danger mb-3"></i>
                                <h6>Arrastra el PDF aquí o haz clic</h6>
                                <p class="text-muted small">Máximo 10MB</p>
                                <input type="file" id="pdf-input" accept="application/pdf" style="display: none;">
                            </div>

                            <div id="processing-status" style="display: none;">
                                <div class="d-flex align-items-center mb-2">
                                    <div class="spinner-border spinner-border-sm text-primary me-2" role="status"></div>
                                    <span id="status-text" class="small fw-bold">Procesando PDF...</span>
                                </div>
                                <div class="progress" style="height: 10px;">
                                    <div id="ocr-progress"
                                        class="progress-bar progress-bar-striped progress-bar-animated"
                                        role="progressbar" style="width: 0%"></div>
                                </div>
                            </div>

                            <div id="pdf-preview-container" class="mt-3 text-center">
                                <canvas id="pdf-canvas" class="img-fluid border"></canvas>
                                <div class="mt-2 small text-muted">Previsualización de página 1</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Columna de Resultados -->
                <div class="col-lg-8">
                    <div class="card shadow-sm h-100">
                        <div class="card-header bg-white d-flex justify-content-between align-items-center">
                            <span class="fw-bold">2. Análisis de Referencias</span>
                            <div id="btn-actions" style="display: none;">
                                <button class="btn btn-sm btn-outline-primary" onclick="analizarReferencias()"><i
                                        class="fa-solid fa-magnifying-glass me-1"></i>Validar en DB</button>
                                <button class="btn btn-sm btn-outline-success" onclick="exportarConciliacion()"><i
                                        class="fa-solid fa-file-excel me-1"></i>Exportar</button>
                            </div>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table id="tabla-conciliacion" class="table table-hover align-middle mb-0"
                                    style="font-size: 0.9rem;">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Referencia Extraída</th>
                                            <th>Estatus</th>
                                            <th>Cliente / Contrato</th>
                                            <th>Monto Reg.</th>
                                            <th>Tipo</th>
                                        </tr>
                                    </thead>
                                    <tbody id="resultado-cuerpo">
                                        <tr>
                                            <td colspan="5" class="text-center py-5 text-muted">
                                                <i class="fa-solid fa-wand-magic-sparkles fa-2x mb-3 d-block"></i>
                                                Carga un PDF para iniciar el análisis inteligente.
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<!-- Scripts necesarios -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js"></script>
<script src="https://unpkg.com/tesseract.js@5.0.3/dist/tesseract.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
    // Configurar pdf.js
    pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js';

    const dropZone = document.getElementById('drop-zone');
    const pdfInput = document.getElementById('pdf-input');
    const statusDiv = document.getElementById('processing-status');
    const ocrBar = document.getElementById('ocr-progress');
    const statusText = document.getElementById('status-text');
    const resultsTable = document.getElementById('resultado-cuerpo');
    const actionsDiv = document.getElementById('btn-actions');

    let referenciasEncontradas = [];

    dropZone.addEventListener('click', () => pdfInput.click());
    dropZone.addEventListener('dragover', (e) => { e.preventDefault(); dropZone.classList.add('bg-light'); });
    dropZone.addEventListener('dragleave', () => dropZone.classList.remove('bg-light'));
    dropZone.addEventListener('drop', (e) => {
        e.preventDefault();
        dropZone.classList.remove('bg-light');
        if (e.dataTransfer.files[0]) processFile(e.dataTransfer.files[0]);
    });

    pdfInput.addEventListener('change', (e) => {
        if (e.target.files[0]) processFile(e.target.files[0]);
    });

    async function processFile(file) {
        if (file.type !== 'application/pdf') {
            Swal.fire('Error', 'Por favor selecciona un archivo PDF', 'error');
            return;
        }

        statusDiv.style.display = 'block';
        statusText.innerText = 'Renderizando PDF...';
        ocrBar.style.width = '10%';

        const reader = new FileReader();
        reader.onload = async function () {
            const typedarray = new Uint8Array(this.result);
            const pdf = await pdfjsLib.getDocument(typedarray).promise;

            referenciasEncontradas = [];
            resultsTable.innerHTML = '';

            // Procesar solo las primeras 5 páginas para evitar saturación
            const pagesToProcess = Math.min(pdf.numPages, 5);

            for (let i = 1; i <= pagesToProcess; i++) {
                statusText.innerText = `Analizando página ${i} de ${pagesToProcess}...`;
                ocrBar.style.width = `${(i / pagesToProcess) * 100}%`;

                const page = await pdf.getPage(i);
                const text = await performOCR(page, i === 1);
                extractReferences(text);
            }

            statusDiv.style.display = 'none';
            if (referenciasEncontradas.length > 0) {
                mostrarReferenciasEnTabla();
                actionsDiv.style.display = 'block';
                Swal.fire('Éxito', `Se extrajeron ${referenciasEncontradas.length} referencias únicas.`, 'success');
            } else {
                Swal.fire('Aviso', 'No se detectaron patrones de referencia claros. Intenta con un PDF con mejor resolución.', 'warning');
            }
        };
        reader.readAsArrayBuffer(file);
    }

    async function performOCR(page, isFirstPage) {
        const viewport = page.getViewport({ scale: 2.0 });
        const canvas = isFirstPage ? document.getElementById('pdf-canvas') : document.createElement('canvas');
        if (isFirstPage) document.getElementById('pdf-preview-container').style.display = 'block';

        const context = canvas.getContext('2d');
        canvas.height = viewport.height;
        canvas.width = viewport.width;

        await page.render({ canvasContext: context, viewport: viewport }).promise;

        // OCR con Tesseract
        const { data: { text } } = await Tesseract.recognize(canvas, 'spa', {
            logger: m => console.log(m)
        });

        return text;
    }

    function extractReferences(text) {
        // Buscamos números de 6 a 12 dígitos que suelen ser referencias
        const regex = /\b\d{6,12}\b/g;
        const matches = text.match(regex) || [];
        matches.forEach(ref => {
            if (!referenciasEncontradas.includes(ref)) {
                referenciasEncontradas.push(ref);
            }
        });
    }

    function mostrarReferenciasEnTabla() {
        resultsTable.innerHTML = referenciasEncontradas.map(ref => `
            <tr id="row-${ref}">
                <td class="fw-bold">${ref}</td>
                <td><span class="badge bg-secondary">Pendiente de Validar</span></td>
                <td>---</td>
                <td>---</td>
                <td>---</td>
            </tr>
        `).join('');
    }

    function analizarReferencias() {
        if (referenciasEncontradas.length === 0) return;

        Swal.fire({
            title: 'Validando...',
            text: 'Cruzando referencias con la base de datos',
            allowOutsideClick: false,
            didOpen: () => Swal.showLoading()
        });

        fetch('buscar_referencias_conciliacion.php', {
            method: 'POST',
            body: JSON.stringify({ referencias: referenciasEncontradas }),
            headers: { 'Content-Type': 'application/json' }
        })
            .then(r => r.json())
            .then(data => {
                Swal.close();
                data.forEach(item => {
                    const row = document.getElementById(`row-${item.referencia}`);
                    if (row) {
                        if (item.encontrado) {
                            row.classList.add('table-success');
                            row.innerHTML = `
                            <td class="fw-bold">${item.referencia}</td>
                            <td><span class="badge bg-success">Encontrado</span></td>
                            <td>${item.nombre_completo} <br> <small class="text-muted">ID: ${item.id_contrato}</small></td>
                            <td class="fw-bold">$${item.monto_pagado}</td>
                            <td><span class="badge bg-info">${item.tipo}</span></td>
                        `;
                        } else {
                            row.innerHTML = `
                            <td class="fw-bold text-danger">${item.referencia}</td>
                            <td><span class="badge bg-danger">No Registrado</span></td>
                            <td>No existe en sistema</td>
                            <td>---</td>
                            <td>---</td>
                        `;
                        }
                    }
                });
            })
            .catch(err => {
                Swal.fire('Error', 'No se pudo conectar con el servidor', 'error');
                console.error(err);
            });
    }

    function exportarConciliacion() {
        Swal.fire('Próximamente', 'La exportación a Excel estará disponible en la siguiente actualización.', 'info');
    }
</script>

<?php require_once '../includes/layout_foot.php'; ?>