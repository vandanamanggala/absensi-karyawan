@extends('layouts.app')

@push('styles')
<style>
    /* Styling khusus kartu & tabel laporan agar lega dan tidak berdesakan */
    .report-employee-card {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 1rem;
        padding: 1.15rem 1.25rem;
        margin-bottom: 0.85rem;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04);
        transition: border-color 0.15s ease;
    }
    .report-employee-card:hover {
        border-color: #cbd5e1;
    }
    .stat-pill {
        display: inline-flex;
        align-items: center;
        padding: 0.25rem 0.65rem;
        border-radius: 0.5rem;
        font-size: 0.75rem;
        font-weight: 600;
    }
    .money-box {
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 0.75rem;
        padding: 0.6rem 0.85rem;
    }
</style>
@endpush

@section('content')

<!-- Header Panel Laporan -->
<div class="mobile-card mb-3">
    <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
        <div>
            <h5 class="fw-bold text-dark mb-0">Laporan & Rekapitulasi Presensi</h5>
            <span class="text-muted small">Analisis kehadiran, keterlambatan, jam & uang lembur</span>
        </div>
        <a href="{{ route('owner.attendance.export', request()->all()) }}" class="btn btn-sm btn-success btn-rounded px-3.5 py-2 text-white shadow-sm d-inline-flex align-items-center gap-1.5">
            <i class="bi bi-file-earmark-excel-fill"></i> Unduh Excel
        </a>
    </div>

    <!-- Filter Form -->
    <form method="GET" action="{{ route('owner.reports') }}" class="pt-2 border-top">
        <div class="row g-2">
            <!-- Pilihan Periode -->
            <div class="col-6">
                <label class="form-label small fw-semibold text-muted mb-1" style="font-size: 0.75rem;">Periode Waktu</label>
                <select name="period" class="form-select form-select-sm rounded-3 py-2" onchange="this.form.submit()">
                    <option value="monthly" {{ $period === 'monthly' ? 'selected' : '' }}>Bulanan (Bulan Ini)</option>
                    <option value="weekly" {{ $period === 'weekly' ? 'selected' : '' }}>Mingguan (Minggu Ini)</option>
                </select>
            </div>

            <!-- Pilihan Kategori -->
            <div class="col-6">
                <label class="form-label small fw-semibold text-muted mb-1" style="font-size: 0.75rem;">Kategori Analisis</label>
                <select name="category" class="form-select form-select-sm rounded-3 py-2" onchange="this.form.submit()">
                    <option value="person" {{ $category === 'person' ? 'selected' : '' }}>1. Per Orang</option>
                    <option value="position" {{ $category === 'position' ? 'selected' : '' }}>2. Per Jabatan</option>
                    <option value="late" {{ $category === 'late' ? 'selected' : '' }}>3. Sering Terlambat</option>
                    <option value="overtime" {{ $category === 'overtime' ? 'selected' : '' }}>4. Jam Lembur Terbanyak</option>
                </select>
            </div>
        </div>
    </form>
</div>

<!-- Tampilan Laporan Berdasarkan Kategori yang Dipilih -->

@if($category === 'person')
    <!-- 1. Kategori: Rekap Kehadiran Per Orang -->
    <div class="mobile-card">
        <div class="section-title mb-3">
            <span class="fs-6">Rekap Presensi & Uang Lembur Per Orang</span>
            <span class="badge badge-soft-primary rounded-pill px-2.5 py-1" style="font-size: 0.72rem;">{{ $periodLabel }}</span>
        </div>

        <div class="d-flex flex-column gap-3">
            @forelse($personReport as $p)
                <div class="report-employee-card">
                    <!-- Baris Atas: Nama, Posisi & Status Hadir -->
                    <div class="d-flex align-items-start justify-content-between mb-2.5 gap-2">
                        <div>
                            <h6 class="fw-bold text-dark mb-0.5" style="font-size: 0.95rem;">{{ $p->name }}</h6>
                            <span class="badge bg-light text-secondary border px-2 py-0.5 rounded-2" style="font-size: 0.72rem;">
                                {{ $p->position }}
                            </span>
                        </div>
                        <div class="text-end">
                            <span class="badge badge-soft-success px-2.5 py-1 rounded-pill fw-bold" style="font-size: 0.75rem;">
                                <i class="bi bi-check2-circle me-1"></i>{{ $p->hadir }} Hari Hadir
                            </span>
                            <div class="text-muted small mt-1" style="font-size: 0.75rem;">
                                Total: <strong>{{ $p->total_hours }} Jam</strong>
                            </div>
                        </div>
                    </div>

                    <!-- Baris Rincian Kehadiran: Cuti, Sakit, Alfa -->
                    <div class="d-flex align-items-center gap-2 mb-3 flex-wrap" style="font-size: 0.75rem;">
                        <span class="stat-pill bg-light text-muted border">
                            Cuti: <strong class="ms-1 text-dark">{{ $p->cuti }}</strong>
                        </span>
                        <span class="stat-pill bg-light text-muted border">
                            Sakit: <strong class="ms-1 text-dark">{{ $p->sakit }}</strong>
                        </span>
                        <span class="stat-pill {{ $p->alfa > 0 ? 'bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25' : 'bg-light text-muted border' }}">
                            Alfa: <strong class="ms-1 {{ $p->alfa > 0 ? 'text-danger' : 'text-dark' }}">{{ $p->alfa }}</strong>
                        </span>
                        @if($p->overtime_hours > 0)
                            <span class="stat-pill bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25">
                                <i class="bi bi-clock-history me-1"></i>Lembur: <strong class="ms-1">{{ $p->overtime_hours }} Jam</strong>
                            </span>
                        @endif
                    </div>

                    <!-- Baris Keuangan: Uang Makan & Total Uang Lembur -->
                    <div class="row g-2 pt-2.5 border-top">
                        <div class="col-6">
                            <div class="money-box">
                                <span class="text-muted small d-block mb-0.5" style="font-size: 0.7rem;">Uang Makan</span>
                                <strong class="text-success small" style="font-size: 0.85rem;">
                                    Rp {{ number_format($p->meal_total, 0, ',', '.') }}
                                </strong>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="money-box">
                                <span class="text-muted small d-block mb-0.5" style="font-size: 0.7rem;">
                                    Uang Lembur ({{ $p->overtime_hours }}j)
                                </span>
                                <strong class="text-primary small" style="font-size: 0.85rem;">
                                    Rp {{ number_format($p->overtime_total, 0, ',', '.') }}
                                </strong>
                            </div>
                        </div>
                    </div>
                </div>
            @empty
                <div class="text-center py-4 text-muted small">Tidak ada data karyawan.</div>
            @endforelse
        </div>
    </div>

@elseif($category === 'position')
    <!-- 2. Kategori: Rekap Kehadiran Per Jabatan -->
    <div class="mobile-card">
        <div class="section-title mb-3">
            <span class="fs-6">Rekap Kehadiran Per Jabatan</span>
            <span class="badge badge-soft-primary rounded-pill px-2.5 py-1" style="font-size: 0.72rem;">{{ $periodLabel }}</span>
        </div>

        <div class="d-flex flex-column gap-2.5">
            @forelse($positionReport as $pos)
                <div class="report-employee-card">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <div>
                            <h6 class="fw-bold text-dark mb-0.5 d-flex align-items-center gap-1.5" style="font-size: 0.95rem;">
                                <i class="bi bi-briefcase text-primary"></i> {{ $pos->position }}
                            </h6>
                            <span class="text-muted small" style="font-size: 0.75rem;">{{ $pos->total_employees }} Karyawan Terdaftar</span>
                        </div>
                        <span class="badge badge-soft-success px-2.5 py-1 rounded-pill fw-bold" style="font-size: 0.78rem;">
                            {{ $pos->hadir }}x Hadir
                        </span>
                    </div>

                    <div class="d-flex align-items-center justify-content-between pt-2 border-top text-muted small" style="font-size: 0.75rem;">
                        <span>Cuti: <strong>{{ $pos->cuti }}</strong> &bull; Sakit: <strong>{{ $pos->sakit }}</strong> &bull; Alfa: <strong class="text-danger">{{ $pos->alfa }}</strong></span>
                        <span>Akumulasi: <strong class="text-dark">{{ $pos->total_hours }} Jam</strong></span>
                    </div>
                </div>
            @empty
                <div class="text-center py-4 text-muted small">Belum ada data jabatan.</div>
            @endforelse
        </div>
    </div>

@elseif($category === 'late')
    <!-- 3. Kategori: Karyawan Sering Terlambat -->
    <div class="mobile-card">
        <div class="section-title mb-3">
            <span class="fs-6">Karyawan Sering Terlambat</span>
            <span class="badge badge-soft-danger rounded-pill px-2.5 py-1" style="font-size: 0.72rem;">Lewat 08:30 WIB</span>
        </div>

        <div class="d-flex flex-column gap-2.5">
            @forelse($lateSummary as $idx => $late)
                <div class="report-employee-card">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <div class="d-flex align-items-center gap-2.5">
                            <div class="rounded-circle bg-danger text-white d-flex align-items-center justify-content-center fw-bold" style="width: 32px; height: 32px; font-size: 0.8rem;">
                                {{ $idx + 1 }}
                            </div>
                            <div>
                                <h6 class="fw-bold text-dark mb-0.5" style="font-size: 0.95rem;">{{ $late->name }}</h6>
                                <span class="text-muted small" style="font-size: 0.72rem;">{{ $late->position }}</span>
                            </div>
                        </div>
                        <span class="badge badge-soft-danger px-2.5 py-1 rounded-pill fw-bold" style="font-size: 0.78rem;">
                            {{ $late->late_count }}x Terlambat
                        </span>
                    </div>

                    <div class="d-flex align-items-center justify-content-between pt-2 border-top text-muted small" style="font-size: 0.75rem;">
                        <span>Terakhir: <strong>{{ $late->last_late_date }}</strong></span>
                        <span class="text-danger fw-semibold">Pukul {{ $late->last_check_in }} WIB</span>
                    </div>
                </div>
            @empty
                <div class="text-center py-4 text-muted small">
                    <i class="bi bi-emoji-smile fs-3 d-block text-success mb-2"></i>
                    <strong>Semua Karyawan Tepat Waktu!</strong><br>
                    Tidak ada yang tercatat terlambat (> 08:30 WIB) pada periode ini.
                </div>
            @endforelse
        </div>
    </div>

@elseif($category === 'overtime')
    <!-- 4. Kategori: Jam Lembur Paling Banyak -->
    <div class="mobile-card">
        <div class="section-title mb-3">
            <span class="fs-6">Karyawan Jam Lembur Terbanyak</span>
            <span class="badge badge-soft-primary rounded-pill px-2.5 py-1" style="font-size: 0.72rem;">> 8 Jam / Hari</span>
        </div>

        <div class="d-flex flex-column gap-2.5">
            @forelse($overtimeSummary as $idx => $ot)
                <div class="report-employee-card">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <div class="d-flex align-items-center gap-2.5">
                            <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center fw-bold" style="width: 32px; height: 32px; font-size: 0.8rem;">
                                {{ $idx + 1 }}
                            </div>
                            <div>
                                <h6 class="fw-bold text-dark mb-0.5" style="font-size: 0.95rem;">{{ $ot->name }}</h6>
                                <span class="text-muted small" style="font-size: 0.72rem;">{{ $ot->position }}</span>
                            </div>
                        </div>
                        <span class="badge badge-soft-primary px-2.5 py-1 rounded-pill fw-bold" style="font-size: 0.78rem;">
                            +{{ $ot->overtime_hours }} Jam Lembur
                        </span>
                    </div>

                    <div class="d-flex align-items-center justify-content-between pt-2 border-top text-muted small" style="font-size: 0.75rem;">
                        <span>Frekuensi: <strong>{{ $ot->overtime_days }} Hari Lembur</strong></span>
                        <span>Total Kerja: <strong class="text-dark">{{ $ot->total_hours }} Jam</strong></span>
                    </div>
                </div>
            @empty
                <div class="text-center py-4 text-muted small">
                    <i class="bi bi-clock-history fs-3 d-block text-secondary mb-2"></i>
                    Belum ada jam lembur (> 8 jam kerja) yang tercatat pada periode ini.
                </div>
            @endforelse
        </div>
    </div>
@endif

@endsection
