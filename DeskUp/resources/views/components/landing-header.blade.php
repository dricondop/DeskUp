@props(['showAuthButtons' => true])

<header class="site-header">
    <div class="container header-inner">
        <div class="brand"> 
            <a href="/" style="text-decoration: none; color: inherit;">
                <div class="logo">DeskUp</div>
                <div class="tag">Smart sit‑stand ergonomics</div>
            </a>
        </div>
        
        @if($showAuthButtons)
            <nav class="actions">
                <a class="btn btn-ghost" href="/signin">Login</a>
                <a class="btn btn-primary" href="/signup">Sign Up</a>
            </nav>
        @endif
    </div>
</header>