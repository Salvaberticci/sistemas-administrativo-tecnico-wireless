<?php
session_start();
if (isset($_SESSION['cliente_cedula'])) {
    header('Location: dashboard.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Portal de Clientes - Wireless Supply</title>
    <!-- Bootstrap CSS -->
    <link href="../css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- Estilos Premium -->
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="login-wrapper">
        <div class="login-box glass-panel animate-fade text-center">
            
            <img src="../images/logo.jpg" alt="Logo" class="img-fluid mb-4" style="max-height: 100px; border-radius: 12px;">
            
            <h3 class="mb-2 font-weight-bold">Portal de Clientes</h3>
            <p class="text-muted mb-4" style="color: #cbd5e1 !important;">Consulta tus contratos y paga tus mensualidades fácilmente.</p>

            <?php if (isset($_SESSION['login_error'])): ?>
                <div class="alert alert-danger" style="background: rgba(239, 68, 68, 0.1); border-color: rgba(239, 68, 68, 0.2); color: #fca5a5; border-radius: 12px; font-size: 0.9rem;">
                    <i class="fas fa-exclamation-circle me-2"></i> <?php echo $_SESSION['login_error']; unset($_SESSION['login_error']); ?>
                </div>
            <?php endif; ?>

            <form action="auth.php" method="POST">
                <div class="mb-4 text-start">
                    <label class="form-label" style="color: #cbd5e1; font-weight: 500;">Ingresa tu Cédula</label>
                    <div class="input-group">
                        <span class="input-group-text glass-input" style="border-right: none; background: rgba(15, 23, 42, 0.6);"><i class="fas fa-id-card" style="color: #3b82f6;"></i></span>
                        <input type="text" name="cedula" class="form-control glass-input" placeholder="Ej: V12345678" required style="border-left: none; text-transform: uppercase;">
                    </div>
                </div>
                
                <button type="submit" class="btn btn-premium w-100 mb-3">
                    INGRESAR <i class="fas fa-arrow-right ms-2"></i>
                </button>

                <p style="font-size: 0.8rem; color: #64748b;">
                    ¿Problemas para acceder? Contacta a <a href="#" class="text-gradient text-decoration-none">Soporte Técnico</a>
                </p>
            </form>

        </div>
    </div>

    <script>
        // Convert to uppercase automatically for cédula
        document.querySelector('input[name="cedula"]').addEventListener('input', function(e) {
            this.value = this.value.toUpperCase().replace(/[^VEJGP0-9]/g, '');
        });
    </script>
</body>
</html>
