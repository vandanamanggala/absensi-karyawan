@extends('layouts.app')

@section('content')
<div class="row g-4">

    <!-- Header & Navigasi ala Google Calendar -->
    <div class="col-12 d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 bg-white p-4 rounded-4 shadow-sm">
        <div>
            <a href="{{ route('owner.dashboard') }}" class="btn btn-light btn-sm rounded-pill px-3 mb-2 border text-muted">
                <i class="bi bi-arrow-left me-1"></i>Kembali
            </a>
            <h4 class="fw-bold mb-1 text-dark">Kalender Kehadiran: {{ $employee->name }}</h4>
            <p class="text-muted small mb-0">Jabatan: <span class="badge bg-light text-dark border">{{ $employee->position ?? 'Staff' }}</span></p>
        </div>

        <!-- Ringkasan Jam Kerja Hari Ini & Minggu Ini -->
        @php
            $todayStr = date('Y-m-d');
            $todayRecord = collect($calendarDays)->first(fn($d) => !empty($d->date) && $d->date == $todayStr);
            $todayHours = ($todayRecord && $todayRecord->attendance) ? $todayRecord->attendance->daily_work_hours : 0;

            // Hitung total jam minggu ini (Senin s.d. Minggu berjalan)
            $startOfWeek = \Carbon\Carbon::now()->startOfWeek();
            $endOfWeek = \Carbon\Carbon::now()->endOfWeek();
            $weeklyTotalHours = \App\Models\Attendance::where('user_id', $employee->id)
                ->whereBetween('date', [$startOfWeek, $endOfWeek])
                ->sum('daily_work_hours');
        @endphp
        <div class="d-flex gap-3">
            <div class="px-3 py-2 bg-light rounded-3 text-center border">
                <div class="small text-muted" style="font-size: 0.75rem;">Hari Ini</div>
                <div class="fw-bold text-dark fs-6">{{ $todayHours }} Jam</div>
            </div>
            <div class="px-3 py-2 bg-primary-subtle rounded-3 text-center border border-primary-subtle">
                <div class="small text-primary fw-semibold" style="font-size: 0.75rem;">Total Minggu Ini</div>
                <div class="fw-bold text-primary fs-6">{{ $weeklyTotalHours }} Jam</div>
            </div>
        </div>

        <!-- Filter Bulan & Tahun Modern -->
        <form action="{{ route('owner.employee.detail', $employee->id) }}" method="GET" class="d-flex align-items-center gap-2">
            <select name="month" class="form-select rounded-pill px-3 bg-light border-0 fw-semibold text-secondary shadow-sm" onchange="this.form.submit()">
                @for($m = 1; $m <= 12; $m++)
                    <option value="{{ $m }}" {{ $month == $m ? 'selected' : '' }}>
                        {{ Carbon\Carbon::create()->month($m)->translatedFormat('F') }}
                    </option>
                @endfor
            </select>
            <select name="year" class="form-select rounded-pill px-3 bg-light border-0 fw-semibold text-secondary shadow-sm" style="width: 110px;" onchange="this.form.submit()">
                @for($y = date('Y') - 1; $y <= date('Y') + 1; $y++)
                    <option value="{{ $y }}" {{ $year == $y ? 'selected' : '' }}>{{ $y }}</option>
                @endfor
            </select>
        </form>
    </div>

    <!-- Tampilan Kalender Style Modern Google Calendar -->
    <div class="col-12">
        <div class="card border-0 rounded-4 bg-white shadow-sm overflow-hidden">
            
            <!-- Header Nama Hari -->
            <div class="row text-center fw-bold text-muted small py-3 bg-light border-bottom m-0">
                <div class="col text-danger">Minggu</div>
                <div class="col">Senin</div>
                <div class="col">Selasa</div>
                <div class="col">Rabu</div>
                <div class="col">Kamis</div>
                <div class="col">Jumat</div>
                <div class="col text-primary">Sabtu</div>
            </div>

            <!-- Grid Tanggal -->
            <div class="row g-0 border-start border-top">
                @foreach($calendarDays as $day)
                    @if(isset($day->is_empty) && $day->is_empty)
                        <!-- Kotak Kosong -->
                        <div class="col border-end border-bottom bg-light bg-opacity-50" style="flex: 0 0 14.2857%; max-width: 14.2857%; min-height: 120px;"></div>
                    @else
                        @php
                            $isToday = (!empty($day->date) && $day->date == date('Y-m-d'));
                        @endphp
                        <!-- Kotak Tanggal Aktif -->
                        <div class="col border-end border-bottom p-2 d-flex flex-column justify-content-between position-relative bg-white" style="flex: 0 0 14.2857%; max-width: 14.2857%; min-height: 120px;">
                            
                            <!-- Angka Tanggal -->
                            <div class="d-flex justify-content-between align-items-center">
                                @if($isToday)
                                    <span class="fw-semibold text-white bg-primary rounded-circle d-inline-flex align-items-center justify-content-center" style="width: 28px; height: 28px; font-size: 0.85rem;">
                                        {{ $day->day }}
                                    </span>
                                @else
                                    <span class="fw-semibold text-dark" style="font-size: 0.9rem;">
                                        {{ $day->day }}
                                    </span>
                                @endif
                            </div>

                            <!-- Detail Event Kehadiran di Kotak -->
                            <div class="mt-2 flex-grow-1 d-flex flex-column justify-content-center">
                                @if($day->attendance)
                                    @if($day->attendance->status == 'hadir')
                                        <div class="p-2 rounded-3 bg-success-subtle border border-success-subtle text-success small shadow-sm" style="font-size: 0.7rem; line-height: 1.2;">
                                            <div class="fw-bold d-flex align-items-center gap-1">
                                                <span class="p-1 bg-success rounded-circle"></span> HADIR
                                            </div>
                                            <div class="mt-1 text-dark text-nowrap overflow-hidden">
                                                {{ \Carbon\Carbon::parse($day->attendance->check_in_time)->format('H:i') }} - {{ $day->attendance->check_out_time ? \Carbon\Carbon::parse($day->attendance->check_out_time)->format('H:i') : '...' }}
                                            </div>
                                            <div class="fw-semibold text-success mt-1">
                                                {{ $day->attendance->daily_work_hours }} Jam
                                            </div>
                                        </div>
                                    @else
                                        <div class="p-2 rounded-3 bg-warning-subtle border border-warning-subtle text-dark small" style="font-size: 0.7rem;">
                                            <div class="fw-bold text-uppercase">{{ $day->attendance->status }}</div>
                                        </div>
                                    @endif
                                @else
                                    <!-- Kosong jika tidak ada data -->
                                    <span class="text-muted opacity-25 small text-center d-block" style="font-size: 0.75rem;">-</span>
                                @endif
                            </div>

                        </div>
                    @endif
                @endforeach
            </div>

        </div>
    </div>

</div>
@endsection