@extends('layouts.app')

@section('content')
<div class="row g-4">

    <!-- Statistik Ringkas Atas -->
    <div class="col-12">
        <div class="row g-3">
            <div class="col-6 col-xl-3">
                <div class="card border-0 shadow-sm rounded-4 bg-white p-3 h-100">
                    <div class="text-muted small fw-semibold text-uppercase mb-1">Total Tim</div>
                    <h3 class="fw-bold text-dark mb-0">{{ $employees->count() }}</h3>
                </div>
            </div>
            <div class="col-6 col-xl-3">
                <div class="card border-0 shadow-sm rounded-4 bg-white p-3 h-100">
                    <div class="text-muted small fw-semibold text-uppercase mb-1">Hadir Hari Ini</div>
                    <h3 class="fw-bold text-success mb-0">{{ $todayAttendances->where('status', 'hadir')->count() }}</h3>
                </div>
            </div>
            <div class="col-6 col-xl-3">
                <div class="card border-0 shadow-sm rounded-4 bg-white p-3 h-100">
                    <div class="text-muted small fw-semibold text-uppercase mb-1">Alfa / Tidak Hadir</div>
                    <h3 class="fw-bold text-danger mb-0">{{ $todayAttendances->where('status', 'alfa')->count() }}</h3>
                </div>
            </div>
            <div class="col-6 col-xl-3">
                <div class="card border-0 shadow-sm rounded-4 bg-white p-3 h-100">
                    <div class="text-muted small fw-semibold text-uppercase mb-1">Izin / Cuti</div>
                    <h3 class="fw-bold text-warning mb-0">0</h3>
                </div>
            </div>
        </div>
    </div>

    <!-- Laporan Rekap Jam Kerja (Lembur) -->
    <div class="col-12">
        <div class="card border-0 rounded-4 bg-white shadow-sm overflow-hidden">
            <div class="card-header bg-white border-0 pt-4 px-4 pb-2">
                <h5 class="fw-bold text-dark mb-0">Laporan Rekap Jam Kerja (Lembur)</h5>
                <p class="text-muted small mb-0">Perhitungan akumulasi jam kerja harian, mingguan, dan bulanan staf.</p>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light text-uppercase text-secondary" style="font-size: 0.75rem; letter-spacing: 0.5px;">
                        <tr>
                            <th class="ps-4 py-3">Nama Karyawan</th>
                            <th class="py-3 text-center">Hari Ini</th>
                            <th class="py-3 text-center text-primary">Minggu Ini</th>
                            <th class="py-3 text-center text-success pe-4">Bulan Ini</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($workHoursReport as $report)
                            <tr>
                                <td class="ps-4 py-3 fw-bold text-dark">{{ $report->name }}</td>
                                <td class="py-3 text-center fw-semibold text-secondary">{{ $report->today }} Jam</td>
                                <td class="py-3 text-center fw-bold text-primary bg-primary-subtle bg-opacity-10">{{ $report->weekly }} Jam</td>
                                <td class="py-3 text-center fw-bold text-success pe-4">{{ $report->monthly }} Jam</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Presensi Hari Ini (Responsif: Card di HP, Tabel di Desktop) -->
    <div class="col-12">
        <div class="card border-0 rounded-4 bg-white shadow-sm p-4">
            <div class="d-flex flex-column mb-3">
                <h5 class="fw-bold text-dark mb-1">Presensi Hari Ini</h5>
                <p class="text-muted small mb-0">Log aktivitas masuk dan keluar karyawan secara *real-time*.</p>
            </div>

            <!-- Tampilan untuk Mobile (Card Stack) -->
            <div class="d-block d-md-none">
                @forelse($todayAttendances as $attendance)
                    <div class="p-3 mb-3 border rounded-4 bg-light bg-opacity-25 shadow-xs">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <div>
                                <h6 class="fw-bold text-dark mb-0">{{ $attendance->user->name ?? 'User Dihapus' }}</h6>
                                <span class="badge bg-light text-dark border px-2 py-0" style="font-size: 0.7rem;">
                                    {{ $attendance->user->position ?? 'Staff' }}
                                </span>
                            </div>
                            <div>
                                @if($attendance->status == 'hadir')
                                    <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-2 py-1" style="font-size: 0.7rem;">Hadir</span>
                                @elseif($attendance->status == 'alfa')
                                    <span class="badge bg-danger-subtle text-danger border border-danger-subtle rounded-pill px-2 py-1" style="font-size: 0.7rem;">Alfa</span>
                                @else
                                    <span class="badge bg-warning-subtle text-warning border border-warning-subtle rounded-pill px-2 py-1" style="font-size: 0.7rem;">{{ strtoupper($attendance->status) }}</span>
                                @endif
                            </div>
                        </div>

                        <div class="row g-2 small text-secondary my-2 border-top border-bottom py-2">
                            <div class="col-6">
                                <div><strong>Masuk:</strong> {{ $attendance->check_in_time ? \Carbon\Carbon::parse($attendance->check_in_time)->format('H:i') : '-' }}</div>
                                @if($attendance->lat_in)
                                    <a href="https://www.google.com/maps?q={{ $attendance->lat_in }},{{ $attendance->long_in }}" target="_blank" class="text-primary text-decoration-none d-block text-truncate mt-1">
                                        <i class="bi bi-geo-alt-fill"></i> {{ $attendance->address_in ?? 'Peta Masuk' }}
                                    </a>
                                @endif
                            </div>
                            <div class="col-6">
                                <div><strong>Keluar:</strong> {{ $attendance->check_out_time ? \Carbon\Carbon::parse($attendance->check_out_time)->format('H:i') : '-' }}</div>
                                @if($attendance->lat_out)
                                    <a href="https://www.google.com/maps?q={{ $attendance->lat_out }},{{ $attendance->long_out }}" target="_blank" class="text-danger text-decoration-none d-block text-truncate mt-1">
                                        <i class="bi bi-geo-alt-fill"></i> {{ $attendance->address_out ?? 'Peta Keluar' }}
                                    </a>
                                @endif
                            </div>
                        </div>

                        <div class="d-flex justify-content-between align-items-center pt-1">
                            <span class="text-muted small">Durasi Kerja:</span>
                            <span class="fw-bold text-dark">{{ $attendance->daily_work_hours }} Jam</span>
                        </div>
                    </div>
                @empty
                    <div class="text-center text-muted py-4">Belum ada data presensi hari ini.</div>
                @endforelse
            </div>

            <!-- Tampilan untuk Desktop (Tabel Standar) -->
            <div class="d-none d-md-block table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light text-uppercase text-secondary" style="font-size: 0.75rem; letter-spacing: 0.5px;">
                        <tr>
                            <th class="ps-4 py-3">Karyawan</th>
                            <th class="py-3">Masuk</th>
                            <th class="py-3">Keluar</th>
                            <th class="py-3">Lokasi & Alamat Masuk</th>
                            <th class="py-3">Lokasi & Alamat Keluar</th>
                            <th class="py-3">Status</th>
                            <th class="py-3 pe-4 text-end">Durasi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($todayAttendances as $attendance)
                            <tr>
                                <td class="ps-4 py-3">
                                    <div class="fw-bold text-dark">{{ $attendance->user->name ?? 'User Dihapus' }}</div>
                                    <div class="text-muted small">
                                        <span class="badge bg-light text-dark border px-2 py-0" style="font-size: 0.7rem;">
                                            {{ $attendance->user->position ?? 'Staff' }}
                                        </span>
                                    </div>
                                </td>
                                <td class="py-3 fw-semibold text-secondary">{{ $attendance->check_in_time ? \Carbon\Carbon::parse($attendance->check_in_time)->format('H:i') : '-' }}</td>
                                <td class="py-3 fw-semibold text-secondary">{{ $attendance->check_out_time ? \Carbon\Carbon::parse($attendance->check_out_time)->format('H:i') : '-' }}</td>
                                <td class="py-3">
                                    @if($attendance->lat_in)
                                        <a href="https://www.google.com/maps?q={{ $attendance->lat_in }},{{ $attendance->long_in }}" target="_blank" class="btn btn-sm btn-light border rounded-pill px-2 py-0.5 text-primary mb-1 fw-semibold" style="font-size: 0.75rem;">
                                            <i class="bi bi-geo-alt-fill me-1"></i>Peta Masuk
                                        </a>
                                        <div class="text-muted small text-truncate" style="max-width: 200px; font-size: 0.75rem;" title="{{ $attendance->address_in }}">
                                            {{ $attendance->address_in ?? 'Alamat tidak terdeteksi' }}
                                        </div>
                                    @else
                                        <span class="text-muted small">-</span>
                                    @endif
                                </td>
                                <td class="py-3">
                                    @if($attendance->lat_out)
                                        <a href="https://www.google.com/maps?q={{ $attendance->lat_out }},{{ $attendance->long_out }}" target="_blank" class="btn btn-sm btn-light border rounded-pill px-2 py-0.5 text-danger mb-1 fw-semibold" style="font-size: 0.75rem;">
                                            <i class="bi bi-geo-alt-fill me-1"></i>Peta Keluar
                                        </a>
                                        <div class="text-muted small text-truncate" style="max-width: 200px; font-size: 0.75rem;" title="{{ $attendance->address_out }}">
                                            {{ $attendance->address_out ?? 'Alamat tidak terdeteksi' }}
                                        </div>
                                    @else
                                        <span class="text-muted small">-</span>
                                    @endif
                                </td>
                                <td class="py-3">
                                    @if($attendance->status == 'hadir')
                                        <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-3 py-1 fw-semibold">Hadir</span>
                                    @elseif($attendance->status == 'alfa')
                                        <span class="badge bg-danger-subtle text-danger border border-danger-subtle rounded-pill px-3 py-1 fw-semibold">Alfa</span>
                                    @else
                                        <span class="badge bg-warning-subtle text-warning border border-warning-subtle rounded-pill px-3 py-1 fw-semibold">{{ strtoupper($attendance->status) }}</span>
                                    @endif
                                </td>
                                <td class="py-3 pe-4 text-end fw-bold text-dark">
                                    {{ $attendance->daily_work_hours }} Jam
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted py-4">Belum ada data presensi hari ini.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Daftar Tim Karyawan (Responsif: Card di HP, Tabel di Desktop) -->
    <div class="col-12">
        <div class="card border-0 rounded-4 bg-white shadow-sm p-4">
            <div class="d-flex flex-column mb-3">
                <h5 class="fw-bold text-dark mb-1">Daftar Tim Karyawan</h5>
                <p class="text-muted small mb-0">Klik pada nama karyawan untuk membuka kalender kehadiran bulanan.</p>
            </div>

            <!-- Tampilan Mobile (Card Stack) -->
            <div class="d-block d-md-none">
                @forelse($employees as $employee)
                    <div class="p-3 mb-3 border rounded-4 bg-light bg-opacity-25 d-flex justify-content-between align-items-center">
                        <div>
                            <a href="{{ route('owner.employee.detail', $employee->id) }}" class="fw-bold text-dark text-decoration-none d-block mb-1">
                                {{ $employee->name }} <i class="bi bi-box-arrow-up-right small text-primary"></i>
                            </a>
                            <div class="text-muted small mb-1">{{ $employee->email }}</div>
                            <span class="badge bg-light text-dark border px-2 py-0.5" style="font-size: 0.7rem;">{{ $employee->position ?? 'Staff' }}</span>
                        </div>
                        <div>
                            <form action="{{ route('owner.user.destroy', $employee->id) }}" method="POST" onsubmit="return confirm('Hapus akun karyawan ini?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-outline-danger btn-sm rounded-pill px-2 py-1" style="font-size: 0.75rem;">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </form>
                        </div>
                    </div>
                @empty
                    <div class="text-center text-muted py-4">Belum ada karyawan terdaftar.</div>
                @endforelse
            </div>

            <!-- Tampilan Desktop (Tabel Standar) -->
            <div class="d-none d-md-block table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light text-uppercase text-secondary" style="font-size: 0.75rem; letter-spacing: 0.5px;">
                        <tr>
                            <th class="ps-4 py-3">Nama Karyawan</th>
                            <th class="py-3">Email</th>
                            <th class="py-3">Jabatan</th>
                            <th class="py-3 pe-4 text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($employees as $employee)
                            <tr>
                                <td class="ps-4 py-3 fw-bold text-dark">
                                    <a href="{{ route('owner.employee.detail', $employee->id) }}" class="text-decoration-none text-primary d-inline-flex align-items-center gap-1">
                                        {{ $employee->name }} <i class="bi bi-box-arrow-up-right small"></i>
                                    </a>
                                </td>
                                <td class="py-3 text-muted">{{ $employee->email }}</td>
                                <td class="py-3">
                                    <span class="badge bg-light text-dark border px-2 py-1">{{ $employee->position ?? 'Staff' }}</span>
                                </td>
                                <td class="pe-4 py-3 text-end">
                                    <form action="{{ route('owner.user.destroy', $employee->id) }}" method="POST" onsubmit="return confirm('Hapus akun karyawan ini?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-light btn-sm rounded-pill px-3 border text-danger fw-semibold">
                                            <i class="bi bi-trash me-1"></i>Hapus
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center text-muted py-4">Belum ada karyawan terdaftar.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>
@endsection