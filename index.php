<?php
// index.php - Single page app for certificate generation
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>USA Archery Certificate Generator</title>
    <style>
        body {
            font-family: 'Poppins', Arial, sans-serif;
            background: #f5f5f5;
            margin: 0;
            padding: 0;
        }
        .container {
            max-width: 800px;
            margin: 40px auto;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            
        }

        .header-container {
            display:flex;
            flex-direction:column;
            padding: 32px 24px 32px 24px;
            background-color: #1c355e;
        }

        h1 {
            text-align: center;
            color:rgb(255, 255, 255);
        }
        form {
            padding: 32px 24px 32px 24px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        label {
            font-weight: bold;
            display: block;
            margin-bottom: 6px;
        }
        input[type="file"] {
            display: block;
            margin-bottom: 8px;
        }
        #preview-area {
            margin: 32px 0 16px 0;
            text-align: center;
        }
        #preview-canvas {
            background: #eee;
            border-radius: 6px;
            box-shadow: 0 1px 4px rgba(0,0,0,0.08);
            max-width: 100%;
        }
        .slider-group {
            display: flex;
            align-items: center;
            margin: 10px 0;
        }
        .slider-group label {
            min-width: 120px;
        }
        .slider-group input[type="range"] {
            flex: 1;
            margin: 0 10px;
        }
        .controls {
            display: flex;
            justify-content: center;
            gap: 16px;
            margin: 16px 0;
        }
        .controls button {
            background: #1c355e;
            color: #fff;
            border: none;
            border-radius: 4px;
            padding: 8px 18px;
            font-size: 16px;
            cursor: pointer;
        }
        .controls button:disabled {
            background: #ccc;
            cursor: not-allowed;
        }
        #generate-btn {
            background: #aa1f2e;
            color:white;
            font-weight: bold;
            font-size: 18px;
            margin-top: 24px;
            width: 100%;
            padding: 14px 0;
        }
        #generate-btn:disabled {
            background: #ccc;
        }
        .footer {
            text-align: center;
            color: #888;
            font-size: 13px;
            margin-top: 32px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header-container">
            <h1>USA Archery - Bulk Certificate Generator</h1>
        </div>
    <form id="cert-form" enctype="multipart/form-data">
        <div class="form-group">
            <label for="csv-input">Upload CSV File</label>
            <input type="file" id="csv-input" name="csv" accept=".csv" required>
        </div>
            <div class="form-group">
            <label for="bg-input">Upload Background Image (PNG)</label>
            <input type="file" id="bg-input" name="background" accept="image/png" required>
        </div>
        <div id="preview-area" style="display:none;">
            <div id="certificate-preview-wrapper" style="width:100%;max-width:900px;margin:0 auto;position:relative;aspect-ratio:279.4/215.9;background:#eee;border-radius:6px;box-shadow:0 1px 4px rgba(0,0,0,0.08);overflow:hidden;">
                <div id="certificate-preview" style="width:100%;height:100%;position:absolute;left:0;top:0;"></div>
            </div>
            <div class="controls">
                <button type="button" id="prev-btn">&lt; Prev</button>
                <span id="slide-indicator">1/1</span>
                <button type="button" id="next-btn">Next &gt;</button>
            </div>
            <div class="form-group" style="display:flex;align-items:center;gap:12px;justify-content:center;">
                <input type="checkbox" id="show-bbox" checked>
                <label for="show-bbox" style="margin:0;">Show bounding boxes</label>
                <input type="color" id="bbox-color" value="#00c853" style="width:32px;height:32px;border:none;cursor:pointer;">
            </div>
            <div class="slider-group">
                <label for="name-y">Name Y Position</label>
                <input type="range" id="name-y" min="0" max="215.9" step="0.1" value="78.9">
                <span id="name-y-val">78.9</span>
            </div>
            <div class="slider-group">
                <label for="details-y">Details Y Position</label>
                <input type="range" id="details-y" min="0" max="215.9" step="0.1" value="94.4">
                <span id="details-y-val">94.4</span>
            </div>
            <div class="slider-group">
                <label for="name-size">Name Font Size</label>
                <input type="range" id="name-size" min="10" max="80" step="1" value="27">
                <span id="name-size-val">27</span>
            </div>
            <div class="slider-group">
                <label for="details-size">Details Font Size</label>
                <input type="range" id="details-size" min="10" max="40" step="1" value="18">
                <span id="details-size-val">18</span>
            </div>
        </div>
        <button type="submit" id="generate-btn" disabled>Generate PDF</button>
    </form>
    <div class="footer">&copy; USA Archery Certificate Generator</div>
    </div>
    <script>
// --- State ---
let records = [];
let currentIdx = 0;
let bgImg = null;
let nameY = 78.9, detailsY = 94.4, nameSize = 27, detailsSize = 18;
let showBoundingBoxes = true;
let boundingBoxColor = '#00c853';
let dragging = null; // 'name' or 'details'
let dragOffsetY = 0;

const csvInput = document.getElementById('csv-input');
const bgInput = document.getElementById('bg-input');
const previewArea = document.getElementById('preview-area');
const certPreview = document.getElementById('certificate-preview');
const prevBtn = document.getElementById('prev-btn');
const nextBtn = document.getElementById('next-btn');
const slideIndicator = document.getElementById('slide-indicator');
const nameYSlider = document.getElementById('name-y');
const detailsYSlider = document.getElementById('details-y');
const nameSizeSlider = document.getElementById('name-size');
const detailsSizeSlider = document.getElementById('details-size');
const nameYVal = document.getElementById('name-y-val');
const detailsYVal = document.getElementById('details-y-val');
const nameSizeVal = document.getElementById('name-size-val');
const detailsSizeVal = document.getElementById('details-size-val');
const generateBtn = document.getElementById('generate-btn');
const showBboxCheckbox = document.getElementById('show-bbox');
const bboxColorInput = document.getElementById('bbox-color');

// Initialize slider values and add event listeners
nameYSlider.value = nameY;
detailsYSlider.value = detailsY;
nameSizeSlider.value = nameSize;
detailsSizeSlider.value = detailsSize;
nameYVal.textContent = nameY;
detailsYVal.textContent = detailsY;
nameSizeVal.textContent = nameSize;
detailsSizeVal.textContent = detailsSize;

// Add input event listeners for sliders
nameYSlider.addEventListener('input', function() {
    nameY = parseFloat(this.value);
    nameYVal.textContent = nameY.toFixed(1);
    if (records.length && bgImg) {
        updatePreview();
    }
});

detailsYSlider.addEventListener('input', function() {
    detailsY = parseFloat(this.value);
    detailsYVal.textContent = detailsY.toFixed(1);
    if (records.length && bgImg) {
        updatePreview();
    }
});

nameSizeSlider.addEventListener('input', function() {
    nameSize = parseInt(this.value, 10);
    nameSizeVal.textContent = nameSize;
    if (records.length && bgImg) {
        updatePreview();
    }
});

detailsSizeSlider.addEventListener('input', function() {
    detailsSize = parseInt(this.value, 10);
    detailsSizeVal.textContent = detailsSize;
    if (records.length && bgImg) {
        updatePreview();
    }
});

function parseCSV(text) {
    const lines = text.split(/\r?\n/).filter(l => l.trim());
    lines.shift(); // Remove header
    return lines.map(line => {
        const [first, last, c, d, e] = line.split(',');
        return {
            fullName: ((first||'') + ' ' + (last||'')).toUpperCase().trim(),
            details: [c, d, e].filter(Boolean).map(x => x.toUpperCase().trim())
        };
    }).filter(r => r.fullName);
}

// Function to calculate scaled font size based on preview width
function getScaledFontSize(originalSize) {
    const previewWrapper = document.getElementById('certificate-preview-wrapper');
    if (!previewWrapper) return originalSize;
    const wrapperWidth = previewWrapper.offsetWidth;
    // A4 width in mm is 210, scale font size proportionally
    const scale = wrapperWidth / (210 * 3.7795275591); // convert mm to px (1mm ≈ 3.7795275591px)
    return originalSize * scale;
}

// Function to get preview scale factor
function getPreviewScale() {
    const previewWrapper = document.getElementById('certificate-preview-wrapper');
    if (!previewWrapper) return 1;
    const wrapperHeight = previewWrapper.offsetHeight;
    // A4 height in mm is 297
    return wrapperHeight / 215.9; // Scale based on height since we're scaling Y positions
}

function renderCertificateHTML(record, options = {}) {
    if (!bgImg) return '';
    const { opacity = 1, showBbox = showBoundingBoxes, bboxColor = boundingBoxColor, draggable = false, idx = null, showGreenBoxes = false, isPreviewSlide = false } = options;
    
    // Calculate scaled sizes for preview
    const scaledNameSize = getScaledFontSize(nameSize);
    const scaledDetailsSize = getScaledFontSize(detailsSize);
    const scale = getPreviewScale();
    const scaledNameY = nameY * scale;
    const scaledDetailsY = detailsY * scale;
    
    // Build details HTML with pipes
    let details_html = '';
    if (record.details && record.details.length) {
        record.details.forEach((d, i) => {
            if (i > 0) details_html += '<span class="pipe" style="color:' + (isPreviewSlide ? 'rgba(0,0,0,0)' : '#aa1f2e') + ';">|</span>';
            details_html += '<span style="color:' + (isPreviewSlide ? 'rgba(0,0,0,0)' : '#1c355e') + ';font-weight:bold;">' + escapeHtml(d) + '</span>';
        });
    }

    // Main HTML with text inside bounding boxes
    return `
        <img src="${bgImg.src}" class="bg" style="position:absolute;left:0;top:0;width:100%;height:100%;z-index:0;object-fit:cover;opacity:${opacity};" />
        ${isPreviewSlide ? `<div style="position:absolute;left:20px;top:20px;background:#aa1f2e;color:white;padding:8px 16px;border-radius:4px;font-family:'Poppins',Arial,sans-serif;font-weight:bold;z-index:3;">PREVIEW</div>` : ''}
        <div class="bbox name-box" data-type="name" style="position:absolute;left:50%;top:${scaledNameY}px;transform:translateX(-50%);z-index:2;border:2px dashed ${showBbox ? bboxColor : 'transparent'};background:${showBbox ? bboxColor+'10' : 'transparent'};padding:2px 8px;cursor:${draggable?'grab':'default'};opacity:${opacity};pointer-events:all;">
            <div class="name" style="color:${isPreviewSlide ? 'rgba(0,0,0,0)' : '#aa1f2e'};font-size:${scaledNameSize}pt;font-family:'Poppins',Arial,sans-serif;font-weight:bold;white-space:nowrap;text-align:center;background:${isPreviewSlide ? 'rgba(0,0,0,0)' : (showGreenBoxes ? 'green' : 'transparent')};border:none;padding:${showGreenBoxes ? '2px 8px' : '0'};opacity:1;pointer-events:none;position:relative;">${escapeHtml(record.fullName)}</div>
        </div>
        <div class="bbox details-box" data-type="details" style="position:absolute;left:50%;top:${scaledDetailsY}px;transform:translateX(-50%);z-index:2;border:2px dashed ${showBbox ? bboxColor : 'transparent'};background:${showBbox ? bboxColor+'10' : 'transparent'};padding:2px 8px;cursor:${draggable?'grab':'default'};opacity:${opacity};pointer-events:all;">
            <div class="details" style="font-size:${scaledDetailsSize}pt;font-family:'Poppins',Arial,sans-serif;font-weight:bold;white-space:nowrap;text-align:center;background:${isPreviewSlide ? 'rgba(0,0,0,0)' : (showGreenBoxes ? 'green' : 'transparent')};border:none;padding:${showGreenBoxes ? '2px 8px' : '0'};opacity:1;pointer-events:none;position:relative;">${details_html}</div>
        </div>
        <style>
        .pipe { color:#aa1f2e;font-weight:bold;padding:0 10mm;font-size:inherit;pointer-events:none; }
        .bbox { width:max-content;max-width:100%; }
        ${isPreviewSlide ? `
        .name::before {
            content: "ATHLETE NAME";
            position: absolute;
            left: 50%;
            top: 50%;
            transform: translate(-50%, -50%);
            color: #aa1f2e;
            opacity: 0.5;
            font-size: ${scaledNameSize}pt;
            white-space: nowrap;
        }
        .details::before {
            content: "ATHLETE DETAILS";
            position: absolute;
            left: 50%;
            top: 50%;
            transform: translate(-50%, -50%);
            color: #1c355e;
            opacity: 0.5;
            font-size: ${scaledDetailsSize}pt;
            white-space: nowrap;
        }
        ` : ''}
        </style>
    `;
}
function escapeHtml(str) {
    return str.replace(/[&<>"']/g, function(tag) {
        const chars = {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;','\'':'&#39;'};
        return chars[tag] || tag;
    });
}
function updatePreview() {
    if (!bgImg || !records.length) return;
    let html = '';
    if (currentIdx === 0) {
        // Find the record with the longest name
        let maxIdx = 0;
        let maxLen = 0;
        records.forEach((rec, i) => {
            const fullLen = rec.fullName.length + (rec.details ? rec.details.join('').length : 0);
            if (fullLen > maxLen) {
                maxLen = fullLen;
                maxIdx = i;
            }
        });
        // Render preview for the longest name
        html = renderCertificateHTML(records[maxIdx], {
            opacity: 1,
            showBbox: showBoundingBoxes,
            draggable: true,
            idx: maxIdx,
            showGreenBoxes: true,
            isPreviewSlide: true
        });
    } else {
        // Adjust index to account for preview slide
        const actualIdx = currentIdx - 1;
        html = renderCertificateHTML(records[actualIdx], { 
            opacity: 1, 
            showBbox: showBoundingBoxes, 
            draggable: true, 
            idx: actualIdx,
            isPreviewSlide: false
        });
    }
    certPreview.innerHTML = html;
    // Update slide indicator to show total + 1 for preview
    slideIndicator.textContent = `${currentIdx+1}/${records.length + 1}`;
    prevBtn.disabled = currentIdx === 0;
    nextBtn.disabled = currentIdx === records.length;
    // Add drag listeners
    addDragListeners();
}
csvInput.addEventListener('change', e => {
    const file = e.target.files[0];
    if (!file) return;
    const reader = new FileReader();
    reader.onload = evt => {
        records = parseCSV(evt.target.result);
        currentIdx = 0;
        if (bgImg && records.length) {
            previewArea.style.display = '';
            updatePreview();
            generateBtn.disabled = false;
        }
    };
    reader.readAsText(file);
});
bgInput.addEventListener('change', e => {
    const file = e.target.files[0];
    if (!file) return;
    const reader = new FileReader();
    reader.onload = evt => {
        bgImg = new window.Image();
        bgImg.onload = () => {
            if (records.length) {
                previewArea.style.display = '';
                updatePreview();
                generateBtn.disabled = false;
            }
        };
        bgImg.src = evt.target.result;
    };
    reader.readAsDataURL(file);
});
prevBtn.addEventListener('click', () => {
    if (currentIdx > 0) {
        currentIdx--;
        updatePreview();
    }
});
nextBtn.addEventListener('click', () => {
    if (currentIdx < records.length) {
        currentIdx++;
        updatePreview();
    }
});
showBboxCheckbox.addEventListener('change', function() {
    showBoundingBoxes = this.checked;
    bboxColorInput.disabled = !this.checked;
    updatePreview();
});
bboxColorInput.addEventListener('input', function() {
    boundingBoxColor = this.value;
    updatePreview();
});
document.getElementById('cert-form').addEventListener('submit', function(e) {
    e.preventDefault();
    if (!records.length || !bgImg) return;
    generateBtn.disabled = true;
    generateBtn.textContent = 'Generating...';
    // Prepare form data
    const formData = new FormData();
    formData.append('background', bgInput.files[0]);
    formData.append('csv', csvInput.files[0]);
    formData.append('name_y', nameY);
    formData.append('details_y', detailsY);
    formData.append('name_size', nameSize);
    formData.append('details_size', detailsSize);
    // POST to backend
    fetch('generate_certificates.php', {
        method: 'POST',
        body: formData
    }).then(resp => {
        if (!resp.ok) throw new Error('Failed to generate PDF');
        return resp.blob();
    }).then(blob => {
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = 'certificates.pdf';
        document.body.appendChild(a);
        a.click();
        a.remove();
        window.URL.revokeObjectURL(url);
        generateBtn.disabled = false;
        generateBtn.textContent = 'Generate PDF';
    }).catch(err => {
        alert('Error: ' + err.message);
        generateBtn.disabled = false;
        generateBtn.textContent = 'Generate PDF';
    });
});

function addDragListeners() {
    const nameBox = certPreview.querySelector('.bbox.name-box');
    const detailsBox = certPreview.querySelector('.bbox.details-box');
    if (nameBox) {
        nameBox.addEventListener('mousedown', startDrag('name'));
        nameBox.addEventListener('touchstart', startDrag('name'));
    }
    if (detailsBox) {
        detailsBox.addEventListener('mousedown', startDrag('details'));
        detailsBox.addEventListener('touchstart', startDrag('details'));
    }
}
function startDrag(type) {
    return function(e) {
        e.preventDefault();
        dragging = type;
        
        // Get the clicked element and its position
        const box = e.currentTarget;
        const boxRect = box.getBoundingClientRect();
        let clientY = e.touches ? e.touches[0].clientY : e.clientY;
        
        // Calculate offset from the center of the box
        dragOffsetY = clientY - (boxRect.top + boxRect.height / 2);
        
        document.addEventListener('mousemove', onDrag);
        document.addEventListener('touchmove', onDrag, { passive: false });
        document.addEventListener('mouseup', stopDrag);
        document.addEventListener('touchend', stopDrag);
        
        // Add dragging class to show feedback
        if (box) {
            box.style.cursor = 'grabbing';
        }

        // Log drag start
        console.log(`Drag started: ${type}`);
        console.log({
            type,
            currentSlide: currentIdx,
            position: type === 'name' ? nameY : detailsY,
            fontSize: type === 'name' ? nameSize : detailsSize,
            clickY: clientY,
            boxTop: boxRect.top,
            boxHeight: boxRect.height,
            dragOffset: dragOffsetY
        });
    };
}
function onDrag(e) {
    if (!dragging) return;
    e.preventDefault(); // Prevent scrolling on touch devices
    
    let clientY = e.touches ? e.touches[0].clientY : e.clientY;
    const wrapper = document.getElementById('certificate-preview-wrapper');
    const rect = wrapper.getBoundingClientRect();
    const box = certPreview.querySelector(`.bbox.${dragging}-box`);
    const boxHeight = box ? box.offsetHeight : 0;
    
    // Calculate position accounting for the box height and drag offset
    let yPx = (clientY - dragOffsetY) - rect.top - (boxHeight / 2);
    
    // Convert px to mm, accounting for scale
    const scale = getPreviewScale();
    let mm = (yPx / scale);
    mm = Math.max(0, Math.min(215.9, mm));
    
    // Log position during drag
    if (mm % 1 === 0) { // Only log on whole numbers to avoid console spam
        console.log(`Dragging ${dragging}: ${mm.toFixed(1)}mm`);
    }
    
    if (dragging === 'name') {
        nameY = mm;
        nameYSlider.value = nameY;
        nameYVal.textContent = nameY.toFixed(1);
    } else if (dragging === 'details') {
        detailsY = mm;
        detailsYSlider.value = detailsY;
        detailsYVal.textContent = detailsY.toFixed(1);
    }
    updatePreview();
}
function stopDrag() {
    if (!dragging) return;
    
    // Log final position before stopping
    console.log(`Drag ended: ${dragging}`);
    console.log({
        type: dragging,
        finalPosition: dragging === 'name' ? nameY : detailsY,
        currentSlide: currentIdx
    });
    
    // Reset cursor
    const box = certPreview.querySelector(`.bbox.${dragging}-box`);
    if (box) {
        box.style.cursor = 'grab';
    }
    
    dragging = null;
    document.removeEventListener('mousemove', onDrag);
    document.removeEventListener('touchmove', onDrag);
    document.removeEventListener('mouseup', stopDrag);
    document.removeEventListener('touchend', stopDrag);
}

// Add resize listener to handle preview scaling
window.addEventListener('resize', () => {
    if (records.length && bgImg) {
        updatePreview();
    }
});
    </script>
</body>
</html> 