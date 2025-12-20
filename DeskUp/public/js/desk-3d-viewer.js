/**
 * Desk3DViewer - Height mapping algorithm with desktop color customization
 * Model: 45 values
 * App: 128 values (68-196 cm) - 64 in reality (68-132)
 */
class Desk3DViewer {
    constructor(config) {
        this.config = config;
        this.currentHeight = config.currentHeight;
        
        // Application range
        this.appMinHeight = 68;
        this.appMaxHeight = 196;
        this.appHeightRange = 128; 
        
        // Model range
        this.modelMinHeight = 60;
        this.modelMaxHeight = 105;
        this.modelHeightRange = 45; 
        
        // DOM elements
        this.container = document.getElementById('desk-3d-viewer');
        this.heightIndicator = document.getElementById('current-height-3d');
        this.loadingIndicator = document.getElementById('loading-3d');
        
        // Three.js
        this.scene = null;
        this.camera = null;
        this.renderer = null;
        this.controls = null;
        this.model = null;
        this.mixer = null;
        this.animationAction = null;
        
        // Material management system
        this.materialGroups = {
            'metallic': [],    // Steel frame 
            'desktop': [],     // Desktop surface (editable - 4 colors)
            'plastic': [],     // Plastic components
            'glass': [],       // Glass panel
            'other': []
        };
        
        // Desktop color presets (4 options)
        this.desktopPresets = {
            'light-wood': {    // Default - light wood
                color: 0xd4b996,
                roughness: 0.8,
                metalness: 0.1,
                label: 'Default'
            },
            'white': {         // White (soft white)
                color: 0xf0f0f0,
                roughness: 0.7,
                metalness: 0.05,
                label: 'White'
            },
            'black': {         // Black steel look
                color: 0x2c3e50,
                roughness: 0.6,
                metalness: 0.3,
                label: 'Black'
            },
            'dark-wood': {     // Dark wood
                color: 0x8b7355,
                roughness: 0.9,
                metalness: 0.1,
                label: 'Dark Wood'
            }
        };
        
        // Background color presets
        this.backgroundPresets = {
            'default': {       // Desk-control white
                type: 'color',
                value: 0xEFF1F2,
                label: 'Default'
            },
            'sky-blue': {      // Sky blue gradient
                type: 'gradient',
                value: {
                    top: 0x87CEEB,
                    bottom: 0xE0F7FF
                },
                label: 'Sky Blue'
            },
            'night-black': {   // Night black gradient with stars
                type: 'gradient',
                value: {
                    top: 0x0A0A0A,
                    bottom: 0x1E1E1E
                },
                label: 'Night'
            }
        };
        
        this.currentDesktopPreset = 'light-wood';
        this.currentBackgroundPreset = 'default';
        
        // Animation states
        this.isAnimating = false;
        this.isModelLoaded = false;
        
        // Animation control
        this.animationStartTime = 0;
        this.animationDuration = 1000; // ms
        this.animationFrom = 0;
        this.animationTo = 0;
        this.animationTargetHeight = 0;
        
        this.init();
    }
    
    /**
     * ALGORITHM 1: Linear mapping
     * Convert app height (68-196) to model height (60-105)
     */
    appToModelHeight(appHeight) {
        // Validate limits
        const clamped = Math.max(this.appMinHeight, Math.min(this.appMaxHeight, appHeight));
        
        // Linear mapping
        const appProgress = (clamped - this.appMinHeight) / this.appHeightRange;
        const modelHeight = this.modelMinHeight + (appProgress * this.modelHeightRange);
        
        // Round to nearest value
        return Math.round(modelHeight * 10) / 10;
    }
    
    /**
     * ALGORITHM 2: Non-linear mapping (better for desks)
     * More precision at low heights (sitting) than high heights
     */
    appToModelHeightOptimized(appHeight) {
        const clamped = Math.max(this.appMinHeight, Math.min(this.appMaxHeight, appHeight));
        
        // Percentage in app range
        const appProgress = (clamped - this.appMinHeight) / this.appHeightRange;
        
        // Non-linear transformation (smooth curve)
        let modelProgress;
        
        if (appProgress < 0.5) {
            // For low heights (68-132cm): more precision
            modelProgress = Math.pow(appProgress * 2, 0.7) / 2;
        } else {
            // For high heights (132-196cm): less precision
            modelProgress = 0.5 + (Math.pow((appProgress - 0.5) * 2, 1.3) / 2);
        }
        
        const modelHeight = this.modelMinHeight + (modelProgress * this.modelHeightRange);
        return Math.round(modelHeight * 10) / 10;
    }
    
    /**
     * Convert height to animation time (0-1)
     */
    heightToAnimationTime(appHeight) {
        // Use algorithm 2 (optimized)
        const modelHeight = this.appToModelHeightOptimized(appHeight);
        
        // Convert model height to animation time
        const modelProgress = (modelHeight - this.modelMinHeight) / this.modelHeightRange;
        
        // Ensure it's between 0 and 1
        return Math.max(0, Math.min(1, modelProgress));
    }
    
    async init() {
        try {
            this.createScene();
            this.setupLights();
            this.createEnvironment();
            this.setupControls();
            await this.loadGLBModel();
            this.animate();
            this.setupEvents();
            
            // Create color selectors after model loads
            this.createColorSelectors();
            
            // Hide height indicator (redundant)
            if (this.heightIndicator) {
                this.heightIndicator.style.display = 'none';
            }
            
        } catch (e) {
            console.error(e);
            this.createFallbackDesk();
        }
    }
    
    createScene() {
        this.scene = new THREE.Scene();
        
        // Set default background (desk-control white)
        this.applyBackgroundPreset('default');
        
        const w = this.container.clientWidth || 800;
        const h = this.container.clientHeight || 500;
        
        this.camera = new THREE.PerspectiveCamera(45, w / h, 0.1, 1000);
        this.camera.position.set(2, 1.8, 3);
        
        this.renderer = new THREE.WebGLRenderer({ 
            antialias: true, 
            alpha: true,
            powerPreference: "high-performance"
        });
        this.renderer.setSize(w, h);
        this.renderer.setPixelRatio(Math.min(window.devicePixelRatio, 2));
        this.renderer.shadowMap.enabled = true;
        this.renderer.shadowMap.type = THREE.PCFSoftShadowMap;
        
        this.container.appendChild(this.renderer.domElement);
    }
    
    setupLights() {
        // Ambient light - soft overall illumination
        const ambient = new THREE.AmbientLight(0xffffff, 0.6);
        this.scene.add(ambient);
        
        // Main directional light - simulates sunlight
        const mainLight = new THREE.DirectionalLight(0xffffff, 0.8);
        mainLight.position.set(5, 12, 5);
        mainLight.castShadow = true;
        mainLight.shadow.mapSize.width = 2048;
        mainLight.shadow.mapSize.height = 2048;
        mainLight.shadow.camera.near = 0.5;
        mainLight.shadow.camera.far = 50;
        this.scene.add(mainLight);
        
        // Fill light - soft light from opposite side
        const fillLight = new THREE.DirectionalLight(0xffffff, 0.3);
        fillLight.position.set(-5, 8, -5);
        this.scene.add(fillLight);
    }
    
    createEnvironment() {
        // Floor - larger and positioned correctly
        const floorGeo = new THREE.PlaneGeometry(20, 20);
        const floorMat = new THREE.MeshStandardMaterial({ 
            color: 0x888888, 
            roughness: 0.9,
            metalness: 0.1
        });
        const floor = new THREE.Mesh(floorGeo, floorMat);
        floor.rotation.x = -Math.PI / 2;
        floor.position.y = 0; // Ground level
        floor.receiveShadow = true;
        this.scene.add(floor);
    }
    
    setupControls() {
        if (THREE.OrbitControls) {
            this.controls = new THREE.OrbitControls(this.camera, this.renderer.domElement);
            this.controls.enableDamping = true;
            this.controls.dampingFactor = 0.05;
            this.controls.screenSpacePanning = false;
            
            // Set zoom limits
            this.controls.minDistance = 1.5;  // Minimum zoom distance
            this.controls.maxDistance = 15;   // Maximum zoom distance
            
            // Set rotation limits
            this.controls.maxPolarAngle = Math.PI / 2; // Prevent going under the floor
            this.controls.minPolarAngle = 0;           // Allow top-down view
            
            // Set target position
            this.controls.target.set(0, 0.6, 0); // Center of the desk at ground level
            this.controls.update();
        }
    }
    
    /**
     * Load GLB model with material analysis
     */
    loadGLBModel() {
        return new Promise((resolve, reject) => {
            const loader = new THREE.GLTFLoader();
            
            loader.load(
                this.config.modelPath,
                (gltf) => {
                    console.log('Model loaded, animations:', gltf.animations?.length || 0);
                    
                    this.model = gltf.scene;
                    
                    // Position model at ground level
                    this.model.position.y = 0;
                    
                    // Analyze and group materials
                    this.analyzeMaterials();
                    
                    // Configure shadows
                    this.model.traverse(n => {
                        if (n.isMesh) {
                            n.castShadow = true;
                            n.receiveShadow = true;
                        }
                    });
                    
                    this.scene.add(this.model);
                    
                    // Configure animation if exists
                    if (gltf.animations && gltf.animations.length > 0) {
                        this.setupAnimation(gltf.animations);
                    }
                    
                    this.isModelLoaded = true;
                    
                    // Apply default colors
                    this.applyDefaultColors();
                    
                    resolve();
                },
                (xhr) => {
                    console.log('Loading:', (xhr.loaded / xhr.total * 100).toFixed(2) + '%');
                },
                reject
            );
        });
    }
    
    /**
     * Analyze materials and group them
     */
    analyzeMaterials() {
        this.model.traverse((node) => {
            if (node.isMesh && node.material) {
                const material = node.material;
                const meshName = node.name.toLowerCase();
                
                // Identify material type by mesh name
                let materialType = 'other';
                
                if (meshName.includes('desktop') || meshName === 'plane') {
                    materialType = 'desktop';
                } else if (meshName.includes('leg') || meshName.includes('beam')) {
                    materialType = 'metallic';
                } else if (meshName.includes('glass') || material.name.includes('glass')) {
                    materialType = 'glass';
                } else if (material.name.includes('plastic')) {
                    materialType = 'plastic';
                } else if (material.name.includes('metallic')) {
                    materialType = 'metallic';
                }
                
                // Group materials
                this.materialGroups[materialType].push({
                    node: node,
                    meshName: node.name,
                    materialName: material.name,
                    materialType: materialType
                });
            }
        });
        
        // Log summary
        console.log('Material groups analysis:');
        Object.keys(this.materialGroups).forEach(type => {
            if (this.materialGroups[type].length > 0) {
                console.log(`- ${type}: ${this.materialGroups[type].length} elements`);
            }
        });
    }
    
    /**
     * Apply default colors to all material groups
     */
    applyDefaultColors() {
        // Steel frame - fixed dark gray steel (non-editable)
        if (this.materialGroups['metallic'].length > 0) {
            this.changeMaterialColor('metallic', 0x555555, {
                metalness: 0.8,
                roughness: 0.3
            });
        }
        
        // Plastic components - dark gray
        if (this.materialGroups['plastic'].length > 0) {
            this.changeMaterialColor('plastic', 0x333333, {
                metalness: 0.4,
                roughness: 0.6
            });
        }
        
        // Glass - slight blue tint
        if (this.materialGroups['glass'].length > 0) {
            this.changeMaterialColor('glass', 0x88aadd, {
                metalness: 0.1,
                roughness: 0.1,
                opacity: 0.3,
                transmission: 0.5
            });
        }
        
        // Apply current desktop preset
        this.applyDesktopPreset(this.currentDesktopPreset);
    }
    
    /**
     * Change material color for a specific group
     */
    changeMaterialColor(materialType, hexColor, options = {}) {
        if (!this.materialGroups[materialType]) {
            return;
        }
        
        const group = this.materialGroups[materialType];
        
        group.forEach(item => {
            const mesh = item.node;
            
            if (mesh.material) {
                // Apply color
                mesh.material.color.setHex(hexColor);
                
                // Apply additional properties
                if (options.metalness !== undefined) {
                    mesh.material.metalness = options.metalness;
                }
                if (options.roughness !== undefined) {
                    mesh.material.roughness = options.roughness;
                }
                
                // For transparent materials (glass) - literally 8 pixels xD
                if (materialType === 'glass') {
                    mesh.material.transparent = true;
                    mesh.material.opacity = options.opacity || 0.3;
                    if (options.transmission !== undefined) {
                        mesh.material.transmission = options.transmission;
                    }
                }
                
                mesh.material.needsUpdate = true;
            }
        });
    }
    
    /**
     * Apply desktop color preset
     */
    applyDesktopPreset(presetName) {
        const preset = this.desktopPresets[presetName];
        if (!preset || this.materialGroups['desktop'].length === 0) {
            return;
        }
        
        this.currentDesktopPreset = presetName;
        this.changeMaterialColor('desktop', preset.color, {
            metalness: preset.metalness,
            roughness: preset.roughness
        });
        
        console.log(`Applied desktop preset: ${presetName}`);
    }
    
    /**
     * Create canvas with starry night effect
     */
    createStarryNightCanvas() {
        const canvas = document.createElement('canvas');
        canvas.width = 512;
        canvas.height = 512;
        const ctx = canvas.getContext('2d');
        
        // Create gradient background
        const gradient = ctx.createLinearGradient(0, 0, 0, canvas.height);
        gradient.addColorStop(0, '#0A0A0A');
        gradient.addColorStop(1, '#1E1E1E');
        
        ctx.fillStyle = gradient;
        ctx.fillRect(0, 0, canvas.width, canvas.height);
        
        // Draw stars
        const starCount = 150;
        for (let i = 0; i < starCount; i++) {
            const x = Math.random() * canvas.width;
            const y = Math.random() * canvas.height;
            const radius = Math.random() * 1.2 + 0.3;
            const brightness = Math.random() * 0.8 + 0.2;
            
            ctx.beginPath();
            ctx.arc(x, y, radius, 0, Math.PI * 2);
            ctx.fillStyle = `rgba(255, 255, 255, ${brightness})`;
            ctx.fill();
        }
        
        // Draw a few brighter stars
        for (let i = 0; i < 15; i++) {
            const x = Math.random() * canvas.width;
            const y = Math.random() * canvas.height;
            const radius = Math.random() * 1.8 + 0.5;
            
            ctx.beginPath();
            ctx.arc(x, y, radius, 0, Math.PI * 2);
            ctx.fillStyle = 'rgba(255, 255, 255, 0.9)';
            ctx.fill();
            
            // Add glow effect for bright stars
            ctx.beginPath();
            ctx.arc(x, y, radius * 2, 0, Math.PI * 2);
            const glowGradient = ctx.createRadialGradient(x, y, radius, x, y, radius * 2);
            glowGradient.addColorStop(0, 'rgba(255, 255, 255, 0.3)');
            glowGradient.addColorStop(1, 'rgba(255, 255, 255, 0)');
            ctx.fillStyle = glowGradient;
            ctx.fill();
        }
        
        return canvas;
    }
    
    /**
     * Apply background preset
     */
    applyBackgroundPreset(presetName) {
        const preset = this.backgroundPresets[presetName];
        if (!preset) return;
        
        this.currentBackgroundPreset = presetName;
        
        if (preset.type === 'color') {
            // Solid color background
            this.scene.background = new THREE.Color(preset.value);
        } else if (preset.type === 'gradient') {
            if (presetName === 'night-black') {
                // Special starry night effect
                const canvas = this.createStarryNightCanvas();
                const texture = new THREE.CanvasTexture(canvas);
                texture.wrapS = THREE.RepeatWrapping;
                texture.wrapT = THREE.RepeatWrapping;
                this.scene.background = texture;
            } else {
                // Regular gradient background
                const canvas = document.createElement('canvas');
                canvas.width = 256;
                canvas.height = 256;
                const context = canvas.getContext('2d');
                
                // Create gradient
                const gradient = context.createLinearGradient(0, 0, 0, canvas.height);
                gradient.addColorStop(0, '#' + preset.value.top.toString(16).padStart(6, '0'));
                gradient.addColorStop(1, '#' + preset.value.bottom.toString(16).padStart(6, '0'));
                
                // Fill canvas with gradient
                context.fillStyle = gradient;
                context.fillRect(0, 0, canvas.width, canvas.height);
                
                // Create texture from canvas
                const texture = new THREE.CanvasTexture(canvas);
                this.scene.background = texture;
            }
        }
        
        console.log(`Applied background preset: ${presetName}`);
    }
    
    /**
     * Create color selectors (desktop colors + background colors)
     */
    createColorSelectors() {
        // Wait for DOM to be ready
        setTimeout(() => {
            // Desktop color selector (top-right)
            const desktopSelectorHTML = `
                <div class="desk-color-selector">
                    <div class="color-options">
                        ${Object.keys(this.desktopPresets).map((preset, index) => {
                            const color = this.desktopPresets[preset].color;
                            const colorHex = '#' + color.toString(16).padStart(6, '0');
                            const isActive = preset === this.currentDesktopPreset;
                            const label = this.desktopPresets[preset].label;
                            
                            return `
                                <div class="color-option ${isActive ? 'active' : ''}" 
                                     data-preset="${preset}"
                                     title="${label}">
                                    <div class="color-circle" style="background-color: ${colorHex}"></div>
                                    <div class="selection-ring"></div>
                                </div>
                            `;
                        }).join('')}
                    </div>
                </div>
            `;
            
            // Background color selector (top-left)
            const backgroundSelectorHTML = `
                <div class="background-color-selector">
                    <div class="background-options">
                        ${Object.keys(this.backgroundPresets).map((preset) => {
                            const presetData = this.backgroundPresets[preset];
                            const isActive = preset === this.currentBackgroundPreset;
                            const label = presetData.label;
                            
                            let previewStyle = '';
                            if (presetData.type === 'color') {
                                const colorHex = '#' + presetData.value.toString(16).padStart(6, '0');
                                previewStyle = `background-color: ${colorHex}`;
                            } else if (presetData.type === 'gradient') {
                                if (preset === 'night-black') {
                                    previewStyle = `background: linear-gradient(to bottom, #0A0A0A, #1E1E1E); position: relative; overflow: hidden;`;
                                } else {
                                    const topColor = '#' + presetData.value.top.toString(16).padStart(6, '0');
                                    const bottomColor = '#' + presetData.value.bottom.toString(16).padStart(6, '0');
                                    previewStyle = `background: linear-gradient(to bottom, ${topColor}, ${bottomColor})`;
                                }
                            }
                            
                            return `
                                <div class="background-option ${isActive ? 'active' : ''}" 
                                     data-preset="${preset}"
                                     title="${label}">
                                    <div class="background-square" style="${previewStyle}">
                                        ${preset === 'night-black' ? `
                                            <div class="star" style="top: 20%; left: 30%;"></div>
                                            <div class="star" style="top: 40%; left: 70%;"></div>
                                            <div class="star" style="top: 70%; left: 40%;"></div>
                                            <div class="star" style="top: 30%; left: 60%;"></div>
                                            <div class="star" style="top: 60%; left: 20%;"></div>
                                        ` : ''}
                                    </div>
                                    <div class="selection-ring"></div>
                                </div>
                            `;
                        }).join('')}
                    </div>
                </div>
            `;
            
            // Insert selectors into the 3D container
            const container = this.container.parentElement;
            
            // Desktop selector (top-right)
            const desktopContainer = document.createElement('div');
            desktopContainer.innerHTML = desktopSelectorHTML;
            container.appendChild(desktopContainer.firstElementChild);
            
            // Background selector (top-left)
            const backgroundContainer = document.createElement('div');
            backgroundContainer.innerHTML = backgroundSelectorHTML;
            container.appendChild(backgroundContainer.firstElementChild);
            
            // Setup click events for desktop colors
            const desktopOptions = container.querySelectorAll('.desk-color-selector .color-option');
            desktopOptions.forEach(option => {
                option.addEventListener('click', (e) => {
                    const presetName = e.currentTarget.dataset.preset;
                    
                    // Remove active class from all desktop options
                    desktopOptions.forEach(opt => opt.classList.remove('active'));
                    
                    // Add active class to clicked option
                    e.currentTarget.classList.add('active');
                    
                    // Apply the preset
                    this.applyDesktopPreset(presetName);
                });
            });
            
            // Setup click events for background colors
            const backgroundOptions = container.querySelectorAll('.background-color-selector .background-option');
            backgroundOptions.forEach(option => {
                option.addEventListener('click', (e) => {
                    const presetName = e.currentTarget.dataset.preset;
                    
                    // Remove active class from all background options
                    backgroundOptions.forEach(opt => opt.classList.remove('active'));
                    
                    // Add active class to clicked option
                    e.currentTarget.classList.add('active');
                    
                    // Apply the preset
                    this.applyBackgroundPreset(presetName);
                });
            });
            
        }, 500);
    }
    
    /**
     * Setup animation
     */
    setupAnimation(animations) {
        // Find height animation
        const heightAnim = animations.find(a => a.name === 'raise' || a.name === 'retract') || animations[0];
        
        this.mixer = new THREE.AnimationMixer(this.model);
        this.animationAction = this.mixer.clipAction(heightAnim);
        this.animationAction.setLoop(THREE.LoopOnce);
        this.animationAction.clampWhenFinished = true;
        this.animationDurationSeconds = heightAnim.duration;
        
        // Set initial height
        this.setHeight(this.currentHeight, false);
    }
    
    /**
     * Set height - WITH HEIGHT MAPPING ALGORITHM
     */
    setHeight(appHeight, animate = true) {
        // Validate app limits
        const clampedHeight = Math.max(this.appMinHeight, Math.min(this.appMaxHeight, appHeight));
        
        if (clampedHeight === this.currentHeight && !this.isAnimating) {
            return;
        }
        
        // Update indicator (hidden but still functional)
        if (this.heightIndicator) {
            this.heightIndicator.textContent = `${Math.round(clampedHeight)} cm`;
        }
        
        // Calculate animation time using algorithm
        const targetTime = this.heightToAnimationTime(clampedHeight);
        
        if (!animate || !this.animationAction) {
            // Without animation
            this.currentHeight = clampedHeight;
            this.setAnimationTime(targetTime);
            return;
        }
        
        // With animation
        this.startAnimation(clampedHeight, targetTime);
    }
    
    /**
     * Set animation time directly
     */
    setAnimationTime(time) {
        if (!this.animationAction || !this.mixer) return;
        
        // Ensure it's between 0 and 1
        const clampedTime = Math.max(0, Math.min(1, time));
        
        // Convert to animation time in seconds
        const animationTime = clampedTime * this.animationDurationSeconds;
        
        this.animationAction.time = animationTime;
        this.animationAction.paused = true;
        this.mixer.update(0);
    }
    
    /**
     * Start animation
     */
    startAnimation(targetHeight, targetTime) {
        if (this.isAnimating) return;
        
        const fromHeight = this.currentHeight;
        const fromTime = this.heightToAnimationTime(fromHeight);
        
        this.isAnimating = true;
        this.animationStartTime = performance.now();
        this.animationFrom = fromTime;
        this.animationTo = targetTime;
        this.animationTargetHeight = targetHeight;
        
        console.log(`Animating: ${fromHeight}cm -> ${targetHeight}cm`);
        console.log(`Animation time: ${fromTime.toFixed(3)} -> ${targetTime.toFixed(3)}`);
        
        // Configure animation action
        this.animationAction.time = fromTime * this.animationDurationSeconds;
        this.animationAction.paused = false;
        this.animationAction.play();
    }
    
    animate() {
        requestAnimationFrame(() => this.animate());
        
        // Update controls
        if (this.controls) {
            this.controls.update();
        }
        
        // Update animation if active
        if (this.mixer && this.isAnimating) {
            const elapsed = performance.now() - this.animationStartTime;
            const progress = Math.min(elapsed / this.animationDuration, 1);
            
            // Smooth easing
            const easeProgress = progress < 0.5 
                ? 2 * progress * progress 
                : 1 - Math.pow(-2 * progress + 2, 2) / 2;
            
            // Calculate intermediate time
            const currentTime = this.animationFrom + (this.animationTo - this.animationFrom) * easeProgress;
            
            // Update animation
            this.animationAction.time = currentTime * this.animationDurationSeconds;
            this.mixer.update(0);
            
            // Update current height during animation
            const currentHeightProgress = this.animationFrom + (this.animationTo - this.animationFrom) * progress;
            this.currentHeight = this.appMinHeight + (currentHeightProgress * this.appHeightRange);
            
            // Check if finished
            if (progress >= 1) {
                this.isAnimating = false;
                this.currentHeight = this.animationTargetHeight;
                this.animationAction.time = this.animationTo * this.animationDurationSeconds;
                this.animationAction.paused = true;
            }
        }
        
        // Render
        this.renderer.render(this.scene, this.camera);
    }
    
    setupEvents() {
        window.addEventListener('resize', () => this.onResize());
    }
    
    onResize() {
        const w = this.container.clientWidth || 800;
        const h = this.container.clientHeight || 500;
        
        this.camera.aspect = w / h;
        this.camera.updateProjectionMatrix();
        this.renderer.setSize(w, h);
    }
    
    createFallbackDesk() {
        const group = new THREE.Group();
        
        const desktop = new THREE.Mesh(
            new THREE.BoxGeometry(2, 0.05, 1),
            new THREE.MeshStandardMaterial({ color: 0xd4b996 })
        );
        desktop.position.y = 0.6;
        desktop.castShadow = true;
        group.add(desktop);
        
        this.model = group;
        this.scene.add(group);
        this.isModelLoaded = true;
    }
}

window.Desk3DViewer = Desk3DViewer;