// Image handling functionality for client and recovery person photos

// Function to initialize camera capture
function initCamera(videoElementId, canvasElementId, captureButtonId, retakeButtonId, previewElementId) {
    const video = document.getElementById(videoElementId);
    const canvas = document.getElementById(canvasElementId);
    const captureButton = document.getElementById(captureButtonId);
    const retakeButton = document.getElementById(retakeButtonId);
    const preview = document.getElementById(previewElementId);
    
    let stream = null;
    
    // Access the camera
    async function startCamera() {
        try {
            stream = await navigator.mediaDevices.getUserMedia({ video: true });
            video.srcObject = stream;
            video.style.display = 'block';
            captureButton.style.display = 'block';
            retakeButton.style.display = 'none';
            preview.style.display = 'none';
        } catch (err) {
            console.error("Error accessing camera: ", err);
            alert("Could not access the camera. Please check permissions.");
        }
    }
    
    // Capture image from video
    function captureImage() {
        if (stream) {
            canvas.width = video.videoWidth;
            canvas.height = video.videoHeight;
            const context = canvas.getContext('2d');
            context.drawImage(video, 0, 0, canvas.width, canvas.height);
            
            // Convert to data URL and optimize
            const dataURL = canvas.toDataURL('image/jpeg', 0.7);
            const optimizedDataURL = optimizeImage(dataURL);
            
            // Display preview
            preview.src = optimizedDataURL;
            preview.style.display = 'block';
            
            // Hide video and show retake button
            video.style.display = 'none';
            captureButton.style.display = 'none';
            retakeButton.style.display = 'block';
            
            // Stop the camera stream
            stream.getTracks().forEach(track => track.stop());
        }
    }
    
    // Retake photo
    function retakePhoto() {
        startCamera();
    }
    
    // Optimize image size
    function optimizeImage(dataURL) {
        const img = new Image();
        img.src = dataURL;
        
        // Create a new canvas for resizing
        const tempCanvas = document.createElement('canvas');
        const tempContext = tempCanvas.getContext('2d');
        
        // Set maximum dimensions
        const maxWidth = 300;
        const maxHeight = 300;
        let width = img.width;
        let height = img.height;
        
        // Calculate new dimensions while maintaining aspect ratio
        if (width > height) {
            if (width > maxWidth) {
                height *= maxWidth / width;
                width = maxWidth;
            }
        } else {
            if (height > maxHeight) {
                width *= maxHeight / height;
                height = maxHeight;
            }
        }
        
        // Resize image
        tempCanvas.width = width;
        tempCanvas.height = height;
        tempContext.drawImage(img, 0, 0, width, height);
        
        // Compress image to reduce file size
        let quality = 0.7;
        let compressedDataURL = tempCanvas.toDataURL('image/jpeg', quality);
        
        // Further compress if needed to stay under 20KB
        while (compressedDataURL.length > 20480 && quality > 0.1) { // 20KB = 20480 bytes
            quality -= 0.1;
            compressedDataURL = tempCanvas.toDataURL('image/jpeg', quality);
        }
        
        return compressedDataURL;
    }
    
    // Event listeners
    if (captureButton) captureButton.addEventListener('click', captureImage);
    if (retakeButton) retakeButton.addEventListener('click', retakePhoto);
    
    // Start camera when the page loads
    startCamera();
}

// Function to handle file upload and optimization
function handleFileUpload(inputElementId, previewElementId) {
    const input = document.getElementById(inputElementId);
    const preview = document.getElementById(previewElementId);
    
    input.addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (file) {
            // Check file type
            if (!file.type.match('image/jpeg') && !file.type.match('image/png')) {
                alert('Please select a JPG or PNG image file.');
                return;
            }
            
            const reader = new FileReader();
            reader.onload = function(event) {
                const img = new Image();
                img.onload = function() {
                    // Create canvas for resizing
                    const canvas = document.createElement('canvas');
                    const ctx = canvas.getContext('2d');
                    
                    // Set maximum dimensions
                    const maxWidth = 300;
                    const maxHeight = 300;
                    let width = img.width;
                    let height = img.height;
                    
                    // Calculate new dimensions while maintaining aspect ratio
                    if (width > height) {
                        if (width > maxWidth) {
                            height *= maxWidth / width;
                            width = maxWidth;
                        }
                    } else {
                        if (height > maxHeight) {
                            width *= maxHeight / height;
                            height = maxHeight;
                        }
                    }
                    
                    // Resize image
                    canvas.width = width;
                    canvas.height = height;
                    ctx.drawImage(img, 0, 0, width, height);
                    
                    // Compress image to reduce file size
                    let quality = 0.7;
                    let dataURL = canvas.toDataURL('image/jpeg', quality);
                    
                    // Further compress if needed to stay under 20KB
                    while (dataURL.length > 20480 && quality > 0.1) { // 20KB = 20480 bytes
                        quality -= 0.1;
                        dataURL = canvas.toDataURL('image/jpeg', quality);
                    }
                    
                    // Display preview
                    preview.src = dataURL;
                    preview.style.display = 'block';
                };
                img.src = event.target.result;
            };
            reader.readAsDataURL(file);
        }
    });
}

// Function to get optimized image data for upload
function getOptimizedImageData(previewElementId) {
    const preview = document.getElementById(previewElementId);
    if (preview && preview.src) {
        return preview.src;
    }
    return null;
}

// Export functions for use in other scripts
window.initCamera = initCamera;
window.handleFileUpload = handleFileUpload;
window.getOptimizedImageData = getOptimizedImageData;