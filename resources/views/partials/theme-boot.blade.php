<script>
(function () {
    var theme = localStorage.getItem('antrol-theme') || 'light';
    var sidebarCollapsed = localStorage.getItem('antrol-sidebar-collapsed') === '1';
    var assetsPath = document.documentElement.getAttribute('data-assets-path') || '{{ asset('sneat/') }}/';
    var html = document.documentElement;

    if (theme === 'dark') {
        html.classList.remove('light-style');
        html.classList.add('dark-style');
        var themeLink = document.querySelector('.template-customizer-theme-css');
        if (themeLink) {
            themeLink.setAttribute('href', assetsPath + 'vendor/css/theme-dark.css');
        }
    }

    if (sidebarCollapsed && window.innerWidth >= 1200) {
        html.classList.add('layout-menu-collapsed');
    }
})();
</script>
