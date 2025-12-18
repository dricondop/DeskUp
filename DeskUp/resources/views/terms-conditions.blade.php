<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Terms & Conditions - DeskUp</title>
    <link rel="stylesheet" href="{{ asset('css/terms-conditions.css') }}">
    <link rel="stylesheet" href="{{ asset('css/welcome.css') }}">
</head>
<body>
    <header class="site-header">
        <div class="container header-inner">
            <div class="brand"> 
                <a href="/" style="text-decoration: none; color: inherit;">
                    <div class="logo">DeskUp</div>
                    <div class="tag">Smart sit‑stand ergonomics</div>
                </a>
            </div>
            <nav class="actions">
                <a class="btn btn-ghost" href="/signin">Login</a>
                <a class="btn btn-primary" href="/signup">Sign Up</a>
            </nav>
        </div>
    </header>

    <main class="container terms-container">
        <h1>Terms & Conditions</h1>
        <div class="terms-content">
            <section>
                <h2>1. Acceptance of Terms</h2>
                <p>By accessing and using DeskUp services, you accept and agree to be bound by these Terms & Conditions.</p>
            </section>

            <section>
                <h2>2. Service Description</h2>
                <p>DeskUp provides smart desk management solutions including height control, posture analysis, and usage analytics.</p>
            </section>

            <section>
                <h2>3. User Accounts</h2>
                <p>You are responsible for maintaining the confidentiality of your account credentials and for all activities that occur under your account.</p>
            </section>

            <section>
                <h2>4. Acceptable Use</h2>
                <p>You agree not to misuse the DeskUp services or help anyone else do so. This includes:</p>
                <ul>
                    <li>Accessing the service without authorization</li>
                    <li>Interfering with the proper functioning of the service</li>
                    <li>Attempting to bypass any security measures</li>
                    <li>Using the service for any illegal purpose</li>
                </ul>
            </section>

            <section>
                <h2>5. Limitation of Liability</h2>
                <p>DeskUp shall not be liable for any indirect, incidental, special, consequential, or punitive damages resulting from your use of or inability to use the service.</p>
            </section>

            <section>
                <h2>6. Changes to Terms</h2>
                <p>We may modify these terms at any time. We will notify users of any material changes via email or through our service.</p>
            </section>

            <section>
                <h2>7. Governing Law</h2>
                <p>These terms shall be governed by the laws of the jurisdiction in which DeskUp operates.</p>
            </section>

            <p class="last-updated">Effective Date: {{ date('F d, Y') }}</p>
        </div>
    </main>

    <footer class="site-footer">    
        <div class="container footer-inner">
            <div class="copyright">© {{ date('Y') }} DeskUp</div>
            <nav class="footer-links">
                <a href="{{ route('privacy.policy') }}">Privacy</a>
                <a href="{{ route('terms.conditions') }}">Terms</a>
                <a href="{{ route('contact.us') }}">Contact</a>
            </nav>
        </div>
    </footer>
</body>
</html>