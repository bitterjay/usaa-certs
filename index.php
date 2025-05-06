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
            padding: 32px 24px 32px 24px;
        }
        h1 {
            text-align: center;
            color: #1c355e;
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
    <h1>USA Archery Certificate Generator</h1>
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
            <canvas id="preview-canvas" width="1100" height="850"></canvas>
            <div class="controls">
                <button type="button" id="prev-btn">&lt; Prev</button>
                <span id="slide-indicator">1/1</span>
                <button type="button" id="next-btn">Next &gt;</button>
            </div>
            <div class="slider-group">
                <label for="name-y">Name Y Position</label>
                <input type="range" id="name-y" min="0" max="850" value="400">
                <span id="name-y-val">400</span>
            </div>
            <div class="slider-group">
                <label for="details-y">Details Y Position</label>
                <input type="range" id="details-y" min="0" max="850" value="500">
                <span id="details-y-val">500</span>
            </div>
            <div class="slider-group">
                <label for="name-size">Name Font Size</label>
                <input type="range" id="name-size" min="20" max="80" value="36">
                <span id="name-size-val">36</span>
            </div>
            <div class="slider-group">
                <label for="details-size">Details Font Size</label>
                <input type="range" id="details-size" min="10" max="40" value="18">
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
let nameY = 400, detailsY = 500, nameSize = 36, detailsSize = 18;
let dragging = null; // 'name' or 'details'
let dragOffsetY = 0;

const csvInput = document.getElementById('csv-input');
const bgInput = document.getElementById('bg-input');
const previewArea = document.getElementById('preview-area');
const canvas = document.getElementById('preview-canvas');
const ctx = canvas.getContext('2d');
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

// PDF dimensions: 279.4mm x 215.9mm (landscape)
const PDF_WIDTH_MM = 279.4;
const PDF_HEIGHT_MM = 215.9;
const MM_TO_PX = 10; // 1mm = 10px for high-res preview
const CANVAS_WIDTH = Math.round(PDF_WIDTH_MM * MM_TO_PX); // 2794px
const CANVAS_HEIGHT = Math.round(PDF_HEIGHT_MM * MM_TO_PX); // 2159px

canvas.width = CANVAS_WIDTH;
canvas.height = CANVAS_HEIGHT;
canvas.style.width = '100%';
canvas.style.height = 'auto';

// Sliders in mm and pt
nameYSlider.min = 0;
nameYSlider.max = PDF_HEIGHT_MM;
nameYSlider.step = 0.1;
detailsYSlider.min = 0;
detailsYSlider.max = PDF_HEIGHT_MM;
detailsYSlider.step = 0.1;
nameSizeSlider.min = 10;
nameSizeSlider.max = 80;
nameSizeSlider.step = 1;
detailsSizeSlider.min = 10;
detailsSizeSlider.max = 40;
detailsSizeSlider.step = 1;

// Default values
nameY = 80.0;
detailsY = 120.0;
nameSize = 36;
detailsSize = 18;
nameYSlider.value = nameY;
detailsYSlider.value = detailsY;
nameSizeSlider.value = nameSize;
detailsSizeSlider.value = detailsSize;
nameYVal.textContent = nameY;
detailsYVal.textContent = detailsY;
nameSizeVal.textContent = nameSize;
detailsSizeVal.textContent = detailsSize;

function ptToPx(pt) {
    // 1pt = 1/72 inch; 1 inch = 25.4mm; 1mm = 10px
    // px = pt * (25.4/72) * MM_TO_PX
    return pt * (25.4/72) * MM_TO_PX;
}

function mmToPx(mm) {
    return mm * MM_TO_PX;
}

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

function updatePreview() {
    if (!bgImg || !records.length) return;
    ctx.clearRect(0, 0, canvas.width, canvas.height);
    ctx.drawImage(bgImg, 0, 0, canvas.width, canvas.height);
    // Name
    ctx.font = `bold ${ptToPx(nameSize)}px 'Poppins', Arial, sans-serif`;
    ctx.fillStyle = '#aa1f2e';
    ctx.textAlign = 'center';
    ctx.textBaseline = 'alphabetic'; // anchor at bottom
    ctx.fillText(records[currentIdx].fullName, CANVAS_WIDTH/2, mmToPx(nameY));
    // Details
    ctx.font = `bold ${ptToPx(detailsSize)}px 'Poppins', Arial, sans-serif`;
    ctx.fillStyle = '#1c355e';
    ctx.textAlign = 'center';
    ctx.textBaseline = 'alphabetic'; // anchor at bottom
    let detailsText = records[currentIdx].details.join('      |      ');
    ctx.fillText(detailsText, CANVAS_WIDTH/2, mmToPx(detailsY));
    // Slide indicator
    slideIndicator.textContent = `${currentIdx+1}/${records.length}`;
    prevBtn.disabled = currentIdx === 0;
    nextBtn.disabled = currentIdx === records.length-1;
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
    if (currentIdx < records.length-1) {
        currentIdx++;
        updatePreview();
    }
});

nameYSlider.addEventListener('input', function() {
    nameY = parseFloat(this.value);
    nameYVal.textContent = this.value;
    updatePreview();
});
detailsYSlider.addEventListener('input', function() {
    detailsY = parseFloat(this.value);
    detailsYVal.textContent = this.value;
    updatePreview();
});
nameSizeSlider.addEventListener('input', function() {
    nameSize = parseInt(this.value, 10);
    nameSizeVal.textContent = this.value;
    updatePreview();
});
detailsSizeSlider.addEventListener('input', function() {
    detailsSize = parseInt(this.value, 10);
    detailsSizeVal.textContent = this.value;
    updatePreview();
});

canvas.addEventListener('mousedown', function(e) {
    const rect = canvas.getBoundingClientRect();
    const x = (e.clientX - rect.left) * (canvas.width / rect.width);
    const y = (e.clientY - rect.top) * (canvas.height / rect.height);
    // Check if user clicked near name or details text (within 30px)
    ctx.font = `bold ${ptToPx(nameSize)}px 'Poppins', Arial, sans-serif`;
    ctx.textAlign = 'center';
    ctx.textBaseline = 'alphabetic';
    let nameYpx = mmToPx(nameY);
    let nameText = records[currentIdx].fullName;
    let nameWidth = ctx.measureText(nameText).width;
    let nameHeight = ptToPx(nameSize);
    if (x > CANVAS_WIDTH/2 - nameWidth/2 && x < CANVAS_WIDTH/2 + nameWidth/2 && Math.abs(y - nameYpx) < 30) {
        dragging = 'name';
        dragOffsetY = y - nameYpx;
        return;
    }
    ctx.font = `bold ${ptToPx(detailsSize)}px 'Poppins', Arial, sans-serif`;
    let detailsYpx = mmToPx(detailsY);
    let detailsText = records[currentIdx].details.join('      |      ');
    let detailsWidth = ctx.measureText(detailsText).width;
    if (x > CANVAS_WIDTH/2 - detailsWidth/2 && x < CANVAS_WIDTH/2 + detailsWidth/2 && Math.abs(y - detailsYpx) < 30) {
        dragging = 'details';
        dragOffsetY = y - detailsYpx;
        return;
    }
});

document.addEventListener('mousemove', function(e) {
    if (!dragging) return;
    const rect = canvas.getBoundingClientRect();
    const y = (e.clientY - rect.top) * (canvas.height / rect.height);
    let newYmm = (y - dragOffsetY) / MM_TO_PX;
    newYmm = Math.max(0, Math.min(PDF_HEIGHT_MM, newYmm));
    if (dragging === 'name') {
        nameY = newYmm;
        nameYSlider.value = nameY;
        nameYVal.textContent = nameY.toFixed(1);
    } else if (dragging === 'details') {
        detailsY = newYmm;
        detailsYSlider.value = detailsY;
        detailsYVal.textContent = detailsY.toFixed(1);
    }
    updatePreview();
});

document.addEventListener('mouseup', function() {
    dragging = null;
});

// --- PDF Generation ---
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
</script>
</body>
</html> 