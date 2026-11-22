@extends('layouts.app')

@section('content')

<!-- Stat Metrics Ringkas Mobile -->
<div class="row g-2 mb-3">
    <div class="col-6">
        <div class="mobile-card m-0 p-3 text-center">
            <span class="text-muted small d-block" style="font-size: 0.7rem;">Karyawan Aktif</span>
            <h3 class="fw-bold text-dark mb-0">{{ $employees->count() }}</h3>
        </div>
    </div>
    <div class="col-6">
        <div class="mobile-card m-0 p-3 text-center">
            <span class="text-muted small d-block" style="font-size: 0.7rem;">Hadir Hari Ini</span>
            <h3 class="fw-bold text-success mb-0">{{ $todayAttendances->where('status', 'hadir')->count() }}</h3>
        </div>
    </div>
    <div class="col-6">
        <div class="mobile-card m-0 p-3 text-center">
            <span class="text-muted small d-block" style="font-size: 0.7rem;">Cuti / Izin Pending</span>
            <h3 class="fw-bold {{ $pendingLeaves->count() > 0 ? 'text-warning' : 'text-dark' }} mb-0">{{ $pendingLeaves->count() }}</h3>
        </div>
    </div>
    <div class="col-6">
        <div class="mobile-card m-0 p-3 text-center">
            <span class="text-muted small d-block" style="font-size: 0.7rem;">Alfa Hari Ini</span>
            <h3 class="fw-bold text-danger mb-0">{{ $todayAttendances->where('status', 'alfa')->count() }}</h3>
        </div>
    </div>
</div>

<!-- Shortcut Aksi Owner -->
<div class="mobile-card">
    <div class="section-title">
        <span>Aksi Cepat Owner</span>
    </div>
    <div class="d-flex gap-2">
        <button type="button" class="btn btn-primary btn-rounded flex-grow-1 py-2 text-white small" data-bs-toggle="modal" data-bs-target="#addEmployeeModal">
            <i class="bi bi-person-plus-fill me-1"></i> Tambah Pegawai
        </button>
        <a href="{{ route('owner.reports') }}" class="btn btn-dark btn-rounded py-2 text-white small px-3">
            <i class="bi bi-bar-chart-fill me-1"></i> Laporan
        </a>
        <a href="{{ route('owner.attendance.export') }}" class="btn btn-success btn-rounded py-2 text-white small px-3">
            <i class="bi bi-file-earmark-excel-fill me-1"></i> Excel
        </a>
    </div>
</div>

<!-- Persetujuan Izin & Cuti Pending -->
@if($pendingLeaves->count() > 0)
<div class="mobile-card border-warning">
    <div class="section-title text-warning">
        <span>Menunggu Persetujuan ({{ $pendingLeaves->count() }})</span>
    </div>
    <div class="d-flex flex-column gap-2">
        @foreach($pendingLeaves as $leave)
            <div class="p-2.5 rounded-3 bg-light border">
                <div class="d-flex align-items-center justify-content-between mb-1">
                    <strong class="text-dark small">{{ $leave->user->name ?? 'User Dihapus' }}</strong>
                    <span class="badge {{ $leave->type === 'cuti' ? 'badge-soft-primary' : 'badge-soft-warning' }} text-uppercase" style="font-size: 0.65rem;">
                        {{ $leave->type }}
                    </span>
                </div>
                <div class="text-muted small mb-2" style="font-size: 0.72rem;">
                    {{ \Carbon\Carbon::parse($leave->start_date)->format('d M') }} s.d. {{ \Carbon\Carbon::parse($leave->end_date)->format('d M Y') }}<br>
                    <em>"{{ $leave->reason }}"</em>
                </div>
                <div class="d-flex gap-1.5 justify-content-end">
                    <form action="{{ route('owner.leave.status', $leave->id) }}" method="POST" class="d-inline">
                        @csrf
                        <input type="hidden" name="status" value="approved">
                        <button type="submit" class="btn btn-sm btn-success btn-rounded px-3 py-1" style="font-size: 0.72rem;">Setujui</button>
                    </form>
                    <form action="{{ route('owner.leave.status', $leave->id) }}" method="POST" class="d-inline">
                        @csrf
                        <input type="hidden" name="status" value="rejected">
                        <button type="submit" class="btn btn-sm btn-outline-danger btn-rounded px-3 py-1" style="font-size: 0.72rem;">Tolak</button>
                    </form>
                </div>
            </div>
        @endforeach
    </div>
</div>
@endif

<!-- Presensi Karyawan Hari Ini (Feed List Card Mobile) -->
<div class="mobile-card">
    <div class="section-title">
        <span>Log Presensi Hari Ini</span>
        <span class="badge badge-soft-secondary rounded-pill px-2 py-0.5" style="font-size: 0.7rem;">{{ $todayAttendances->count() }} Record</span>
    </div>

    <div class="d-flex flex-column gap-2">
        @forelse($todayAttendances as $att)
            <div class="p-2.5 rounded-3 border bg-light">
                <div class="d-flex align-items-center justify-content-between mb-1">
                    <div>
                        <strong class="text-dark small d-block">{{ $att->user->name ?? 'User' }}</strong>
                        <span class="text-muted" style="font-size: 0.7rem;">{{ $att->user->position ?? 'Staff' }}</span>
                    </div>
                    @if($att->status === 'hadir')
                        <span class="badge badge-soft-success px-2 py-0.5 rounded-pill" style="font-size: 0.68rem;">Hadir ({{ $att->daily_work_hours }}j)</span>
                    @elseif($att->status === 'alfa')
                        <span class="badge badge-soft-danger px-2 py-0.5 rounded-pill" style="font-size: 0.68rem;">Alfa</span>
                    @else
                        <span class="badge badge-soft-primary px-2 py-0.5 rounded-pill" style="font-size: 0.68rem;">{{ strtoupper($att->status) }}</span>
                    @endif
                </div>

                <div class="d-flex align-items-center justify-content-between text-muted pt-1 border-top" style="font-size: 0.72rem;">
                    <div>
                        <i class="bi bi-clock me-1"></i>
                        Masuk: <strong>{{ $att->check_in_time ? \Carbon\Carbon::parse($att->check_in_time)->format('H:i') : '-' }}</strong> &bull; 
                        Pulang: <strong>{{ $att->check_out_time ? \Carbon\Carbon::parse($att->check_out_time)->format('H:i') : '-' }}</strong>
                    </div>
                    @if($att->lat_in)
                        <a href="https://www.google.com/maps?q={{ $att->lat_in }},{{ $att->long_in }}" target="_blank" class="text-primary text-decoration-none fw-semibold">
                            <i class="bi bi-geo-alt"></i> Peta
                        </a>
                    @endif
                </div>
            </div>
        @empty
            <div class="text-center py-3 text-muted small">
                Belum ada data presensi hari ini.
            </div>
        @endforelse
    </div>
</div>

<!-- Modal Tambah Karyawan Mobile -->
<div class="modal fade" id="addEmployeeModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 p-3">
            <form action="{{ route('owner.employee.store') }}" method="POST">
                @csrf
                <div class="modal-header border-0 pb-0">
                    <h6 class="modal-title fw-bold">Tambah Karyawan Baru</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-2">
                        <label class="form-label small fw-semibold">Nama Lengkap</label>
                        <input type="text" name="name" class="form-control rounded-3" required>
                    </div>
                    <div class="mb-2">
                        <label class="form-label small fw-semibold">Email</label>
                        <input type="email" name="email" class="form-control rounded-3" required>
                    </div>
                    <div class="mb-2">
                        <label class="form-label small fw-semibold">Password Awal</label>
                        <input type="password" name="password" class="form-control rounded-3" required>
                    </div>
                    <div class="row g-2 mb-2">
                        <div class="col-6">
                            <label class="form-label small fw-semibold">Jabatan</label>
                            <input type="text" name="position" class="form-control rounded-3" placeholder="Staff">
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-semibold">Uang Makan / Hari</label>
                            <input type="number" name="meal_allowance" class="form-control rounded-3" placeholder="0">
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-light btn-rounded px-3" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary btn-rounded px-4">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection
