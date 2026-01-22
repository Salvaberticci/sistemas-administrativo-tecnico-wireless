    </div>
    
    <?php $path_fix = isset($path_to_root) ? $path_to_root : '../'; ?>

    <!-- Bootstrap Bundle -->
    <script src="<?php echo $path_fix; ?>js/bootstrap.bundle.min.js"></script>
    
    <!-- Custom Scripts -->
    <script>
        // Toggle Sidebar on Mobile
        document.addEventListener('DOMContentLoaded', function() {
            const toggleBtn = document.getElementById('sidebarToggle');
            const sidebar = document.querySelector('.sidebar-wrapper');
            
            if(toggleBtn && sidebar) {
                toggleBtn.addEventListener('click', () => {
                    sidebar.classList.toggle('show');
                });
            }
        });
    </script>
</body>
</html>
