@extends('layouts.app')

@section('content')
<div class="row g-4">

    <!-- Header Profil Karyawan & Filter Bulan -->
    <div class="col-12 bg-white p-4 rounded-4 shadow-sm border">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
            <div>
                <a href="{{ route('owner.dashboard') }}" class="btn btn-light btn-sm btn-rounded px-3 mb-2 border text-muted">
                    <i class="bi bi-arrow-left me-1"></i> Kembali ke Dashboard Owner
                </a>
                <h4 class="fw-bold mb-1 text-dark">Kalender Presensi: {{ $employee->name }}</h4>
                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <span class="badge bg-light text-dark border">{{ $employee->position ?? 'Staff' }}</span>
                    <span class="text-muted small"><i class="bi bi-envelope me-1"></i>{{ $employee->email }}</span>
                    @if($employee->wa_number)
                        <span class="text-success small"><i class="bi bi-whatsapp me-1"></i>{{ $employee->wa_number }}</span>
                    @endif
                </div>
            </div>

            <!-- Filter Bulan & Tahun -->
            <form action="{{ route('owner.employee.detail', $employee->id) }}" method="GET" class="d-flex align-items-center gap-2">
                <select name="month" class="form-select rounded-3 px-3 fw-semibold text-secondary shadow-sm" onchange="this.form.submit()">
                    @for($m = 1; $m <= 12; $m++)
                        <option value="{{ $m }}" {{ $month == $m ? 'selected' : '' }}>
                            {{ Carbon\Carbon::create()->month($m)->translatedFormat('F') }}
                        </option>
                    @endfor
                </select>
                <select name="year" class="form-select rounded-3 px-3 fw-semibold text-secondary shadow-sm" style="width: 110px;" onchange="this.form.submit()">
                    @for($y = date('Y') - 1; $y <= date('Y') + 1; $y++)
                        <option value="{{ $y }}" {{ $year == $y ? 'selected' : '' }}>{{ $y }}</option>
                    @endfor
                </select>
            </form>
        </div>

        <!-- Ringkasan Statistik Bulan Ini -->
        <div class="row g-3 mt-3 pt-3 border-top">
            <div class="col-6 col-md-3">
                <div class="p-3 bg-light rounded-3 text-center border">
                    <div class="text-muted small">Total Jam Kerja</div>
                    <div class="fw-bold text-dark fs-5 mt-1">{{ number_format($totalWorkHours, 2) }} Jam</div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="p-3 bg-light rounded-3 text-center border">
                    <div class="text-muted small">Hari Hadir</div>
                    <div class="fw-bold text-success fs-5 mt-1">{{ $presentDaysCount }} Hari</div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="p-3 bg-light rounded-3 text-center border">
                    <div class="text-muted small">Tarif Uang Makan</div>
                    <div class="fw-bold text-dark fs-5 mt-1">
                        @if(!empty($employee->meal_allowance) && $employee->meal_allowance > 0)
                            Rp {{ number_format($employee->meal_allowance, 0, ',', '.') }}/hari
                        @else
                            <span class="text-muted fs-6 fw-normal">Tidak Disetting</span>
                        @endif
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="p-3 bg-success bg-opacity-10 rounded-3 text-center border border-success border-opacity-25">
                    <div class="text-success fw-semibold small">Total Uang Makan Bulan Ini</div>
                    <div class="fw-bold text-success fs-5 mt-1">
                        Rp {{ number_format($totalMealAllowance, 0, ',', '.') }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Kalender Bulanan -->
    <div class="col-12">
        <div class="card border-0 rounded-4 bg-white shadow-sm overflow-hidden">
            <div class="card-header bg-white border-0 pt-4 px-4 pb-2">
                <h5 class="fw-bold text-dark mb-0">
                    Log Presensi: {{ Carbon\Carbon::create($year, $month, 1)->translatedFormat('F Y') }}
                </h5>
                <p class="text-muted small mb-0">Hari kerja Senin sampai Sabtu. Hari Minggu libur.</p>
            </div>

            <!-- Kalender Grid Responsif -->
            <div class="p-3 p-md-4">
                <div class="table-responsive">
                    <table class="table table-bordered align-middle text-center mb-0" style="min-width: 700px;">
                        <thead class="bg-light text-uppercase text-secondary" style="font-size: 0.75rem;">
                            <tr>
                                <th style="width: 70px;">Tgl</th>
                                <th style="width: 120px;">Hari</th>
                                <th style="width: 110px;">Masuk</th>
                                <th style="width: 110px;">Keluar</th>
                                <th style="width: 110px;">Durasi</th>
                                <th style="width: 130px;">Status</th>
                                <th style="width: 130px;">Uang Makan</th>
                                <th>Alamat / Keterangan</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($calendarDays as $day)
                                @php
                                    $att = $day->attendance;
                                    $isSunday = $day->is_sunday;
                                    $isToday = ($day->date == date('Y-m-d'));
                                    $mealEarned = ($att && $att->status === 'hadir' && !empty($employee->meal_allowance)) ? (float)$employee->meal_allowance : 0;
                                @endphp
                                <tr class="{{ $isToday ? 'table-primary table-opacity-10' : ($isSunday ? 'bg-light bg-opacity-50 text-muted' : '') }}">
                                    <td class="fw-bold {{ $isToday ? 'text-primary' : ($isSunday ? 'text-danger' : 'text-dark') }}">
                                        {{ $day->day }}
                                        @if($isToday)
                                            <span class="badge bg-primary text-white" style="font-size: 9px;">HARI INI</span>
                                        @endif
                                    </td>
                                    <td class="{{ $isSunday ? 'text-danger fw-semibold' : '' }}">
                                        {{ $day->day_name }}
                                    </td>
                                    <td>
                                        @if($att && $att->check_in_time)
                                            <span class="fw-semibold text-dark">{{ \Carbon\Carbon::parse($att->check_in_time)->format('H:i') }}</span>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($att && $att->check_out_time)
                                            <span class="fw-semibold text-dark">{{ \Carbon\Carbon::parse($att->check_out_time)->format('H:i') }}</span>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($att && $att->daily_work_hours > 0)
                                            <span class="fw-bold text-dark">{{ $att->daily_work_hours }} Jam</span>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($isSunday)
                                            <span class="badge bg-light text-secondary border px-2.5 py-1">LIBUR</span>
                                        @elseif($att)
                                            @if($att->status === 'hadir')
                                                <span class="badge badge-soft-success px-2.5 py-1 rounded-pill">Hadir</span>
                                            @elseif($att->status === 'cuti')
                                                <span class="badge badge-soft-primary px-2.5 py-1 rounded-pill">Cuti</span>
                                            @elseif($att->status === 'sakit')
                                                <span class="badge badge-soft-warning px-2.5 py-1 rounded-pill">Sakit</span>
                                            @elseif($att->status === 'alfa')
                                                <span class="badge badge-soft-danger px-2.5 py-1 rounded-pill">Alfa</span>
                                            @else
                                                <span class="badge badge-soft-secondary px-2.5 py-1 rounded-pill">{{ strtoupper($att->status) }}</span>
                                            @endif
                                        @else
                                            <span class="text-muted small">-</span>
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        @if($mealEarned > 0)
                                            <span class="text-success fw-bold">Rp {{ number_format($mealEarned, 0, ',', '.') }}</span>
                                        @else
                                            <span class="text-muted small">-</span>
                                        @endif
                                    </td>
                                    <td class="text-start small text-muted text-truncate" style="max-width: 250px;">
                                        @if($isSunday)
                                            Hari libur mingguan
                                        @elseif($att)
                                            {{ $att->address_in ?? $att->address_out ?? '-' }}
                                        @else
                                            -
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

</div>
@endsection
