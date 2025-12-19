@use(Illuminate\Support\Facades\Auth)

<aside class="sidebar">
    <header>
        <a href="{{ url('/') }}">
            <img class="sidebar-logo" src="{{ asset('assets/logo.png') }}" alt="Logo">
        </a>
    </header>

    @if (Auth::check())
        <div class="sidebar-user-info">
            <a href="/profile" class="user-details">
                <div class="user-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                        <circle cx="12" cy="7" r="4"></circle>
                    </svg>
                </div>
                <div class="user-text">
                    <span class="user-name">{{ Auth::user()->name }}</span>
                    @if(Auth::user()->isAdmin())
                        <span class="user-role admin">Administrator</span>
                    @else
                        <span class="user-role">User</span>
                    @endif
                </div>
            </a>
            <form action="{{ route('logout') }}" method="POST" class="logout-form">
                @csrf
                <button type="submit" class="logout-btn">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                        <polyline points="16 17 21 12 16 7"></polyline>
                        <line x1="21" y1="12" x2="9" y2="12"></line>
                    </svg>
                    Logout
                </button>
            </form>
        </div>
    @endif
    
    <label>My desk:</label>
    <nav>
        @if (Auth::check() && Auth::user()->assigned_desk_id)
            <a href="{{ route('desk.control', ['id' => Auth::user()->assigned_desk_id]) }}">Desk Control</a>
        @else
            <a href="{{ route('desk.control.redirect') }}">Desk Control</a>
        @endif
        <a href="/events">Events</a>
        <a href="/health">My Usage</a>
    </nav>
    
    @if (Auth::check() && Auth::user()->isAdmin())
        <label>Office management:</label>
        <nav>
            <a href="/layout">Office Layout</a>
            <a href="/users-management">Users Management</a>
            <a href="/admin-statistics">Admin Statistics</a>
        </nav>
    @endif
</aside>
