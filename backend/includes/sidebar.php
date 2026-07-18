<nav id="sidebar" class="sidebar">
    <div class="sidebar-header d-flex align-items-center justify-content-between mb-4 mt-2">
        <div class="sidebar-title">
            <h3 class="fw-bold text-light mb-0">🛒 Sistema POS</h3>
            <small class="text-white-50">Gestión Comercial</small>
        </div>
        <button id="sidebarToggler" class="sidebar-hamburger" type="button" aria-label="Alternar menú">
            <span class="hamburger-line"></span>
            <span class="hamburger-line"></span>
            <span class="hamburger-line"></span>
        </button>
    </div>

    <div id="sidebarMenu" class="sidebar-menu">
        <ul class="nav nav-pills flex-column mb-auto mt-2">
            <li class="nav-item mb-2">
                <a href="dashboard.php" class="nav-link text-white fw-semibold menu-item" aria-current="page"><i class="fa fa-home"></i> Inicio</a>
            </li>
            <li class="nav-item mb-2">
                <a href="pos.php" class="nav-link text-white fw-semibold menu-item">🛒 Punto de Venta</a>
            </li>
            <li class="nav-item mb-2">
                <a href="catalogo.php" class="nav-link text-white fw-semibold menu-item">📦 Catálogo</a>
            </li>
            <li class="nav-item mb-2">
                <a href="clientes.php" class="nav-link text-white fw-semibold menu-item">👥 Clientes</a>
            </li>
            <li class="nav-item mb-2">
                <a href="historial.php" class="nav-link text-white fw-semibold menu-item">📑 Historial</a>
            </li>
        </ul>
    </div>

    <hr style="border-color: var(--verde-medio);">

    <div class="text-center pb-2">
        <a href="backend/includes/logout.php" class="btn btn-sm btn-logout w-100">Cerrar sesión</a>
        <small class="d-block mt-2 text-white-50">Versión 1.0.0 © 2026</small>
    </div>
</nav>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const sidebar = document.getElementById('sidebar');
        const menu = document.getElementById('sidebarMenu');
        const toggler = document.getElementById('sidebarToggler');

        const isMobile = () => window.innerWidth <= 900;

        const restoreState = () => {
            const savedState = localStorage.getItem('sidebarCollapsed');
            if (!isMobile() && savedState === 'true') {
                sidebar.classList.add('collapsed');
                menu.classList.add('hidden');
            } else {
                sidebar.classList.remove('collapsed');
                menu.classList.remove('hidden');
            }
        };

        restoreState();

        toggler.addEventListener('click', function() {
            if (isMobile()) {
                menu.classList.toggle('hidden');
            } else {
                const collapsed = sidebar.classList.toggle('collapsed');
                menu.classList.toggle('hidden', collapsed);
                localStorage.setItem('sidebarCollapsed', collapsed ? 'true' : 'false');
            }
        });

        window.addEventListener('resize', restoreState);
    });
</script>