<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\OfficeLocation;
use App\Models\Setting;
use App\Services\AttendanceService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;

class AttendanceController extends Controller
{
    public function index()
    {
        Carbon::setLocale('id');

        // Run automated checks: auto-checkout for past/open records & auto-alfa past 11:00 AM
        AttendanceService::runAutomatedChecks();

        $user = Auth::user();
        $userId = $user->id;
        $today = Carbon::today();
        $startOfWeek = Carbon::now()->startOfWeek();
        $endOfWeek = Carbon::now()->endOfWeek();
        $startOfMonth = Carbon::now()->startOfMonth();
        $endOfMonth = Carbon::now()->endOfMonth();

        $todayAttendance = Attendance::where('user_id', $userId)->whereDate('date', $today)->first();
        $weeklyHours = Attendance::where('user_id', $userId)->whereBetween('date', [$startOfWeek, $endOfWeek])->sum('daily_work_hours');
        $monthlyHours = Attendance::where('user_id', $userId)->whereBetween('date', [$startOfMonth, $endOfMonth])->sum('daily_work_hours');

        // Total Uang Makan yang berhasil diperoleh bulan ini
        $monthlyMealAllowance = $user->calculateMealAllowance($startOfMonth, $endOfMonth);
        $totalPresentMonth = Attendance::where('user_id', $userId)->where('status', 'hadir')->whereBetween('date', [$startOfMonth, $endOfMonth])->count();

        // Data Kalender Jam Kerja Pribadi Mingguan (Senin - Minggu)
        $weekCalendar = [];
        for ($i = 0; $i < 7; $i++) {
            $date = $startOfWeek->copy()->addDays($i);
            $att = Attendance::where('user_id', $userId)->whereDate('date', $date)->first();
            $isSunday = $date->isSunday();

            $statusText = '-';
            if ($isSunday) {
                $statusText = 'LIBUR';
            } elseif ($att) {
                $statusText = strtoupper($att->status);
            }

            $weekCalendar[] = (object)[
                'day_name' => $date->translatedFormat('l'),
                'formatted' => $date->format('d M'),
                'is_today' => $date->isToday(),
                'is_sunday' => $isSunday,
                'hours' => $att ? $att->daily_work_hours : 0,
                'status' => $statusText
            ];
        }

        $officeLocations = OfficeLocation::where('is_active', true)->get();
        $isLocationRequired = Setting::get('location_verification_enabled', '1') === '1';
        $isSundayToday = $today->isSunday();

        return view('attendance.index', compact(
            'todayAttendance',
            'weeklyHours',
            'monthlyHours',
            'monthlyMealAllowance',
            'totalPresentMonth',
            'weekCalendar',
            'officeLocations',
            'isLocationRequired',
            'user',
            'isSundayToday'
        ));
    }

    private function getAddressFromCoords(?string $lat, ?string $long): ?string
    {
        if (!$lat || !$long) return null;
        try {
            $response = Http::withoutVerifying()->timeout(3)->withHeaders([
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

    private function validateLocation(float $lat, float $long): array
    {
        $isLocationRequired = Setting::get('location_verification_enabled', '1') === '1';
        if (!$isLocationRequired) {
            return ['valid' => true, 'office' => null, 'distance' => 0, 'required_radius' => 0];
        }

        $locations = OfficeLocation::where('is_active', true)->get();

        if ($locations->isEmpty()) {
            return ['valid' => true, 'office' => null, 'distance' => 0, 'required_radius' => 0];
        }

        $minDistance = null;
        $closestOffice = null;

        foreach ($locations as $office) {
            $dist = $office->calculateDistance($lat, $long);
            if ($dist <= $office->radius_meters) {
                return ['valid' => true, 'office' => $office, 'distance' => $dist];
            }

            if ($minDistance === null || $dist < $minDistance) {
                $minDistance = $dist;
                $closestOffice = $office;
            }
        }

        return [
            'valid' => false,
            'office' => $closestOffice,
            'distance' => $minDistance,
            'required_radius' => $closestOffice ? $closestOffice->radius_meters : 100
        ];
    }

    private function euclideanDistance(array $desc1, array $desc2): float
    {
        $sum = 0.0;
        for ($i = 0; $i < 128; $i++) {
            $diff = $desc1[$i] - $desc2[$i];
            $sum += $diff * $diff;
        }
        return sqrt($sum);
    }

    public function checkIn(Request $request)
    {
        $now = Carbon::now('Asia/Jakarta');

        // Check if Sunday
        if ($now->isSunday()) {
            return response()->json([
                'success' => false,
                'message' => 'Hari Minggu adalah hari libur kerja. Presensi kehadiran tidak aktif.'
            ], 422);
        }

        $user = Auth::user();
        $userId = $user->id;
        $today = $now->toDateString();

        // Check existing attendance status
        $existing = Attendance::where('user_id', $userId)->whereDate('date', $today)->first();
        if ($existing) {
            if ($existing->status === 'cuti') {
                return response()->json([
                    'success' => false,
                    'message' => 'Anda sedang dalam masa izin cuti hari ini.'
                ], 422);
            }
            if ($existing->status === 'sakit') {
                return response()->json([
                    'success' => false,
                    'message' => 'Anda berstatus izin sakit hari ini.'
                ], 422);
            }
            if ($existing->status === 'alfa') {
                return response()->json([
                    'success' => false,
                    'message' => 'Batas waktu presensi masuk telah terlewat (lewat jam 11:00 siang). Anda tercatat Alfa.'
                ], 422);
            }
            return response()->json([
                'success' => false,
                'message' => 'Anda sudah absen masuk hari ini.'
            ], 422);
        }

        // Check if past 11:00 AM cutoff
        if ($now->hour >= 11) {
            Attendance::create([
                'user_id' => $userId,
                'date' => $today,
                'status' => 'alfa',
                'daily_work_hours' => 0,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Waktu presensi masuk telah melewati batas jam 11:00 siang. Anda otomatis dinyatakan Alfa (tidak hadir).'
            ], 422);
        }

        $request->validate([
            'lat_in' => 'required|numeric',
            'long_in' => 'required|numeric',
            'face_descriptor' => 'nullable|string',
        ]);

        if (!$user->hasRegisteredFace()) {
            return response()->json([
                'success' => false,
                'message' => 'Anda wajib mendaftarkan wajah di dashboard terlebih dahulu sebelum dapat absen.'
            ], 422);
        }

        if ($request->filled('face_descriptor')) {
            $currentDescriptor = json_decode($request->face_descriptor, true);
            $registeredDescriptor = json_decode($user->face_descriptor, true);

            if (is_array($currentDescriptor) && is_array($registeredDescriptor)) {
                $distance = $this->euclideanDistance($currentDescriptor, $registeredDescriptor);
                if ($distance > 0.55) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Wajah tidak cocok dengan data biometrik terdaftar! (Jarak akurasi: ' . round($distance, 2) . ')'
                    ], 422);
                }
            }
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Data pengenalan wajah tidak ditemukan. Harap pastikan wajah terdeteksi kamera.'
            ], 422);
        }

        $locationCheck = $this->validateLocation((float)$request->lat_in, (float)$request->long_in);
        if (!$locationCheck['valid']) {
            $officeName = $locationCheck['office'] ? $locationCheck['office']->name : 'Kantor';
            $radius = $locationCheck['required_radius'];
            $currentDist = round($locationCheck['distance']);
            return response()->json([
                'success' => false,
                'message' => "Anda berada di luar area {$officeName}! Jarak Anda: {$currentDist}m (Maksimal: {$radius}m)."
            ], 422);
        }

        $attendance = Attendance::create([
            'user_id' => $userId,
            'date' => $today,
            'check_in_time' => $now->toTimeString(),
            'lat_in' => (string)$request->lat_in,
            'long_in' => (string)$request->long_in,
            'address_in' => $this->getAddressFromCoords((string)$request->lat_in, (string)$request->long_in),
            'status' => 'hadir',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Presensi Masuk Berhasil! Wajah dan lokasi terverifikasi valid.',
            'attendance' => $attendance
        ]);
    }

    public function checkOut(Request $request)
    {
        $now = Carbon::now('Asia/Jakarta');

        if ($now->isSunday()) {
            return response()->json([
                'success' => false,
                'message' => 'Hari Minggu adalah hari libur kerja.'
            ], 422);
        }

        $request->validate([
            'lat_out' => 'required|numeric',
            'long_out' => 'required|numeric',
            'face_descriptor' => 'nullable|string',
        ]);

        $user = Auth::user();

        if (!$user->hasRegisteredFace()) {
            return response()->json([
                'success' => false,
                'message' => 'Anda wajib mendaftarkan wajah di dashboard terlebih dahulu.'
            ], 422);
        }

        if ($request->filled('face_descriptor')) {
            $currentDescriptor = json_decode($request->face_descriptor, true);
            $registeredDescriptor = json_decode($user->face_descriptor, true);

            if (is_array($currentDescriptor) && is_array($registeredDescriptor)) {
                $distance = $this->euclideanDistance($currentDescriptor, $registeredDescriptor);
                if ($distance > 0.55) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Wajah tidak cocok dengan data biometrik terdaftar!'
                    ], 422);
                }
            }
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Data pengenalan wajah tidak ditemukan.'
            ], 422);
        }

        $locationCheck = $this->validateLocation((float)$request->lat_out, (float)$request->long_out);
        if (!$locationCheck['valid']) {
            $officeName = $locationCheck['office'] ? $locationCheck['office']->name : 'Kantor';
            $radius = $locationCheck['required_radius'];
            $currentDist = round($locationCheck['distance']);
            return response()->json([
                'success' => false,
                'message' => "Anda berada di luar area {$officeName}! Jarak Anda: {$currentDist}m (Maksimal: {$radius}m)."
            ], 422);
        }

        $userId = $user->id;
        $today = $now->toDateString();
        $attendance = Attendance::where('user_id', $userId)->whereDate('date', $today)->first();

        if (!$attendance) {
            return response()->json([
                'success' => false,
                'message' => 'Data absen masuk tidak ditemukan untuk hari ini.'
            ], 422);
        }

        if ($attendance->check_out_time) {
            return response()->json([
                'success' => false,
                'message' => 'Anda sudah melakukan absen keluar hari ini.'
            ], 422);
        }

        $checkIn = Carbon::parse($attendance->check_in_time);
        $checkOut = $now;
        $workHours = round($checkOut->diffInMinutes($checkIn) / 60, 2);

        $attendance->update([
            'check_out_time' => $checkOut->toTimeString(),
            'lat_out' => (string)$request->lat_out,
            'long_out' => (string)$request->long_out,
            'address_out' => $this->getAddressFromCoords((string)$request->lat_out, (string)$request->long_out),
            'daily_work_hours' => $workHours,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Presensi Keluar Berhasil! Terima kasih atas kerja keras Anda hari ini.',
            'attendance' => $attendance
        ]);
    }
}
