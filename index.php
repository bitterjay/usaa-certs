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
    <h1>USA Archery - Bulk Certificate Generator</h1>
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
                <input type="range" id="name-y" min="0" max="215.9" step="0.1" value="111.8">
                <span id="name-y-val">111.8</span>
            </div>
            <div class="slider-group">
                <label for="details-y">Details Y Position</label>
                <input type="range" id="details-y" min="0" max="215.9" step="0.1" value="130.1">
                <span id="details-y-val">130.1</span>
            </div>
            <div class="slider-group">
                <label for="name-size">Name Font Size</label>
                <input type="range" id="name-size" min="10" max="80" step="1" value="36">
                <span id="name-size-val">36</span>
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
let nameY = 111.8, detailsY = 130.1, nameSize = 36, detailsSize = 18;
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

function renderCertificateHTML(record, options = {}) {
    if (!bgImg) return '';
    const { opacity = 1, showBbox = showBoundingBoxes, bboxColor = boundingBoxColor, draggable = false, idx = null, showGreenBoxes = false } = options;
    // Build details HTML with pipes
    let details_html = '';
    if (record.details && record.details.length) {
        record.details.forEach((d, i) => {
            if (i > 0) details_html += '<span class="pipe" style="' + (showGreenBoxes ? 'color:rgba(0,0,0,0);' : '') + '">|</span>';
            details_html += '<span style="color:' + (showGreenBoxes ? 'rgba(0,0,0,0)' : '#1c355e') + ';font-weight:bold;">' + escapeHtml(d) + '</span>';
        });
    }
    // Bounding box logic
    let nameBox = '';
    let detailsBox = '';
    if (showBbox && !showGreenBoxes) {
        nameBox = `<div class="bbox" data-type="name" style="position:absolute;left:50%;top:${nameY}mm;transform:translateX(-50%);z-index:2;pointer-events:none;border:2px dashed ${bboxColor};background:${bboxColor}10;width:max-content;max-width:100%;padding:2px 8px;opacity:0.5;"></div>`;
        detailsBox = `<div class="bbox" data-type="details" style="position:absolute;left:50%;top:${detailsY}mm;transform:translateX(-50%);z-index:2;pointer-events:none;border:2px dashed ${bboxColor};background:${bboxColor}10;width:max-content;max-width:100%;padding:2px 8px;opacity:0.5;"></div>`;
    }
    // Main HTML
    return `
        <img src="${bgImg.src}" class="bg" style="position:absolute;left:0;top:0;width:100%;height:100%;z-index:0;object-fit:cover;opacity:${opacity};" />
        ${nameBox}
        ${detailsBox}
        <div class="name" data-type="name" style="position:absolute;left:50%;top:${nameY}mm;transform:translateX(-50%);color:${showGreenBoxes ? 'rgba(0,0,0,0)' : '#aa1f2e'};font-size:${nameSize}pt;font-family:'Poppins',Arial,sans-serif;font-weight:bold;white-space:nowrap;z-index:3;text-align:center;width:100%;cursor:${draggable?'grab':'default'};opacity:${opacity};background:${showGreenBoxes ? 'green' : 'transparent'};border:${showGreenBoxes ? '5px dashed black' : 'none'};padding:${showGreenBoxes ? '2px 8px' : '0'};opacity:${showGreenBoxes ? '0.5' : opacity};">${escapeHtml(record.fullName)}</div>
        <div class="details" data-type="details" style="position:absolute;left:50%;top:${detailsY}mm;transform:translateX(-50%);font-size:${detailsSize}pt;font-family:'Poppins',Arial,sans-serif;font-weight:bold;white-space:nowrap;z-index:3;text-align:center;width:100%;cursor:${draggable?'grab':'default'};opacity:${opacity};background:${showGreenBoxes ? 'green' : 'transparent'};border:${showGreenBoxes ? '5px dashed black' : 'none'};padding:${showGreenBoxes ? '2px 8px' : '0'};opacity:${showGreenBoxes ? '0.5' : opacity};">${details_html}</div>
        <style>
        .pipe { color:#aa1f2e;font-weight:bold;padding:0 10mm;font-size:inherit; }
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
            if (rec.fullName.length > maxLen) {
                maxLen = rec.fullName.length;
                maxIdx = i;
            }
        });
        // Render both the invisible bounding box AND visible preview for the longest name
        html = renderCertificateHTML(records[maxIdx], {
            opacity: 0,
            showBbox: showBoundingBoxes,
            draggable: false,
            idx: maxIdx,
            showGreenBoxes: true
        });
        // Add the visible preview below
        html += renderCertificateHTML(records[maxIdx], {
            opacity: 1,
            showBbox: showBoundingBoxes,
            draggable: true,
            idx: maxIdx
        });
    } else {
        html = renderCertificateHTML(records[currentIdx], { opacity: 1, showBbox: showBoundingBoxes, draggable: true, idx: currentIdx });
    }
    certPreview.innerHTML = html;
    slideIndicator.textContent = `${currentIdx+1}/${records.length}`;
    prevBtn.disabled = currentIdx === 0;
    nextBtn.disabled = currentIdx === records.length-1;
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
    const nameDiv = certPreview.querySelector('.name');
    const detailsDiv = certPreview.querySelector('.details');
    if (nameDiv) {
        nameDiv.addEventListener('mousedown', startDrag('name'));
        nameDiv.addEventListener('touchstart', startDrag('name'));
    }
    if (detailsDiv) {
        detailsDiv.addEventListener('mousedown', startDrag('details'));
        detailsDiv.addEventListener('touchstart', startDrag('details'));
    }
}
function startDrag(type) {
    return function(e) {
        e.preventDefault();
        dragging = type;
        let clientY = e.touches ? e.touches[0].clientY : e.clientY;
        const previewRect = certPreview.getBoundingClientRect();
        dragOffsetY = clientY - previewRect.top;
        document.addEventListener('mousemove', onDrag);
        document.addEventListener('touchmove', onDrag);
        document.addEventListener('mouseup', stopDrag);
        document.addEventListener('touchend', stopDrag);
    };
}
function onDrag(e) {
    if (!dragging) return;
    let clientY = e.touches ? e.touches[0].clientY : e.clientY;
    const wrapper = document.getElementById('certificate-preview-wrapper');
    const rect = wrapper.getBoundingClientRect();
    let yPx = clientY - rect.top;
    // Convert px to mm
    let mm = yPx / rect.height * 215.9;
    mm = Math.max(0, Math.min(215.9, mm));
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
    dragging = null;
    document.removeEventListener('mousemove', onDrag);
    document.removeEventListener('touchmove', onDrag);
    document.removeEventListener('mouseup', stopDrag);
    document.removeEventListener('touchend', stopDrag);
}
    </script>
</body>
</html> 