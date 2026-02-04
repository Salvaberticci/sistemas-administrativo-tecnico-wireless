<?php
// Header include with Top Navigation Bar
// Assumes $page_title is set before including
$page_title = isset($page_title) ? $page_title : 'Panel de Control';

// Asegurar que $path_fix esté definido (normalmente viene de layout_head.php, pero por seguridad)
if (!isset($path_fix)) {
    $path_fix = isset($path_to_root) ? $path_to_root : '../';
}
?>

<nav class="navbar navbar-expand-lg navbar-dark bg-dark top-header py-0 shadow-sm"
    style="background-color: var(--bg-sidebar) !important;">
    <div class="container-fluid px-4">
        <!-- Brand / Logo -->
        <a class="navbar-brand d-flex align-items-center gap-2 fw-bold" href="<?php echo $path_fix; ?>paginas/menu.php">
            <img src="<?php echo $path_fix; ?>images/logo.jpg" alt="Logo" style="height: 35px; border-radius: 50%;">
            <span class="d-none d-sm-inline font-heading tracking-wide">Wireless Supply</span>
        </a>

        <!-- Mobile Toggle -->
        <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navbarMain"
            aria-controls="navbarMain" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <!-- Navbar Content -->
        <div class="collapse navbar-collapse" id="navbarMain">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0 gap-1">

                <!-- Administración Dropdown -->
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="dropAdmin" role="button" data-bs-toggle="dropdown"
                        aria-expanded="false">
                        <i class="fa-solid fa-briefcase me-1 text-primary-light"></i> Admin
                    </a>
                    <ul class="dropdown-menu shadow-lg border-0" aria-labelledby="dropAdmin">
                        <li><a class="dropdown-item"
                                href="<?php echo $path_fix; ?>paginas/principal/gestion_contratos.php"><i
                                    class="fa-solid fa-file-contract w-20 me-2 text-muted"></i> Contratos</a></li>
                        <li>
                            <hr class="dropdown-divider">
                        </li>
                        <li><a class="dropdown-item" href="<?php echo $path_fix; ?>paginas/gestion_planes.php"><i
                                    class="fa-solid fa-wifi w-20 me-2 text-muted"></i> Planes</a></li>
                        <li><a class="dropdown-item" href="<?php echo $path_fix; ?>paginas/gestion_bancos.php"><i
                                    class="fa-solid fa-building-columns w-20 me-2 text-muted"></i> Bancos</a></li>

                    </ul>
                </li>

                <!-- Red Técnica Dropdown -->
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="dropTech" role="button" data-bs-toggle="dropdown"
                        aria-expanded="false">
                        <i class="fa-solid fa-network-wired me-1 text-info"></i> Técnica
                    </a>
                    <ul class="dropdown-menu shadow-lg border-0" aria-labelledby="dropTech">
                        <li><a class="dropdown-item" href="<?php echo $path_fix; ?>paginas/gestion_olt.php"><i
                                    class="fa-solid fa-server w-20 me-2 text-muted"></i> OLTs</a></li>
                        <li><a class="dropdown-item" href="<?php echo $path_fix; ?>paginas/gestion_pon.php"><i
                                    class="fa-solid fa-network-wired w-20 me-2 text-muted"></i> PONs</a></li>
                        <li><a class="dropdown-item" href="<?php echo $path_fix; ?>paginas/gestion_municipios.php"><i
                                    class="fa-solid fa-map-location-dot w-20 me-2 text-muted"></i> Ubicaciones</a></li>
                    </ul>
                </li>

                <!-- Cobranzas Dropdown -->
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="dropCobranzas" role="button"
                        data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fa-solid fa-money-bill-wave me-1 text-success"></i> Cobranzas
                    </a>
                    <ul class="dropdown-menu shadow-lg border-0" aria-labelledby="dropCobranzas">
                        <li><a class="dropdown-item"
                                href="<?php echo $path_fix; ?>paginas/principal/gestion_mensualidades.php"><i
                                    class="fa-solid fa-file-invoice-dollar w-20 me-2 text-muted"></i> Gestión de
                                Mensualidades y Pagos</a></li>
                        <li><a class="dropdown-item"
                                href="<?php echo $path_fix; ?>paginas/principal/gestion_deudores.php"><i
                                    class="fa-solid fa-triangle-exclamation w-20 me-2 text-danger"></i> Clientes
                                Deudores</a></li>
                    </ul>
                </li>

                <!-- Soporte Técnico Link -->
                <!-- Soporte Técnico Dropdown -->
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="dropSupport" role="button"
                        data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fa-solid fa-screwdriver-wrench me-1 text-danger"></i> Soporte
                    </a>
                    <ul class="dropdown-menu shadow-lg border-0" aria-labelledby="dropSupport">
                        <li><a class="dropdown-item"
                                href="<?php echo $path_fix; ?>paginas/soporte/historial_soportes.php"><i
                                    class="fa-solid fa-list-check w-20 me-2 text-muted"></i> Historial</a></li>
                        <li><a class="dropdown-item"
                                href="<?php echo $path_fix; ?>paginas/soporte/registro_contrato_instalador.php"
                                target="_blank"><i class="fa-solid fa-file-signature w-20 me-2 text-muted"></i> Registro
                                Contrato
                                Instalador</a></li>
                        <li><a class="dropdown-item" href="<?php echo $path_fix; ?>paginas/soporte/reporte_tecnico.php"
                                target="_blank"><i class="fa-solid fa-pen-to-square w-20 me-2 text-muted"></i> Nuevo
                                Reporte</a></li>
                    </ul>
                </li>

                <!-- Reportes Dropdown -->
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="dropReports" role="button"
                        data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fa-solid fa-chart-pie me-1 text-warning"></i> Reportes
                    </a>
                    <ul class="dropdown-menu shadow-lg border-0" aria-labelledby="dropReports">
                        <li><a class="dropdown-item"
                                href="<?php echo $path_fix; ?>paginas/reportes_pdf/reporte_cobranza.php"><i
                                    class="fa-solid fa-chart-line w-20 me-2 text-muted"></i> Reporte Cobranzas</a></li>
                        <li><a class="dropdown-item"
                                href="<?php echo $path_fix; ?>paginas/reportes_pdf/reporte_clientes.php"><i
                                    class="fa-solid fa-users-viewfinder w-20 me-2 text-muted"></i> Reporte Clientes</a>
                        </li>
                    </ul>
                </li>
            </ul>

            <!-- Right Side: User Profile & Actions -->
            <div class="d-flex align-items-center gap-3">
                <!-- User Dropdown -->
                <div class="dropdown">
                    <a href="#" class="d-flex align-items-center text-decoration-none dropdown-toggle text-white"
                        id="dropdownUser1" data-bs-toggle="dropdown" aria-expanded="false">
                        <div class="text-end me-2 d-none d-md-block">
                            <span class="d-block small text-muted text-uppercase fw-bold"
                                style="font-size: 0.65rem;">Bienvenido</span>
                            <span class="d-block fw-semibold" style="font-size: 0.9rem;">Admin</span>
                        </div>
                        <div class="rounded-circle bg-primary d-flex align-items-center justify-content-center text-white fw-bold shadow-sm"
                            style="width: 38px; height: 38px;">A</div>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end shadow-lg border-0 text-small"
                        aria-labelledby="dropdownUser1">
                        <li><a class="dropdown-item" href="<?php echo $path_fix; ?>paginas/gestion_usuarios.php"><i
                                    class="fa-solid fa-user-shield me-2"></i> Gestión Usuarios</a></li>
                        <li>
                            <hr class="dropdown-divider">
                        </li>
                        <li><a class="dropdown-item text-danger" href="<?php echo $path_fix; ?>index.html"><i
                                    class="fa-solid fa-right-from-bracket me-2"></i> Cerrar Sesión</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</nav>

<!-- Sub-header for Page Title (Optional, keeps the context) -->
<div class="bg-white border-bottom py-2 shadow-sm d-none d-lg-block">
    <div class="container-fluid px-4">
        <div class="d-flex justify-content-between align-items-center">
            <h5 class="m-0 text-secondary fw-bold"><i
                    class="fa-solid fa-angle-right me-2 opacity-50 small"></i><?php echo htmlspecialchars($page_title); ?>
            </h5>
            <small class="text-muted"><?php echo date('d/m/Y'); ?></small>
        </div>
    </div>
</div>