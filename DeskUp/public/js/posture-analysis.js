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
                        <p class="subtitle">Position yourself in frame for accurate ideal height calculation</p>
                    </div>
                </div>
            </div>

            <div class="analysis-main-layout">
                <div class="sidebar-left"></div>

                <div class="camera-main-section">
                    <div class="camera-container" v-if="!isAnalyzing && !analysisResult">
                        <!-- Display current desk height -->
                        <div class="current-height-display" style="
                            background: white;
                            padding: 1rem;
                            border-radius: 8px;
                            margin-bottom: 1.5rem;
                            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
                            border-left: 4px solid #4299e1;
                        ">
                            <div style="display: flex; justify-content: space-between; align-items: center;">
                                <span style="font-weight: 600; color: #4a5568;">Current Desk Height:</span>
                                <span style="font-weight: 700; color: #1a202c; font-size: 1.2rem;">
                                    {{ currentHeight }} cm
                                </span>
                            </div>
                            <p style="margin: 0.5rem 0 0 0; font-size: 0.9rem; color: #718096;">
                                This is your desk's current height. We'll calculate the ideal height based on your posture.
                            </p>
                        </div>

                        <div class="camera-wrapper">
                            <video 
                                id="posture-video"
                                ref="videoElement" 
                                autoplay 
                                playsinline 
                                class="camera-feed"
                                :class="{ 'camera-active': isCameraActive }"
                                @loadedmetadata="onVideoLoaded"
                            ></video>
                            <canvas id="posture-canvas" ref="canvasElement" class="capture-canvas"></canvas>
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
                                <span v-else>Calculate Ideal Height</span>
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
                        <p>Calculating your ideal desk height...</p>
                    </div>

                    <div class="results-section" v-if="analysisResult && !isAnalyzing">
                        <div class="result-card success" v-if="analysisResult.success">
                            <div class="result-header">
                                <h2>Analysis Complete! 🎉</h2>
                                <p>Your ideal desk height has been calculated</p>
                            </div>
                            
                            <div class="result-data">
                                <div class="data-item">
                                    <span class="data-label">Current Desk Height</span>
                                    <span class="data-value">{{ analysisResult.current_height }} cm</span>
                                </div>
                                <div class="data-item">
                                    <span class="data-label">Recommended Ideal Height</span>
                                    <span class="data-value highlight">{{ analysisResult.ideal_height }} cm</span>
                                </div>
                                <div class="data-item" v-if="analysisResult.difference">
                                    <span class="data-label">Adjustment Needed</span>
                                    <span class="data-value" :class="adjustmentClass">
                                        {{ analysisResult.difference > 0 ? '+' : '' }}{{ analysisResult.difference }} cm
                                    </span>
                                </div>
                            </div>

                            <div class="result-actions">
                                <button @click="saveAndContinue" class="btn-primary">
                                    Save Ideal Height
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
                    
                    <div class="instruction-card">
                        <h3>📊 About Ideal Height</h3>
                        <ul>
                            <li>Based on your posture analysis</li>
                            <li>Calculated using computer vision</li>
                            <li>Saved to your profile</li>
                            <li>Used for desk adjustments</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    `,
    
    props: {
        currentHeight: {
            type: Number,
            default: 0
        }
    },
    
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

    computed: {
        adjustmentClass() {
            if (!this.analysisResult || !this.analysisResult.difference) return '';
            const diff = this.analysisResult.difference;
            if (diff > 0) return 'score-good';
            if (diff < 0) return 'score-poor';
            return 'score-excellent';
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

        async captureFrame() {
            try {
                console.log('Attempting to capture frame...');
                
                // Wait for Vue.js to update the DOM
                await this.$nextTick();
                
                // Use getElementById as a secure fallback
                const video = document.getElementById('posture-video') || this.$refs.videoElement;
                const canvas = document.getElementById('posture-canvas') || this.$refs.canvasElement;
                
                console.log('Video element:', video);
                console.log('Canvas element:', canvas);
                
                if (!video) {
                    throw new Error('Video element not found. Please restart camera.');
                }
                
                if (!canvas) {
                    throw new Error('Canvas element not found.');
                }

                if (!this.isVideoReady || video.videoWidth === 0 || video.videoHeight === 0) {
                    throw new Error('Video not ready. Please wait for camera to initialize.');
                }

                const context = canvas.getContext('2d');
                if (!context) {
                    throw new Error('Could not get canvas context');
                }

                // Set dimensions
                canvas.width = video.videoWidth;
                canvas.height = video.videoHeight;

                // Capture frame
                context.drawImage(video, 0, 0, canvas.width, canvas.height);

                // Convert to base64
                const imageData = canvas.toDataURL('image/jpeg', 0.8);
                
                if (!imageData || imageData.length < 1000) {
                    throw new Error('Captured image is empty or too small');
                }

                console.log('Image captured successfully');
                return imageData;
                
            } catch (error) {
                console.error('Capture error:', error);
                throw new Error('Failed to capture image: ' + error.message);
            }
        },

        async captureAndAnalyze() {
            console.log('Starting capture and analyze...');
            
            this.isAnalyzing = true;
            this.cameraStatus = { type: 'info', message: 'Capturing image...' };

            try {
                // Critical delay: Wait for DOM to be ready
                await new Promise(resolve => setTimeout(resolve, 500));
                
                const imageData = await this.captureFrame();
                
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
                        image: imageData,
                        current_height: this.currentHeight
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
                    // Calculate the difference
                    const difference = result.ideal_height - this.currentHeight;
                    
                    this.analysisResult = {
                        success: true,
                        current_height: this.currentHeight,
                        ideal_height: result.ideal_height,
                        difference: Math.round(difference * 10) / 10, // One decimal
                        message: result.message || 'Ideal height calculated'
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
            // Already saved automatically in the backend
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
        }
    },

    mounted() {
        console.log('Height Detection component mounted');
        console.log('Current height from Laravel:', this.currentHeight);
        this.cameraStatus = { type: 'info', message: 'Click "Start Camera" to begin analysis' };
    },

    beforeUnmount() {
        this.stopCamera();
    }
};

// Initialize Vue application
document.addEventListener('DOMContentLoaded', function() {
    const app = createApp(HeightDetection);
    
    // Get current height from data attribute
    const appElement = document.getElementById('height-detection-app');
    const currentHeight = appElement ? (appElement.getAttribute('data-current-height') || 0) : 0;
    
    console.log('Initializing Vue app with currentHeight:', currentHeight);
    app.mount('#height-detection-app');
});