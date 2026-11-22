<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Absensi Karyawan</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 11pt;
            color: #1e293b;
        }
        .header-title {
            font-size: 15pt;
            font-weight: bold;
            text-align: center;
            margin-bottom: 4px;
        }
        .header-subtitle {
            font-size: 10pt;
            color: #475569;
            text-align: center;
            margin-bottom: 20px;
        }
        table {
            border-collapse: collapse;
            width: 100%;
        }
        th {
            background-color: #1d4ed8;
            color: #ffffff;
            font-weight: bold;
            text-align: center;
            padding: 9px 12px;
            border: 1px solid #0f172a;
        }
        td {
            padding: 8px 12px;
            border: 1px solid #cbd5e1;
            vertical-align: middle;
        }
        .text-center { text-align: center; }
        .text-end { text-align: right; }
        .bg-odd { background-color: #f8fafc; }
        .footer-total {
            background-color: #e2e8f0;
            font-weight: bold;
            border-top: 2px solid #0f172a;
        }
    </style>
</head>
<body>

    <div class="header-title">
        @if($category === 'person')
            LAPORAN PRESENSI, UANG MAKAN & UANG LEMBUR PER KARYAWAN
        @elseif($category === 'position')
            LAPORAN REKAPITULASI PRESENSI PER JABATAN
        @elseif($category === 'late')
            RINGKASAN KARYAWAN SERING TERLAMBAT (SETELAH 08:30 WIB)
        @elseif($category === 'overtime')
            RINGKASAN JAM LEMBUR KARYAWAN (> 8 JAM / HARI)
        @else
            REKAPITULASI DETAIL PRESENSI KARYAWAN
        @endif
    </div>
    <div class="header-subtitle">Periode: {{ $periodLabel }} | Dicetak pada: {{ \Carbon\Carbon::now('Asia/Jakarta')->translatedFormat('d F Y, H:i') }} WIB</div>

    @if($category === 'person')
        <!-- Kategori 1: Per Orang Lengkap dengan Uang Makan & Uang Lembur -->
        <table border="1">
            <thead>
                <tr>
                    <th style="width: 40px;">No</th>
                    <th style="width: 180px;">Nama Karyawan</th>
                    <th style="width: 120px;">Jabatan</th>
                    <th style="width: 80px;">Hadir (Hari)</th>
                    <th style="width: 60px;">Cuti</th>
                    <th style="width: 60px;">Sakit</th>
                    <th style="width: 60px;">Alfa</th>
                    <th style="width: 90px;">Total Jam Kerja</th>
                    <th style="width: 80px;">Jam Lembur</th>
                    <th style="width: 120px;">Uang Makan (Rp)</th>
                    <th style="width: 120px;">Uang Lembur (Rp)</th>
                    <th style="width: 130px;">Total Kompensasi</th>
                </tr>
            </thead>
            <tbody>
                @php
                    $totHadir = 0; $totCuti = 0; $totSakit = 0; $totAlfa = 0; 
                    $totHours = 0; $totOvertimeHours = 0;
                    $totMeal = 0; $totOvertimePay = 0; $totIncome = 0;
                @endphp
                @forelse($personReport as $idx => $r)
                    @php
                        $totHadir += $r->hadir;
                        $totCuti += $r->cuti;
                        $totSakit += $r->sakit;
                        $totAlfa += $r->alfa;
                        $totHours += $r->total_hours;
                        $totOvertimeHours += $r->overtime_hours;
                        $totMeal += $r->meal_total;
                        $totOvertimePay += $r->overtime_total;
                        $totIncome += $r->total_income;
                    @endphp
                    <tr class="{{ $idx % 2 === 1 ? 'bg-odd' : '' }}">
                        <td class="text-center">{{ $idx + 1 }}</td>
                        <td>{{ $r->name }}</td>
                        <td>{{ $r->position }}</td>
                        <td class="text-center" style="font-weight: bold; color: #15803d;">{{ $r->hadir }}</td>
                        <td class="text-center">{{ $r->cuti }}</td>
                        <td class="text-center">{{ $r->sakit }}</td>
                        <td class="text-center" style="color: #dc2626;">{{ $r->alfa }}</td>
                        <td class="text-center">{{ $r->total_hours }} jam</td>
                        <td class="text-center">{{ $r->overtime_hours }} jam</td>
                        <td class="text-end">Rp {{ number_format($r->meal_total, 0, ',', '.') }}</td>
                        <td class="text-end">Rp {{ number_format($r->overtime_total, 0, ',', '.') }}</td>
                        <td class="text-end" style="font-weight: bold; color: #1d4ed8;">Rp {{ number_format($r->total_income, 0, ',', '.') }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="12" class="text-center">Tidak ada data karyawan.</td>
                    </tr>
                @endforelse
            </tbody>
            <tfoot>
                <tr class="footer-total">
                    <td colspan="3" class="text-center">TOTAL KESELURUHAN</td>
                    <td class="text-center">{{ $totHadir }}</td>
                    <td class="text-center">{{ $totCuti }}</td>
                    <td class="text-center">{{ $totSakit }}</td>
                    <td class="text-center">{{ $totAlfa }}</td>
                    <td class="text-center">{{ $totHours }} jam</td>
                    <td class="text-center">{{ $totOvertimeHours }} jam</td>
                    <td class="text-end">Rp {{ number_format($totMeal, 0, ',', '.') }}</td>
                    <td class="text-end">Rp {{ number_format($totOvertimePay, 0, ',', '.') }}</td>
                    <td class="text-end">Rp {{ number_format($totIncome, 0, ',', '.') }}</td>
                </tr>
            </tfoot>
        </table>

    @elseif($category === 'position')
        <!-- Kategori 2: Per Jabatan -->
        <table border="1">
            <thead>
                <tr>
                    <th style="width: 40px;">No</th>
                    <th style="width: 220px;">Nama Jabatan</th>
                    <th style="width: 120px;">Jumlah Pegawai</th>
                    <th style="width: 100px;">Total Hadir</th>
                    <th style="width: 90px;">Total Cuti</th>
                    <th style="width: 90px;">Total Sakit</th>
                    <th style="width: 90px;">Total Alfa</th>
                    <th style="width: 130px;">Akumulasi Jam Kerja</th>
                </tr>
            </thead>
            <tbody>
                @forelse($positionReport as $idx => $pr)
                    <tr class="{{ $idx % 2 === 1 ? 'bg-odd' : '' }}">
                        <td class="text-center">{{ $idx + 1 }}</td>
                        <td><strong>{{ $pr->position }}</strong></td>
                        <td class="text-center">{{ $pr->total_employees }} orang</td>
                        <td class="text-center" style="font-weight: bold; color: #15803d;">{{ $pr->hadir }}</td>
                        <td class="text-center">{{ $pr->cuti }}</td>
                        <td class="text-center">{{ $pr->sakit }}</td>
                        <td class="text-center" style="color: #dc2626;">{{ $pr->alfa }}</td>
                        <td class="text-center">{{ $pr->total_hours }} jam</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="text-center">Belum ada data jabatan terdaftar.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

    @elseif($category === 'late')
        <!-- Kategori 3: Sering Terlambat -->
        <table border="1">
            <thead>
                <tr>
                    <th style="width: 40px;">No</th>
                    <th style="width: 220px;">Nama Karyawan</th>
                    <th style="width: 140px;">Jabatan</th>
                    <th style="width: 120px;">Frekuensi Terlambat</th>
                    <th style="width: 140px;">Terakhir Terlambat</th>
                    <th style="width: 120px;">Jam Masuk Terakhir</th>
                </tr>
            </thead>
            <tbody>
                @forelse($lateSummary as $idx => $ls)
                    <tr class="{{ $idx % 2 === 1 ? 'bg-odd' : '' }}">
                        <td class="text-center">{{ $idx + 1 }}</td>
                        <td><strong>{{ $ls->name }}</strong></td>
                        <td>{{ $ls->position }}</td>
                        <td class="text-center" style="color: #dc2626; font-weight: bold;">{{ $ls->late_count }} Kali</td>
                        <td class="text-center">{{ $ls->last_late_date }}</td>
                        <td class="text-center">{{ $ls->last_check_in }} WIB</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center" style="color: #15803d; font-weight: bold;">
                            Semua karyawan hadir tepat waktu sebelum 08:30 WIB pada periode ini.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

    @elseif($category === 'overtime')
        <!-- Kategori 4: Jam Lembur Paling Banyak -->
        <table border="1">
            <thead>
                <tr>
                    <th style="width: 40px;">No</th>
                    <th style="width: 220px;">Nama Karyawan</th>
                    <th style="width: 140px;">Jabatan</th>
                    <th style="width: 140px;">Total Jam Lembur</th>
                    <th style="width: 120px;">Jumlah Hari Lembur</th>
                    <th style="width: 140px;">Total Jam Kerja Kumulatif</th>
                </tr>
            </thead>
            <tbody>
                @forelse($overtimeSummary as $idx => $ot)
                    <tr class="{{ $idx % 2 === 1 ? 'bg-odd' : '' }}">
                        <td class="text-center">{{ $idx + 1 }}</td>
                        <td><strong>{{ $ot->name }}</strong></td>
                        <td>{{ $ot->position }}</td>
                        <td class="text-center" style="color: #1d4ed8; font-weight: bold;">{{ $ot->overtime_hours }} Jam</td>
                        <td class="text-center">{{ $ot->overtime_days }} Hari</td>
                        <td class="text-center">{{ $ot->total_hours }} Jam</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center">Tidak ada catatan jam kerja lembur (> 8 jam) pada periode ini.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    @endif

</body>
</html>
