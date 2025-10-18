<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Profile - DeskUp</title>
    <link rel="stylesheet" href="{{ asset('css/style.css') }}">
</head>
<body>
    <!-- Include Header Component -->
    {{ include('components/header.html') }}

    <div class="container">
        <div class="profile-container">
            <div class="profile-header">
                <div class="profile-image-container">
                    <!-- Database integration: User profile image or initials from users table -->
                    <div class="profile-image" data-user-initials="JD">JD</div>
                    <!-- Database integration: User email from users table -->
                    <p class="profile-email" data-user-email="john.doe@example.com">john.doe@example.com</p>
                </div>
                <div class="profile-info">
                    <!-- Database integration: User full name from users table -->
                    <h1 class="profile-name" data-user-name="John Doe">John Doe</h1>
                    <!-- Database integration: User job title from users table -->
                    <p class="profile-title" data-user-title="Software Developer">Software Developer</p>
                </div>
            </div>

            <div class="stats-section">
                <h2 class="section-title">Today's Health & Position</h2>
                
                <div class="charts-container">
                    <div class="chart-wrapper">
                        <div class="chart-container">
                            <div class="chart health-chart">
                                <div class="chart-fill" data-health-percentage="85"></div>
                                <!-- Database integration: Health score from daily_health table -->
                                <div class="chart-center" data-health-score="85">85%</div>
                            </div>
                        </div>
                        <div class="chart-label">Health Score</div>
                        <!-- Database integration: Health details from health_metrics table -->
                        <div class="chart-details" data-health-details="Based on posture & movement">Based on posture & movement</div>
                    </div>
                    
                    <div class="chart-wrapper">
                        <div class="chart-container">
                            <div class="chart position-chart">
                                <div class="chart-fill" data-standing-percentage="65"></div>
                                <!-- Database integration: Standing percentage from desk_usage table -->
                                <div class="chart-center" data-standing-percentage="65">65%</div>
                            </div>
                        </div>
                        <div class="chart-label">Work Position</div>
                        <!-- Database integration: Position breakdown from desk_usage table -->
                        <div class="chart-details" data-position-breakdown="35% sitting / 65% standing">35% sitting / 65% standing</div>
                    </div>
                </div>
                
                <div class="actions-section">
                    <a href="/statistics" class="action-btn">View Detailed Statistics</a>
                    <a href="/ideal-position" class="action-btn secondary">Find Your Ideal Position</a>
                </div>
            </div>
        </div>
    </div>

    <!-- Include Footer Component -->
    {{ include('components/footer.html') }}

    <script src="{{ asset('js/profile.js') }}"></script>
</body>
</html>