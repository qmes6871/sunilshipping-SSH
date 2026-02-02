<?php
/**
 * 이미지 변환 도구
 */

require_once dirname(dirname(__DIR__)) . '/includes/auth_check.php';

$pageTitle = '이미지 변환';
$pageSubtitle = '이미지 포맷 및 품질 변환';

include dirname(dirname(__DIR__)) . '/includes/header.php';
?>

<style>
* { margin: 0; padding: 0; box-sizing: border-box; }

.container { max-width: 820px; margin: 0 auto; padding: 28px 16px; }
.page-title { font-size: 22px; font-weight: 700; color: #212529; margin-bottom: 6px; text-align: center; }
.page-subtitle { font-size: 13px; color: #6c757d; text-align: center; margin-bottom: 20px; }

.upload-area {
    background: #fff;
    border: 2px dashed #dee2e6;
    border-radius: 6px;
    padding: 28px 16px;
    text-align: center;
    cursor: pointer;
    transition: border-color .2s, background .2s;
    margin-bottom: 16px;
}
.upload-area:hover { border-color: #0d6efd; background: #f8f9ff; }
.upload-area.dragover { border-color: #0d6efd; background: #e7f1ff; }
.upload-icon { font-size: 32px; margin-bottom: 8px; }
.upload-text { font-size: 14px; color: #212529; margin-bottom: 6px; }
.upload-hint { font-size: 12px; color: #6c757d; }

.preview-area, .result-area {
    background: #fff;
    border: 1px solid #e9ecef;
    border-radius: 6px;
    padding: 16px;
    margin-bottom: 12px;
    display: none;
}
.preview-area.active, .result-area.active { display: block; }

.preview-header, .result-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 10px;
    padding-bottom: 8px;
    border-bottom: 1px solid #f1f3f5;
}
.preview-title, .result-title { font-size: 14px; font-weight: 600; color: #212529; }

.btn-remove { padding: 6px 10px; background: #fff; border: 1px solid #dee2e6; border-radius: 4px; font-size: 12px; color: #495057; cursor: pointer; }
.btn-remove:hover { background: #f8f9fa; }

.preview-image, .result-image { width: 100%; max-height: 360px; object-fit: contain; border-radius: 4px; background: #f8f9fa; }

.image-info, .result-info {
    margin-top: 8px;
    padding: 10px;
    background: #f8f9fa;
    border-radius: 4px;
    font-size: 12px;
    color: #495057;
}

.convert-options {
    background: #fff;
    border: 1px solid #e9ecef;
    border-radius: 6px;
    padding: 16px;
    margin-bottom: 12px;
    display: none;
}
.convert-options.active { display: block; }
.option-title { font-size: 14px; font-weight: 600; color: #212529; margin-bottom: 10px; }

.select-control {
    width: 100%;
    padding: 10px 12px;
    border: 1px solid #dee2e6;
    border-radius: 4px;
    font-size: 13px;
    background: #fff;
    color: #212529;
    margin-bottom: 12px;
}

.quality-control { margin: 6px 0 12px; }
.quality-label { display: flex; justify-content: space-between; font-size: 13px; color: #495057; margin-bottom: 8px; }

.quality-slider {
    width: 100%;
    height: 6px;
    border-radius: 3px;
    background: #e9ecef;
    outline: none;
    -webkit-appearance: none;
}
.quality-slider::-webkit-slider-thumb {
    -webkit-appearance: none;
    width: 18px;
    height: 18px;
    border-radius: 50%;
    background: #0d6efd;
    cursor: pointer;
}

.button-group { display: flex; gap: 10px; }
.btn { flex: 1; padding: 12px; border: none; border-radius: 4px; font-size: 14px; font-weight: 600; cursor: pointer; }
.btn-convert { background: #0d6efd; color: white; }
.btn-convert:hover { background: #0b5ed7; }
.btn-convert:disabled { background: #e9ecef; color: #adb5bd; cursor: not-allowed; }
.btn-download { background: #198754; color: white; display: none; }
.btn-download:hover { background: #157347; }
.btn-download.active { display: block; }

@media (max-width: 640px) {
    .container { padding: 20px 12px; }
    .upload-area { padding: 20px 12px; }
    .button-group { flex-direction: column; }
}
</style>

<div class="container">
    <h1 class="page-title">이미지 변환</h1>
    <p class="page-subtitle">이미지를 드래그하거나 클릭하여 업로드하세요</p>

    <!-- 업로드 영역 -->
    <div class="upload-area" id="uploadArea">
        <div class="upload-icon">📁</div>
        <div class="upload-text">이미지를 여기에 드래그하세요</div>
        <div class="upload-hint">또는 클릭하여 파일 선택</div>
    </div>
    <input type="file" id="fileInput" accept="image/*" style="display:none;">

    <!-- 미리보기 영역 -->
    <div class="preview-area" id="previewArea">
        <div class="preview-header">
            <span class="preview-title">원본 이미지</span>
            <button class="btn-remove" onclick="removeImage()">삭제</button>
        </div>
        <img id="previewImage" class="preview-image" alt="미리보기">
        <div class="image-info" id="imageInfo"></div>
    </div>

    <!-- 결과 영역 -->
    <div class="result-area" id="resultArea">
        <div class="result-header">
            <span class="result-title">결과 이미지</span>
            <button class="btn-remove" onclick="clearResult()">초기화</button>
        </div>
        <img id="resultImage" class="result-image" alt="결과 미리보기">
        <div class="result-info" id="resultInfo"></div>
    </div>

    <!-- 변환 옵션 -->
    <div class="convert-options" id="convertOptions">
        <div class="option-title">변환 옵션</div>
        <select class="select-control" id="formatSelect">
            <option value="jpeg">JPEG</option>
            <option value="png">PNG</option>
            <option value="webp">WEBP</option>
        </select>

        <div class="quality-control">
            <div class="quality-label">
                <span>품질</span>
                <span id="qualityValue">80%</span>
            </div>
            <input type="range" class="quality-slider" id="qualitySlider" min="1" max="100" value="80">
        </div>

        <div class="button-group">
            <button class="btn btn-convert" onclick="convertImage()">변환하기</button>
            <button class="btn btn-download" id="downloadBtn" onclick="downloadImage()">다운로드</button>
        </div>
    </div>
</div>

<?php
$pageScripts = <<<'SCRIPT'
<script>
let uploadedFile = null;
let selectedFormat = 'jpeg';
let convertedImageUrl = null;

const uploadArea = document.getElementById('uploadArea');
const fileInput = document.getElementById('fileInput');
const previewArea = document.getElementById('previewArea');
const previewImage = document.getElementById('previewImage');
const imageInfo = document.getElementById('imageInfo');
const resultArea = document.getElementById('resultArea');
const resultImage = document.getElementById('resultImage');
const resultInfo = document.getElementById('resultInfo');
const convertOptions = document.getElementById('convertOptions');
const qualitySlider = document.getElementById('qualitySlider');
const qualityValue = document.getElementById('qualityValue');
const downloadBtn = document.getElementById('downloadBtn');
const formatSelect = document.getElementById('formatSelect');

// 업로드 영역 클릭
uploadArea.addEventListener('click', () => fileInput.click());

// 파일 선택
fileInput.addEventListener('change', (e) => handleFile(e.target.files[0]));

// 드래그 앤 드롭
uploadArea.addEventListener('dragover', (e) => {
    e.preventDefault();
    uploadArea.classList.add('dragover');
});

uploadArea.addEventListener('dragleave', () => uploadArea.classList.remove('dragover'));

uploadArea.addEventListener('drop', (e) => {
    e.preventDefault();
    uploadArea.classList.remove('dragover');
    const file = e.dataTransfer.files[0];
    if (file && file.type.startsWith('image/')) handleFile(file);
});

// 파일 처리
function handleFile(file) {
    if (!file || !file.type.startsWith('image/')) {
        alert('이미지 파일만 업로드 가능합니다.');
        return;
    }

    uploadedFile = file;
    const reader = new FileReader();

    reader.onload = (e) => {
        previewImage.src = e.target.result;
        previewArea.classList.add('active');
        convertOptions.classList.add('active');
        resultArea.classList.remove('active');
        downloadBtn.classList.remove('active');
        convertedImageUrl = null;

        const img = new Image();
        img.onload = () => {
            const sizeKB = (file.size / 1024).toFixed(2);
            imageInfo.innerHTML = `<strong>${file.name}</strong><br>크기: ${img.width} × ${img.height}px | 용량: ${sizeKB} KB`;
        };
        img.src = e.target.result;
    };

    reader.readAsDataURL(file);
}

// 포맷 선택
formatSelect.addEventListener('change', (e) => selectedFormat = e.target.value);

// 품질 슬라이더
qualitySlider.addEventListener('input', (e) => qualityValue.textContent = e.target.value + '%');

// 이미지 변환
function convertImage() {
    if (!uploadedFile) return;

    const quality = qualitySlider.value / 100;
    const reader = new FileReader();

    reader.onload = (e) => {
        const img = new Image();
        img.onload = () => {
            const canvas = document.createElement('canvas');
            canvas.width = img.width;
            canvas.height = img.height;

            const ctx = canvas.getContext('2d');
            ctx.drawImage(img, 0, 0);

            let mimeType = 'image/jpeg';
            if (selectedFormat === 'png') mimeType = 'image/png';
            if (selectedFormat === 'webp') mimeType = 'image/webp';

            canvas.toBlob((blob) => {
                convertedImageUrl = URL.createObjectURL(blob);
                resultImage.src = convertedImageUrl;
                resultArea.classList.add('active');
                downloadBtn.classList.add('active');

                const sizeKB = (blob.size / 1024).toFixed(2);
                resultInfo.innerHTML = `<strong>변환 완료</strong><br>포맷: ${selectedFormat.toUpperCase()} | 품질: ${qualitySlider.value}% | 용량: ${sizeKB} KB`;
            }, mimeType, quality);
        };
        img.src = e.target.result;
    };

    reader.readAsDataURL(uploadedFile);
}

// 다운로드
function downloadImage() {
    if (!convertedImageUrl) return;
    const link = document.createElement('a');
    link.href = convertedImageUrl;
    link.download = `converted_image.${selectedFormat}`;
    link.click();
}

// 이미지 삭제
function removeImage() {
    uploadedFile = null;
    convertedImageUrl = null;
    previewArea.classList.remove('active');
    convertOptions.classList.remove('active');
    resultArea.classList.remove('active');
    downloadBtn.classList.remove('active');
    fileInput.value = '';
}

// 결과 초기화
function clearResult() {
    convertedImageUrl = null;
    resultArea.classList.remove('active');
    downloadBtn.classList.remove('active');
}
</script>
SCRIPT;

include dirname(dirname(__DIR__)) . '/includes/footer.php';
?>
