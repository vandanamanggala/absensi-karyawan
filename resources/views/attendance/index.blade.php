@extends('layouts.app')

@section('content')
<div class="row g-4 max-width-md mx-auto">

    {{-- Alert Notification --}}
    @if(session('success'))
        <div class="col-12">
            <div class="alert alert-success border-0 rounded-4 shadow-sm mb-0 d-flex align-items-center" role="alert">
                <i class="bi bi-check-circle-fill me-2 fs-5"></i>
                <div>{{ session('success') }}</div>
            </div>
        </div>
    @endif

    @if(session('error'))
        <div class="col-12">
            <div class="alert alert-danger border-0 rounded-4 shadow-sm mb-0 d-flex align-items-center" role="alert">
                <i class="bi bi-exclamation-triangle-fill me-2 fs-5"></i>
                <div>{{ session('error') }}</div>
            </div>
        </div>
    @endif

    {{-- Welcome Header Card --}}
    <div class="col-12">
        <div class="card bg-primary text-white border-0 rounded-4 p-3 p-lg-4 shadow-sm">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <span class="badge bg-white text-primary rounded-pill mb-2 px-3 py-2 fw-semibold">
                        {{ Auth::user()->position ?? 'Karyawan' }}
                    </span>
                    <h3 class="fw-bold mb-1">Halo, {{ Auth::user()->name }}! 👋</h3>
                    <p class="mb-0 text-white-50 small">Selamat beraktivitas hari ini.</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Kalender Jam Kerja Pribadi Mingguan --}}
    <div class="col-12 mt-2">
        <h6 class="fw-bold text-dark mb-3">Kalender Jam Kerja (Minggu Ini)</h6>
        <div class="d-flex flex-nowrap overflow-auto gap-3 pb-3" style="scrollbar-width: thin;">
            @foreach($weekCalendar as $day)
                <div class="card border-0 rounded-4 shadow-sm flex-shrink-0 {{ $day->is_today ? 'bg-primary text-white border-primary' : 'bg-white' }}" style="width: 140px;">
                    <div class="card-body p-3 text-center">
                        <div class="small fw-bold text-uppercase mb-1 opacity-75">{{ $day->day_name }}</div>
                        <div class="fw-bold mb-2">{{ $day->formatted }}</div>
                        
                        @if($day->status == 'HADIR')
                            <h4 class="fw-black mb-0 {{ $day->is_today ? 'text-white' : 'text-primary' }}">{{ $day->hours }}</h4>
                            <span class="small opacity-75">Jam</span>
                        @else
                            <div class="mt-2">
                                <span class="badge {{ $day->is_today ? 'bg-white text-primary' : 'bg-light text-muted' }} rounded-pill">{{ $day->status }}</span>
                            </div>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    </div>
    
    {{-- Quick Stats Grid --}}
    <div class="col-6">
        <div class="card border-0 rounded-4 p-3 bg-white shadow-sm">
            <span class="text-muted small fw-medium">Jam Kerja Hari Ini</span>
            <h2 class="fw-bold text-dark mt-2 mb-0">{{ $todayAttendance->daily_work_hours ?? '0' }} <small class="fs-6 text-muted fw-normal">Jam</small></h2>
        </div>
    </div>
    <div class="col-6">
        <div class="card border-0 rounded-4 p-3 bg-white shadow-sm">
            <span class="text-muted small fw-medium">Minggu Ini</span>
            <h2 class="fw-bold text-primary mt-2 mb-0">{{ $weeklyHours ?? 0 }} <small class="fs-6 text-muted fw-normal">Jam</small></h2>
        </div>
    </div>

    {{-- Main Action Card (Presensi Geolocation) --}}
    <div class="col-12">
        <div class="card border-0 rounded-4 p-4 text-center bg-white shadow-sm">
            <h5 class="fw-bold mb-1">Presensi Kehadiran</h5>
            <p class="text-muted small mb-4">Pastikan lokasi/GPS pada HP atau laptop Anda aktif untuk verifikasi posisi</p>

            @if(empty($todayAttendance))
                {{-- Form Absen Masuk --}}
                <form action="{{ route('attendance.checkin') }}" method="POST" id="form-checkin">
                    @csrf
                    <input type="hidden" name="lat_in" id="lat_in">
                    <input type="hidden" name="long_in" id="long_in">
                    <input type="hidden" name="address_in" id="address_in">
                    
                    <button type="button" onclick="getLocation('checkin')" class="btn btn-primary btn-lg rounded-4 px-5 py-3 fw-bold w-100 w-sm-auto shadow-sm">
                        <i class="bi bi-geo-alt-fill me-2"></i>Absen Masuk
                    </button>
                </form>
            @elseif(is_null($todayAttendance->check_out_time))
                {{-- Form Absen Keluar --}}
                <div class="badge bg-light text-dark mb-3 p-2 px-3 border rounded-pill">
                    Jam Masuk: <strong>{{ \Carbon\Carbon::parse($todayAttendance->check_in_time)->format('H:i') }} WIB</strong>
                </div>
                <br>
                <form action="{{ route('attendance.checkout') }}" method="POST" id="form-checkout">
                    @csrf
                    <input type="hidden" name="lat_out" id="lat_out">
                    <input type="hidden" name="long_out" id="long_out">
                    <input type="hidden" name="address_out" id="address_out">
                    
                    <button type="button" onclick="getLocation('checkout')" class="btn btn-danger btn-lg rounded-4 px-5 py-3 fw-bold w-100 w-sm-auto shadow-sm">
                        <i class="bi bi-box-arrow-right me-2"></i>Absen Keluar
                    </button>
                </form>
            @else
                {{-- Status Selesai --}}
                <div class="alert alert-success border-0 rounded-4 mb-0 py-3">
                    <i class="bi bi-check-circle-fill me-2"></i>Presensi hari ini telah selesai
                </div>
            @endif
        </div>
    </div>

    {{-- Form Pengajuan Cuti / Sakit --}}
    <div class="col-12">
        <div class="card border-0 rounded-4 p-4 bg-white shadow-sm">
            <h6 class="fw-bold text-dark mb-1">Form Pengajuan Cuti / Sakit</h6>
            <p class="text-muted small mb-3">Kirimkan permohonan ke Owner untuk dikonfirmasi</p>
            
            <form action="{{ route('leave.store') }}" method="POST">
                @csrf
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label small fw-semibold text-muted">Tipe Izin</label>
                        <select name="type" class="form-select rounded-3" required>
                            <option value="sakit">Sakit</option>
                            <option value="cuti">Cuti</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-semibold text-muted">Tanggal Mulai</label>
                        <input type="date" name="start_date" class="form-control rounded-3" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-semibold text-muted">Tanggal Selesai</label>
                        <input type="date" name="end_date" class="form-control rounded-3" required>
                    </div>
                    <div class="col-12">
                        <label class="form-label small fw-semibold text-muted">Alasan</label>
                        <textarea name="reason" rows="2" class="form-control rounded-3" placeholder="Tuliskan alasan pengajuan secara jelas..." required></textarea>
                    </div>
                    <div class="col-12">
                        <button type="submit" class="btn btn-dark rounded-3 px-4 fw-semibold">Kirim Pengajuan</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection

<script>
    function getLocation(type) {
        if (!navigator.geolocation) {
            alert('Browser Anda tidak mendukung Geolocation.');
            return;
        }

        const isCheckin = type === 'checkin';
        
        navigator.geolocation.getCurrentPosition(
            (pos) => {
                const lat = pos.coords.latitude;
                const long = pos.coords.longitude;

                document.getElementById('lat_' + (isCheckin ? 'in' : 'out')).value = lat;
                document.getElementById('long_' + (isCheckin ? 'in' : 'out')).value = long;

                // Langsung submit form koordinat
                document.getElementById('form-' + type).submit();
            },
            (err) => {
                alert('Gagal mengambil lokasi GPS. Mohon aktifkan izin akses lokasi di browser Anda.');
            },
            { enableHighAccuracy: true, timeout: 10000 }
        );
    }
</script>