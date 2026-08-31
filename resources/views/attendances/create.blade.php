@extends('layouts.app')

@section('title', 'Presensi: ' . $agenda->judul_rapat)
@section('heading', 'Formulir Presensi Rapat Kedinasan')
@section('subtitle', 'Perekaman selfie wajah dan tanda tangan digital terverifikasi')

@section('content')
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 items-start">
    <!-- Left: Main Attendance Form & Interactive Controls (2 cols) -->
    <div class="lg:col-span-2 space-y-6">
        <form id="attendance-form" method="POST" action="{{ route('attendances.store', $agenda) }}" enctype="multipart/form-data" class="space-y-6">
            @csrf
            <input type="hidden" name="selfie_data" id="selfie_data">
            <input type="hidden" name="signature_data" id="signature_data">

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Step 1: Face Selfie Capture (WebRTC + Auto Compression) -->
                <div class="panel p-6 flex flex-col justify-between space-y-4">
                    <div>
                        <div class="flex items-center justify-between border-b border-slate-200 pb-3 mb-4">
                            <div class="flex items-center gap-2">
                                <span class="w-6 h-6 rounded-full bg-slate-950 text-white font-bold text-xs flex items-center justify-center">1</span>
                                <h3 class="font-bold text-slate-950 text-sm">Foto Selfie Wajah</h3>
                            </div>
                            <span id="selfie-status-badge" class="text-[11px] font-bold text-slate-600">Belum Diambil</span>
                        </div>

                        <!-- Camera Viewport with Oval Frame Guide -->
                        <div class="relative bg-slate-950 rounded-2xl overflow-hidden aspect-[4/3] flex items-center justify-center border border-slate-800 shadow-inner">
                            <!-- Live Video Stream -->
                            <video id="camera-stream" autoplay playsinline muted class="w-full h-full object-cover"></video>

                            <!-- Oval Face Overlay Guide -->
                            <div id="camera-guide" class="absolute inset-0 pointer-events-none flex items-center justify-center">
                                <div class="w-44 h-56 border-2 border-dashed border-white/80 rounded-[50%] shadow-2xl"></div>
                                <div class="absolute bottom-3 text-center text-white text-[11px] font-bold bg-slate-950/80 px-3 py-1 rounded-full backdrop-blur-xs">
                                    Posisikan wajah di dalam bingkai oval
                                </div>
                            </div>

                            <!-- Captured Preview Image -->
                            <img id="selfie-preview" class="hidden w-full h-full object-cover">

                            <!-- Hidden Canvas for Client-side Compression -->
                            <canvas id="selfie-canvas" class="hidden"></canvas>
                        </div>
                    </div>

                    <!-- Camera Controls & Fallback -->
                    <div class="space-y-2.5 pt-2">
                        <button type="button" id="btn-capture-selfie" class="button w-full flex items-center justify-center gap-2 text-xs font-bold bg-slate-950 hover:bg-slate-800 text-white h-11 cursor-pointer">
                            <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><circle cx="12" cy="12" r="3"/></svg>
                            <span>Ambil Foto Wajah</span>
                        </button>

                        <button type="button" id="btn-retake-selfie" class="hidden button secondary w-full text-xs font-bold h-10 cursor-pointer">
                            Ambil Ulang Foto
                        </button>

                        <!-- Fallback Upload Button -->
                        <div class="pt-2 border-t border-slate-200 text-center">
                            <label class="text-[11px] text-slate-900 font-bold hover:underline cursor-pointer">
                                <span>Bermasalah dengan kamera? Unggah berkas foto</span>
                                <input type="file" id="fallback-selfie-file" name="selfie_file" accept="image/*" capture="user" class="hidden">
                            </label>
                        </div>
                    </div>
                </div>

                <!-- Step 2: Digital Signature Pad -->
                <div class="panel p-6 flex flex-col justify-between space-y-4">
                    <div>
                        <div class="flex items-center justify-between border-b border-slate-200 pb-3 mb-4">
                            <div class="flex items-center gap-2">
                                <span class="w-6 h-6 rounded-full bg-slate-950 text-white font-bold text-xs flex items-center justify-center">2</span>
                                <h3 class="font-bold text-slate-950 text-sm">Tanda Tangan Digital</h3>
                            </div>
                            <span id="signature-status-badge" class="text-[11px] font-bold text-slate-600">Belum Ditandatangani</span>
                        </div>

                        <!-- Canvas Signature Pad -->
                        <div class="relative bg-slate-50 rounded-2xl border-2 border-dashed border-slate-400 aspect-[4/3] flex items-center justify-center overflow-hidden">
                            <canvas id="signature-canvas" class="w-full h-full cursor-crosshair touch-none bg-white"></canvas>

                            <div id="signature-placeholder" class="absolute pointer-events-none text-center text-slate-400 text-xs select-none font-medium">
                                <svg class="mx-auto mb-1 opacity-60" viewBox="0 0 24 24" width="28" height="28" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"/><path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg>
                                Goreskan tanda tangan Anda di area ini
                            </div>
                        </div>
                    </div>

                    <!-- Signature Controls -->
                    <div class="space-y-2.5 pt-2">
                        <button type="button" id="btn-clear-signature" class="button secondary w-full text-xs font-bold h-11 flex items-center justify-center gap-2 cursor-pointer">
                            <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/></svg>
                            <span>Bersihkan / Ulangi TTD</span>
                        </button>
                        <p class="text-[11px] text-slate-600 text-center font-medium">Gunakan jari tangan pada layar sentuh atau mouse pada komputer.</p>
                    </div>
                </div>
            </div>

            <!-- Step 3: Confirmation and Submit -->
            <div class="bg-white p-6 rounded-2xl border border-slate-300 shadow-xs flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div class="text-xs text-slate-700">
                    <div class="font-bold text-slate-950 text-sm">Pernyataan Kehadiran:</div>
                    <p class="font-medium">Dengan menekan tombol di samping, saya menyatakan hadir secara sah pada agenda rapat kedinasan ini.</p>
                </div>

                <button type="submit" id="btn-submit-attendance" class="button flex items-center justify-center gap-2 text-sm font-bold bg-emerald-700 hover:bg-emerald-800 text-white px-8 h-12 shadow-md shrink-0 cursor-pointer">
                    <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                    <span>Konfirmasi & Kirim Presensi</span>
                </button>
            </div>
        </form>
    </div>

    <!-- Right: Meeting Information & Verification Guide Sidebar (1 col) -->
    <div class="lg:col-span-1 space-y-6">
        <!-- Meeting Info Card -->
        <div class="panel p-5 space-y-3">
            <div class="flex items-center justify-between">
                <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-amber-50 text-amber-900 border border-amber-300">
                    <span class="w-1.5 h-1.5 rounded-full bg-amber-600 animate-pulse"></span>
                    Sesi Presensi Dibuka
                </span>
                <span class="text-[11px] font-mono font-bold uppercase text-slate-900 bg-slate-100 px-2 py-0.5 rounded border border-slate-300">{{ $agenda->tipe_rapat }}</span>
            </div>

            <h3 class="font-bold text-slate-950 text-sm leading-snug">{{ $agenda->judul_rapat }}</h3>

            <div class="space-y-2 text-xs text-slate-800 pt-2 border-t border-slate-200 font-medium">
                <div class="flex items-center gap-2">
                    <svg class="text-slate-600 shrink-0" viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                    <span class="font-bold text-slate-900">{{ $agenda->waktu_mulai->translatedFormat('d M Y, H:i') }} - {{ $agenda->waktu_selesai->format('H:i') }} WIB</span>
                </div>

                <div class="flex items-center gap-2">
                    <svg class="text-slate-600 shrink-0" viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/></svg>
                    <span>{{ $agenda->lokasi_ruang ?? 'Daring / Ruang Virtual' }}</span>
                </div>

                <div class="flex items-center gap-2">
                    <svg class="text-slate-600 shrink-0" viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>
                    <span>Penyelenggara: <b class="text-slate-950">{{ $agenda->creator->name }}</b></span>
                </div>
            </div>
        </div>

        <!-- Verification Guide Card -->
        <div class="panel p-5 space-y-3">
            <h4 class="text-xs font-bold uppercase tracking-wider text-slate-700">Ketentuan Presensi Sah</h4>
            <ul class="text-xs text-slate-800 space-y-2 leading-relaxed font-medium">
                <li class="flex items-start gap-2">
                    <span class="text-slate-900 font-bold mt-0.5">&bull;</span>
                    <span><strong class="text-slate-950">Selfie Wajah:</strong> Wajah tampak lurus menghadap kamera dan berada dalam bingkai oval panduan.</span>
                </li>
                <li class="flex items-start gap-2">
                    <span class="text-slate-900 font-bold mt-0.5">&bull;</span>
                    <span><strong class="text-slate-950">Tanda Tangan:</strong> Buat paraf atau tanda tangan asli Anda pada kotak canvas digital.</span>
                </li>
                <li class="flex items-start gap-2">
                    <span class="text-slate-900 font-bold mt-0.5">&bull;</span>
                    <span><strong class="text-slate-950">Integritas Data:</strong> Presensi tersimpan bersama stempel waktu (timestamp) dan alamat IP jaringan.</span>
                </li>
            </ul>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    // --- 1. WebRTC CAMERA & COMPRESSION MODULE ---
    const video = document.getElementById('camera-stream');
    const canvas = document.getElementById('selfie-canvas');
    const preview = document.getElementById('selfie-preview');
    const guide = document.getElementById('camera-guide');
    const btnCapture = document.getElementById('btn-capture-selfie');
    const btnRetake = document.getElementById('btn-retake-selfie');
    const selfieDataInput = document.getElementById('selfie_data');
    const selfieBadge = document.getElementById('selfie-status-badge');
    const fallbackFileInput = document.getElementById('fallback-selfie-file');

    let stream = null;

    async function initCamera() {
        try {
            stream = await navigator.mediaDevices.getUserMedia({
                video: {
                    facingMode: 'user',
                    width: { ideal: 640 },
                    height: { ideal: 480 }
                },
                audio: false
            });
            video.srcObject = stream;
        } catch (err) {
            console.warn('WebRTC camera stream not available:', err);
            guide.innerHTML = '<div class="text-center text-white p-4 text-xs">Akses kamera tidak diizinkan atau tidak didukung.<br>Silakan gunakan opsi unggah foto di bawah.</div>';
        }
    }

    initCamera();

    btnCapture.addEventListener('click', () => {
        if (!video.videoWidth) {
            window.showModal({
                title: 'Kamera Belum Siap',
                message: 'Aliran kamera belum siap atau izin peramban dibatasi. Silakan gunakan opsi unggah berkas foto selfie di bawah.',
                type: 'warning',
                confirmText: 'Mengerti'
            });
            return;
        }

        // Client-side Canvas Compression (Max 600x800, JPEG 0.75)
        const maxWidth = 600;
        const scale = maxWidth / video.videoWidth;
        const targetWidth = maxWidth;
        const targetHeight = video.videoHeight * scale;

        canvas.width = targetWidth;
        canvas.height = targetHeight;
        const ctx = canvas.getContext('2d');
        ctx.drawImage(video, 0, 0, targetWidth, targetHeight);

        // Export compressed JPEG base64
        const dataUrl = canvas.toDataURL('image/jpeg', 0.75);
        selfieDataInput.value = dataUrl;

        // Display preview
        preview.src = dataUrl;
        preview.classList.remove('hidden');
        video.classList.add('hidden');
        guide.classList.add('hidden');

        btnCapture.classList.add('hidden');
        btnRetake.classList.remove('hidden');

        selfieBadge.textContent = 'Foto Terverifikasi';
        selfieBadge.className = 'text-[11px] font-bold text-emerald-600';
    });

    btnRetake.addEventListener('click', () => {
        selfieDataInput.value = '';
        preview.classList.add('hidden');
        video.classList.remove('hidden');
        guide.classList.remove('hidden');

        btnCapture.classList.remove('hidden');
        btnRetake.classList.add('hidden');

        selfieBadge.textContent = 'Belum Diambil';
        selfieBadge.className = 'text-[11px] font-semibold text-slate-400';
    });

    // Fallback file input handler
    fallbackFileInput.addEventListener('change', (e) => {
        const file = e.target.files[0];
        if (!file) return;

        const reader = new FileReader();
        reader.onload = (event) => {
            const img = new Image();
            img.onload = () => {
                const maxWidth = 600;
                const scale = maxWidth / img.width;
                canvas.width = maxWidth;
                canvas.height = img.height * scale;
                const ctx = canvas.getContext('2d');
                ctx.drawImage(img, 0, 0, canvas.width, canvas.height);

                const dataUrl = canvas.toDataURL('image/jpeg', 0.75);
                selfieDataInput.value = dataUrl;

                preview.src = dataUrl;
                preview.classList.remove('hidden');
                video.classList.add('hidden');
                guide.classList.add('hidden');

                btnCapture.classList.add('hidden');
                btnRetake.classList.remove('hidden');

                selfieBadge.textContent = 'Berkas Foto Siap';
                selfieBadge.className = 'text-[11px] font-bold text-emerald-600';
            };
            img.src = event.target.result;
        };
        reader.readAsDataURL(file);
    });

    // --- 2. HTML5 CANVAS SIGNATURE PAD MODULE ---
    const sigCanvas = document.getElementById('signature-canvas');
    const sigPlaceholder = document.getElementById('signature-placeholder');
    const btnClearSig = document.getElementById('btn-clear-signature');
    const sigDataInput = document.getElementById('signature_data');
    const sigBadge = document.getElementById('signature-status-badge');

    const sCtx = sigCanvas.getContext('2d');
    let isDrawing = false;
    let hasDrawn = false;

    function resizeSigCanvas() {
        const existingData = sigDataInput.value;
        const rect = sigCanvas.getBoundingClientRect();
        const dpr = window.devicePixelRatio || 1;
        sigCanvas.width = rect.width * dpr;
        sigCanvas.height = rect.height * dpr;
        sCtx.scale(dpr, dpr);
        sCtx.lineWidth = 2.5;
        sCtx.lineCap = 'round';
        sCtx.lineJoin = 'round';
        sCtx.strokeStyle = '#0f172a'; // Deep Navy Ink

        if (existingData) {
            const img = new Image();
            img.onload = () => {
                sCtx.drawImage(img, 0, 0, rect.width, rect.height);
            };
            img.src = existingData;
        }
    }

    window.addEventListener('resize', resizeSigCanvas);
    resizeSigCanvas();

    function getCanvasCoordinates(e) {
        const rect = sigCanvas.getBoundingClientRect();
        const clientX = e.touches ? e.touches[0].clientX : e.clientX;
        const clientY = e.touches ? e.touches[0].clientY : e.clientY;
        return {
            x: clientX - rect.left,
            y: clientY - rect.top
        };
    }

    function startDrawing(e) {
        isDrawing = true;
        hasDrawn = true;
        sigPlaceholder.classList.add('hidden');
        sigBadge.textContent = 'Tanda Tangan Terisi';
        sigBadge.className = 'text-[11px] font-bold text-emerald-600';

        const coords = getCanvasCoordinates(e);
        sCtx.beginPath();
        sCtx.moveTo(coords.x, coords.y);
        if (e.touches) e.preventDefault();
    }

    function draw(e) {
        if (!isDrawing) return;
        const coords = getCanvasCoordinates(e);
        sCtx.lineTo(coords.x, coords.y);
        sCtx.stroke();
        if (e.touches) e.preventDefault();
    }

    function stopDrawing() {
        if (!isDrawing) return;
        isDrawing = false;
        // Save compressed signature PNG to hidden input
        sigDataInput.value = sigCanvas.toDataURL('image/png');
    }

    // Mouse Events
    sigCanvas.addEventListener('mousedown', startDrawing);
    sigCanvas.addEventListener('mousemove', draw);
    sigCanvas.addEventListener('mouseup', stopDrawing);
    sigCanvas.addEventListener('mouseleave', stopDrawing);

    // Touch Events for Mobile / Tablet
    sigCanvas.addEventListener('touchstart', startDrawing, { passive: false });
    sigCanvas.addEventListener('touchmove', draw, { passive: false });
    sigCanvas.addEventListener('touchend', stopDrawing);

    btnClearSig.addEventListener('click', () => {
        sCtx.clearRect(0, 0, sigCanvas.width, sigCanvas.height);
        sigDataInput.value = '';
        hasDrawn = false;
        sigPlaceholder.classList.remove('hidden');
        sigBadge.textContent = 'Belum Ditandatangani';
        sigBadge.className = 'text-[11px] font-semibold text-slate-400';
    });

    // --- 3. FORM VALIDATION BEFORE SUBMIT ---
    const form = document.getElementById('attendance-form');
    form.addEventListener('submit', (e) => {
        if (!selfieDataInput.value && !fallbackFileInput.files.length) {
            e.preventDefault();
            window.showModal({
                title: 'Kondisi Belum Terpenuhi',
                message: 'Mohon ambil <strong>foto selfie wajah</strong> Anda terlebih dahulu menggunakan kamera atau unggah berkas foto.',
                type: 'warning',
                confirmText: 'Lengkapi Foto'
            });
            return false;
        }

        if (!hasDrawn || !sigDataInput.value) {
            e.preventDefault();
            window.showModal({
                title: 'Kondisi Belum Terpenuhi',
                message: 'Mohon bubuhkan <strong>tanda tangan digital</strong> Anda pada area kanvas yang tersedia.',
                type: 'warning',
                confirmText: 'Lengkapi Tanda Tangan'
            });
            return false;
        }

        const btnSubmit = document.getElementById('btn-submit-attendance');
        btnSubmit.disabled = true;
        btnSubmit.innerHTML = '<span>Memverifikasi & Menyimpan Data...</span>';
    });
});
</script>
@endsection
