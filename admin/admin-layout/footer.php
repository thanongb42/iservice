    </main>
    <!-- Main Content Area End -->

    <!-- Footer -->
    <footer class="bg-gray-800 text-white mt-8 py-6">
        <div class="max-w-7xl mx-auto px-4 text-center">
            <p>&copy; <?php echo date('Y'); ?> เทศบาลนครรังสิต. All rights reserved.</p>
            <p class="text-sm text-gray-400 mt-2">ระบบบริการดิจิทัล iService v2.0.0</p>
        </div>
    </footer>
</div>
<!-- Main Content Wrapper End -->

<script>
    // Sidebar state
    let sidebarExpanded = true;  // Default: expanded
    let mobileSidebarOpen = false;

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

    // Toggle sidebar (desktop)
    function toggleSidebar() {
        const sidebar = document.getElementById('sidebar');
        if (!sidebar) {
            console.error('Sidebar element not found');
            return;
        }

        if (window.innerWidth >= 1024) {
            sidebarExpanded = !sidebarExpanded;
            const mainContent = document.getElementById('mainContent');
            const collapseIcon = document.getElementById('collapseIcon');
            const collapseText = document.getElementById('collapseText');
            const menuTexts = document.querySelectorAll('.menu-text');
            const sidebarLogo = document.getElementById('sidebarLogo');
            const userInfo = document.getElementById('userInfo');

            console.log('Toggle sidebar - expanded:', sidebarExpanded);

            if (sidebarExpanded) {
                // EXPAND
                sidebar.classList.remove('sidebar-collapsed');
                sidebar.classList.add('sidebar-expanded');
                
                if (mainContent) {
                    // Remove collapsed margin class
                    mainContent.classList.remove('lg:ml-[80px]');
                    // Add expanded margin class
                    mainContent.classList.add('lg:ml-[280px]');
                    console.log('MainContent classes:', mainContent.className);
                }
                
                if (collapseIcon) {
                    collapseIcon.classList.remove('fa-chevron-right');
                    collapseIcon.classList.add('fa-chevron-left');
                }
                if (collapseText) collapseText.textContent = 'ย่อเมนู';
                
                menuTexts.forEach(el => {
                    el.style.opacity = '1';
                    el.style.width = 'auto';
                });
                if (sidebarLogo) sidebarLogo.style.display = 'block';
                if (userInfo) userInfo.style.display = 'block';
            } else {
                // COLLAPSE
                sidebar.classList.remove('sidebar-expanded');
                sidebar.classList.add('sidebar-collapsed');
                
                if (mainContent) {
                    // Remove expanded margin class
                    mainContent.classList.remove('lg:ml-[280px]');
                    // Add collapsed margin class
                    mainContent.classList.add('lg:ml-[80px]');
                    console.log('MainContent classes:', mainContent.className);
                }
                
                if (collapseIcon) {
                    collapseIcon.classList.remove('fa-chevron-left');
                    collapseIcon.classList.add('fa-chevron-right');
                }
                if (collapseText) collapseText.textContent = '';
                
                menuTexts.forEach(el => {
                    el.style.opacity = '0';
                    el.style.width = '0';
                });
                if (sidebarLogo) sidebarLogo.style.display = 'none';
                if (userInfo) userInfo.style.display = 'none';
            }
        } else {
            toggleMobileSidebar();
        }
    }

    // Force expand sidebar (helper function)
    function expandSidebar() {
        sidebarExpanded = false;  // Set to false so toggle will expand
        toggleSidebar();
    }

    // Force collapse sidebar (helper function)
    function collapseSidebar() {
        sidebarExpanded = true;   // Set to true so toggle will collapse
        toggleSidebar();
    }

    // Toggle mobile sidebar
    function toggleMobileSidebar() {
        mobileSidebarOpen = !mobileSidebarOpen;
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('mobileOverlay');

        if (mobileSidebarOpen) {
            sidebar.classList.add('active');
            if (overlay) overlay.classList.remove('hidden');
        } else {
            sidebar.classList.remove('active');
            if (overlay) overlay.classList.add('hidden');
        }
    }

    // Toggle user dropdown
    function toggleUserDropdown() {
        const dropdown = document.getElementById('userDropdown');
        if (dropdown) dropdown.classList.toggle('hidden');
    }

    // Close dropdown when clicking outside
    document.addEventListener('click', function(event) {
        const dropdown = document.getElementById('userDropdown');
        const button = event.target.closest('button[onclick="toggleUserDropdown()"]');

        if (dropdown && !button && !dropdown.contains(event.target)) {
            dropdown.classList.add('hidden');
        }
    });

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
