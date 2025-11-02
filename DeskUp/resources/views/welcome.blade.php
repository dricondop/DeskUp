<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <title>DeskUp</title>
    <link rel="stylesheet" href="{{ asset('css/welcome.css') }}">
</head>
<body>
    <header class="site-header">
        <div class="container header-inner">
            <div class="brand"> 
                <div class="logo">DeskUp</div>
                <div class="tag">Smart sit‑stand ergonomics</div>
            </div>
            <nav class="actions">
                <a class="btn btn-ghost" href="/signin">Login</a>
                <a class="btn btn-primary" href="/signup">Sign Up</a>
            </nav>
        </div>
    </header>

    <main>
        <section class="hero container">
            <div class="hero-left">
                <h1 class="hero-title">
                    Your desk, your health, your data -
                    <span id="dynamic-text">smart ergonomics for a better workspace.</span>
                </h1>
                <p class="hero-lead">
                    DeskUp monitors desk usage and posture, automates height for comfort,
                    and delivers actionable analytics for users and facility managers.
                </p>
                <div class="hero-cta">
                    <a class="btn btn-primary" href="/signup">Get Started</a>
                    <a class="btn btn-ghost" href="#features">Learn More</a>
                </div>
            </div>
            <div class="hero-right">
                <div class="device-card">
                    <div class="device-header">
                        <div class="dot green"></div>
                        <div class="dot yellow"></div>
                        <div class="dot red"></div>
                    </div>
                    <div class="device-body">
                        <div class="stat">
                            <div class="stat-value">35%</div>
                            <div class="stat-label">Standing time</div>
                        </div>
                        <div class="stat">
                            <div class="stat-value">89 points</div>
                            <div class="stat-label">Posture score</div>
                        </div>
                        <div class="stat">
                            <div class="stat-label">Calories burnt</div>
                            <div class="stat-value">120 kcal</div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section id="features" class="features container">
            <h2 class="section-title">Core features</h2>
            <div class="grid">
                <article class="card">
                    <div class="card-icon">1</div>
                    <h3>Well‑being monitoring</h3>
                    <p>Track posture and activity to encourage healthy habits and break reminders.</p>
                </article>

                <article class="card">
                    <div class="card-icon">2</div>
                    <h3>Smart height control</h3>
                    <p>Preset positions and adaptive adjustments ensure ergonomic comfort automatically.</p>
                </article>

                <article class="card">
                    <div class="card-icon">3</div>
                    <h3>Usage analytics</h3>
                    <p>Visualize desk utilization and posture trends for individuals and teams.</p>
                </article>

                <article class="card">
                    <div class="card-icon">4</div>
                    <h3>Predictive maintenance</h3>
                    <p>Detect anomalies and forecast maintenance to reduce downtime and extend lifecycle.</p>
                </article>
            </div>
        </section>

        <section class="secondary container">
            <h3>Designed for people and places</h3>
            <p>Whether you're an individual user or a facilities manager, DeskUp delivers insights that improve comfort and productivity across the office.</p>
        </section>
    </main>

    <footer class="site-footer">    
        <div class="container footer-inner">
            <div class="copyright">© {{ date('Y') }} DeskUp</div>
            <nav class="footer-links">
                <a href="#">Privacy</a>
                <a href="#">Terms</a>
                <a href="#">Contact</a>
            </nav>
        </div>
    </footer>

    <script src="{{ asset('js/welcome.js') }}"></script>
</body>
</html>