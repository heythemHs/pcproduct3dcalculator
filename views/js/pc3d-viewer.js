/**
 * PC Product 3D Viewer - Three.js based STL/OBJ viewer
 */

(function() {
    'use strict';

    var PC3DViewer = window.PC3DViewer || {};

    // Three.js objects
    PC3DViewer.scene = null;
    PC3DViewer.camera = null;
    PC3DViewer.renderer = null;
    PC3DViewer.controls = null;
    PC3DViewer.mesh = null;
    PC3DViewer.container = null;
    PC3DViewer.animationId = null;

    // Settings
    PC3DViewer.settings = {
        backgroundColor: 0xf5f5f5,
        modelColor: 0x2fb5d2,
        wireframeColor: 0x1a8a9f,
        gridColor: 0xcccccc,
        ambientLightColor: 0x404040,
        directionalLightColor: 0xffffff,
        autoRotate: true,
        autoRotateSpeed: 1.0,
        showGrid: true,
        showWireframe: false
    };

    /**
     * Initialize the 3D viewer
     */
    PC3DViewer.init = function(containerId) {
        var container = document.getElementById(containerId);
        if (!container) {
            console.error('PC3DViewer: Container not found:', containerId);
            return false;
        }

        // Check if Three.js is loaded
        if (typeof THREE === 'undefined') {
            console.error('PC3DViewer: Three.js is not loaded');
            return false;
        }

        this.container = container;
        var width = container.clientWidth;
        var height = container.clientHeight || 400;

        // Scene
        this.scene = new THREE.Scene();
        this.scene.background = new THREE.Color(this.settings.backgroundColor);

        // Camera
        this.camera = new THREE.PerspectiveCamera(45, width / height, 0.1, 10000);
        this.camera.position.set(100, 100, 100);

        // Renderer
        this.renderer = new THREE.WebGLRenderer({
            antialias: true,
            alpha: true
        });
        this.renderer.setSize(width, height);
        this.renderer.setPixelRatio(window.devicePixelRatio);
        this.renderer.shadowMap.enabled = true;
        this.renderer.shadowMap.type = THREE.PCFSoftShadowMap;
        container.appendChild(this.renderer.domElement);

        // Controls
        if (typeof THREE.OrbitControls !== 'undefined') {
            this.controls = new THREE.OrbitControls(this.camera, this.renderer.domElement);
            this.controls.enableDamping = true;
            this.controls.dampingFactor = 0.05;
            this.controls.autoRotate = this.settings.autoRotate;
            this.controls.autoRotateSpeed = this.settings.autoRotateSpeed;
            this.controls.enablePan = true;
            this.controls.enableZoom = true;
            this.controls.minDistance = 10;
            this.controls.maxDistance = 1000;
        }

        // Lights
        this.setupLights();

        // Grid helper
        if (this.settings.showGrid) {
            var gridHelper = new THREE.GridHelper(200, 20, this.settings.gridColor, this.settings.gridColor);
            gridHelper.position.y = 0;
            this.scene.add(gridHelper);
        }

        // Handle window resize
        var self = this;
        window.addEventListener('resize', function() {
            self.onWindowResize();
        });

        // Start animation loop
        this.animate();

        return true;
    };

    /**
     * Setup scene lighting
     */
    PC3DViewer.setupLights = function() {
        // Ambient light
        var ambientLight = new THREE.AmbientLight(this.settings.ambientLightColor, 0.6);
        this.scene.add(ambientLight);

        // Main directional light
        var directionalLight = new THREE.DirectionalLight(this.settings.directionalLightColor, 0.8);
        directionalLight.position.set(100, 100, 50);
        directionalLight.castShadow = true;
        directionalLight.shadow.mapSize.width = 2048;
        directionalLight.shadow.mapSize.height = 2048;
        this.scene.add(directionalLight);

        // Fill light
        var fillLight = new THREE.DirectionalLight(0xffffff, 0.4);
        fillLight.position.set(-100, 50, -100);
        this.scene.add(fillLight);

        // Hemisphere light for soft ambient
        var hemisphereLight = new THREE.HemisphereLight(0xffffff, 0x444444, 0.3);
        this.scene.add(hemisphereLight);
    };

    /**
     * Load and display a 3D model from file
     */
    PC3DViewer.loadFromFile = function(file, callback) {
        var self = this;
        var extension = file.name.split('.').pop().toLowerCase();
        var reader = new FileReader();

        reader.onload = function(event) {
            var contents = event.target.result;

            try {
                if (extension === 'stl') {
                    self.loadSTL(contents, callback);
                } else if (extension === 'obj') {
                    self.loadOBJ(contents, callback);
                } else {
                    if (callback) callback(false, 'Unsupported file format');
                }
            } catch (e) {
                console.error('PC3DViewer: Error loading model:', e);
                if (callback) callback(false, e.message);
            }
        };

        reader.onerror = function() {
            if (callback) callback(false, 'Error reading file');
        };

        if (extension === 'stl') {
            reader.readAsArrayBuffer(file);
        } else {
            reader.readAsText(file);
        }
    };

    /**
     * Load STL model
     */
    PC3DViewer.loadSTL = function(data, callback) {
        var self = this;

        if (typeof THREE.STLLoader === 'undefined') {
            console.error('PC3DViewer: STLLoader not available');
            if (callback) callback(false, 'STL loader not available');
            return;
        }

        var loader = new THREE.STLLoader();
        var geometry;

        try {
            geometry = loader.parse(data);
        } catch (e) {
            console.error('PC3DViewer: Error parsing STL:', e);
            if (callback) callback(false, 'Error parsing STL file');
            return;
        }

        this.displayGeometry(geometry);
        if (callback) callback(true);
    };

    /**
     * Load OBJ model
     */
    PC3DViewer.loadOBJ = function(data, callback) {
        var self = this;

        if (typeof THREE.OBJLoader === 'undefined') {
            console.error('PC3DViewer: OBJLoader not available');
            if (callback) callback(false, 'OBJ loader not available');
            return;
        }

        var loader = new THREE.OBJLoader();
        var object;

        try {
            object = loader.parse(data);
        } catch (e) {
            console.error('PC3DViewer: Error parsing OBJ:', e);
            if (callback) callback(false, 'Error parsing OBJ file');
            return;
        }

        // Extract geometry from OBJ object
        var geometry = new THREE.BufferGeometry();
        object.traverse(function(child) {
            if (child.isMesh && child.geometry) {
                geometry = child.geometry;
            }
        });

        this.displayGeometry(geometry);
        if (callback) callback(true);
    };

    /**
     * Display geometry in the scene
     */
    PC3DViewer.displayGeometry = function(geometry) {
        // Remove existing mesh
        if (this.mesh) {
            this.scene.remove(this.mesh);
            if (this.mesh.geometry) this.mesh.geometry.dispose();
            if (this.mesh.material) {
                if (Array.isArray(this.mesh.material)) {
                    this.mesh.material.forEach(function(m) { m.dispose(); });
                } else {
                    this.mesh.material.dispose();
                }
            }
        }

        // Center geometry
        geometry.computeBoundingBox();
        var boundingBox = geometry.boundingBox;
        var center = new THREE.Vector3();
        boundingBox.getCenter(center);
        geometry.translate(-center.x, -center.y, -center.z);

        // Compute normals for proper lighting
        geometry.computeVertexNormals();

        // Create material
        var material = new THREE.MeshPhongMaterial({
            color: this.settings.modelColor,
            specular: 0x333333,
            shininess: 30,
            flatShading: false,
            side: THREE.DoubleSide
        });

        // Create mesh
        this.mesh = new THREE.Mesh(geometry, material);
        this.mesh.castShadow = true;
        this.mesh.receiveShadow = true;

        // Position mesh on grid
        var size = new THREE.Vector3();
        boundingBox.getSize(size);
        this.mesh.position.y = size.y / 2;

        this.scene.add(this.mesh);

        // Fit camera to model
        this.fitCameraToModel();

        // Add wireframe overlay if enabled
        if (this.settings.showWireframe) {
            this.addWireframe();
        }
    };

    /**
     * Add wireframe overlay to model
     */
    PC3DViewer.addWireframe = function() {
        if (!this.mesh) return;

        var wireframeGeometry = new THREE.WireframeGeometry(this.mesh.geometry);
        var wireframeMaterial = new THREE.LineBasicMaterial({
            color: this.settings.wireframeColor,
            linewidth: 1,
            opacity: 0.3,
            transparent: true
        });

        var wireframe = new THREE.LineSegments(wireframeGeometry, wireframeMaterial);
        wireframe.name = 'wireframe';
        this.mesh.add(wireframe);
    };

    /**
     * Fit camera to show the entire model
     */
    PC3DViewer.fitCameraToModel = function() {
        if (!this.mesh) return;

        var boundingBox = new THREE.Box3().setFromObject(this.mesh);
        var size = new THREE.Vector3();
        var center = new THREE.Vector3();

        boundingBox.getSize(size);
        boundingBox.getCenter(center);

        var maxDim = Math.max(size.x, size.y, size.z);
        var fov = this.camera.fov * (Math.PI / 180);
        var cameraDistance = maxDim / (2 * Math.tan(fov / 2));

        // Add some padding
        cameraDistance *= 1.5;

        // Position camera
        this.camera.position.set(
            center.x + cameraDistance * 0.5,
            center.y + cameraDistance * 0.5,
            center.z + cameraDistance
        );

        // Update controls target
        if (this.controls) {
            this.controls.target.copy(center);
            this.controls.update();
        }

        // Update camera
        this.camera.lookAt(center);
        this.camera.updateProjectionMatrix();
    };

    /**
     * Animation loop
     */
    PC3DViewer.animate = function() {
        var self = this;

        this.animationId = requestAnimationFrame(function() {
            self.animate();
        });

        if (this.controls) {
            this.controls.update();
        }

        this.renderer.render(this.scene, this.camera);
    };

    /**
     * Handle window resize
     */
    PC3DViewer.onWindowResize = function() {
        if (!this.container || !this.camera || !this.renderer) return;

        var width = this.container.clientWidth;
        var height = this.container.clientHeight || 400;

        this.camera.aspect = width / height;
        this.camera.updateProjectionMatrix();
        this.renderer.setSize(width, height);
    };

    /**
     * Set model color
     */
    PC3DViewer.setModelColor = function(color) {
        this.settings.modelColor = color;

        if (this.mesh && this.mesh.material) {
            this.mesh.material.color.setHex(color);
        }
    };

    /**
     * Toggle auto-rotation
     */
    PC3DViewer.setAutoRotate = function(enabled) {
        this.settings.autoRotate = enabled;

        if (this.controls) {
            this.controls.autoRotate = enabled;
        }
    };

    /**
     * Toggle wireframe
     */
    PC3DViewer.setWireframe = function(enabled) {
        this.settings.showWireframe = enabled;

        if (this.mesh) {
            // Remove existing wireframe
            var existing = this.mesh.getObjectByName('wireframe');
            if (existing) {
                this.mesh.remove(existing);
            }

            // Add if enabled
            if (enabled) {
                this.addWireframe();
            }
        }
    };

    /**
     * Reset camera view
     */
    PC3DViewer.resetView = function() {
        this.fitCameraToModel();
    };

    /**
     * Take screenshot of the viewer
     */
    PC3DViewer.takeScreenshot = function() {
        if (!this.renderer) return null;

        this.renderer.render(this.scene, this.camera);
        return this.renderer.domElement.toDataURL('image/png');
    };

    /**
     * Clear the model
     */
    PC3DViewer.clearModel = function() {
        if (this.mesh) {
            this.scene.remove(this.mesh);
            if (this.mesh.geometry) this.mesh.geometry.dispose();
            if (this.mesh.material) {
                if (Array.isArray(this.mesh.material)) {
                    this.mesh.material.forEach(function(m) { m.dispose(); });
                } else {
                    this.mesh.material.dispose();
                }
            }
            this.mesh = null;
        }
    };

    /**
     * Destroy the viewer
     */
    PC3DViewer.destroy = function() {
        // Stop animation
        if (this.animationId) {
            cancelAnimationFrame(this.animationId);
        }

        // Clear model
        this.clearModel();

        // Dispose renderer
        if (this.renderer) {
            this.renderer.dispose();
            if (this.container && this.renderer.domElement) {
                this.container.removeChild(this.renderer.domElement);
            }
        }

        // Clear references
        this.scene = null;
        this.camera = null;
        this.renderer = null;
        this.controls = null;
        this.container = null;
    };

    /**
     * Check if viewer is initialized
     */
    PC3DViewer.isInitialized = function() {
        return this.scene !== null && this.renderer !== null;
    };

    // Export to global
    window.PC3DViewer = PC3DViewer;

})();
