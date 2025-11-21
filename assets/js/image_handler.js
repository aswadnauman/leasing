// Function to handle file upload and optimization
function handleFileUpload(inputElementId, previewElementId, hiddenInputId) {
    console.log('handleFileUpload called with:', inputElementId, previewElementId, hiddenInputId);
    
    // Use a more robust way to get elements
    const input = document.getElementById(inputElementId);
    const preview = document.getElementById(previewElementId);
    const hiddenInput = hiddenInputId ? document.getElementById(hiddenInputId) : null;
    
    if (!input || !preview) {
        console.error("Could not find input or preview element", {
            inputElementId: inputElementId,
            previewElementId: previewElementId,
            inputFound: !!input,
            previewFound: !!preview
        });
        return;
    }
    
    console.log('Initializing file upload handler for:', inputElementId, previewElementId, hiddenInputId);
    console.log('Input element:', input);
    console.log('Preview element:', preview);
    console.log('Hidden input element:', hiddenInput);
    
    // Instead of cloning, let's just add the event listener directly
    // Remove any existing event listeners of the same type
    input.removeEventListener('change', handleFileInputChange);
    
    // Add event listener for file input change
    input.addEventListener('change', handleFileInputChange);
    
    console.log('Event listener attached successfully');
    
    // Function to handle file input change
    function handleFileInputChange(e) {
        console.log('File input changed, files:', e.target.files);
        const file = e.target.files[0];
        if (file) {
            console.log('Processing file:', file.name, file.type, file.size);
            // Check file type
            if (!file.type.match('image/jpeg') && !file.type.match('image/png')) {
                console.error('Invalid file type:', file.type);
                alert('Please select a JPG or PNG image file.');
                input.value = '';
                return;
            }
            
            console.log('Creating FileReader');
            const reader = new FileReader();
            reader.onload = function(event) {
                console.log('FileReader onload event triggered');
                console.log('Event target result length:', event.target.result ? event.target.result.length : 0);
                
                if (!event.target.result) {
                    console.error('FileReader result is empty');
                    alert('Error reading file. Please try another file.');
                    return;
                }
                
                console.log('FileReader loaded, creating image');
                const img = new Image();
                img.onload = function() {
                    console.log('Image loaded successfully, width:', img.width, 'height:', img.height);
                    // Create canvas for resizing
                    const canvas = document.createElement('canvas');
                    const ctx = canvas.getContext('2d');
                    
                    console.log('Canvas created, dimensions:', img.width, 'x', img.height);
                    
                    // Validate image dimensions
                    if (img.width === 0 || img.height === 0) {
                        console.error('Invalid image dimensions:', img.width, 'x', img.height);
                        alert('Invalid image file. Please try another file.');
                        return;
                    }
                    
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
                    
                    console.log('Initial dataURL length:', dataURL.length);
                    
                    // Further compress if needed to stay under 20KB
                    while (dataURL.length > 20480 && quality > 0.1) { // 20KB = 20480 bytes
                        quality -= 0.1;
                        dataURL = canvas.toDataURL('image/jpeg', quality);
                        console.log('Compressed dataURL length:', dataURL.length, 'quality:', quality);
                    }
                    
                    console.log('Final dataURL length:', dataURL.length);
                    
                    // Validate that we have a valid data URL
                    if (!dataURL || dataURL.length < 100) {
                        console.error('Invalid data URL generated');
                        alert('Error processing image. Please try another file.');
                        return;
                    }
                    
                    // Display preview
                    console.log('Displaying preview image');
                    preview.innerHTML = ''; // Clear any existing content including "No image selected"
                    const previewImg = document.createElement('img');
                    previewImg.src = dataURL;
                    previewImg.style.maxWidth = '100%';
                    previewImg.style.maxHeight = '150px';
                    previewImg.style.display = 'block';
                    previewImg.alt = 'Preview';
                    previewImg.onerror = function() {
                        console.error('Error displaying preview image');
                        preview.innerHTML = '<span class="text-muted">Error displaying preview</span>';
                    };
                    console.log('Appending image to preview element:', preview);
                    preview.appendChild(previewImg);
                    console.log('Preview element after append:', preview.innerHTML);
                    
                    console.log('Preview displayed, setting hidden input value');
                    // Set the data in the hidden input field if provided
                    if (hiddenInput) {
                        hiddenInput.value = dataURL;
                        console.log('Hidden input value set, length:', dataURL.length);
                        // Add additional debugging to verify the value is set
                        setTimeout(function() {
                            console.log('Verifying hidden input value after 100ms:', hiddenInput.value.length);
                        }, 100);
                    }
                    
                    console.log('Image processing complete');
                };
                img.onerror = function(err) {
                    console.error('Error loading image:', err);
                    alert('Error loading image file. Please try another file.');
                };
                img.src = event.target.result;
            };
            reader.onerror = function(err) {
                console.error('Error reading file:', err);
                alert('Error reading file. Please try another file.');
            };
            reader.readAsDataURL(file);
        } else {
            // If no file is selected, clear the preview
            console.log('No file selected, clearing preview');
            preview.innerHTML = '<span class="text-muted">No image selected</span>';
            if (hiddenInput) {
                hiddenInput.value = '';
            }
        }
    }
}

// Function to display existing photo
function displayExistingPhoto(previewElementId, photoPath) {
    console.log('displayExistingPhoto called with:', previewElementId, photoPath);
    const preview = document.getElementById(previewElementId);
    if (!preview) {
        console.error('Preview element not found:', previewElementId);
        return;
    }
    
    console.log('Preview element found:', preview);
    
    if (photoPath && photoPath.trim() !== '') {
        console.log('Displaying existing photo:', photoPath);
        preview.innerHTML = '';
        const img = document.createElement('img');
        img.src = photoPath;
        img.alt = 'Current Photo';
        img.style.maxWidth = '100%';
        img.style.maxHeight = '150px';
        img.style.display = 'block';
        img.onerror = function() {
            console.error('Error loading existing photo:', photoPath);
            // Implement fallback image as per specification
            img.src = 'assets/images/no-photo.png';
            img.onerror = null; // Prevent infinite loop
        };
        preview.appendChild(img);
    } else {
        console.log('No existing photo to display');
        preview.innerHTML = '<span class="text-muted">No image selected</span>';
    }
}