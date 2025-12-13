const { createApp } = Vue;

const HeightDetection = {
    template: `
        <div class="posture-analysis-container">
            <div class="analysis-header">
                <div class="header-content">
                    <a href="/ideal-height" class="back-button">
                        <img src="/assets/back.png" alt="Back">
                    </a>
                    <div class="header-text">
                        <h1>Posture Analysis</h1>
                        <p class="subtitle">Position yourself in frame for accurate height detection</p>
                    </div>
                </div>
            </div>

            <div class="analysis-main-layout">
                <div class="sidebar-left"></div>

                <div class="camera-main-section">
                    <div class="camera-container" v-if="!isAnalyzing && !analysisResult">
                        <div class="camera-wrapper">
                            <video 
                                ref="videoElement" 
                                autoplay 
                                playsinline 
                                class="camera-feed"
                                :class="{ 'camera-active': isCameraActive }"
                                @loadedmetadata="onVideoLoaded"
                            ></video>
                            <canvas ref="canvasElement" class="capture-canvas"></canvas>
                        </div>
                        
                        <div class="camera-controls">
                            <button 
                                @click="startCamera" 
                                class="btn-primary"
                                :disabled="isCameraStarting"
                                v-if="!isCameraActive"
                            >
                                <span v-if="isCameraStarting">Starting Camera...</span>
                                <span v-else>Start Camera</span>
                            </button>
                            
                            <button 
                                @click="captureAndAnalyze" 
                                class="btn-primary analyze-btn"
                                :disabled="!isCameraActive || isAnalyzing || !isVideoReady"
                                v-if="isCameraActive && !isAnalyzing"
                            >
                                <span v-if="!isVideoReady">Preparing Camera...</span>
                                <span v-else>Analyze Posture</span>
                            </button>

                            <button 
                                @click="stopCamera" 
                                class="btn-secondary"
                                v-if="isCameraActive"
                            >
                                Stop Camera
                            </button>
                        </div>

                        <div class="camera-status" v-if="cameraStatus">
                            <span :class="'status-' + cameraStatus.type">
                                {{ cameraStatus.message }}
                            </span>
                        </div>
                    </div>

                    <div class="loading-state" v-if="isAnalyzing">
                        <div class="loading-spinner"></div>
                        <h3>Analyzing Your Posture</h3>
                        <p>Please wait while we calculate your ideal desk height...</p>
                    </div>

                    <div class="results-section" v-if="analysisResult && !isAnalyzing">
                        <div class="result-card success" v-if="analysisResult.success">
                            <div class="result-header">
                                <h2>Analysis Complete! 🎉</h2>
                                <p>Your ideal desk height has been calculated</p>
                            </div>
                            
                            <div class="result-data">
                                <div class="data-item">
                                    <span class="data-label">Your Height</span>
                                    <span class="data-value">{{ analysisResult.user_height }} cm</span>
                                </div>
                                <div class="data-item">
                                    <span class="data-label">Recommended Desk Height</span>
                                    <span class="data-value highlight">{{ analysisResult.recommended_height }} cm</span>
                                </div>
                                <div class="data-item">
                                    <span class="data-label">Posture Score</span>
                                    <span class="data-value" :class="'score-' + getScoreLevel(analysisResult.posture_score)">
                                        {{ analysisResult.posture_score }}/100
                                    </span>
                                </div>
                            </div>

                            <div class="result-actions">
                                <button @click="saveAndContinue" class="btn-primary">
                                    Save & Continue
                                </button>
                                <button @click="retryAnalysis" class="btn-secondary">
                                    Analyze Again
                                </button>
                            </div>
                        </div>

                        <div class="result-card error" v-else>
                            <div class="result-header">
                                <h2>Analysis Failed</h2>
                                <p>{{ analysisResult.error }}</p>
                            </div>
                            <div class="result-actions">
                                <button @click="fullRestart" class="btn-primary">
                                    Start New Analysis
                                </button>
                                <button @click="goBack" class="btn-secondary">
                                    Go Back
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="instructions-sidebar">
                    <div class="instruction-card">
                        <h3>📸 Camera Setup</h3>
                        <ul>
                            <li>Ensure good lighting</li>
                            <li>Position camera at eye level</li>
                            <li>Stand 1-2 meters from camera</li>
                            <li>Remove background distractions</li>
                        </ul>
                    </div>

                    <div class="instruction-card">
                        <h3>🧘 Proper Posture</h3>
                        <ul>
                            <li>Stand straight with relaxed shoulders</li>
                            <li>Look directly at the camera</li>
                            <li>Keep arms at your sides</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    `,
    
    data() {
        return {
            isCameraActive: false,
            isCameraStarting: false,
            isAnalyzing: false,
            isVideoReady: false,
            analysisResult: null,
            cameraStream: null,
            cameraStatus: null
        }
    },

    methods: {
        async startCamera() {
            this.isCameraStarting = true;
            this.isVideoReady = false;
            this.cameraStatus = { type: 'info', message: 'Requesting camera access...' };

            try {
                if (this.cameraStream) {
                    this.cameraStream.getTracks().forEach(track => track.stop());
                }

                this.cameraStream = await navigator.mediaDevices.getUserMedia({
                    video: { 
                        width: 1280,
                        height: 720,
                        facingMode: 'user'
                    }
                });

                this.$refs.videoElement.srcObject = this.cameraStream;
                this.isCameraActive = true;
                this.isCameraStarting = false;
                
                this.cameraStatus = { type: 'info', message: 'Camera starting...' };

            } catch (error) {
                console.error('Camera error:', error);
                this.isCameraStarting = false;
                this.cameraStatus = { 
                    type: 'error', 
                    message: 'Cannot access camera. Please check permissions.' 
                };
            }
        },

        onVideoLoaded() {
            console.log('Video metadata loaded, video is ready');
            this.isVideoReady = true;
            this.cameraStatus = { type: 'success', message: 'Camera active - Position yourself in frame' };
        },

        stopCamera() {
            if (this.cameraStream) {
                this.cameraStream.getTracks().forEach(track => track.stop());
                this.cameraStream = null;
            }
            this.isCameraActive = false;
            this.isVideoReady = false;
            this.cameraStatus = null;
        },

        captureFrame() {
            try {
                const video = this.$refs.videoElement;
                const canvas = this.$refs.canvasElement;
                
                if (!video) {
                    throw new Error('Video element not found');
                }
                
                if (!canvas) {
                    throw new Error('Canvas element not found');
                }

                if (!this.isVideoReady || video.videoWidth === 0 || video.videoHeight === 0) {
                    throw new Error('Video not ready. Please wait for camera to initialize.');
                }

                const context = canvas.getContext('2d');
                
                if (!context) {
                    throw new Error('Could not get canvas context');
                }

                canvas.width = video.videoWidth;
                canvas.height = video.videoHeight;

                context.drawImage(video, 0, 0, canvas.width, canvas.height);

                const imageData = canvas.toDataURL('image/jpeg', 0.8);
                
                if (!imageData || imageData.length < 1000) {
                    throw new Error('Captured image is empty or too small');
                }

                console.log('Image captured successfully, size:', imageData.length, 'chars');
                return imageData;
                
            } catch (error) {
                console.error('Error in captureFrame:', error);
                throw new Error('Failed to capture image: ' + error.message);
            }
        },

        async captureAndAnalyze() {
            this.isAnalyzing = true;
            this.cameraStatus = { type: 'info', message: 'Capturing image...' };

            try {
                await new Promise(resolve => setTimeout(resolve, 500));
                
                const imageData = this.captureFrame();
                
                this.cameraStatus = { type: 'info', message: 'Analyzing posture...' };
                console.log('Sending request to server...');

                const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
                
                const response = await fetch('/height-detection/analyze', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    },
                    body: JSON.stringify({
                        image: imageData
                    })
                });

                console.log('Response status:', response.status);
                
                if (!response.ok) {
                    const errorText = await response.text();
                    throw new Error(`Server error (${response.status}): ${errorText}`);
                }

                const result = await response.json();
                console.log('Analysis result:', result);

                if (result.success) {
                    this.analysisResult = {
                        success: true,
                        user_height: result.user_height,
                        recommended_height: result.recommended_height,
                        posture_score: result.posture_score,
                        posture_issues: result.posture_issues || [],
                        detection_id: result.detection_id
                    };
                    this.cameraStatus = { type: 'success', message: 'Analysis complete!' };
                } else {
                    this.analysisResult = {
                        success: false,
                        error: result.error || 'Analysis failed. Please try again.'
                    };
                    this.cameraStatus = { type: 'error', message: 'Analysis failed' };
                }

            } catch (error) {
                console.error('Analysis error:', error);
                this.analysisResult = {
                    success: false,
                    error: error.message
                };
                this.cameraStatus = { type: 'error', message: 'Error: ' + error.message };
            } finally {
                this.isAnalyzing = false;
            }
        },

        saveAndContinue() {
            window.location.href = '/profile';
        },

        retryAnalysis() {
            this.analysisResult = null;
            this.isAnalyzing = false;
            
            if (this.isCameraActive && this.isVideoReady) {
                this.cameraStatus = { type: 'info', message: 'Ready for new analysis' };
            } else {
                this.cameraStatus = { type: 'warning', message: 'Camera not ready. Please restart camera.' };
            }
        },

        fullRestart() {
            this.stopCamera();
            this.analysisResult = null;
            this.isAnalyzing = false;
            this.cameraStatus = { type: 'info', message: 'Click "Start Camera" to begin analysis' };
        },

        goBack() {
            this.stopCamera();
            window.location.href = '/ideal-height';
        },

        getScoreLevel(score) {
            if (score >= 80) return 'excellent';
            if (score >= 60) return 'good';
            if (score >= 40) return 'fair';
            return 'poor';
        }
    },

    mounted() {
        console.log('Height Detection component mounted');
        this.cameraStatus = { type: 'info', message: 'Click "Start Camera" to begin analysis' };
    },

    beforeUnmount() {
        this.stopCamera();
    }
};

// Inicializar la aplicación Vue
document.addEventListener('DOMContentLoaded', function() {
    createApp(HeightDetection).mount('#height-detection-app');
});