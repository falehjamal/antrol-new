<aside class="antrol-sidebar" id="antrolSidebar">
    <div class="antrol-sidebar-brand">
        <h1>Antrol MJKN</h1>
        <p>Webservice RS BPJS</p>
    </div>

    <ul class="antrol-nav">
        <li class="antrol-nav-item">
            <a href="{{ route('dashboard') }}" class="antrol-nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                <i class="bx bx-grid-alt"></i>
                <span>Dashboard</span>
            </a>
        </li>
        <li class="antrol-nav-item">
            <a href="{{ route('logs.index') }}" class="antrol-nav-link {{ request()->routeIs('logs.*') ? 'active' : '' }}">
                <i class="bx bx-list-ul"></i>
                <span>Logs</span>
            </a>
        </li>
    </ul>

    <div class="antrol-sidebar-footer">
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit">
                <i class="bx bx-log-out"></i>
                <span>Sign Out</span>
            </button>
        </form>
    </div>
</aside>
