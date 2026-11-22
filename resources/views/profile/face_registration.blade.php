@extends('layouts.app')

@section('content')

<div class="mobile-card">
    <div class="d-flex align-items-center justify-content-between mb-3">
        <div>
            <h5 class="fw-bold text-dark mb-0">Pendaftaran Wajah</h5>
            <span class="text-muted small" style="font-size: 0.75rem;">Verifikasi kehadiran AI Face Recognition</span>
        </div>
        @if($user->hasRegisteredFace())
            <span class="badge badge-soft-success px-2.5 py-1 rounded-pill small">Terdaftar</span>
        @else
            <span class="badge badge-soft-warning px-2.5 py-1 rounded-pill small">Belum Ada</span>
        @endif
    </div>

    <!-- Live Camera View -->
    <div class="position-relative mx-auto rounded-4 overflow-hidden border bg-dark mb-3" style="width: 100%; height: 260px;">
        <video id="video" autoplay playsinline muted class="w-100 h-100 object-fit-cover" style="transform: scaleX(-1);"></video>
        <canvas id="overlay" class="position-absolute top-0 start-0 w-100 h-100" style="pointer-events: none;"></canvas>

        <div id="cameraLoading" class="position-absolute top-0 start-0 w-100 h-100 d-flex flex-column align-items-center justify-content-center bg-dark text-white">
            <div class="spinner-border spinner-border-sm text-primary mb-2" role="status"></div>
            <span class="text-light small" id="loadingStatus">Memuat AI Wajah...</span>
        </div>

        <div class="position-absolute bottom-0 start-0 w-100 p-2 text-center" style="background: linear-gradient(to top, rgba(0,0,0,0.8), transparent);">
            <div id="detectionStatus" class="badge bg-secondary text-white px-3 py-1.5 rounded-pill small">
                Menunggu Kamera...
            </div>
        </div>
    </div>

    <div class="alert alert-light border rounded-3 p-2.5 mb-3 small" style="font-size: 0.75rem;">
        <i class="bi bi-info-circle text-primary me-1"></i> Posisikan wajah tepat di tengah frame dengan pencahayaan yang cukup.
    </div>

    <div id="feedbackAlert" class="alert d-none rounded-3 small py-2 mb-3 text-center"></div>

    <button id="btnCapture" class="btn btn-primary btn-rounded w-100 py-3 mb-2" disabled>
        <i class="bi bi-camera me-1.5"></i> Ambil & Simpan Biometrik Wajah
    </button>
    <a href="{{ route('attendance.index') }}" class="btn btn-light border btn-rounded w-100 py-2.5 text-muted small">
        Kembali ke Dashboard
    </a>
</div>

@endsection

@push('scripts')
<script src="{{ asset('js/face-api.min.js') }}"></script>
<script>
    const video = document.getElementById('video');
    const overlay = document.getElementById('overlay');
    const btnCapture = document.getElementById('btnCapture');
    const loadingDiv = document.getElementById('cameraLoading');
    const statusText = document.getElementById('loadingStatus');
    const badgeStatus = document.getElementById('detectionStatus');
    const feedback = document.getElementById('feedbackAlert');

    let isModelLoaded = false;
    let currentDescriptor = null;
    let detectionInterval = null;

    async function initFaceAPI() {
        try {
            statusText.innerText = 'Mengunduh model deteksi...';
            const MODEL_URL = '{{ asset("models") }}';
            await Promise.all([
                faceapi.nets.ssdMobilenetv1.loadFromUri(MODEL_URL),
                faceapi.nets.faceLandmark68Net.loadFromUri(MODEL_URL),
                faceapi.nets.faceRecognitionNet.loadFromUri(MODEL_URL)
            ]);

            isModelLoaded = true;
            statusText.innerText = 'Mengaktifkan sensor kamera...';
            startCamera();
        } catch (error) {
            console.error(error);
            statusText.innerText = 'Gagal memuat sistem: ' + error.message;
        }
    }

    async function startCamera() {
        try {
            const stream = await navigator.mediaDevices.getUserMedia({
                video: { width: 480, height: 360, facingMode: 'user' }
            });
            video.srcObject = stream;
            video.onloadedmetadata = () => {
                loadingDiv.classList.add('d-none');
                trackFace();
            };
        } catch (err) {
            alert('Tidak dapat mengakses kamera: ' + err.message);
        }
    }

    function trackFace() {
        overlay.width = video.videoWidth;
        overlay.height = video.videoHeight;

        detectionInterval = setInterval(async () => {
            if (!isModelLoaded) return;
            const detection = await faceapi.detectSingleFace(video)
                .withFaceLandmarks()
                .withFaceDescriptor();

            const ctx = overlay.getContext('2d');
            ctx.clearRect(0, 0, overlay.width, overlay.height);

            if (detection) {
                const dims = faceapi.matchDimensions(overlay, video, true);
                const resized = faceapi.resizeResults(detection, dims);
                faceapi.draw.drawDetections(overlay, resized);

                currentDescriptor = Array.from(detection.descriptor);
                badgeStatus.className = 'badge bg-success text-white px-3 py-1.5 rounded-pill small';
                badgeStatus.innerHTML = '<i class="bi bi-check-circle-fill me-1"></i> Wajah Terdeteksi Jelas';
                btnCapture.disabled = false;
            } else {
                currentDescriptor = null;
                badgeStatus.className = 'badge bg-warning text-dark px-3 py-1.5 rounded-pill small';
                badgeStatus.innerHTML = '<i class="bi bi-person-x me-1"></i> Posisikan Wajah ke Tengah';
                btnCapture.disabled = true;
            }
        }, 300);
    }

    btnCapture.addEventListener('click', async () => {
        if (!currentDescriptor) return;
        btnCapture.disabled = true;
        btnCapture.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Menyimpan Data Biometrik...';

        try {
            const res = await fetch('{{ route("face.store") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({
                    face_descriptor: JSON.stringify(currentDescriptor)
                })
            });

            const data = await res.json();
            feedback.classList.remove('d-none', 'alert-danger', 'alert-success');

            if (data.success) {
                feedback.classList.add('alert-success');
                feedback.innerHTML = `<i class="bi bi-check-circle-fill me-1"></i> ${data.message}`;
                setTimeout(() => {
                    window.location.href = '{{ route("attendance.index") }}';
                }, 1200);
            } else {
                feedback.classList.add('alert-danger');
                feedback.innerHTML = `<i class="bi bi-exclamation-triangle-fill me-1"></i> ${data.message}`;
                btnCapture.disabled = false;
                btnCapture.innerHTML = '<i class="bi bi-camera me-1.5"></i> Coba Lagi';
            }
        } catch (e) {
            feedback.classList.remove('d-none');
            feedback.classList.add('alert-danger');
            feedback.innerText = 'Terjadi galat: ' + e.message;
            btnCapture.disabled = false;
            btnCapture.innerHTML = '<i class="bi bi-camera me-1.5"></i> Coba Lagi';
        }
    });

    window.addEventListener('beforeunload', () => {
        if (detectionInterval) clearInterval(detectionInterval);
        if (video.srcObject) {
            video.srcObject.getTracks().forEach(t => t.stop());
        }
    });

    document.addEventListener('DOMContentLoaded', initFaceAPI);
</script>
@endpush
