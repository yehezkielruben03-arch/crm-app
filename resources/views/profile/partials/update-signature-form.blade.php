<section class="space-y-6">
    <header class="border-b border-slate-200 pb-4">
        <div class="flex items-center gap-2.5">
            <div class="w-9 h-9 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center font-bold shadow-xs">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/>
                </svg>
            </div>
            <div>
                <h2 class="text-lg font-bold text-slate-800">
                    Identitas Penawaran & Tanda Tangan Digital
                </h2>
                <p class="text-xs text-slate-500 mt-0.5">
                    Kelola gelar jabatan dan tanda tangan resmi Anda yang akan otomatis tercetak pada lembar penawaran (Quotation).
                </p>
            </div>
        </div>
    </header>

    <form method="post" action="{{ route('profile.update') }}" enctype="multipart/form-data" id="signatureProfileForm" class="space-y-6">
        @csrf
        @method('patch')

        {{-- Hidden fields to preserve name and email required by ProfileUpdateRequest --}}
        <input type="hidden" name="name" value="{{ old('name', $user->name) }}">
        <input type="hidden" name="email" value="{{ old('email', $user->email) }}">
        <input type="hidden" name="signature_data" id="signatureDataInput">
        <input type="hidden" name="remove_signature" id="removeSignatureInput" value="0">

        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
            {{-- Input Jabatan Resmi --}}
            <div>
                <label for="job_title" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                    Jabatan Resmi di Dokumen (Job Title) <span class="text-rose-500">*</span>
                </label>
                <input 
                    type="text" 
                    id="job_title" 
                    name="job_title" 
                    value="{{ old('job_title', $user->job_title ?: ($user->isSales() ? 'Account Manager' : $user->role)) }}" 
                    class="w-full text-sm border-slate-300 rounded-xl focus:ring-blue-500 focus:border-blue-500 transition px-3.5 py-2.5 shadow-xs"
                    placeholder="Contoh: Account Manager, Senior Sales Executive, Sales Engineer"
                    oninput="updateSignaturePreviewTitle(this.value)"
                    required
                >
                <p class="text-[11px] text-slate-400 mt-1">
                    Teks jabatan ini akan tercetak di bawah nama Anda pada tanda tangan penawaran ke klien.
                </p>
            </div>

            {{-- Input Nomor Telepon / WA --}}
            <div>
                <label for="phone" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                    Nomor Telepon / WhatsApp Sales
                </label>
                <input 
                    type="text" 
                    id="phone" 
                    name="phone" 
                    value="{{ old('phone', $user->phone) }}" 
                    class="w-full text-sm border-slate-300 rounded-xl focus:ring-blue-500 focus:border-blue-500 transition px-3.5 py-2.5 shadow-xs"
                    placeholder="Contoh: 0812-3456-7890 atau 021-3971-2155"
                >
                <p class="text-[11px] text-slate-400 mt-1">
                    Nomor kontak yang tercantum pada paragraf penutup penawaran resmi.
                </p>
            </div>
        </div>

        {{-- Area Tanda Tangan Digital --}}
        <div class="border border-slate-200 rounded-2xl p-5 bg-slate-50/50">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-4">
                <div>
                    <label class="text-sm font-bold text-slate-800 flex items-center gap-2">
                        <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        Tanda Tangan Digital Anda
                    </label>
                    <p class="text-xs text-slate-500">Pilih salah satu metode: gambar langsung dengan jari/mouse atau upload file scan.</p>
                </div>

                {{-- Tab Switcher --}}
                <div class="inline-flex rounded-xl bg-slate-200 p-1 text-xs font-semibold">
                    <button type="button" id="tabDrawBtn" onclick="switchSigTab('draw')" class="px-3.5 py-1.5 rounded-lg bg-white shadow-xs text-slate-800 transition">
                        ✍️ Coret di Layar
                    </button>
                    <button type="button" id="tabUploadBtn" onclick="switchSigTab('upload')" class="px-3.5 py-1.5 rounded-lg text-slate-600 hover:text-slate-800 transition">
                        📁 Upload Gambar
                    </button>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
                {{-- Panel Input (Kiri - 7 cols) --}}
                <div class="lg:col-span-7">
                    {{-- 1. Mode Draw (Canvas) --}}
                    <div id="drawSection" class="space-y-2">
                        <div class="border-2 border-dashed border-slate-300 rounded-xl bg-white relative overflow-hidden shadow-inner">
                            <canvas id="sigCanvas" width="460" height="150" class="w-full h-[150px] touch-none cursor-crosshair block"></canvas>
                            <div id="canvasPlaceholder" class="absolute inset-0 flex items-center justify-center pointer-events-none text-slate-300 text-xs italic select-none">
                                Coret tanda tangan Anda di sini (Mouse / Touchscreen HP)
                            </div>
                        </div>
                        <div class="flex items-center justify-between text-xs flex-wrap gap-2">
                            <div class="flex items-center gap-2">
                                <button type="button" onclick="clearSigCanvas()" class="text-slate-600 hover:text-rose-600 font-semibold flex items-center gap-1.5 px-2.5 py-1 rounded bg-slate-200/60 hover:bg-rose-50 transition">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                    Bersihkan
                                </button>
                                <div class="inline-flex items-center gap-1 bg-slate-100 p-0.5 rounded-lg border border-slate-200 text-[11px]">
                                    <span class="px-1 text-slate-400 font-medium">Ketebalan:</span>
                                    <button type="button" onclick="setCanvasThickness(2.0, this)" class="thickness-btn px-2 py-0.5 rounded text-slate-600 hover:bg-white transition">Tipis</button>
                                    <button type="button" onclick="setCanvasThickness(3.5, this)" class="thickness-btn px-2 py-0.5 rounded bg-white font-bold text-slate-800 shadow-xs transition">Sedang</button>
                                    <button type="button" onclick="setCanvasThickness(5.5, this)" class="thickness-btn px-2 py-0.5 rounded text-slate-600 hover:bg-white transition">Tebal / Kereng</button>
                                </div>
                            </div>
                            <span class="text-slate-400 text-[11px]">Format: PNG Transparan Otomatis</span>
                        </div>
                    </div>

                    {{-- 2. Mode Upload File --}}
                    <div id="uploadSection" class="hidden space-y-3">
                        <div class="border-2 border-dashed border-slate-300 rounded-xl bg-white p-5 text-center hover:border-blue-400 transition cursor-pointer relative" onclick="document.getElementById('sigFileInput').click()">
                            <input type="file" id="sigFileInput" name="signature_file" accept="image/png,image/jpeg,image/jpg" class="hidden" onchange="handleSigFileSelect(this)">
                            <div class="mx-auto w-10 h-10 rounded-full bg-blue-50 text-blue-600 flex items-center justify-center mb-2">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                            </div>
                            <div class="text-xs font-bold text-slate-700" id="fileUploadLabel">Klik untuk pilih file tanda tangan</div>
                            <p class="text-[11px] text-slate-400 mt-1">Mendukung format PNG / JPG (Maks. 2MB). Disarankan berlatar transparan.</p>
                        </div>

                        {{-- Panel Pengaturan Kepekatan & Auto-Crop (Muncul saat file dipilih) --}}
                        <div id="uploadOptionsCard" class="hidden bg-slate-50 border border-slate-200 rounded-xl p-3.5 text-xs space-y-2.5">
                            <div class="font-bold text-slate-700 flex items-center gap-1.5">
                                <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"/></svg>
                                Pengaturan Ketebalan & Kepekatan Gambar
                            </div>
                            
                            {{-- Pilihan Kepekatan Garis --}}
                            <div>
                                <label class="text-[11px] font-semibold text-slate-600 mb-1 block">Tingkat Kepekatan Tinta:</label>
                                <div class="grid grid-cols-3 gap-2">
                                    <button type="button" onclick="setThicknessLevel('normal')" id="btnLevelNormal" class="py-1 px-2 text-center rounded-lg border text-xs font-medium transition bg-white border-slate-200 text-slate-700 hover:bg-slate-100">
                                        Asli / Tipis
                                    </button>
                                    <button type="button" onclick="setThicknessLevel('kereng')" id="btnLevelKereng" class="py-1 px-2 text-center rounded-lg border text-xs font-bold transition bg-blue-600 border-blue-600 text-white shadow-xs">
                                        ✨ Pekat & Tebal
                                    </button>
                                    <button type="button" onclick="setThicknessLevel('super')" id="btnLevelSuper" class="py-1 px-2 text-center rounded-lg border text-xs font-medium transition bg-white border-slate-200 text-slate-700 hover:bg-slate-100">
                                        🔥 Super Kereng
                                    </button>
                                </div>
                            </div>

                            <div class="flex flex-col gap-1.5 pt-1 text-[11px] text-slate-600">
                                <label class="inline-flex items-center gap-2 cursor-pointer">
                                    <input type="checkbox" id="chkAutoTrim" checked onchange="applySignatureProcessing()" class="rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                                    <span>Pangkas spasi kosong (Auto-Crop agar tanda tangan tidak menciut)</span>
                                </label>
                                <label class="inline-flex items-center gap-2 cursor-pointer">
                                    <input type="checkbox" id="chkRemoveBg" checked onchange="applySignatureProcessing()" class="rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                                    <span>Otomatis bersihkan background putih kertas (jadikan transparan)</span>
                                </label>
                            </div>
                        </div>
                    </div>

                    {{-- Tombol Hapus TTD yang ada --}}
                    @if($user->signature_path)
                    <div class="mt-4 pt-3 border-t border-slate-200 flex items-center justify-between">
                        <span class="text-xs text-emerald-600 font-semibold flex items-center gap-1.5">
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                            Tanda tangan aktif tersimpan
                        </span>
                        <button type="button" onclick="triggerRemoveSignature()" class="text-xs text-rose-600 hover:text-rose-700 font-bold hover:underline">
                            Hapus Tanda Tangan Ini
                        </button>
                    </div>
                    @endif
                </div>

                {{-- Live Preview Dokumen (Kanan - 5 cols) --}}
                <div class="lg:col-span-5 bg-white border border-slate-200 rounded-xl p-4 shadow-xs">
                    <div class="text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-2">Simulasi di Lembar Quotation</div>
                    <div class="p-3 bg-slate-50/70 border border-slate-100 rounded-lg text-left text-xs text-black space-y-1">
                        <div class="font-normal text-slate-700 text-[11px]">Hormat Kami</div>
                        <div class="relative h-[70px] w-[220px] my-1">
                            {{-- Stempel Resmi Perusahaan --}}
                            <img src="{{ asset('images/pedia_company_stamp.png') }}" 
                                 alt="Stempel PT Pedia Teknologi Indonesia" 
                                 class="absolute left-7 top-1 h-[54px] object-contain opacity-90 select-none pointer-events-none" style="z-index: 1;">
                            {{-- Tanda Tangan Preview --}}
                            <img id="liveSigImg" 
                                 src="{{ $user->signature_url ?: '' }}" 
                                 alt="Signature Preview" 
                                 class="absolute left-0 top-0 h-[70px] object-contain block {{ $user->signature_url ? '' : 'hidden' }}"
                                 style="z-index: 2;"
                            >
                            <span id="liveSigPlaceholder" class="text-[10px] text-slate-400 italic absolute left-1 top-6 {{ $user->signature_url ? 'hidden' : '' }}" style="z-index: 2;">
                                (Tanda tangan belum diisi)
                            </span>
                        </div>
                        <div class="font-bold text-[13px] text-black leading-tight">{{ $user->name }}</div>
                        <div class="italic text-[11.5px] text-slate-600 leading-tight" id="liveTitlePreview">
                            {{ $user->job_title ?: ($user->isSales() ? 'Account Manager' : $user->role) }}
                        </div>
                        <div class="text-[10px] text-slate-400 font-semibold pt-1">PT. PEDIA TEKNOLOGI INDONESIA</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="flex items-center gap-4 pt-2">
            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs px-6 py-2.5 rounded-xl shadow-md transition flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                Simpan Perubahan Identitas & TTD
            </button>

            @if (session('status') === 'profile-updated')
                <p x-data="{ show: true }" x-show="show" x-transition x-init="setTimeout(() => show = false, 2500)" class="text-xs font-bold text-emerald-600 flex items-center gap-1.5">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    Berhasil disimpan!
                </p>
            @endif
        </div>
    </form>
</section>

{{-- Script Canvas Pad & Live Preview with Auto-Trim & Auto-Enhance --}}
<script>
    let sigCanvas, ctx, isDrawing = false, hasDrawn = false;
    let currentRawUploadUrl = @json($user->signature_url);
    let currentThicknessLevel = 'kereng'; // 'normal', 'kereng', 'super'
    let currentCanvasLineWidth = 3.5;

    document.addEventListener('DOMContentLoaded', function() {
        sigCanvas = document.getElementById('sigCanvas');
        if (!sigCanvas) return;
        ctx = sigCanvas.getContext('2d');

        if (currentRawUploadUrl) {
            const card = document.getElementById('uploadOptionsCard');
            if (card) card.classList.remove('hidden');
        }

        // Setup canvas resolution for retina/high-DPI screens
        const rect = sigCanvas.getBoundingClientRect();
        sigCanvas.width = rect.width * 2;
        sigCanvas.height = rect.height * 2;
        ctx.scale(2, 2);

        ctx.strokeStyle = '#000000';
        ctx.lineWidth = currentCanvasLineWidth;
        ctx.lineCap = 'round';
        ctx.lineJoin = 'round';

        // Mouse events
        sigCanvas.addEventListener('mousedown', startDrawing);
        sigCanvas.addEventListener('mousemove', draw);
        sigCanvas.addEventListener('mouseup', stopDrawing);
        sigCanvas.addEventListener('mouseleave', stopDrawing);

        // Touch events for smartphone/tablet
        sigCanvas.addEventListener('touchstart', function(e) {
            e.preventDefault();
            const touch = e.touches[0];
            const mouseEvent = new MouseEvent('mousedown', {
                clientX: touch.clientX,
                clientY: touch.clientY
            });
            sigCanvas.dispatchEvent(mouseEvent);
        }, { passive: false });

        sigCanvas.addEventListener('touchmove', function(e) {
            e.preventDefault();
            const touch = e.touches[0];
            const mouseEvent = new MouseEvent('mousemove', {
                clientX: touch.clientX,
                clientY: touch.clientY
            });
            sigCanvas.dispatchEvent(mouseEvent);
        }, { passive: false });

        sigCanvas.addEventListener('touchend', function(e) {
            const mouseEvent = new MouseEvent('mouseup', {});
            sigCanvas.dispatchEvent(mouseEvent);
        });

        // Form submit handler: trim canvas to bounding box so it fills signature space
        document.getElementById('signatureProfileForm').addEventListener('submit', function() {
            if (hasDrawn) {
                const croppedCanvasData = cropCanvasBoundingBox(sigCanvas);
                if (croppedCanvasData) {
                    document.getElementById('signatureDataInput').value = croppedCanvasData;
                }
            }
        });
    });

    function setCanvasThickness(w, btn) {
        currentCanvasLineWidth = w;
        if (ctx) ctx.lineWidth = w;
        document.querySelectorAll('.thickness-btn').forEach(b => {
            b.className = 'thickness-btn px-2 py-0.5 rounded text-slate-600 hover:bg-white transition';
        });
        btn.className = 'thickness-btn px-2 py-0.5 rounded bg-white font-bold text-slate-800 shadow-xs transition';
    }

    function getCanvasCoordinates(e) {
        const rect = sigCanvas.getBoundingClientRect();
        return {
            x: e.clientX - rect.left,
            y: e.clientY - rect.top
        };
    }

    function startDrawing(e) {
        isDrawing = true;
        const coords = getCanvasCoordinates(e);
        ctx.beginPath();
        ctx.moveTo(coords.x, coords.y);
        document.getElementById('canvasPlaceholder').style.display = 'none';
    }

    function draw(e) {
        if (!isDrawing) return;
        hasDrawn = true;
        const coords = getCanvasCoordinates(e);
        ctx.lineTo(coords.x, coords.y);
        ctx.stroke();

        // Update live preview in real time (cropped to fit naturally)
        const cropped = cropCanvasBoundingBox(sigCanvas);
        if (cropped) {
            document.getElementById('liveSigImg').src = cropped;
            document.getElementById('liveSigImg').classList.remove('hidden');
            const ph = document.getElementById('liveSigPlaceholder');
            if (ph) ph.classList.add('hidden');
        }
    }

    function stopDrawing() {
        if (isDrawing) {
            isDrawing = false;
            ctx.closePath();
        }
    }

    function clearSigCanvas() {
        if (!ctx) return;
        ctx.clearRect(0, 0, sigCanvas.width, sigCanvas.height);
        hasDrawn = false;
        document.getElementById('canvasPlaceholder').style.display = 'flex';
        document.getElementById('signatureDataInput').value = '';
        
        const existingSig = @json($user->signature_url);
        if (existingSig) {
            document.getElementById('liveSigImg').src = existingSig;
            document.getElementById('liveSigImg').classList.remove('hidden');
            const ph = document.getElementById('liveSigPlaceholder');
            if (ph) ph.classList.add('hidden');
        } else {
            document.getElementById('liveSigImg').classList.add('hidden');
            const ph = document.getElementById('liveSigPlaceholder');
            if (ph) ph.classList.remove('hidden');
        }
    }

    function switchSigTab(mode) {
        const drawSec = document.getElementById('drawSection');
        const uploadSec = document.getElementById('uploadSection');
        const drawBtn = document.getElementById('tabDrawBtn');
        const uploadBtn = document.getElementById('tabUploadBtn');

        if (mode === 'draw') {
            drawSec.classList.remove('hidden');
            uploadSec.classList.add('hidden');
            drawBtn.className = 'px-3.5 py-1.5 rounded-lg bg-white shadow-xs text-slate-800 transition font-bold';
            uploadBtn.className = 'px-3.5 py-1.5 rounded-lg text-slate-600 hover:text-slate-800 transition';
        } else {
            drawSec.classList.add('hidden');
            uploadSec.classList.remove('hidden');
            uploadBtn.className = 'px-3.5 py-1.5 rounded-lg bg-white shadow-xs text-slate-800 transition font-bold';
            drawBtn.className = 'px-3.5 py-1.5 rounded-lg text-slate-600 hover:text-slate-800 transition';
        }
    }

    // Process & Enhance Uploaded Signature File
    function handleSigFileSelect(input) {
        if (input.files && input.files[0]) {
            const file = input.files[0];
            document.getElementById('fileUploadLabel').innerText = file.name;

            const reader = new FileReader();
            reader.onload = function(e) {
                currentRawUploadUrl = e.target.result;
                document.getElementById('uploadOptionsCard').classList.remove('hidden');
                applySignatureProcessing();
            };
            reader.readAsDataURL(file);
        }
    }

    function setThicknessLevel(lvl) {
        currentThicknessLevel = lvl;
        const btnNormal = document.getElementById('btnLevelNormal');
        const btnKereng = document.getElementById('btnLevelKereng');
        const btnSuper  = document.getElementById('btnLevelSuper');

        btnNormal.className = 'py-1 px-2 text-center rounded-lg border text-xs font-medium transition bg-white border-slate-200 text-slate-700 hover:bg-slate-100';
        btnKereng.className = 'py-1 px-2 text-center rounded-lg border text-xs font-medium transition bg-white border-slate-200 text-slate-700 hover:bg-slate-100';
        btnSuper.className  = 'py-1 px-2 text-center rounded-lg border text-xs font-medium transition bg-white border-slate-200 text-slate-700 hover:bg-slate-100';

        if (lvl === 'normal') {
            btnNormal.className = 'py-1 px-2 text-center rounded-lg border text-xs font-bold transition bg-blue-600 border-blue-600 text-white shadow-xs';
        } else if (lvl === 'kereng') {
            btnKereng.className = 'py-1 px-2 text-center rounded-lg border text-xs font-bold transition bg-blue-600 border-blue-600 text-white shadow-xs';
        } else {
            btnSuper.className  = 'py-1 px-2 text-center rounded-lg border text-xs font-bold transition bg-blue-600 border-blue-600 text-white shadow-xs';
        }

        applySignatureProcessing();
    }

    function applySignatureProcessing() {
        if (!currentRawUploadUrl) return;

        // Radius for morphological stroke dilation (actual physical stroke thickening)
        const radiusMap = {
            'normal': 0,     // original stroke
            'kereng': 2,     // +4px thicker bold stroke
            'super': 4       // +8px heavy bold stroke
        };
        const radius = radiusMap[currentThicknessLevel] ?? 2;
        const autoTrim = document.getElementById('chkAutoTrim') ? document.getElementById('chkAutoTrim').checked : true;
        const removeBg = document.getElementById('chkRemoveBg') ? document.getElementById('chkRemoveBg').checked : true;

        processSignatureImage(currentRawUploadUrl, function(resultDataUrl) {
            document.getElementById('liveSigImg').src = resultDataUrl;
            document.getElementById('liveSigImg').classList.remove('hidden');
            const ph = document.getElementById('liveSigPlaceholder');
            if (ph) ph.classList.add('hidden');

            // Set to signatureDataInput so the form submits this enhanced & cropped version
            document.getElementById('signatureDataInput').value = resultDataUrl;
        }, { radius: radius, autoTrim: autoTrim, removeWhiteBg: removeBg });
    }

    // Core Canvas Image Processing with True Dilation (Stroke Thickening)
    function processSignatureImage(srcUrl, callback, options = {}) {
        const img = new Image();
        img.crossOrigin = 'anonymous';
        img.onload = function() {
            const rawCanvas = document.createElement('canvas');
            rawCanvas.width = img.width;
            rawCanvas.height = img.height;
            const rCtx = rawCanvas.getContext('2d');
            rCtx.drawImage(img, 0, 0);

            const imgData = rCtx.getImageData(0, 0, rawCanvas.width, rawCanvas.height);
            const data = imgData.data;
            const w = rawCanvas.width;
            const h = rawCanvas.height;

            const removeWhite = options.removeWhiteBg ?? true;
            const autoTrim = options.autoTrim ?? true;
            const radius = options.radius ?? 2;

            let minX = w, minY = h, maxX = 0, maxY = 0;
            let hasInk = false;

            // Step 1: Clean background & isolate ink pixels to pure black
            for (let y = 0; y < h; y++) {
                for (let x = 0; x < w; x++) {
                    const idx = (y * w + x) * 4;
                    let r = data[idx];
                    let g = data[idx + 1];
                    let b = data[idx + 2];
                    let a = data[idx + 3];

                    // Remove white / light paper background
                    if (removeWhite && a > 20) {
                        if (r > 215 && g > 215 && b > 215) {
                            data[idx + 3] = 0;
                            continue;
                        }
                    }

                    // Check for ink pixels
                    if (data[idx + 3] > 25) {
                        const lum = 0.299 * r + 0.587 * g + 0.114 * b;
                        if (lum < 215) {
                            hasInk = true;
                            if (x < minX) minX = x;
                            if (x > maxX) maxX = x;
                            if (y < minY) minY = y;
                            if (y > maxY) maxY = y;

                            // Turn ink pixel to pure solid black
                            data[idx] = 0;
                            data[idx + 1] = 0;
                            data[idx + 2] = 0;
                            data[idx + 3] = 255;
                        } else {
                            data[idx + 3] = 0; // faint noise
                        }
                    } else {
                        data[idx + 3] = 0;
                    }
                }
            }

            rCtx.putImageData(imgData, 0, 0);

            if (!hasInk) {
                callback(rawCanvas.toDataURL('image/png'));
                return;
            }

            // Step 2: Auto-Crop Bounding Box & Normalize Resolution First
            let sourceCanvas = rawCanvas;
            let finalW = w;
            let finalH = h;

            if (autoTrim && hasInk) {
                const pad = Math.max(12, radius * 3 + 6);
                minX = Math.max(0, minX - pad);
                minY = Math.max(0, minY - pad);
                maxX = Math.min(w, maxX + pad);
                maxY = Math.min(h, maxY + pad);

                const cropW = Math.max(1, maxX - minX);
                const cropH = Math.max(1, maxY - minY);

                // Standardize display height to 160px for consistent, predictable stroke thickening
                const targetH = 160;
                const targetW = Math.max(1, Math.round(cropW * (targetH / cropH)));

                const normCanvas = document.createElement('canvas');
                normCanvas.width = targetW;
                normCanvas.height = targetH;
                const nCtx = normCanvas.getContext('2d');
                nCtx.drawImage(rawCanvas, minX, minY, cropW, cropH, 0, 0, targetW, targetH);

                sourceCanvas = normCanvas;
                finalW = targetW;
                finalH = targetH;
            }

            // Step 3: Dilation (Stroke Thickening by radius on normalized resolution)
            if (radius > 0) {
                const dilatedCanvas = document.createElement('canvas');
                dilatedCanvas.width = finalW;
                dilatedCanvas.height = finalH;
                const dCtx = dilatedCanvas.getContext('2d');

                // Draw circular multi-pass offsets
                for (let dx = -radius; dx <= radius; dx++) {
                    for (let dy = -radius; dy <= radius; dy++) {
                        if (dx * dx + dy * dy <= radius * radius) {
                            dCtx.drawImage(sourceCanvas, dx, dy);
                        }
                    }
                }

                // Force solid black on dilated strokes
                dCtx.globalCompositeOperation = 'source-in';
                dCtx.fillStyle = '#000000';
                dCtx.fillRect(0, 0, finalW, finalH);
                dCtx.globalCompositeOperation = 'source-over';

                sourceCanvas = dilatedCanvas;
            }

            callback(sourceCanvas.toDataURL('image/png'));
        };
        img.src = srcUrl;
    }

    // Helper: Crop Canvas Bounding Box
    function cropCanvasBoundingBox(canvas) {
        if (!canvas) return null;
        const w = canvas.width;
        const h = canvas.height;
        const cCtx = canvas.getContext('2d');
        const imgData = cCtx.getImageData(0, 0, w, h);
        const data = imgData.data;

        let minX = w, minY = h, maxX = 0, maxY = 0;
        let found = false;

        for (let y = 0; y < h; y++) {
            for (let x = 0; x < w; x++) {
                const a = data[(y * w + x) * 4 + 3];
                if (a > 20) {
                    found = true;
                    if (x < minX) minX = x;
                    if (x > maxX) maxX = x;
                    if (y < minY) minY = y;
                    if (y > maxY) maxY = y;
                }
            }
        }

        if (!found) return null;

        const pad = 10;
        minX = Math.max(0, minX - pad);
        minY = Math.max(0, minY - pad);
        maxX = Math.min(w, maxX + pad);
        maxY = Math.min(h, maxY + pad);

        const cropW = Math.max(1, maxX - minX);
        const cropH = Math.max(1, maxY - minY);

        const cropCanvas = document.createElement('canvas');
        cropCanvas.width = cropW;
        cropCanvas.height = cropH;
        const cropCtx = cropCanvas.getContext('2d');
        cropCtx.drawImage(canvas, minX, minY, cropW, cropH, 0, 0, cropW, cropH);

        return cropCanvas.toDataURL('image/png');
    }

    function updateSignaturePreviewTitle(val) {
        document.getElementById('liveTitlePreview').innerText = val.trim() || 'Account Manager';
    }

    function triggerRemoveSignature() {
        if (confirm('Apakah Anda yakin ingin menghapus tanda tangan digital Anda? Dokumen penawaran akan dikosongkan untuk tanda tangan basah.')) {
            document.getElementById('removeSignatureInput').value = '1';
            document.getElementById('signatureProfileForm').submit();
        }
    }
</script>
