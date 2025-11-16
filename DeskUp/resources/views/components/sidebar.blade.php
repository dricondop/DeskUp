@use(Illuminate\Support\Facades\Auth)

<aside class="sidebar">
    <header>
        <img class="sidebar-logo" src="{{ asset('assets/logo.png') }}" alt="Logo">
    </header>
    
    <label>My desk:</label>
    <nav>
        @if (Auth::check() && Auth::user()->assigned_desk_id)
            <a href="{{ route('desk.control', ['id' => Auth::user()->assigned_desk_id]) }}">Desk Control</a>
        @else
            <a href="{{ route('desk.control.redirect') }}">Desk Control</a>
        @endif
        <a href="/health">My Usage</a>
        <a href="/profile">My Profile</a>
    </nav>
    
    @if (Auth::check() && Auth::user()->isAdmin())
        <label>Office management:</label>
        <nav>
            <a href="/layout">Office layout</a>
            <a href="#">Usage stats</a>
            <a href="#">Users management</a>
        </nav>
    @endif
</aside>
