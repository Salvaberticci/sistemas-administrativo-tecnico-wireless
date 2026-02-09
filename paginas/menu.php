<?php
$page_title = "Panel de Control";
require_once 'includes/layout_head.php';
require_once 'includes/sidebar.php';
?>

<main class="main-content">
    <?php include 'includes/header.php'; ?>

    <div class="page-content">
        <!-- Welcome Hero Section -->
        <div class="card bg-white border-0 shadow-lg overflow-hidden position-relative mb-4">
            <div class="card-body p-5 position-relative text-center text-md-start">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <h1 class="display-5 fw-bold text-primary mb-3">Bienvenido a Wireless Supply</h1>
                        <p class="lead text-muted mb-4">Sistema de Gestión Administrativa y Técnica</p>


                    </div>
                    <div class="col-md-4 text-center">
                        <img src="../images/logo.jpg" alt="Wireless Supply" class="img-fluid rounded-circle shadow-sm"
                            style="max-height: 180px;">
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Actions Grid -->
        <h4 class="mb-4 text-muted fw-bold">Accesos Directos</h4>
        <div class="row g-4">
            <div class="col-md-6 col-lg-3">
                <a href="principal/gestion_contratos.php" class="text-decoration-none">
                    <div class="card h-100 hover-lift">
                        <div class="card-body text-center p-4">
                            <i class="fa-solid fa-file-contract fa-3x text-primary mb-3"></i>
                            <h5 class="card-title text-dark">Contratos</h5>
                            <p class="card-text text-muted small">Administrar contratos de clientes</p>
                        </div>
                    </div>
                </a>
            </div>
            <div class="col-md-6 col-lg-3">
                <a href="principal/gestion_mensualidades.php" class="text-decoration-none">
                    <div class="card h-100 hover-lift">
                        <div class="card-body text-center p-4">
                            <i class="fa-solid fa-money-bill-wave fa-3x text-success mb-3"></i>
                            <h5 class="card-title text-dark">Cobranzas</h5>
                            <p class="card-text text-muted small">Gestión de cuentas por cobrar</p>
                        </div>
                    </div>
                </a>
            </div>
            <div class="col-md-6 col-lg-3">
                <a href="gestion_usuarios.php" class="text-decoration-none">
                    <div class="card h-100 hover-lift">
                        <div class="card-body text-center p-4">
                            <i class="fa-solid fa-users fa-3x text-info mb-3"></i>
                            <h5 class="card-title text-dark">Usuarios</h5>
                            <p class="card-text text-muted small">Gestión de usuarios del sistema</p>
                        </div>
                    </div>
                </a>
            </div>
            <div class="col-md-6 col-lg-3">
                <a href="soporte/gestion_fallas.php" class="text-decoration-none">
                    <div class="card h-100 hover-lift">
                        <div class="card-body text-center p-4">
                            <i class="fa-solid fa-chart-line fa-3x text-danger mb-3"></i>
                            <h5 class="card-title text-dark">Gestión de Fallas</h5>
                            <p class="card-text text-muted small">Estadísticas y reportes técnicos</p>
                        </div>
                    </div>
                </a>
            </div>
            <div class="col-md-6 col-lg-3">
                <a href="reportes_pdf/reporte_cobranza.php" class="text-decoration-none">
                    <div class="card h-100 hover-lift">
                        <div class="card-body text-center p-4">
                            <i class="fa-solid fa-chart-pie fa-3x text-warning mb-3"></i>
                            <h5 class="card-title text-dark">Reportes</h5>
                            <p class="card-text text-muted small">Ver métricas y reportes</p>
                        </div>
                    </div>
                </a>
            </div>
            <div class="col-md-6 col-lg-3">
                <a href="principal/aprobar_pagos.php" class="text-decoration-none">
                    <div class="card h-100 hover-lift">
                        <div class="card-body text-center p-4">
                            <i class="fa-solid fa-check-double fa-3x text-warning mb-3"></i>
                            <h5 class="card-title text-dark">Aprobar Pagos</h5>
                            <p class="card-text text-muted small">Revisar reportes de clientes</p>
                        </div>
                    </div>
                </a>
            </div>
            <div class="col-md-6 col-lg-3">
                <a href="../reportar_pago.php" target="_blank" class="text-decoration-none">
                    <div class="card h-100 hover-lift border-0 shadow-sm" style="background-color: #f0f7ff;">
                        <div class="card-body text-center p-4">
                            <i class="fa-solid fa-paper-plane fa-3x text-info mb-3"></i>
                            <h5 class="card-title text-dark">Link de Pago</h5>
                            <p class="card-text text-muted small">Copiar para el cliente</p>
                        </div>
                    </div>
                </a>
            </div>
        </div>

        <footer class="mt-5 text-center text-muted small">
            &copy; <span id="current-year"></span> Wireless Supply, C.A. Todos los derechos reservados.
        </footer>
    </div>
</main>

<script>
    document.getElementById("current-year").textContent = new Date().getFullYear();
</script>

<?php require_once 'includes/layout_foot.php'; ?>