<?php
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<nav id="sidebar">
    <div class="sb-header">
        <div class="sb-brand">
            <span class="sb-logo">S</span>
            <div class="sb-brand-text">
                <span class="sb-name">Sistema POS</span>
                <span class="sb-sub">Gestion Comercial</span>
            </div>
        </div>
        <button id="sbToggle" class="sb-toggle" type="button" aria-label="Menu">
            <span></span><span></span><span></span>
        </button>
    </div>

    <div id="sbMenu" class="sb-menu">
        <ul class="sb-nav">
            <li>
                <a href="dashboard.php" class="sb-link <?php echo $currentPage === 'dashboard.php' ? 'active' : ''; ?>">
                    <span class="sb-icon">&#9750;</span>
                    <span class="sb-label">Inicio</span>
                </a>
            </li>
            <li>
                <a href="pos.php" class="sb-link <?php echo $currentPage === 'pos.php' ? 'active' : ''; ?>">
                    <span class="sb-icon">&#9881;</span>
                    <span class="sb-label">Punto de Venta</span>
                </a>
            </li>
            <li>
                <a href="catalogo.php" class="sb-link <?php echo $currentPage === 'catalogo.php' ? 'active' : ''; ?>">
                    <span class="sb-icon">&#9733;</span>
                    <span class="sb-label">Catalogo</span>
                </a>
            </li>
            <li>
                <a href="clientes.php" class="sb-link <?php echo $currentPage === 'clientes.php' ? 'active' : ''; ?>">
                    <span class="sb-icon">&#9823;</span>
                    <span class="sb-label">Clientes</span>
                </a>
            </li>
            <li>
                <a href="historial.php" class="sb-link <?php echo $currentPage === 'historial.php' ? 'active' : ''; ?>">
                    <span class="sb-icon">&#9776;</span>
                    <span class="sb-label">Historial</span>
                </a>
            </li>
        </ul>
    </div>

    <div class="sb-footer">
        <a href="backend/includes/logout.php" class="sb-logout">Cerrar sesion</a>
        <small>v1.0.0</small>
    </div>
</nav>

<script>
(function(){
    var sb = document.getElementById('sidebar');
    var menu = document.getElementById('sbMenu');
    var btn = document.getElementById('sbToggle');
    if(!sb || !menu || !btn) return;

    var MOBILE = 1024;

    function mobile(){ return window.innerWidth <= MOBILE; }

    function openMenu(){
        sb.classList.add('sb-open');
        menu.classList.remove('sb-closed');
    }
    function closeMenu(){
        sb.classList.remove('sb-open');
        menu.classList.add('sb-closed');
    }
    function collapseDesktop(){
        sb.classList.add('sb-collapsed');
    }
    function expandDesktop(){
        sb.classList.remove('sb-collapsed');
    }

    /* --- init --- */
    (function(){
        if(mobile()){
            sb.classList.remove('sb-collapsed');
            var s = localStorage.getItem('sb_mobile_open');
            if(s === 'true') openMenu(); else closeMenu();
        } else {
            var s = localStorage.getItem('sb_desktop_collapsed');
            if(s === 'true') collapseDesktop(); else expandDesktop();
            sb.classList.remove('sb-open');
            menu.classList.remove('sb-closed');
        }
    })();

    /* --- toggle --- */
    btn.addEventListener('click', function(){
        if(mobile()){
            if(sb.classList.contains('sb-open')){
                closeMenu();
                localStorage.setItem('sb_mobile_open','false');
            } else {
                openMenu();
                localStorage.setItem('sb_mobile_open','true');
            }
        } else {
            if(sb.classList.contains('sb-collapsed')){
                expandDesktop();
                localStorage.setItem('sb_desktop_collapsed','false');
            } else {
                collapseDesktop();
                localStorage.setItem('sb_desktop_collapsed','true');
            }
        }
    });

    /* --- resize --- */
    var resizeTimer;
    window.addEventListener('resize', function(){
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(function(){
            if(mobile()){
                sb.classList.remove('sb-collapsed');
                var s = localStorage.getItem('sb_mobile_open');
                if(s === 'true') openMenu(); else closeMenu();
            } else {
                sb.classList.remove('sb-open');
                menu.classList.remove('sb-closed');
                var s = localStorage.getItem('sb_desktop_collapsed');
                if(s === 'true') collapseDesktop(); else expandDesktop();
            }
        }, 100);
    });
})();
</script>
