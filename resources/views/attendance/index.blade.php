@extends('layouts.app')

@push('styles')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="" />
<style>
    /* Full dashboard map container responsive for mobile */
    .dashboard-map-container {
        width: 100%;
        height: 250px;
        border-radius: 1.25rem;
        position: relative;
        overflow: hidden;
        z-index: 1;
    }

    .custom-driver-pin {
        background: transparent;
        border: none;
    }
    .driver-pin-wrap {
        position: relative;
        width: 44px;
        height: 44px;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .driver-pin-pulse {
        position: absolute;
        width: 38px;
        height: 38px;
        border-radius: 50%;
        background-color: rgba(37, 99, 235, 0.35);
        animation: ojolPulse 2s infinite ease-out;
    }
    .driver-pin-icon {
        position: relative;
        width: 34px;
        height: 34px;
        background: linear-gradient(135deg, #1d4ed8 0%, #2563eb 100%);
        border: 2px solid #ffffff;
        border-radius: 50%;
        color: #ffffff;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1rem;
        box-shadow: 0 4px 10px rgba(0, 0, 0, 0.25);
    }
    .driver-pin-icon.checked-in {
        background: linear-gradient(135deg, #059669 0%, #10b981 100%);
    }
    @keyframes ojolPulse {
        0% { transform: scale(0.6); opacity: 0.9; }
        100% { transform: scale(1.6); opacity: 0; }
    }

    /* Swipe to Check-in Button (iOS App Look) */
    .swipe-btn-wrapper {
        position: relative;
        height: 56px;
        background: linear-gradient(135deg, #1d4ed8 0%, #2563eb 100%);
        border-radius: 9999px;
        display: flex;
        align-items: center;
        justify-content: center;
        overflow: hidden;
        box-shadow: 0 4px 14px rgba(37, 99, 235, 0.35);
        cursor: pointer;
        user-select: none;
        transition: transform 0.15s ease, opacity 0.2s ease;
    }
    .swipe-btn-wrapper.disabled {
        background: #cbd5e1;
        box-shadow: none;
        cursor: not-allowed;
        opacity: 0.7;
    }
    .swipe-btn-wrapper.checkout {
        background: linear-gradient(135deg, #dc2626 0%, #ef4444 100%);
        box-shadow: 0 4px 14px rgba(220, 38, 38, 0.35);
    }
    .swipe-slider-thumb {
        position: absolute;
        left: 5px;
        width: 46px;
        height: 46px;
        background: #ffffff;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #2563eb;
        font-size: 1.25rem;
        box-shadow: 0 2px 6px rgba(0,0,0,0.15);
    }
    .swipe-btn-wrapper.checkout .swipe-slider-thumb {
        color: #dc2626;
    }
    .swipe-btn-label {
        color: #ffffff;
        font-weight: 700;
        font-size: 0.95rem;
        letter-spacing: 0.3px;
    }

    /* Quick Action Grid Icons */
    .action-grid-item {
        display: flex;
        flex-direction: column;
        align-items: center;
        text-align: center;
        text-decoration: none;
        padding: 0.65rem 0.25rem;
        border-radius: 1rem;
        transition: background 0.15s ease;
    }
    .action-grid-item:hover {
        background: #f8fafc;
    }
    .action-icon-circle {
        width: 46px;
        height: 46px;
        border-radius: 1rem;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.25rem;
        margin-bottom: 0.45rem;
    }

    .time-card-box {
        background: #f8fafc;
        border: 1px solid var(--card-border);
        border-radius: 1rem;
        padding: 0.75rem 1rem;
    }
</style>
@endpush

@section('content')

    {{-- Banner Registrasi Wajah jika belum --}}
    @if(!$user->hasRegisteredFace())
    <div class="alert alert-warning border-0 rounded-4 p-3 mb-3 d-flex align-items-center justify-content-between flex-wrap gap-2 shadow-sm">
        <div class="d-flex align-items-center gap-2.5">
            <i class="bi bi-camera-fill fs-4 text-warning"></i>
            <div>
                <strong class="d-block text-dark small leading-tight">Wajah Belum Terdaftar</strong>
                <span class="text-muted" style="font-size: 0.75rem;">Daftarkan biometrik untuk mulai absen.</span>
            </div>
        </div>
        <a href="{{ route('face.registration') }}" class="btn btn-sm btn-warning text-dark btn-rounded px-3 py-1.5 fw-bold" style="font-size: 0.78rem;">
            Daftar
        </a>
    </div>
    @endif

    {{-- Card 1: Header Tanggal & Status Card (Sesuai Mockup) --}}
    <div class="mobile-card">
        <div class="d-flex align-items-center justify-content-between mb-2.5">
            <div class="d-flex align-items-center gap-2">
                <i class="bi bi-calendar4-week text-primary"></i>
                <span class="fw-bold small text-dark">{{ \Carbon\Carbon::now('Asia/Jakarta')->translatedFormat('l, d F Y') }}</span>
            </div>
            <span class="badge {{ $isLocationRequired ? 'badge-soft-primary' : 'badge-soft-success' }} rounded-pill px-2.5 py-1" style="font-size: 0.7rem;">
                <i class="bi {{ $isLocationRequired ? 'bi-shield-check' : 'bi-unlock' }} me-1"></i>
                {{ $isLocationRequired ? 'Wajib Titik Kantor' : 'Lokasi Fleksibel' }}
            </span>
        </div>

        <!-- Check-in & Check-out Summary Box (Sesuai Referensi Gambar) -->
        <div class="row g-2 text-center">
            <div class="col-6">
                <div class="time-card-box d-flex align-items-center gap-2.5">
                    <div class="rounded-3 {{ $todayAttendance ? 'bg-success bg-opacity-10 text-success' : 'bg-primary bg-opacity-10 text-primary' }} p-2 d-flex align-items-center justify-content-center" style="width: 38px; height: 38px; min-width: 38px;">
                        <i class="bi bi-box-arrow-in-right fs-5"></i>
                    </div>
                    <div class="text-start">
                        <div class="text-muted" style="font-size: 0.7rem; font-weight: 600;">Check In</div>
                        <div class="fw-bold text-dark fs-6">
                            {{ $todayAttendance && $todayAttendance->check_in_time ? \Carbon\Carbon::parse($todayAttendance->check_in_time)->format('H:i') : '-- : --' }}
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-6">
                <div class="time-card-box d-flex align-items-center gap-2.5">
                    <div class="rounded-3 {{ $todayAttendance && $todayAttendance->check_out_time ? 'bg-danger bg-opacity-10 text-danger' : 'bg-secondary bg-opacity-10 text-secondary' }} p-2 d-flex align-items-center justify-content-center" style="width: 38px; height: 38px; min-width: 38px;">
                        <i class="bi bi-box-arrow-right fs-5"></i>
                    </div>
                    <div class="text-start">
                        <div class="text-muted" style="font-size: 0.7rem; font-weight: 600;">Check Out</div>
                        <div class="fw-bold text-dark fs-6">
                            {{ $todayAttendance && $todayAttendance->check_out_time ? \Carbon\Carbon::parse($todayAttendance->check_out_time)->format('H:i') : '-- : --' }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Card 2: Interactive Map Section (Full Map Tampil Sebelum & Sesudah Absen) --}}
    <div class="mobile-card p-2.5">
        <div class="d-flex align-items-center justify-content-between px-2 pt-1 pb-2">
            <div class="d-flex align-items-center gap-2">
                <i class="bi bi-geo-alt-fill text-primary"></i>
                <span class="fw-bold small text-dark">Peta Titik Lokasi</span>
            </div>
            <button type="button" class="btn btn-sm btn-link text-primary text-decoration-none p-0 fw-semibold" style="font-size: 0.75rem;" onclick="recenterDashboardMap()">
                <i class="bi bi-crosshair me-1"></i>Pusatkan
            </button>
        </div>

        <div class="position-relative">
            <div id="dashboardMap" class="dashboard-map-container border"></div>
        </div>

        <!-- Lokasi Kantor Info Bar -->
        <div class="px-2 pt-2 pb-1 d-flex align-items-center justify-content-between small text-muted" style="font-size: 0.75rem;">
            <div class="text-truncate me-2" id="userAddressDisplay">
                @if($todayAttendance && $todayAttendance->address_in)
                    <i class="bi bi-pin-map-fill text-success me-1"></i>{{ $todayAttendance->address_in }}
                @else
                    <i class="bi bi-broadcast text-primary me-1"></i>Mendeteksi posisi GPS Anda...
                @endif
            </div>
            <span class="badge bg-light text-dark border px-2 py-1 flex-shrink-0" id="gpsAccuracyBadge">GPS Aktif</span>
        </div>
    </div>

    {{-- Card 3: Camera Live Preview & Submit Attendance Slider Button --}}
    <div class="mobile-card">
        <div class="section-title">
            <span>Presensi Kehadiran Wajah & Lokasi</span>
            @if($user->hasRegisteredFace())
                <span class="badge badge-soft-success px-2 py-0.5 rounded-pill" style="font-size: 0.7rem;">Wajah OK</span>
            @endif
        </div>

        @if($isSundayToday)
            <div class="alert alert-info border-0 rounded-4 text-center py-3 mb-0 small">
                <i class="bi bi-cup-hot-fill fs-3 text-primary d-block mb-1"></i>
                <strong>Hari Minggu Libur Kerja</strong>
                <p class="text-muted mb-0" style="font-size: 0.75rem;">Tidak ada jadwal presensi kehadiran hari ini.</p>
            </div>
        @elseif($todayAttendance && $todayAttendance->status === 'cuti')
            <div class="alert alert-primary bg-primary bg-opacity-10 border-0 rounded-4 text-center py-3 mb-0 small">
                <i class="bi bi-calendar-check-fill fs-3 text-primary d-block mb-1"></i>
                <strong>Status: Izin Cuti</strong>
                <p class="text-muted mb-0" style="font-size: 0.75rem;">Pengajuan cuti Anda telah disetujui untuk hari ini.</p>
            </div>
        @elseif($todayAttendance && $todayAttendance->status === 'sakit')
            <div class="alert alert-warning bg-warning bg-opacity-10 border-0 rounded-4 text-center py-3 mb-0 small">
                <i class="bi bi-heart-pulse-fill fs-3 text-warning d-block mb-1"></i>
                <strong>Status: Izin Sakit</strong>
                <p class="text-muted mb-0" style="font-size: 0.75rem;">Status izin sakit Anda telah tercatat.</p>
            </div>
        @elseif($todayAttendance && $todayAttendance->status === 'alfa')
            <div class="alert alert-danger bg-danger bg-opacity-10 border-0 rounded-4 text-center py-3 mb-0 small">
                <i class="bi bi-x-circle-fill fs-3 text-danger d-block mb-1"></i>
                <strong>Status: Alfa (Tidak Hadir)</strong>
                <p class="text-muted mb-0" style="font-size: 0.75rem;">Batas absen masuk telah melewati jam 11:00 WIB.</p>
            </div>
        @elseif(empty($todayAttendance))
            {{-- Mode Absen Masuk --}}
            @if(!$user->hasRegisteredFace())
                <div class="text-center py-3">
                    <p class="text-muted small mb-3">Harap rekam wajah Anda sebelum melakukan presensi masuk.</p>
                    <a href="{{ route('face.registration') }}" class="btn btn-warning text-dark btn-rounded px-4">
                        Daftarkan Wajah Sekarang
                    </a>
                </div>
            @else
                <!-- Camera Feed Box -->
                <div class="position-relative mx-auto rounded-4 overflow-hidden border bg-dark mb-3" style="width: 100%; height: 210px;">
                    <video id="attendanceVideo" autoplay playsinline muted class="w-100 h-100 object-fit-cover" style="transform: scaleX(-1);"></video>
                    <canvas id="attendanceOverlay" class="position-absolute top-0 start-0 w-100 h-100" style="pointer-events: none;"></canvas>

                    <div id="attendanceCameraLoading" class="position-absolute top-0 start-0 w-100 h-100 d-flex flex-column align-items-center justify-content-center bg-dark text-white">
                        <div class="spinner-border spinner-border-sm text-primary mb-2" role="status"></div>
                        <span class="text-light" style="font-size: 0.75rem;" id="camStatusText">Mengaktifkan Kamera AI...</span>
                    </div>

                    <div class="position-absolute bottom-0 start-0 w-100 p-2 text-center" style="background: linear-gradient(to top, rgba(0,0,0,0.8), transparent);">
                        <span id="faceStatusBadge" class="badge bg-secondary text-white px-3 py-1.5 rounded-pill small">
                            Mendeteksi Wajah...
                        </span>
                    </div>
                </div>

                <div id="attendanceFeedback" class="alert d-none rounded-3 small py-2 mb-2 text-center"></div>

                <!-- Swipe to Check In Button -->
                <div id="btnCheckInWrapper" class="swipe-btn-wrapper disabled" onclick="triggerAttendance('checkin')">
                    <div class="swipe-slider-thumb">
                        <i class="bi bi-chevron-right"></i>
                    </div>
                    <span class="swipe-btn-label" id="checkInLabel">
                        <i class="bi bi-fingerprint me-1"></i> Tap / Klik untuk Masuk
                    </span>
                </div>
            @endif
        @elseif(is_null($todayAttendance->check_out_time))
            {{-- Mode Absen Keluar --}}
            <div class="position-relative mx-auto rounded-4 overflow-hidden border bg-dark mb-3" style="width: 100%; height: 210px;">
                <video id="attendanceVideo" autoplay playsinline muted class="w-100 h-100 object-fit-cover" style="transform: scaleX(-1);"></video>
                <canvas id="attendanceOverlay" class="position-absolute top-0 start-0 w-100 h-100" style="pointer-events: none;"></canvas>

                <div id="attendanceCameraLoading" class="position-absolute top-0 start-0 w-100 h-100 d-flex flex-column align-items-center justify-content-center bg-dark text-white">
                    <div class="spinner-border spinner-border-sm text-primary mb-2" role="status"></div>
                    <span class="text-light" style="font-size: 0.75rem;" id="camStatusText">Mengaktifkan Kamera...</span>
                </div>

                <div class="position-absolute bottom-0 start-0 w-100 p-2 text-center" style="background: linear-gradient(to top, rgba(0,0,0,0.8), transparent);">
                    <span id="faceStatusBadge" class="badge bg-secondary text-white px-3 py-1.5 rounded-pill small">
                        Mendeteksi Wajah...
                    </span>
                </div>
            </div>

            <div id="attendanceFeedback" class="alert d-none rounded-3 small py-2 mb-2 text-center"></div>

            <!-- Swipe to Check Out Button -->
            <div id="btnCheckOutWrapper" class="swipe-btn-wrapper checkout disabled" onclick="triggerAttendance('checkout')">
                <div class="swipe-slider-thumb">
                    <i class="bi bi-chevron-right"></i>
                </div>
                <span class="swipe-btn-label" id="checkOutLabel">
                    <i class="bi bi-box-arrow-right me-1"></i> Tap / Klik untuk Pulang
                </span>
            </div>
        @else
            {{-- Status Sudah Selesai --}}
            <div class="alert alert-success border-0 rounded-4 p-3 mb-0 text-center small">
                <i class="bi bi-check-circle-fill fs-3 text-success d-block mb-1"></i>
                <strong class="d-block text-dark">Presensi Hari Ini Telah Lengkap</strong>
                <span class="text-muted">Masuk: {{ \Carbon\Carbon::parse($todayAttendance->check_in_time)->format('H:i') }} WIB &bull; Pulang: {{ \Carbon\Carbon::parse($todayAttendance->check_out_time)->format('H:i') }} WIB ({{ $todayAttendance->daily_work_hours }} Jam)</span>
            </div>
        @endif
    </div>

    {{-- Card 4: Quick Menu Grid (Sesuai Referensi Gambar Menu Attendify) --}}
    <div class="mobile-card">
        <div class="section-title">
            <span>Menu & Fitur Cepat</span>
        </div>
        <div class="row g-2 text-center">
            <div class="col-3">
                <a href="{{ route('leave.index') }}" class="action-grid-item">
                    <div class="action-icon-circle bg-primary bg-opacity-10 text-primary">
                        <i class="bi bi-envelope-paper"></i>
                    </div>
                    <span class="text-dark small fw-semibold" style="font-size: 0.72rem;">Izin / Cuti</span>
                </a>
            </div>
            <div class="col-3">
                <a href="{{ route('face.registration') }}" class="action-grid-item">
                    <div class="action-icon-circle bg-success bg-opacity-10 text-success">
                        <i class="bi bi-person-bounding-box"></i>
                    </div>
                    <span class="text-dark small fw-semibold" style="font-size: 0.72rem;">Biometrik</span>
                </a>
            </div>
            <div class="col-3">
                <a href="{{ route('profile.edit') }}" class="action-grid-item">
                    <div class="action-icon-circle bg-warning bg-opacity-10 text-warning">
                        <i class="bi bi-wallet2"></i>
                    </div>
                    <span class="text-dark small fw-semibold" style="font-size: 0.72rem;">Uang Makan</span>
                </a>
            </div>
            <div class="col-3">
                <a href="{{ route('profile.edit') }}" class="action-grid-item">
                    <div class="action-icon-circle bg-info bg-opacity-10 text-info">
                        <i class="bi bi-person-circle"></i>
                    </div>
                    <span class="text-dark small fw-semibold" style="font-size: 0.72rem;">Profil</span>
                </a>
            </div>
        </div>
    </div>

    {{-- Card 5: Kalender Jam Kerja Pribadi Mingguan --}}
    <div class="mobile-card">
        <div class="section-title">
            <span>Kalender Kehadiran Mingguan</span>
            <span class="badge badge-soft-secondary rounded-pill px-2 py-0.5" style="font-size: 0.68rem;">Senin - Minggu</span>
        </div>
        <div class="row g-1 text-center">
            @foreach($weekCalendar as $day)
                <div class="col">
                    <div class="p-1.5 rounded-3 border {{ $day->is_today ? 'bg-primary text-white border-primary shadow-sm' : ($day->is_sunday ? 'bg-danger bg-opacity-10 border-danger border-opacity-25' : 'bg-light') }}">
                        <div class="fw-semibold {{ $day->is_today ? 'text-white' : ($day->is_sunday ? 'text-danger' : 'text-muted') }}" style="font-size: 0.65rem;">{{ substr($day->day_name, 0, 3) }}</div>
                        <div class="fw-bold {{ $day->is_today ? 'text-white' : 'text-dark' }}" style="font-size: 0.72rem;">{{ $day->formatted }}</div>
                        @if($day->is_sunday)
                            <span class="badge bg-white text-danger border px-1 py-0" style="font-size: 0.6rem;">LIBUR</span>
                        @elseif($day->status === 'HADIR')
                            <div class="fw-bold {{ $day->is_today ? 'text-white' : 'text-primary' }}" style="font-size: 0.75rem;">{{ $day->hours }}j</div>
                        @else
                            <span class="badge {{ $day->is_today ? 'bg-white text-primary' : 'bg-white text-secondary border' }} px-1 py-0" style="font-size: 0.58rem;">
                                {{ substr($day->status, 0, 4) }}
                            </span>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <!-- Modal Pengajuan Cuti / Izin -->
    <div class="modal fade" id="formLeaveModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content rounded-4 border-0 p-3">
                <form action="{{ route('leave.store') }}" method="POST">
                    @csrf
                    <div class="modal-header border-0 pb-0">
                        <h5 class="modal-title fw-bold">Pengajuan Izin / Cuti</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Jenis Pengajuan</label>
                            <select name="type" class="form-select rounded-3" required>
                                <option value="cuti">Cuti Tahunan / Khusus</option>
                                <option value="sakit">Sakit (Dengan Keterangan)</option>
                            </select>
                        </div>
                        <div class="row g-2 mb-3">
                            <div class="col-6">
                                <label class="form-label small fw-semibold">Tgl Mulai</label>
                                <input type="date" name="start_date" class="form-control rounded-3" value="{{ \Carbon\Carbon::today()->toDateString() }}" required>
                            </div>
                            <div class="col-6">
                                <label class="form-label small fw-semibold">Tgl Selesai</label>
                                <input type="date" name="end_date" class="form-control rounded-3" value="{{ \Carbon\Carbon::today()->toDateString() }}" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Alasan / Keterangan</label>
                            <textarea name="reason" rows="2" class="form-control rounded-3" placeholder="Tuliskan alasan izin/cuti..." required></textarea>
                        </div>
                    </div>
                    <div class="modal-footer border-0 pt-0">
                        <button type="button" class="btn btn-light btn-rounded px-3" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary btn-rounded px-4">Kirim Pengajuan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

@endsection

@push('scripts')
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
<script src="{{ asset('js/face-api.min.js') }}"></script>
<script>
    const userHasFace = {{ $user->hasRegisteredFace() ? 'true' : 'false' }};
    const isAttendanceOpen = {{ (!$isSundayToday && (empty($todayAttendance) || ($todayAttendance->status === 'hadir' && is_null($todayAttendance->check_out_time))) && $user->hasRegisteredFace()) ? 'true' : 'false' }};
    const hasCheckedIn = {{ ($todayAttendance && $todayAttendance->lat_in && $todayAttendance->long_in) ? 'true' : 'false' }};
    const checkedInLat = {{ ($todayAttendance && $todayAttendance->lat_in) ? (float)$todayAttendance->lat_in : 'null' }};
    const checkedInLong = {{ ($todayAttendance && $todayAttendance->long_in) ? (float)$todayAttendance->long_in : 'null' }};
    const checkedInAddress = {!! json_encode($todayAttendance->address_in ?? '') !!};
    const officeLocationsData = {!! json_encode($officeLocations) !!};

    let dashboardMap = null;
    let userMarker = null;
    let officeCircles = [];

    let userLat = null;
    let userLong = null;
    let registeredDescriptor = null;
    let currentLiveDescriptor = null;
    let isFaceMatched = false;
    let detectInterval = null;

    function createDriverIcon(isCheckedIn) {
        const iconClass = isCheckedIn ? 'bi-person-check-fill' : 'bi-geo-alt-fill';
        const colorClass = isCheckedIn ? 'checked-in' : '';
        return L.divIcon({
            className: 'custom-driver-pin',
            html: `
                <div class="driver-pin-wrap">
                    <div class="driver-pin-pulse"></div>
                    <div class="driver-pin-icon ${colorClass}">
                        <i class="bi ${iconClass}"></i>
                    </div>
                </div>
            `,
            iconSize: [44, 44],
            iconAnchor: [22, 22],
            popupAnchor: [0, -24]
        });
    }

    function initDashboardMap() {
        const container = document.getElementById('dashboardMap');
        if (!container) return;

        let centerLat = -6.200000;
        let centerLng = 106.816666;
        let zoom = 15;

        if (hasCheckedIn && checkedInLat !== null && checkedInLong !== null) {
            centerLat = checkedInLat;
            centerLng = checkedInLong;
        } else if (officeLocationsData && officeLocationsData.length > 0) {
            centerLat = parseFloat(officeLocationsData[0].latitude);
            centerLng = parseFloat(officeLocationsData[0].longitude);
        }

        dashboardMap = L.map('dashboardMap', {
            zoomControl: false,
            scrollWheelZoom: true
        }).setView([centerLat, centerLng], zoom);

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '&copy; OpenStreetMap'
        }).addTo(dashboardMap);

        if (officeLocationsData && officeLocationsData.length > 0) {
            officeLocationsData.forEach(office => {
                const offLat = parseFloat(office.latitude);
                const offLng = parseFloat(office.longitude);
                const rad = parseInt(office.radius_meters) || 100;

                const officeMarker = L.marker([offLat, offLng]).addTo(dashboardMap);
                officeMarker.bindPopup(`<strong>${office.name}</strong><br><small>Radius: ${rad}m</small>`);

                const circle = L.circle([offLat, offLng], {
                    radius: rad,
                    color: '#2563eb',
                    fillColor: '#3b82f6',
                    fillOpacity: 0.15,
                    weight: 2
                }).addTo(dashboardMap);
                officeCircles.push(circle);
            });
        }

        if (hasCheckedIn && checkedInLat !== null && checkedInLong !== null) {
            userMarker = L.marker([checkedInLat, checkedInLong], {
                icon: createDriverIcon(true)
            }).addTo(dashboardMap);

            userMarker.bindPopup(`
                <div class="p-1">
                    <strong class="text-success">Lokasi Masuk Anda</strong><br>
                    <small>${checkedInAddress || 'Sesuai koordinat absensi masuk'}</small>
                </div>
            `).openPopup();
        }
    }

    function recenterDashboardMap() {
        if (!dashboardMap) return;
        if (hasCheckedIn && checkedInLat !== null && checkedInLong !== null) {
            dashboardMap.setView([checkedInLat, checkedInLong], 16);
            if (userMarker) userMarker.openPopup();
        } else if (userLat !== null && userLong !== null) {
            dashboardMap.setView([userLat, userLong], 16);
            if (userMarker) userMarker.openPopup();
        } else if (officeLocationsData && officeLocationsData.length > 0) {
            dashboardMap.setView([parseFloat(officeLocationsData[0].latitude), parseFloat(officeLocationsData[0].longitude)], 15);
        }
    }

    function initGeolocation() {
        if (!navigator.geolocation) return;

        navigator.geolocation.watchPosition(
            (pos) => {
                userLat = pos.coords.latitude;
                userLong = pos.coords.longitude;

                const badge = document.getElementById('gpsAccuracyBadge');
                if (badge) badge.innerText = `GPS Terkunci (±${Math.round(pos.coords.accuracy)}m)`;

                if (!hasCheckedIn && dashboardMap) {
                    if (!userMarker) {
                        userMarker = L.marker([userLat, userLong], {
                            icon: createDriverIcon(false)
                        }).addTo(dashboardMap);
                        dashboardMap.setView([userLat, userLong], 16);
                    } else {
                        userMarker.setLatLng([userLat, userLong]);
                    }

                    const addr = document.getElementById('userAddressDisplay');
                    if (addr) addr.innerHTML = `<i class="bi bi-geo-alt-fill text-primary me-1"></i>Lat: ${userLat.toFixed(4)}, Long: ${userLong.toFixed(4)}`;
                }

                updateButtonState();
            },
            (err) => {
                const badge = document.getElementById('gpsAccuracyBadge');
                if (badge) {
                    badge.innerText = 'GPS Mati';
                    badge.className = 'badge bg-danger text-white border px-2 py-1';
                }
            },
            { enableHighAccuracy: true, maximumAge: 5000 }
        );
    }

    async function initCameraAndAI() {
        if (!isAttendanceOpen) return;

        const video = document.getElementById('attendanceVideo');
        const overlay = document.getElementById('attendanceOverlay');
        const loadingBox = document.getElementById('attendanceCameraLoading');
        const camStatus = document.getElementById('camStatusText');
        const faceBadge = document.getElementById('faceStatusBadge');

        if (!video) return;

        try {
            if (camStatus) camStatus.innerText = 'Mengambil data biometrik...';
            const descRes = await fetch('{{ route("face.descriptor") }}');
            const descData = await descRes.json();
            if (descData.descriptor) {
                registeredDescriptor = descData.descriptor;
            }

            if (camStatus) camStatus.innerText = 'Memuat AI Wajah...';
            const MODEL_URL = '{{ asset("models") }}';
            await Promise.all([
                faceapi.nets.ssdMobilenetv1.loadFromUri(MODEL_URL),
                faceapi.nets.faceLandmark68Net.loadFromUri(MODEL_URL),
                faceapi.nets.faceRecognitionNet.loadFromUri(MODEL_URL)
            ]);

            if (camStatus) camStatus.innerText = 'Membuka kamera...';
            const stream = await navigator.mediaDevices.getUserMedia({
                video: { width: 480, height: 360, facingMode: 'user' }
            });
            video.srcObject = stream;

            video.onloadedmetadata = () => {
                if (loadingBox) loadingBox.classList.add('d-none');
                startLiveFaceDetection(video, overlay, faceBadge);
            };
        } catch (err) {
            console.error(err);
            if (camStatus) camStatus.innerText = 'Kamera/AI Gagal: ' + err.message;
        }
    }

    function calcDistance(d1, d2) {
        let sum = 0;
        for (let i = 0; i < 128; i++) {
            const diff = d1[i] - d2[i];
            sum += diff * diff;
        }
        return Math.sqrt(sum);
    }

    function startLiveFaceDetection(video, overlay, faceBadge) {
        if (!overlay) return;
        overlay.width = video.videoWidth;
        overlay.height = video.videoHeight;

        detectInterval = setInterval(async () => {
            const detection = await faceapi.detectSingleFace(video)
                .withFaceLandmarks()
                .withFaceDescriptor();

            const ctx = overlay.getContext('2d');
            ctx.clearRect(0, 0, overlay.width, overlay.height);

            if (detection && registeredDescriptor) {
                currentLiveDescriptor = Array.from(detection.descriptor);
                const distance = calcDistance(currentLiveDescriptor, registeredDescriptor);

                const dims = faceapi.matchDimensions(overlay, video, true);
                const resized = faceapi.resizeResults(detection, dims);
                faceapi.draw.drawDetections(overlay, resized);

                if (distance <= 0.55) {
                    isFaceMatched = true;
                    if (faceBadge) {
                        faceBadge.className = 'badge bg-success text-white px-3 py-1.5 rounded-pill small';
                        faceBadge.innerHTML = `<i class="bi bi-check-circle-fill me-1"></i> Cocok (${Math.round((1 - distance) * 100)}%)`;
                    }
                } else {
                    isFaceMatched = false;
                    if (faceBadge) {
                        faceBadge.className = 'badge bg-danger text-white px-3 py-1.5 rounded-pill small';
                        faceBadge.innerHTML = `<i class="bi bi-x-circle-fill me-1"></i> Wajah Tidak Cocok`;
                    }
                }
            } else {
                currentLiveDescriptor = null;
                isFaceMatched = false;
                if (faceBadge) {
                    faceBadge.className = 'badge bg-warning text-dark px-3 py-1.5 rounded-pill small';
                    faceBadge.innerHTML = `Arahkan Wajah ke Kamera`;
                }
            }

            updateButtonState();
        }, 350);
    }

    function updateButtonState() {
        const canSubmit = isFaceMatched && userLat !== null && userLong !== null;
        const btnIn = document.getElementById('btnCheckInWrapper');
        const btnOut = document.getElementById('btnCheckOutWrapper');

        if (btnIn) {
            if (canSubmit) {
                btnIn.classList.remove('disabled');
            } else {
                btnIn.classList.add('disabled');
            }
        }

        if (btnOut) {
            if (canSubmit) {
                btnOut.classList.remove('disabled');
            } else {
                btnOut.classList.add('disabled');
            }
        }
    }

    async function triggerAttendance(type) {
        const btn = document.getElementById(type === 'checkin' ? 'btnCheckInWrapper' : 'btnCheckOutWrapper');
        if (btn.classList.contains('disabled')) {
            alert('Wajah dan sinyal GPS harus valid terlebih dahulu.');
            return;
        }

        const feedback = document.getElementById('attendanceFeedback');
        btn.classList.add('disabled');

        const label = document.getElementById(type === 'checkin' ? 'checkInLabel' : 'checkOutLabel');
        const originalLabel = label.innerHTML;
        label.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Memproses Presensi...';

        const endpoint = type === 'checkin' ? '{{ route("attendance.checkin") }}' : '{{ route("attendance.checkout") }}';
        const payload = type === 'checkin' ? {
            lat_in: userLat,
            long_in: userLong,
            face_descriptor: JSON.stringify(currentLiveDescriptor)
        } : {
            lat_out: userLat,
            long_out: userLong,
            face_descriptor: JSON.stringify(currentLiveDescriptor)
        };

        try {
            const res = await fetch(endpoint, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify(payload)
            });

            const data = await res.json();
            feedback.classList.remove('d-none', 'alert-danger', 'alert-success');

            if (data.success) {
                feedback.classList.add('alert-success');
                feedback.innerHTML = `<i class="bi bi-check-circle-fill me-1"></i> ${data.message}`;
                setTimeout(() => {
                    window.location.reload();
                }, 1200);
            } else {
                feedback.classList.add('alert-danger');
                feedback.innerHTML = `<i class="bi bi-exclamation-triangle-fill me-1"></i> ${data.message}`;
                btn.classList.remove('disabled');
                label.innerHTML = originalLabel;
            }
        } catch (err) {
            feedback.classList.remove('d-none');
            feedback.classList.add('alert-danger');
            feedback.innerText = 'Terjadi kesalahan sistem: ' + err.message;
            btn.classList.remove('disabled');
            label.innerHTML = originalLabel;
        }
    }

    window.addEventListener('beforeunload', () => {
        if (detectInterval) clearInterval(detectInterval);
        const video = document.getElementById('attendanceVideo');
        if (video && video.srcObject) {
            video.srcObject.getTracks().forEach(t => t.stop());
        }
    });

    document.addEventListener('DOMContentLoaded', () => {
        initDashboardMap();
        initGeolocation();
        if (isAttendanceOpen) {
            initCameraAndAI();
        }
    });
</script>
@endpush
