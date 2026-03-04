    </main>
    <!-- Main Content Area End -->

    <!-- Footer -->
    <footer class="bg-gray-800 text-white py-4">
        <div class="max-w-7xl mx-auto px-4 text-center">
            <p>&copy; <?php echo date('Y'); ?> เทศบาลนครรังสิต. All rights reserved.</p>
            <p class="text-sm text-gray-400 mt-2">ระบบบริการดิจิทัล iService v2.0.0</p>
        </div>
    </footer>
</div>
<!-- Main Content Wrapper End -->

<script>
    // sidebarExpanded declared as var in topbar.php (early script)
    // mobileSidebarOpen declared as var in header.php (early script)
    if (typeof mobileSidebarOpen === 'undefined') { var mobileSidebarOpen = false; }

    // Initialize sidebar based on screen size
    function initializeSidebar() {
        const sidebar = document.getElementById('sidebar');
        const mainContent = document.getElementById('mainContent');

        if (!sidebar) {
            console.error('Sidebar element not found');
            return;
        }

        if (window.innerWidth < 1024) {
            // Mobile/Tablet: Sidebar hidden by default
            sidebar.classList.add('sidebar-mobile');
            sidebar.classList.remove('active');
            if (mainContent) {
                mainContent.classList.remove('lg:ml-[280px]', 'lg:ml-[80px]');
                mainContent.classList.add('ml-0');
            }
            mobileSidebarOpen = false;
            sidebarExpanded = false;
        } else {
            // Desktop: Sidebar expanded by default
            sidebar.classList.remove('sidebar-mobile', 'active', 'sidebar-collapsed');
            sidebar.classList.add('sidebar-expanded');
            if (mainContent) {
                mainContent.classList.remove('ml-0', 'lg:ml-[80px]');
                mainContent.classList.add('lg:ml-[280px]');
            }
            sidebarExpanded = true;
            
            // Show all text elements
            document.querySelectorAll('.menu-text').forEach(el => {
                el.style.opacity = '1';
                el.style.width = 'auto';
            });
            const sidebarLogo = document.getElementById('sidebarLogo');
            const userInfo = document.getElementById('userInfo');
            const collapseText = document.getElementById('collapseText');
            const collapseIcon = document.getElementById('collapseIcon');

            if (sidebarLogo) sidebarLogo.style.display = 'block';
            if (userInfo) userInfo.style.display = 'block';
            if (collapseText) collapseText.textContent = 'ย่อเมนู';
            if (collapseIcon) {
                collapseIcon.classList.remove('fa-chevron-right');
                collapseIcon.classList.add('fa-chevron-left');
            }
        }
        console.log('Sidebar initialized - expanded:', sidebarExpanded, 'width:', window.innerWidth);
    }

    // toggleSidebar is defined in topbar.php

    // Toggle mobile sidebar (full implementation — overrides early stub from header.php)
    function toggleMobileSidebar() {
        mobileSidebarOpen = !mobileSidebarOpen;
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('mobileOverlay');
        if (sidebar) sidebar.classList.toggle('active', mobileSidebarOpen);
        if (overlay) overlay.classList.toggle('hidden', !mobileSidebarOpen);
    }

    // toggleUserDropdown is defined in topbar.php

    // Handle window resize
    window.addEventListener('resize', function() {
        initializeSidebar();
    });

    // Initialize sidebar state on load
    window.addEventListener('load', function() {
        initializeSidebar();
    });

    // Also initialize on DOMContentLoaded for faster initial render
    document.addEventListener('DOMContentLoaded', function() {
        initializeSidebar();
    });
</script>
</body>
</html>
