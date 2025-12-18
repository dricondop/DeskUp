<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Privacy Policy - DeskUp</title>
    <link rel="stylesheet" href="{{ asset('css/privacy-policy.css') }}">
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

    <main class="container policy-container">
        <h1>Privacy Policy</h1>
        <div class="policy-content">
            <section>
                <h2>1. Information We Collect</h2>
                <p>We collect information to provide better services to our users. This includes:</p>
                <ul>
                    <li>Account information (name, email, organization)</li>
                    <li>Desk usage data (height adjustments, standing/sitting times)</li>
                    <li>Posture analysis data</li>
                    <li>Device information and usage statistics</li>
                </ul>
            </section>

            <section>
                <h2>2. How We Use Your Information</h2>
                <p>We use the information we collect to:</p>
                <ul>
                    <li>Provide and improve our services</li>
                    <li>Personalize your experience</li>
                    <li>Analyze usage patterns for product development</li>
                    <li>Send important notifications about your account</li>
                </ul>
            </section>

            <section>
                <h2>3. Data Security</h2>
                <p>We implement appropriate technical and organizational measures to protect your personal data against unauthorized access, alteration, disclosure, or destruction.</p>
            </section>

            <section>
                <h2>4. Data Sharing</h2>
                <p>We do not sell your personal data. We may share aggregated, non-personally identifiable information with partners for analytical purposes.</p>
            </section>

            <section>
                <h2>5. Your Rights</h2>
                <p>You have the right to access, correct, or delete your personal data. Contact us at privacy@deskup.com to exercise these rights.</p>
            </section>

            <p class="last-updated">Last Updated: {{ date('F d, Y') }}</p>
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