<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>อัปโหลดใบเสร็จผ่านมือถือ - FuelFleet</title>
    <!-- Fonts & Icons -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&family=Sarabun:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Tailwind Play CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        darkBg: '#090d16',
                        slateCard: 'rgba(30, 41, 59, 0.45)',
                        accentPurple: '#818cf8',
                    }
                }
            }
        }
    </script>
    
    <style>
        body {
            font-family: 'Outfit', 'Sarabun', sans-serif;
            background: linear-gradient(135deg, #090d16 0%, #0f172a 50%, #1e1b4b 100%);
        }
        .glass-panel {
            background: rgba(30, 41, 59, 0.45);
            border: 1px solid rgba(255, 255, 255, 0.08);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
        }
    </style>
</head>
<body class="min-h-screen text-slate-200 flex items-center justify-center p-4">

    <div class="w-full max-w-md glass-panel rounded-2xl p-6 shadow-2xl relative overflow-hidden" id="uploadApp">
        <!-- Background light effect -->
        <div class="absolute -top-10 -right-10 w-32 h-32 bg-indigo-500/10 rounded-full blur-3xl"></div>
        <div class="absolute -bottom-10 -left-10 w-32 h-32 bg-purple-500/10 rounded-full blur-3xl"></div>

        <!-- Header -->
        <div class="text-center mb-6 relative">
            <div class="inline-flex h-12 w-12 rounded-xl bg-gradient-to-tr from-indigo-500 to-purple-600 items-center justify-center shadow-lg shadow-indigo-500/25 mb-3">
                <i class="fa-solid fa-camera text-white text-xl"></i>
            </div>
            <h1 class="text-lg font-bold text-white tracking-tight">อัปโหลดหลักฐานสลิปใบเสร็จ</h1>
            <p class="text-xs text-slate-400 font-light mt-1">สแกนจากระบบ FuelFleet PC เพื่อเชื่อมต่อข้อมูลเรียบร้อยแล้ว</p>
        </div>

        <?php if (!$isValid): ?>
            <!-- Expired / Invalid Token State -->
            <div class="text-center space-y-4 py-8">
                <div class="inline-flex h-14 w-14 rounded-full bg-rose-500/10 border border-rose-500/25 items-center justify-center text-rose-400 mb-2">
                    <i class="fa-solid fa-circle-exclamation text-2xl animate-pulse"></i>
                </div>
                <div class="space-y-1">
                    <h3 class="text-sm font-bold text-white">ลิงก์หรือรหัสผ่านหมดอายุแล้ว</h3>
                    <p class="text-xs text-slate-400 font-light leading-relaxed">กรุณากดปุ่ม "อัปโหลดผ่านมือถือ" บนหน้าจอ PC ของคุณอีกครั้งเพื่อรับรหัสผ่านใหม่</p>
                </div>
                <div class="pt-4">
                    <button onclick="window.close()" class="w-full py-2.5 bg-slate-800 hover:bg-slate-700 text-slate-300 text-xs font-semibold rounded-xl transition border border-slate-700/60">
                        ปิดหน้านี้
                    </button>
                </div>
            </div>
        <?php else: ?>
            <!-- Active Upload Form State -->
            <div class="space-y-6">
                <!-- Dropzone Area -->
                <div id="dropzone" class="border-2 border-dashed border-slate-750 hover:border-indigo-500/60 bg-slate-950/40 rounded-xl p-8 text-center transition cursor-pointer relative group">
                    <input type="file" id="fileInput" accept="image/*,.pdf" capture="environment" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-15">
                    
                    <div id="dropzoneContent" class="space-y-3">
                        <div class="inline-flex items-center justify-center h-12 w-12 rounded-xl bg-slate-900 border border-slate-800 text-slate-400 group-hover:text-indigo-400 group-hover:border-indigo-500/30 transition-all duration-200">
                            <i class="fa-solid fa-cloud-arrow-up text-2xl text-slate-450 mr-0.5"></i>
                        </div>
                        <div>
                            <p class="text-xs font-medium text-slate-200">ถ่ายรูป หรือเลือกไฟล์จากในเครื่อง</p>
                            <p class="text-[10px] text-slate-500 mt-1">รองรับสลิปใบเสร็จทุกประเภท รูปภาพ หรือไฟล์ PDF (สูงสุด 10MB)</p>
                        </div>
                    </div>

                    <!-- Selected file state display -->
                    <div id="fileState" class="hidden space-y-3">
                        <div class="inline-flex items-center justify-center h-12 w-12 rounded-xl bg-indigo-500/10 border border-indigo-500/30 text-indigo-400">
                            <i id="fileIcon" class="text-2xl fa-solid fa-file-image"></i>
                        </div>
                        <div>
                            <p id="selectedFileName" class="text-xs font-bold text-slate-200 truncate max-w-[250px] mx-auto"></p>
                            <p id="selectedFileSize" class="text-[10px] text-slate-500 mt-1"></p>
                        </div>
                    </div>
                </div>

                <!-- Progress / Status Bar -->
                <div id="progressContainer" class="hidden space-y-2">
                    <div class="flex items-center justify-between text-[10px] text-slate-400">
                        <span id="progressText">กำลังอัปโหลด...</span>
                        <span id="progressPercent">0%</span>
                    </div>
                    <div class="w-full bg-slate-850 h-1.5 rounded-full overflow-hidden">
                        <div id="progressBar" class="bg-indigo-500 h-full rounded-full transition-all duration-200" style="width: 0%"></div>
                    </div>
                </div>

                <!-- Buttons -->
                <div class="space-y-2">
                    <button id="uploadBtn" disabled class="w-full py-3 bg-slate-800/80 border border-slate-700/40 text-slate-500 text-xs font-semibold rounded-xl transition cursor-not-allowed">
                        <i class="fa-solid fa-arrow-up-from-bracket mr-1.5"></i> เริ่มอัปโหลดไฟล์หลักฐาน
                    </button>
                </div>
            </div>

            <!-- Success Screen (Initial hidden) -->
            <div id="successState" class="hidden text-center space-y-4 py-8">
                <div class="inline-flex h-14 w-14 rounded-full bg-emerald-500/10 border border-emerald-500/25 items-center justify-center text-emerald-400 mb-2">
                    <i class="fa-solid fa-circle-check text-2xl"></i>
                </div>
                <div class="space-y-1">
                    <h3 class="text-sm font-bold text-white">อัปโหลดไฟล์หลักฐานสำเร็จแล้ว!</h3>
                    <p class="text-xs text-slate-400 font-light leading-relaxed">ข้อมูลใบเสร็จได้รับการเชื่อมต่อและแสดงขึ้นหน้าจอคอมพิวเตอร์ของคุณแล้ว คุณสามารถปิดหน้านี้และทำรายการบน PC ต่อได้เลยครับ</p>
                </div>
                <div class="pt-4">
                    <button onclick="window.close()" class="w-full py-2.5 bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-semibold rounded-xl transition shadow-lg shadow-emerald-500/15">
                        ปิดหน้านี้
                    </button>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Script logic -->
    <?php if ($isValid): ?>
    <script>
        const fileInput = document.getElementById('fileInput');
        const dropzone = document.getElementById('dropzone');
        const dropzoneContent = document.getElementById('dropzoneContent');
        const fileState = document.getElementById('fileState');
        const fileIcon = document.getElementById('fileIcon');
        const selectedFileName = document.getElementById('selectedFileName');
        const selectedFileSize = document.getElementById('selectedFileSize');
        
        const uploadBtn = document.getElementById('uploadBtn');
        const progressContainer = document.getElementById('progressContainer');
        const progressBar = document.getElementById('progressBar');
        const progressPercent = document.getElementById('progressPercent');
        const progressText = document.getElementById('progressText');
        const successState = document.getElementById('successState');
        const uploadApp = document.getElementById('uploadApp');

        let fileToUpload = null;
        const token = "<?= htmlspecialchars($token) ?>";

        fileInput.addEventListener('change', (e) => {
            const file = e.target.files[0];
            if (file) {
                fileToUpload = file;
                
                // Show file details
                selectedFileName.textContent = file.name;
                const sizeInMb = (file.size / (1024 * 1024)).toFixed(2);
                selectedFileSize.textContent = sizeInMb + ' MB';

                // Toggle icon
                const ext = file.name.split('.').pop().toLowerCase();
                if (ext === 'pdf') {
                    fileIcon.className = 'text-2xl fa-solid fa-file-pdf text-rose-400';
                } else {
                    fileIcon.className = 'text-2xl fa-regular fa-image text-indigo-400';
                }

                // Show file details and hide initial state
                dropzoneContent.classList.add('hidden');
                fileState.classList.remove('hidden');

                // Enable upload button
                uploadBtn.disabled = false;
                uploadBtn.className = 'w-full py-3 bg-gradient-to-r from-indigo-500 to-purple-600 hover:from-indigo-600 hover:to-purple-700 text-white text-xs font-semibold rounded-xl transition shadow-lg shadow-indigo-500/15 cursor-pointer transform active:scale-98';
            }
        });

        uploadBtn.addEventListener('click', () => {
            if (!fileToUpload) return;

            const formData = new FormData();
            formData.append('doc_file', fileToUpload);

            // Show progress state
            progressContainer.classList.remove('hidden');
            uploadBtn.disabled = true;
            uploadBtn.className = 'w-full py-3 bg-slate-850 text-slate-500 text-xs font-semibold rounded-xl transition cursor-not-allowed';
            fileInput.disabled = true;

            const xhr = new XMLHttpRequest();
            
            // Subdirectory-friendly dynamic URL endpoint
            const basePath = "<?= \App\Core\Request::getBasePath() ?>";
            xhr.open('POST', `${basePath}/api/receipts/save-mobile?token=${token}`, true);

            // Progress event
            xhr.upload.onprogress = (e) => {
                if (e.lengthComputable) {
                    const percent = Math.round((e.loaded / e.total) * 100);
                    progressBar.style.width = percent + '%';
                    progressPercent.textContent = percent + '%';
                    if (percent >= 100) {
                        progressText.textContent = 'กำลังประมวลผลไฟล์...';
                    }
                }
            };

            // Complete event
            xhr.onload = () => {
                try {
                    const response = JSON.parse(xhr.responseText);
                    if (xhr.status === 200 && response.success) {
                        // Show success state
                        document.querySelector('.space-y-6').classList.add('hidden');
                        successState.classList.remove('hidden');
                    } else {
                        alert('เกิดข้อผิดพลาด: ' + (response.message || 'ไม่สามารถอัปโหลดไฟล์ได้'));
                        resetForm();
                    }
                } catch (err) {
                    alert('เกิดข้อผิดพลาดในการประมวลผลการตอบรับของเซิร์ฟเวอร์');
                    resetForm();
                }
            };

            xhr.onerror = () => {
                alert('เกิดข้อผิดพลาดทางเทคนิคในการเชื่อมต่ออินเทอร์เน็ต');
                resetForm();
            };

            xhr.send(formData);
        });

        function resetForm() {
            fileToUpload = null;
            fileInput.value = '';
            fileInput.disabled = false;
            
            dropzoneContent.classList.remove('hidden');
            fileState.classList.add('hidden');
            progressContainer.classList.add('hidden');
            progressBar.style.width = '0%';
            progressPercent.textContent = '0%';
            progressText.textContent = 'กำลังอัปโหลด...';

            uploadBtn.disabled = true;
            uploadBtn.className = 'w-full py-3 bg-slate-850 text-slate-500 text-xs font-semibold rounded-xl transition cursor-not-allowed';
        }
    </script>
    <?php endif; ?>

</body>
</html>
