<?php
// conciliacion.php
require_once '../conexion.php';

$path_to_root = "../../";
$page_title = "Conciliación Bancaria (Excel vs Capture)";
require_once '../includes/layout_head.php';
require_once '../includes/sidebar.php';
?>

<style>
    .drop-zone {
        border: 2px dashed #0d6efd;
        border-radius: 10px;
        padding: 30px;
        text-align: center;
        background: #f8fbff;
        cursor: pointer;
        transition: all 0.3s;
        height: 100%;
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
        min-height: 200px;
    }

    .drop-zone:hover {
        background: #eef5ff;
        border-color: #0a58ca;
    }

    .drop-zone.active {
        background-color: #d1e7dd;
        border-color: #198754;
    }

    #preview-image {
        max-width: 100%;
        max-height: 300px;
        border-radius: 8px;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        display: none;
        margin: 0 auto;
    }

    .result-card {
        transition: all 0.3s ease;
    }

    .step-number {
        width: 30px;
        height: 30px;
        background-color: #0d6efd;
        color: white;
        border-radius: 50%;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-weight: bold;
        margin-right: 10px;
    }
</style>

<main class="main-content">
    <?php include '../includes/header.php'; ?>

    <div class="page-content">
        <div class="container-fluid">
            <!-- Header -->
            <div class="row mb-4">
                <div class="col-12">
                    <h3 class="fw-bold text-primary"><i class="fa-solid fa-file-invoice-dollar me-2"></i>Conciliación:
                        Capture vs Banco</h3>
                    <p class="text-muted">Sube el Excel del Banco y el Capture del Pago Móvil para verificar si la
                        operación fue exitosa.</p>
                </div>
            </div>

            <div class="row g-4">
                <!-- Columna Izquierda: Entradas -->
                <div class="col-lg-5">
                    <!-- Paso 1: Cargar Excel -->
                    <div class="card shadow-sm mb-4">
                        <div class="card-header bg-white">
                            <h6 class="mb-0 fw-bold text-success d-flex align-items-center">
                                <span class="step-number bg-success">1</span> Archivo del Banco (Excel)
                            </h6>
                        </div>
                        <div class="card-body">
                            <div id="drop-zone-excel" class="drop-zone">
                                <i class="fa-solid fa-file-excel fa-3x text-success mb-3"></i>
                                <h6 id="excel-label">Arrastra el Excel (.xls, .xlsx)</h6>
                                <p class="text-muted small mb-0">Debe tener columna "Referencia"</p>
                                <input type="file" id="excel-input" accept=".xlsx, .xls, .csv" style="display: none;">
                            </div>
                            <div id="excel-info" class="mt-3 text-center text-success fw-bold small"
                                style="display: none;"></div>
                        </div>
                    </div>

                    <!-- Paso 2: Cargar Capture -->
                    <div class="card shadow-sm">
                        <div class="card-header bg-white">
                            <h6 class="mb-0 fw-bold text-primary d-flex align-items-center">
                                <span class="step-number bg-primary">2</span> Capture de Pago (Imagen)
                            </h6>
                        </div>
                        <div class="card-body">
                            <div id="drop-zone-img" class="drop-zone">
                                <i class="fa-solid fa-cloud-upload-alt fa-3x text-primary mb-3"></i>
                                <h6 id="img-label">Sube el Capture del Pago</h6>
                                <input type="file" id="img-input" accept="image/*" style="display: none;">
                            </div>
                            <div class="mt-3 text-center">
                                <img id="preview-image" alt="Vista previa">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Columna Derecha: Resultados -->
                <div class="col-lg-7">
                    <div class="card shadow-sm h-100">
                        <div class="card-header bg-white fw-bold"><i
                                class="fa-solid fa-magnifying-glass-chart me-2"></i>Resultados del Análisis</div>
                        <div class="card-body d-flex flex-column justify-content-center">

                            <!-- Estado del Proceso -->
                            <div id="processing-status" class="text-center py-5" style="display: none;">
                                <div class="spinner-border text-primary mb-3" role="status"
                                    style="width: 3rem; height: 3rem;"></div>
                                <h5 class="fw-bold animate__animated animate__pulse animate__infinite">Analizando imagen
                                    con IA...</h5>
                                <p class="text-muted" id="ocr-status-text">Extrayendo número de operación...</p>
                                <div class="progress mt-3 mx-auto" style="width: 80%; height: 8px;">
                                    <div id="ocr-progress"
                                        class="progress-bar progress-bar-striped progress-bar-animated"
                                        style="width: 0%"></div>
                                </div>
                            </div>

                            <!-- Estado Inicial -->
                            <div id="initial-state" class="text-center py-5 text-muted">
                                <i class="fa-solid fa-arrow-left fa-3x mb-3 opacity-25"></i>
                                <h5>Esperando archivos...</h5>
                                <p>Carga primero el Excel del banco y luego la imagen del capture.</p>
                            </div>

                            <!-- Resultado: ÉXITO -->
                            <div id="result-success" class="result-card text-center py-4" style="display: none;">
                                <div class="mb-3">
                                    <span class="fa-stack fa-4x text-success">
                                        <i class="fa-solid fa-circle fa-stack-2x"></i>
                                        <i class="fa-solid fa-check fa-stack-1x fa-inverse"></i>
                                    </span>
                                </div>
                                <h3 class="fw-bold text-success mb-2">¡PAGO ENCONTRADO!</h3>
                                <p class="lead mb-4">La referencia coincide con un registro del banco.</p>

                                <div class="card border-success bg-light mx-4 text-start shadow-sm">
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-6 border-end">
                                                <h6 class="fw-bold text-muted small text-uppercase">Detectado en Capture
                                                </h6>
                                                <p class="fs-4 fw-bold text-dark mb-0" id="ref-ocr">---</p>
                                            </div>
                                            <div class="col-md-6">
                                                <h6 class="fw-bold text-success small text-uppercase"><i
                                                        class="fa-solid fa-database me-1"></i> Datos del Banco</h6>
                                                <ul class="list-unstyled mb-0 small" id="bank-details-list">
                                                    <!-- Detalles llenados por JS -->
                                                </ul>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Resultado: NO ENCONTRADO -->
                            <div id="result-error" class="result-card text-center py-4" style="display: none;">
                                <div class="mb-3">
                                    <span class="fa-stack fa-4x text-danger">
                                        <i class="fa-solid fa-circle fa-stack-2x"></i>
                                        <i class="fa-solid fa-xmark fa-stack-1x fa-inverse"></i>
                                    </span>
                                </div>
                                <h3 class="fw-bold text-danger mb-2">NO ENCONTRADO</h3>
                                <p class="lead mb-4">El número no aparece en el Excel cargado.</p>

                                <div class="card border-danger bg-light mx-4 text-start shadow-sm">
                                    <div class="card-body">
                                        <p class="mb-1"><strong>🔍 Número Buscado (OCR):</strong> <span
                                                id="ref-ocr-error" class="fw-bold fs-5 text-danger"></span></p>
                                        <hr>
                                        <p class="text-muted small mt-2 mb-0">
                                            <i class="fa-solid fa-triangle-exclamation me-1"></i> <strong>Posibles
                                                causas:</strong><br>
                                            1. El número de referencia está mal escrito en el Excel.<br>
                                            2. El OCR leyó mal un dígito (ver imagen).<br>
                                            3. El pago aún no se ha hecho efectivo en este estado de cuenta.
                                        </p>
                                    </div>
                                </div>
                                <button class="btn btn-outline-secondary mt-3" onclick="retryOCR()">Intentar de
                                    nuevo</button>
                            </div>

                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<!-- SheetJS para Excel -->
<script src="https://cdn.sheetjs.com/xlsx-0.19.3/package/dist/xlsx.full.min.js"></script>
<!-- Tesseract.js para OCR -->
<script src="https://unpkg.com/tesseract.js@5.0.3/dist/tesseract.min.js"></script>
<!-- SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
    let bankData = []; // Array of row objects from Excel
    let ocrResultText = "";
    let workbook = null;

    // --- MANEJO DE EXCEL ---
    const dropZoneExcel = document.getElementById('drop-zone-excel');
    const excelInput = document.getElementById('excel-input');
    const excelInfo = document.getElementById('excel-info');

    dropZoneExcel.addEventListener('click', () => excelInput.click());
    dropZoneExcel.addEventListener('dragover', (e) => { e.preventDefault(); dropZoneExcel.classList.add('active'); });
    dropZoneExcel.addEventListener('dragleave', () => dropZoneExcel.classList.remove('active'););
    dropZoneExcel.addEventListener('drop', (e) => {
        e.preventDefault();
        dropZoneExcel.classList.remove('active');
        if (e.dataTransfer.files[0]) handleExcel(e.dataTransfer.files[0]);
    });
    excelInput.addEventListener('change', (e) => {
        if (e.target.files[0]) handleExcel(e.target.files[0]);
    });

    function handleExcel(file) {
        const reader = new FileReader();
        reader.onload = function (e) {
            const data = new Uint8Array(e.target.result);
            workbook = XLSX.read(data, { type: 'array' });

            // Asumimos primera hoja
            const firstSheetName = workbook.SheetNames[0];
            const worksheet = workbook.Sheets[firstSheetName];

            // Convertir a JSON
            // range: 0 -> empieza desde fila 1. Si hay encabezados extraños, ajustar.
            const jsonData = XLSX.utils.sheet_to_json(worksheet, { defval: "" });

            if (jsonData.length > 0) {
                bankData = jsonData;
                console.log("Datos Excel Cargados:", bankData.length, "filas");
                console.log("Encabezados detectados:", Object.keys(bankData[0])); // Debug

                excelInfo.innerHTML = `<i class="fa-solid fa-check-circle me-1"></i> ${file.name} cargado (${bankData.length} filas)`;
                excelInfo.style.display = 'block';
                document.getElementById('excel-label').innerText = "Excel Cargado";
                dropZoneExcel.classList.add('border-success');

                // Si ya hay imagen procesada, re-verificar
                if (ocrResultText) processExtractedText(ocrResultText);
            } else {
                Swal.fire('Error', 'El archivo Excel parece estar vacío o no es válido.', 'error');
            }
        };
        reader.readAsArrayBuffer(file);
    }

    // --- MANEJO DE IMAGEN (OCR) ---
    const dropZoneImg = document.getElementById('drop-zone-img');
    const imgInput = document.getElementById('img-input');
    const previewImage = document.getElementById('preview-image');

    // Elementos de estado
    const statusDiv = document.getElementById('processing-status');
    const initialStateDiv = document.getElementById('initial-state');
    const resultSuccess = document.getElementById('result-success');
    const resultError = document.getElementById('result-error');
    const ocrProgress = document.getElementById('ocr-progress');
    const ocrStatusText = document.getElementById('ocr-status-text');

    dropZoneImg.addEventListener('click', () => imgInput.click());
    dropZoneImg.addEventListener('dragover', (e) => { e.preventDefault(); dropZoneImg.classList.add('active'); });
    dropZoneImg.addEventListener('dragleave', () => dropZoneImg.classList.remove('active'););
    dropZoneImg.addEventListener('drop', (e) => {
        e.preventDefault();
        dropZoneImg.classList.remove('active');
        if (e.dataTransfer.files[0]) processImage(e.dataTransfer.files[0]);
    });
    imgInput.addEventListener('change', (e) => {
        if (e.target.files[0]) processImage(e.target.files[0]);
    });

    async function processImage(file) {
        // Validar si hay Excel cargado primero (opcional pero recomendado)
        if (bankData.length === 0) {
            Swal.fire('Atención', 'Por favor carga primero el archivo Excel del banco.', 'info');
            // Permitimos cargar imagen pero mantenemos aviso
        }

        // Reset UI
        initialStateDiv.style.display = 'none';
        resultSuccess.style.display = 'none';
        resultError.style.display = 'none';
        statusDiv.style.display = 'block';
        ocrProgress.style.width = '0%';
        ocrResultText = "";

        // Preview
        const reader = new FileReader();
        reader.onload = (e) => {
            previewImage.src = e.target.result;
            previewImage.style.display = 'block';
            document.getElementById('img-label').innerText = "Imagen Cargada";
        };
        reader.readAsDataURL(file);

        // OCR Processing
        ocrStatusText.innerText = "Inicializando OCR...";

        try {
            const result = await Tesseract.recognize(
                file,
                'spa', // Usar español para mejor detección de palabras
                {
                    logger: m => {
                        if (m.status === 'recognizing text') {
                            let prog = Math.round(m.progress * 100);
                            ocrProgress.style.width = `${prog}%`;
                            ocrStatusText.innerText = `Leyendo texto... ${prog}%`;
                        }
                    }
                }
            );

            ocrResultText = result.data.text;
            console.log("TEXTO OCR RAW:", ocrResultText);

            processExtractedText(ocrResultText);

        } catch (err) {
            console.error(err);
            statusDiv.style.display = 'none';
            initialStateDiv.style.display = 'block';
            Swal.fire('Error OCR', 'No se pudo leer la imagen. Intenta con una captura más nítida.', 'error');
        }
    }

    function processExtractedText(text) {
        // Estrategia: Buscar números que se parezcan a referencias
        // Referencia ejemplo usuario: 3701185137377 (13 dígitos)
        // Patrones comunes: 6 a 20 dígitos.

        // 1. Limpiar texto de ruido, dejar solo alphanum y espacios, newlines
        // Convertir a array de palabras/tokens
        const tokens = text.match(/\b\d{5,25}\b/g) || [];

        console.log("Tokens numéricos detectados:", tokens);

        if (tokens.length === 0) {
            showError("No se detectaron números válidos en la imagen.");
            return;
        }

        // Si no hay Excel cargado, pausar aquí
        if (bankData.length === 0) {
            statusDiv.style.display = 'none';
            initialStateDiv.style.display = 'block';
            return; // Esperar a que carguen Excel
        }

        // BUSCAR CORRESPONDENCIA
        let foundMatch = null;
        let matchedRef = "";

        // Buscar columna 'Referencia' o similar
        const headers = Object.keys(bankData[0]);
        // Posibles nombres de columna en el Excel del usuario
        // "Referencia", "Ref", "Nro Operacion", "Doc", "Documento"
        const colRef = headers.find(h => {
            const hLower = h.toLowerCase();
            return hLower.includes('referencia') || hLower.includes('ref') || hLower.includes('operacion') || hLower.includes('doc');
        });

        console.log("Columna de referencia identificada:", colRef || "NINGUNA (Buscando global)");

        // Iterar sobre cada número encontrado en el OCR y buscarlo en el Excel
        for (let token of tokens) {
            // Limpieza extra del token
            let cleanToken = token.trim();

            // Buscar en todas las filas del Excel
            for (let row of bankData) {
                let cellValue = "";

                if (colRef) {
                    cellValue = String(row[colRef]).trim();
                } else {
                    // Búsqueda profunda en toda la fila sí no se halló columna header
                    cellValue = Object.values(row).join(" ");
                }

                // Comparación flexible (contains)
                // A veces el excel tiene '000123' y OCR lee '123', o viceversa.
                if (cellValue.includes(cleanToken) && cleanToken.length > 5) {
                    foundMatch = row;
                    matchedRef = cleanToken;
                    break;
                }
            }
            if (foundMatch) break;
        }

        statusDiv.style.display = 'none';

        if (foundMatch) {
            showSuccess(matchedRef, foundMatch);
        } else {
            // Mostrar los tokens que intentó buscar para feedback
            showError(tokens.join(", "));
        }
    }

    function showSuccess(ref, rowData) {
        resultSuccess.style.display = 'block';
        resultError.style.display = 'none';

        document.getElementById('ref-ocr').innerText = ref;

        let listHtml = '';
        for (let [key, val] of Object.entries(rowData)) {
            if (val && String(val).trim() !== '') {
                listHtml += `<li class="mb-1"><strong class="text-dark">${key}:</strong> ${val}</li>`;
            }
        }
        document.getElementById('bank-details-list').innerHTML = listHtml;
    }

    function showError(refAttempt) {
        resultSuccess.style.display = 'none';
        resultError.style.display = 'block';

        // Truncar si es muy largo
        let displayRef = refAttempt;
        if (displayRef.length > 50) displayRef = displayRef.substring(0, 50) + "...";

        document.getElementById('ref-ocr-error').innerText = displayRef;
    }

    function retryOCR() {
        imgInput.value = '';
        document.getElementById('drop-zone-img').click();
    }
</script>

<?php require_once '../includes/layout_foot.php'; ?>