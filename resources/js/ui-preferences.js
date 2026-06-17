'use strict';

(function () {
    const THEME_KEY = 'antrol-theme';
    const SIDEBAR_KEY = 'antrol-sidebar-collapsed';
    const DESKTOP_BREAKPOINT = 1200;

    function isDesktop() {
        return window.innerWidth >= DESKTOP_BREAKPOINT;
    }

    function getThemeLink() {
        return document.querySelector('.template-customizer-theme-css');
    }

    function getAssetsPath() {
        return document.documentElement.getAttribute('data-assets-path') || '/sneat/';
    }

    function applyTheme(theme) {
        const html = document.documentElement;
        const isDark = theme === 'dark';
        const link = getThemeLink();

        html.classList.remove('light-style', 'dark-style');
        html.classList.add(isDark ? 'dark-style' : 'light-style');

        if (link) {
            link.setAttribute(
                'href',
                getAssetsPath() + 'vendor/css/' + (isDark ? 'theme-dark.css' : 'theme-default.css')
            );
        }

        updateThemeToggleIcon(isDark);
    }

    function getTheme() {
        return localStorage.getItem(THEME_KEY) === 'dark' ? 'dark' : 'light';
    }

    function toggleTheme() {
        const next = getTheme() === 'dark' ? 'light' : 'dark';
        localStorage.setItem(THEME_KEY, next);
        applyTheme(next);
    }

    function updateThemeToggleIcon(isDark) {
        document.querySelectorAll('[data-theme-toggle]').forEach(function (btn) {
            const icon = btn.querySelector('i');
            if (!icon) return;

            icon.classList.remove('bx-moon', 'bx-sun');
            icon.classList.add(isDark ? 'bx-sun' : 'bx-moon');

            btn.setAttribute('aria-label', isDark ? 'Mode terang' : 'Mode gelap');
            btn.setAttribute('title', isDark ? 'Mode terang' : 'Mode gelap');
        });
    }

    function isSidebarCollapsed() {
        return document.documentElement.classList.contains('layout-menu-collapsed');
    }

    function setSidebarCollapsed(collapsed) {
        const html = document.documentElement;
        const helpers = window.Helpers;

        if (helpers && typeof helpers._setMenuHoverState === 'function') {
            helpers._setMenuHoverState(false);
        }

        html.classList.toggle('layout-menu-collapsed', collapsed);
        localStorage.setItem(SIDEBAR_KEY, collapsed ? '1' : '0');

        window.dispatchEvent(new Event('resize'));
    }

    function toggleSidebar() {
        if (!isDesktop()) {
            if (window.Helpers && typeof window.Helpers.toggleCollapsed === 'function') {
                window.Helpers.toggleCollapsed();
            }
            return;
        }

        setSidebarCollapsed(!isSidebarCollapsed());
    }

    function initSidebarFromStorage() {
        if (!isDesktop()) {
            if (window.Helpers && typeof window.Helpers.setCollapsed === 'function') {
                window.Helpers.setCollapsed(true, false);
            }
            return;
        }

        document.documentElement.classList.toggle(
            'layout-menu-collapsed',
            localStorage.getItem(SIDEBAR_KEY) === '1'
        );
    }

    function bindThemeToggle() {
        document.querySelectorAll('[data-theme-toggle]').forEach(function (btn) {
            btn.addEventListener('click', function (e) {
                e.preventDefault();
                toggleTheme();
            });
        });
    }

    function bindSidebarToggle() {
        document.querySelectorAll('.layout-menu-toggle').forEach(function (el) {
            el.addEventListener('click', function (e) {
                e.preventDefault();
                e.stopImmediatePropagation();
                toggleSidebar();
            }, true);
        });
    }

    function init() {
        applyTheme(getTheme());
        initSidebarFromStorage();
        bindThemeToggle();
        bindSidebarToggle();
        updateThemeToggleIcon(getTheme() === 'dark');
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
