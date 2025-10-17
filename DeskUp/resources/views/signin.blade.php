<!DOCTYPE html>
<html>
    <head>
        <title>DeskUp-SignIn</title>
        <link rel="stylesheet" href="{{ asset ('css/sign-in-style.css') }}">
        <meta charset="utf-8">
    </head>
    <body class="signin-body">
        <div class="sign-in-container">
            <a href="" class="back"> <img src="{{ asset ('assets/back.png') }}"> </a>
            <img class="sign-in-logo" src="{{ asset ('assets/logo.png') }}">
            <div class="form-container">
                @if ($errors->has('auth'))
                    <p class="alert error" role="alert">{{ $errors->first('auth') }}</p>
                @endif
                <form class="sign-in-form" method="post" action="{{ url('/signin') }}">
                    @csrf
                    <input type="email" name="email" class="email" placeholder="Email" required>
                    <div class="password-container">
                        <input type="password" id="password" name="password" class="password" placeholder="Password" required>
                        <button type="button" id="toggle-password" class="toggle-password" aria-label="Show password" title="Show password" aria-pressed="false">
                            <img src="{{ asset('assets/eye.png') }}">
                        </button>
                    </div>
                    <a href="" class="forgot-password">Forgot password?</a>
                    <button type="submit" class="submit">Sign In</button>
                </form>
            </div>
        </div>
    </body>
</html>

<script>
    const password = document.getElementById('password');
    const togglePassword = document.getElementById('toggle-password');
    
    if (password && togglePassword) {
        togglePassword.addEventListener('click', () => {
            const show = password.type === 'password';
            password.type = show ? 'text' : 'password';
            togglePassword.setAttribute('aria-pressed', String(show));
            togglePassword.title = show ? 'Hide password' : 'Show password';
        });
    }
    
</script>