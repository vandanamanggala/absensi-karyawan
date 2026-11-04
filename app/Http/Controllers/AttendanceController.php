<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;

class AttendanceController extends Controller
{
    public function index()
    {
        Carbon::setLocale('id');
        $userId = Auth::id();
        $today = Carbon::today();
        $startOfWeek = Carbon::now()->startOfWeek();
        $endOfWeek = Carbon::now()->endOfWeek();

        $todayAttendance = Attendance::where('user_id', $userId)->whereDate('date', $today)->first();
        $weeklyHours = Attendance::where('user_id', $userId)->whereBetween('date', [$startOfWeek, $endOfWeek])->sum('daily_work_hours');

        // Data Kalender Jam Kerja Pribadi Mingguan
        $weekCalendar = [];
        for ($i = 0; $i < 7; $i++) {
            $date = $startOfWeek->copy()->addDays($i);
            $att = Attendance::where('user_id', $userId)->whereDate('date', $date)->first();
            $weekCalendar[] = (object)[
                'day_name' => $date->translatedFormat('l'),
                'formatted' => $date->format('d M'),
                'is_today' => $date->isToday(),
                'hours' => $att ? $att->daily_work_hours : 0,
                'status' => $att ? strtoupper($att->status) : '-'
            ];
        }

        return view('attendance.index', compact('todayAttendance', 'weeklyHours', 'weekCalendar'));
    }

    private function getAddressFromCoords(?string $lat, ?string $long): ?string
    {
        if (!$lat || !$long) return null;
        try {
            $response = Http::withoutVerifying()->withHeaders([
                'User-Agent' => 'AbsensiApp/1.0 (contact@perusahaan.com)'
            ])->get("https://nominatim.openstreetmap.org/reverse", [
                'format' => 'jsonv2', 'lat' => $lat, 'lon' => $long, 'accept-language' => 'id',
            ]);

            if ($response->successful()) {
                $data = $response->json();
                if (isset($data['address'])) {
                    $a = $data['address'];
                    $road = $a['road'] ?? $a['pedestrian'] ?? $a['suburb'] ?? $a['amenity'] ?? '';
                    $village = $a['village'] ?? $a['suburb'] ?? $a['quarter'] ?? $a['neighbourhood'] ?? '';
                    $district = $a['subdistrict'] ?? $a['city_district'] ?? $a['county'] ?? '';
                    $city = $a['city'] ?? $a['regency'] ?? $a['town'] ?? '';
                    $state = $a['state'] ?? '';

                    $full = [];
                    if ($road) $full[] = $road;
                    if ($village) $full[] = 'Kel. ' . $village;
                    if ($district) $full[] = 'Kec. ' . $district;
                    if ($city) $full[] = $city;
                    if ($state) $full[] = 'Prov. ' . $state;

                    return count($full) > 0 ? implode(', ', $full) : ($data['display_name'] ?? null);
                }
            }
        } catch (\Exception $e) { return null; }
        return null;
    }

    public function checkIn(Request $request)
    {
        $request->validate(['lat_in' => 'nullable|string', 'long_in' => 'nullable|string']);
        $userId = Auth::id();
        $today = Carbon::today()->toDateString();

        if (Attendance::where('user_id', $userId)->whereDate('date', $today)->first()) {
            return redirect()->back()->with('error', 'Anda sudah absen masuk hari ini.');
        }

        Attendance::create([
            'user_id' => $userId,
            'date' => $today,
            'check_in_time' => Carbon::now()->toTimeString(),
            'lat_in' => $request->lat_in,
            'long_in' => $request->long_in,
            'address_in' => $this->getAddressFromCoords($request->lat_in, $request->long_in),
            'status' => 'hadir',
        ]);
        return redirect()->back()->with('success', 'Berhasil Absen Masuk!');
    }

    public function checkOut(Request $request)
    {
        $request->validate(['lat_out' => 'nullable|string', 'long_out' => 'nullable|string']);
        $userId = Auth::id();
        $today = Carbon::today()->toDateString();
        $attendance = Attendance::where('user_id', $userId)->whereDate('date', $today)->first();

        if (!$attendance) return redirect()->back()->with('error', 'Data absen masuk tidak ditemukan.');
        if ($attendance->check_out_time) return redirect()->back()->with('error', 'Anda sudah absen keluar.');

        $checkIn = Carbon::parse($attendance->check_in_time);
        $checkOut = Carbon::now();
        $workHours = round($checkOut->diffInMinutes($checkIn) / 60, 2);

        $attendance->update([
            'check_out_time' => $checkOut->toTimeString(),
            'lat_out' => $request->lat_out,
            'long_out' => $request->long_out,
            'address_out' => $this->getAddressFromCoords($request->lat_out, $request->long_out),
            'daily_work_hours' => $workHours,
        ]);
        return redirect()->back()->with('success', 'Berhasil Absen Keluar!');
    }
}