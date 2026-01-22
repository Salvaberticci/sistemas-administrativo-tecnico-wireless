<?php
require_once '../conexion.php'; 

// 1. CAPTURA DE PARÁMETROS DE FILTRO
$busqueda_filtro = isset($_GET['busqueda']) ? $_GET['busqueda'] : ''; // 🔍 NUEVO: Filtro de búsqueda textual
$id_municipio_filtro = isset($_GET['municipio']) ? $_GET['municipio'] : 'TODOS';
$id_parroquia_filtro = isset($_GET['parroquia']) ? $_GET['parroquia'] : 'TODOS';
$estado_contrato_filtro = isset($_GET['estado_contrato']) ? $_GET['estado_contrato'] : 'TODOS';
$id_vendedor_filtro = isset($_GET['vendedor']) ? $_GET['vendedor'] : 'TODOS';
$id_plan_filtro = isset($_GET['plan']) ? $_GET['plan'] : 'TODOS';
$cobros_estado_filtro = isset($_GET['estado_cobros']) ? $_GET['estado_cobros'] : 'TODOS'; 
$id_olt_filtro = isset($_GET['olt']) ? $_GET['olt'] : 'TODOS';
$id_pon_filtro = isset($_GET['pon']) ? $_GET['pon'] : 'TODOS'; 

$clientes = [];
$total_clientes = 0;

// Consultas para cargar los filtros
$municipios = $conn->query("SELECT id_municipio, nombre_municipio FROM municipio ORDER BY nombre_municipio")->fetch_all(MYSQLI_ASSOC);
$vendedores = $conn->query("SELECT id_vendedor, nombre_vendedor FROM vendedores ORDER BY nombre_vendedor")->fetch_all(MYSQLI_ASSOC);
$planes = $conn->query("SELECT id_plan, nombre_plan FROM planes ORDER BY nombre_plan")->fetch_all(MYSQLI_ASSOC);
$estados_contrato = ['ACTIVO', 'INACTIVO', 'SUSPENDIDO', 'CANCELADO'];
$estados_cobros = ['PENDIENTE', 'VENCIDO', 'PAGADO', 'TODOS']; 
$olts = $conn->query("SELECT id_olt, nombre_olt FROM olt ORDER BY nombre_olt")->fetch_all(MYSQLI_ASSOC);
$pons = $conn->query("SELECT id_pon, nombre_pon FROM pon ORDER BY nombre_pon")->fetch_all(MYSQLI_ASSOC);

// Carga condicional de parroquias
$parroquias_filtradas = []; 
if ($id_municipio_filtro !== 'TODOS') {
    $stmt_parroquias = $conn->prepare("SELECT id_parroquia, nombre_parroquia FROM parroquia WHERE id_municipio = ? ORDER BY nombre_parroquia");
    if ($stmt_parroquias) {
        $stmt_parroquias->bind_param("i", $id_municipio_filtro);
        $stmt_parroquias->execute();
        $resultado_parroquias = $stmt_parroquias->get_result();
        $parroquias_filtradas = $resultado_parroquias->fetch_all(MYSQLI_ASSOC);
        $stmt_parroquias->close();
    }
}

// 2. CONSTRUCCIÓN DINÁMICA DE LA CLÁUSULA WHERE
$where_clause = " WHERE 1=1 "; 
$params = []; 
$types = ''; 

// Para reportes de clientes, siempre necesitamos JOINs
$join_clause = "
    LEFT JOIN municipio m ON c.id_municipio = m.id_municipio
    LEFT JOIN parroquia pa ON c.id_parroquia = pa.id_parroquia
    LEFT JOIN planes pl ON c.id_plan = pl.id_plan
    LEFT JOIN vendedores v ON c.id_vendedor = v.id_vendedor
    LEFT JOIN olt ol ON c.id_olt = ol.id_olt
    LEFT JOIN pon p ON c.id_pon = p.id_pon 
";

// 2.1. Filtros

// 🔍 NUEVO: Filtro de Búsqueda General
if (!empty($busqueda_filtro)) {
    $where_clause .= " AND (c.nombre_completo LIKE ? OR c.cedula LIKE ? OR c.ip LIKE ? OR c.telefono LIKE ?) ";
    $busqueda_param = "%" . $busqueda_filtro . "%";
    $params[] = $busqueda_param;
    $params[] = $busqueda_param;
    $params[] = $busqueda_param;
    $params[] = $busqueda_param;
    $types .= 'ssss';
}

if ($id_municipio_filtro !== 'TODOS') {
    $where_clause .= " AND c.id_municipio = ? ";
    $params[] = $id_municipio_filtro;
    $types .= 'i';
}
if ($id_parroquia_filtro !== 'TODOS') {
    $where_clause .= " AND c.id_parroquia = ? ";
    $params[] = $id_parroquia_filtro;
    $types .= 'i';
}
if ($estado_contrato_filtro !== 'TODOS') {
    $where_clause .= " AND c.estado = ? ";
    $params[] = $estado_contrato_filtro;
    $types .= 's';
}
if ($id_vendedor_filtro !== 'TODOS') {
    $where_clause .= " AND c.id_vendedor = ? ";
    $params[] = $id_vendedor_filtro;
    $types .= 'i';
}
if ($id_plan_filtro !== 'TODOS') {
    $where_clause .= " AND c.id_plan = ? ";
    $params[] = $id_plan_filtro;
    $types .= 'i';
}
if ($id_olt_filtro !== 'TODOS') {
    $where_clause .= " AND c.id_olt = ? ";
    $params[] = $id_olt_filtro;
    $types .= 'i';
}
if ($id_pon_filtro !== 'TODOS') {
    $where_clause .= " AND c.id_pon = ? ";
    $params[] = $id_pon_filtro;
    $types .= 'i';
}
if ($cobros_estado_filtro !== 'TODOS') {
    $join_clause .= " INNER JOIN cuentas_por_cobrar cxc ON c.id = cxc.id_contrato "; 
    $where_clause .= " AND cxc.estado = ? ";
    $params[] = $cobros_estado_filtro;
    $types .= 's';
}

// 3. CONSULTA SQL FINAL
$sql = "
    SELECT 
        c.id, c.nombre_completo, c.cedula, c.telefono, c.estado AS estado_contrato, c.ip,
        m.nombre_municipio AS municipio, pa.nombre_parroquia AS parroquia, 
        pl.nombre_plan AS plan, v.nombre_vendedor AS vendedor,
        ol.nombre_olt AS olt_nombre, p.nombre_pon AS pon_nombre 
    FROM contratos c
    {$join_clause}
    {$where_clause}
    GROUP BY c.id 
    ORDER BY c.nombre_completo ASC
";

$stmt = $conn->prepare($sql);

if (!empty($params)) {
    $stmt->bind_param($types, ...$params); 
}

$stmt->execute();
$resultado = $stmt->get_result();

if ($resultado->num_rows > 0) {
    $clientes = $resultado->fetch_all(MYSQLI_ASSOC);
    $total_clientes = count($clientes);
}

// --- TEMPLATE START ---
$path_to_root = "../../";
$page_title = "Reporte de Clientes";
include $path_to_root . 'paginas/includes/layout_head.php';
?>

<main class="main-content">
    <?php include $path_to_root . 'paginas/includes/header.php'; ?>
    
    <div class="page-content">
        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
            <div>
                 <h2 class="h4 fw-bold mb-1 text-primary">Análisis de Cartera de Clientes</h2>
                 <p class="text-muted mb-0">Total Encontrado: <span class="fw-bold text-dark"><?php echo number_format($total_clientes, 0, ',', '.'); ?></span></p>
            </div>
            <a href="../menu.php" class="btn btn-outline-secondary">
                <i class="fa-solid fa-arrow-left me-2"></i>Volver al Menú
            </a>
        </div>

        <!-- Filtros -->
        <div class="card border-0 shadow-sm mb-4">
             <div class="card-header bg-white border-bottom">
                 <h6 class="mb-0 fw-bold text-primary"><i class="fa-solid fa-filter me-2"></i>Criterios de Búsqueda Avanzada</h6>
            </div>
            <div class="card-body p-4 bg-light">
                <form method="GET" class="row g-3">
                    
                    <!-- 🔍 NUEVO: Barra de Búsqueda Principal -->
                    <div class="col-12 mb-2">
                        <label for="busqueda" class="form-label fw-semibold text-secondary small text-uppercase">Búsqueda General</label>
                        <div class="input-group">
                            <span class="input-group-text bg-white border-end-0"><i class="fa-solid fa-search text-muted"></i></span>
                            <input type="text" class="form-control border-start-0 ps-0" id="busqueda" name="busqueda" 
                                   value="<?php echo htmlspecialchars($busqueda_filtro); ?>" 
                                   placeholder="Nombre, Cédula, IP o Teléfono...">
                        </div>
                    </div>

                    <!-- Estado Contrato -->
                     <div class="col-md-3">
                        <label for="estado_contrato" class="form-label fw-semibold text-secondary small text-uppercase">Estado Contrato</label>
                        <select name="estado_contrato" id="estado_contrato" class="form-select bg-white">
                            <option value="TODOS">TODOS</option>
                            <?php foreach ($estados_contrato as $estado): ?>
                                <option value="<?php echo $estado; ?>" <?php echo ($estado_contrato_filtro === $estado) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($estado); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Municipio -->
                    <div class="col-md-3">
                        <label for="municipio" class="form-label fw-semibold text-secondary small text-uppercase">Municipio</label>
                        <select name="municipio" id="municipio" class="form-select bg-white">
                            <option value="TODOS">TODOS</option>
                            <?php foreach ($municipios as $m): ?>
                                <option value="<?php echo $m['id_municipio']; ?>" <?php echo ($id_municipio_filtro == $m['id_municipio']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($m['nombre_municipio']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Parroquia -->
                    <div class="col-md-3">
                        <label for="parroquia" class="form-label fw-semibold text-secondary small text-uppercase">Parroquia</label>
                        <select name="parroquia" id="parroquia" class="form-select bg-white">
                            <option value="TODOS">TODOS</option>
                            <?php 
                            if (!empty($parroquias_filtradas)):
                                foreach ($parroquias_filtradas as $p): ?>
                                    <option value="<?php echo $p['id_parroquia']; ?>" <?php echo ($id_parroquia_filtro == $p['id_parroquia']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($p['nombre_parroquia']); ?>
                                    </option>
                                <?php endforeach;
                            endif;
                            ?>
                        </select>
                    </div>

                    <!-- Plan -->
                    <div class="col-md-3">
                         <label for="plan" class="form-label fw-semibold text-secondary small text-uppercase">Plan</label>
                        <select name="plan" id="plan" class="form-select bg-white">
                            <option value="TODOS">TODOS</option>
                            <?php foreach ($planes as $pl): ?>
                                <option value="<?php echo $pl['id_plan']; ?>" <?php echo ($id_plan_filtro == $pl['id_plan']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($pl['nombre_plan']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Vendedor -->
                    <div class="col-md-3">
                        <label for="vendedor" class="form-label fw-semibold text-secondary small text-uppercase">Vendedor</label>
                        <select name="vendedor" id="vendedor" class="form-select bg-white">
                            <option value="TODOS">TODOS</option>
                            <?php foreach ($vendedores as $v): ?>
                                <option value="<?php echo $v['id_vendedor']; ?>" <?php echo ($id_vendedor_filtro == $v['id_vendedor']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($v['nombre_vendedor']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <!-- OLT -->
                    <div class="col-md-3">
                         <label for="olt" class="form-label fw-semibold text-secondary small text-uppercase">OLT</label>
                        <select name="olt" id="olt" class="form-select bg-white">
                            <option value="TODOS">TODOS</option>
                            <?php foreach ($olts as $o): ?>
                                <option value="<?php echo $o['id_olt']; ?>" <?php echo ($id_olt_filtro == $o['id_olt']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($o['nombre_olt']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- PON -->
                    <div class="col-md-3">
                        <label for="pon" class="form-label fw-semibold text-secondary small text-uppercase">PON</label>
                        <select name="pon" id="pon" class="form-select bg-white">
                            <option value="TODOS">TODOS</option>
                            <?php foreach ($pons as $p): ?>
                                <option value="<?php echo htmlspecialchars($p['id_pon']); ?>" <?php echo ($id_pon_filtro == $p['id_pon']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($p['nombre_pon']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <!-- Estado Cobranza -->
                    <div class="col-md-3">
                        <label for="estado_cobros" class="form-label fw-semibold text-secondary small text-uppercase">Estado Deuda</label>
                         <select name="estado_cobros" id="estado_cobros" class="form-select bg-white">
                            <option value="TODOS">TODOS</option>
                            <?php foreach ($estados_cobros as $est): ?>
                                <option value="<?php echo $est; ?>" <?php echo ($cobros_estado_filtro === $est) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($est); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Botones -->
                    <div class="col-12 d-flex justify-content-between align-items-center mt-4 border-top pt-3">
                         <a href="reporte_clientes.php" class="btn btn-outline-secondary">
                             <i class="fa-solid fa-redo me-2"></i>Limpiar
                         </a>
                         <button type="button" onclick="this.form.submit()" class="btn btn-primary px-5">
                             <i class="fa-solid fa-filter me-2"></i>Filtrar Resultados
                         </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Botones de Exportación -->
        <div class="d-flex justify-content-end mb-3 gap-2">
                 <a href="exportar_clientes_pdf.php?<?php echo http_build_query($_GET); ?>" class="btn btn-danger shadow-sm" target="_blank">
                    <i class="fa-solid fa-file-pdf me-2"></i>Exportar PDF
                </a>
                <a href="exportar_clientes_excel.php?<?php echo http_build_query($_GET); ?>" class="btn btn-success shadow-sm">
                    <i class="fa-solid fa-file-excel me-2"></i>Exportar Excel
                </a>
        </div>

        <?php if ($total_clientes > 0): ?>
            <!-- Tabla Resultados -->
             <div class="card border-0 shadow-lg mb-4">
                 <div class="card-body p-0">
                     <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0 table-sm">
                            <thead class="bg-light">
                                <tr>
                                    <th class="ps-3 text-secondary text-uppercase small fw-bold">ID</th>
                                    <th class="text-secondary text-uppercase small fw-bold">Cliente</th>
                                    <th class="text-secondary text-uppercase small fw-bold">Cédula</th>
                                     <th class="text-secondary text-uppercase small fw-bold">Teléfono</th>
                                    <th class="text-secondary text-uppercase small fw-bold">Ubicación</th>
                                    <th class="text-secondary text-uppercase small fw-bold">Plan</th>
                                    <th class="text-secondary text-uppercase small fw-bold">Red</th>
                                    <th class="text-center text-secondary text-uppercase small fw-bold">Estado</th>
                                </tr>
                            </thead>
                             <tbody>
                                <?php foreach ($clientes as $fila): ?>
                                <tr>
                                    <td class="ps-3 fw-medium text-muted small">#<?php echo htmlspecialchars($fila['id']); ?></td>
                                    <td>
                                        <div class="fw-bold text-dark"><?php echo htmlspecialchars($fila['nombre_completo']); ?></div>
                                        <div class="small text-muted"><?php echo htmlspecialchars($fila['ip']); ?></div>
                                    </td>
                                    <td class="text-nowrap"><?php echo htmlspecialchars($fila['cedula']); ?></td>
                                     <td class="text-nowrap"><?php echo htmlspecialchars($fila['telefono']); ?></td>
                                    <td>
                                        <div class="d-block small text-dark"><?php echo htmlspecialchars($fila['municipio'] ?? '-'); ?></div>
                                        <div class="d-block text-muted" style="font-size: 0.75rem;"><?php echo htmlspecialchars($fila['parroquia'] ?? '-'); ?></div>
                                    </td>
                                    <td><span class="badge bg-light text-dark border"><?php echo htmlspecialchars($fila['plan'] ?? '-'); ?></span></td>
                                    <td>
                                        <div class="d-block small text-dark"><?php echo htmlspecialchars($fila['olt_nombre'] ?? '-'); ?></div>
                                        <div class="d-block text-muted" style="font-size: 0.75rem;"><?php echo htmlspecialchars($fila['pon_nombre'] ?? '-'); ?></div>
                                    </td>
                                    <td class="text-center">
                                       <span class="badge bg-<?php 
                                            switch ($fila['estado_contrato']) {
                                                case 'ACTIVO': echo 'success'; break;
                                                case 'SUSPENDIDO': echo 'warning text-dark'; break;
                                                case 'INACTIVO': echo 'secondary'; break;
                                                case 'CANCELADO': echo 'danger'; break;
                                                default: echo 'info';
                                            }
                                        ?>">
                                            <?php echo htmlspecialchars($fila['estado_contrato']); ?>
                                        </span>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                     </div>
                 </div>
             </div>
        <?php else: ?>
             <div class="alert alert-warning shadow-sm border-0 text-center py-4">
                <i class="fa-solid fa-triangle-exclamation fa-2x mb-3 text-warning d-block"></i>
                <h5 class="fw-bold text-dark">No se encontraron clientes</h5>
                <p class="text-muted mb-0">No hay coincidencias con los filtros actuales.</p>
            </div>
        <?php endif; ?>
    </div>
</main>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const selectMunicipio = document.getElementById('municipio');
        const selectParroquia = document.getElementById('parroquia');
        
        // Guardar el valor de la parroquia seleccionada en el filtro inicial
        const parroquiaFiltroInicial = selectParroquia.value;

        selectMunicipio.addEventListener('change', function() {
            const idMunicipio = this.value;

            // Limpiar el select de Parroquias, manteniendo la opción 'TODOS'
            selectParroquia.innerHTML = '<option value="TODOS">TODOS</option>';

            if (idMunicipio !== 'TODOS') {
                // Realizar la petición AJAX
                fetch('obtener_parroquias.php?id_municipio=' + idMunicipio)
                    .then(response => response.json())
                    .then(parroquias => {
                        parroquias.forEach(parroquia => {
                            const option = document.createElement('option');
                            option.value = parroquia.id_parroquia;
                            option.textContent = parroquia.nombre_parroquia;
                            
                            // Si estamos en la carga inicial y el municipio fue cambiado por JS, 
                            // necesitamos que la parroquia filtrada se mantenga seleccionada.
                            if (parroquia.id_parroquia == parroquiaFiltroInicial) {
                                option.selected = true;
                            }
                            
                            selectParroquia.appendChild(option);
                        });
                    })
                    .catch(error => console.error('Error al obtener parroquias:', error));
            }
        });
        
        // Ejecutar el evento 'change' en la carga inicial si hay un municipio preseleccionado
        // Esto asegura que si el usuario regresa con filtros aplicados, la lista de parroquias sea correcta.
        if (selectMunicipio.value !== 'TODOS' && selectParroquia.options.length <= 1) {
            selectMunicipio.dispatchEvent(new Event('change'));
        }
        
    });
</script>

<?php include $path_to_root . 'paginas/includes/layout_foot.php'; ?>