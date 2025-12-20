<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DeskUp | Ideal Height</title>
    
    <link rel="stylesheet" href="{{ asset('css/sidebar.css') }}">
    <link rel="stylesheet" href="{{ asset('css/ideal-height.css') }}">
</head>
<body>
    @include('components.sidebar')
    
    <div class="main-content">
    <a href="{{ url()->previous() }}" class="back-button">
        <img src="{{ asset('assets/back.png') }}" alt="Back">
    </a>

    <div class="ideal-height-container">
        <!-- Header -->
        <div class="profile-header">
            <div class="header-content">
                <div class="profile-main">
                    <div class="profile-info">
                        <h1>Perfect Posture Setup</h1>
                        <p class="position">Follow these 4 steps for optimal ergonomic positioning</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="profile-content">
            <!-- Instructions Grid -->
            <div class="info-card">
                <div class="card-header">
                    <h2 class="card-title">Posture Instructions</h2>
                </div>
                <div class="instructions-grid">
                    <!-- Step 1 -->
                    <div class="instruction-item">
                        <div class="instruction-step">1</div>
                        <div class="instruction-content">
                            <div class="instruction-image">
                                <div class="image-placeholder">
                                    <div class="placeholder-icon">🪑</div>
                                </div>
                            </div>
                            <div class="instruction-text">
                                <h3 class="step-title">Sit Straight</h3>
                                <p class="step-subtitle">Align your back with the chair backrest for full support</p>
                            </div>
                        </div>
                    </div>

                    <!-- Step 2 -->
                    <div class="instruction-item">
                        <div class="instruction-step">2</div>
                        <div class="instruction-content">
                            <div class="instruction-image">
                                <div class="image-placeholder">
                                    <div class="placeholder-icon">💆</div>
                                </div>
                            </div>
                            <div class="instruction-text">
                                <h3 class="step-title">Relax Shoulders</h3>
                                <p class="step-subtitle">Keep shoulders relaxed and arms resting naturally</p>
                            </div>
                        </div>
                    </div>

                    <!-- Step 3 -->
                    <div class="instruction-item">
                        <div class="instruction-step">3</div>
                        <div class="instruction-content">
                            <div class="instruction-image">
                                <div class="image-placeholder">
                                    <div class="placeholder-icon">👀</div>
                                </div>
                            </div>
                            <div class="instruction-text">
                                <h3 class="step-title">Position Head</h3>
                                <p class="step-subtitle">Look straight ahead with screen at eye level</p>
                            </div>
                        </div>
                    </div>

                    <!-- Step 4 -->
                    <div class="instruction-item">
                        <div class="instruction-step">4</div>
                        <div class="instruction-content">
                            <div class="instruction-image">
                                <div class="image-placeholder">
                                    <div class="placeholder-icon">📷</div>
                                </div>
                            </div>
                            <div class="instruction-text">
                                <h3 class="step-title">Ready for Analysis</h3>
                                <p class="step-subtitle">Stay still in frame with good lighting</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="action-buttons">
                <a href="{{ route('profile') }}" class="back-to-profile-btn">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/>
                    </svg>
                </a>
                <a href="{{ route('posture.analysis') }}" class="analyze-posture-btn">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M12 4V1L8 5l4 4V6c3.31 0 6 2.69 6 6 0 1.01-.25 1.97-.7 2.8l1.46 1.46C19.54 15.03 20 13.57 20 12c0-4.42-3.58-8-8-8zm0 14c-3.31 0-6-2.69-6-6 0-1.01.25-1.97.7-2.8L5.24 7.74C4.46 8.97 4 10.43 4 12c0 4.42 3.58 8 8 8v3l4-4-4-4v3z"/>
                    </svg>
                    Analyze Posture
                </a>
            </div>
        </div>
    </div>
    </div>

    <script src="{{ asset('js/ideal-height.js') }}"></script>
</body>
</html>