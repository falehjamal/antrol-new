<header class="antrol-topbar">
    <button type="button" class="antrol-icon-btn d-xl-none" id="sidebarToggle" aria-label="Menu">
        <i class="bx bx-menu"></i>
    </button>

    <div class="antrol-search">
        <i class="bx bx-search"></i>
        <input type="text" placeholder="Search patients, records, or schedules..." readonly tabindex="-1" aria-hidden="true">
    </div>

    <div class="antrol-topbar-actions">
        <a href="{{ route('logs.index') }}" class="antrol-icon-btn" title="Notifikasi logs">
            <i class="bx bx-bell"></i>
            <span class="dot"></span>
        </a>
        <span class="antrol-icon-btn" title="Kalender">
            <i class="bx bx-calendar"></i>
        </span>
        <div class="antrol-user">
            <div class="antrol-user-avatar">
                {{ strtoupper(substr(auth('admin')->user()->name ?? auth('admin')->user()->username, 0, 1)) }}
            </div>
            <span class="antrol-user-name">{{ auth('admin')->user()->name }}</span>
            <i class="bx bx-chevron-down text-muted" style="font-size: 1.125rem;"></i>
        </div>
    </div>
</header>

<script>
document.getElementById('sidebarToggle')?.addEventListener('click', function () {
    document.getElementById('antrolSidebar')?.classList.toggle('open');
});
</script>
