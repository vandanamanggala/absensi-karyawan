<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Leave;
use App\Models\User;
use App\Services\AttendanceService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class OwnerController extends Controller
{
    public function dashboard(Request $request)
    {
        Carbon::setLocale('id');

        AttendanceService::runAutomatedChecks();

        $today = Carbon::today();
        $startOfWeek = Carbon::now()->startOfWeek();
        $endOfWeek = Carbon::now()->endOfWeek();
        $startOfMonth = Carbon::now()->startOfMonth();
        $endOfMonth = Carbon::now()->endOfMonth();

        $employees = User::where('role', '!=', 'owner')->latest()->get();
        $todayAttendances = Attendance::with('user')->whereDate('date', $today)->get();

        $pendingLeaves = Leave::with('user')->where('status', 'pending')->latest()->get();
        $recentLeaves = Leave::with('user')->latest()->take(10)->get();

        $workHoursReport = $employees->map(function ($emp) use ($today, $startOfWeek, $endOfWeek, $startOfMonth, $endOfMonth) {
            $monthPresent = Attendance::where('user_id', $emp->id)
                ->where('status', 'hadir')
                ->whereBetween('date', [$startOfMonth, $endOfMonth])
                ->count();

            $mealTotal = $emp->calculateMealAllowance($startOfMonth, $endOfMonth);

            return (object)[
                'id' => $emp->id,
                'name' => $emp->name,
                'position' => $emp->position ?? 'Staff',
                'meal_allowance' => $emp->meal_allowance ?? 0,
                'month_present' => $monthPresent,
                'meal_total_month' => $mealTotal,
                'today' => Attendance::where('user_id', $emp->id)->whereDate('date', $today)->sum('daily_work_hours'),
                'weekly' => Attendance::where('user_id', $emp->id)->whereBetween('date', [$startOfWeek, $endOfWeek])->sum('daily_work_hours'),
                'monthly' => Attendance::where('user_id', $emp->id)->whereBetween('date', [$startOfMonth, $endOfMonth])->sum('daily_work_hours'),
            ];
        });

        $weekCalendar = [];
        for ($i = 0; $i < 7; $i++) {
            $date = $startOfWeek->copy()->addDays($i);
            $weekCalendar[] = (object)[
                'day_name' => $date->translatedFormat('l'),
                'formatted' => $date->format('d M Y'),
                'is_today' => $date->isToday(),
                'is_sunday' => $date->isSunday(),
                'attendances' => Attendance::with('user')->whereDate('date', $date)->where('status', 'hadir')->get()
            ];
        }

        return view('owner.dashboard', compact(
            'employees',
            'todayAttendances',
            'pendingLeaves',
            'recentLeaves',
            'workHoursReport',
            'weekCalendar'
        ));
    }

    public function storeEmployee(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:6',
            'position' => 'nullable|string|max:255',
            'meal_allowance' => 'nullable|numeric|min:0',
        ]);

        User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => 'employee',
            'position' => $request->position ?? 'Staff',
            'meal_allowance' => $request->meal_allowance ?? 0,
        ]);

        return redirect()->back()->with('success', 'Karyawan baru berhasil ditambahkan!');
    }

    public function updateEmployee(Request $request, User $user)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'position' => 'nullable|string|max:255',
            'meal_allowance' => 'nullable|numeric|min:0',
            'password' => 'nullable|string|min:6',
        ]);

        $data = [
            'name' => $request->name,
            'email' => $request->email,
            'position' => $request->position,
            'meal_allowance' => $request->meal_allowance ?? 0,
        ];

        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }

        $user->update($data);

        return redirect()->back()->with('success', 'Data karyawan berhasil diperbarui!');
    }

    public function destroy(User $user)
    {
        if ($user->role === 'owner') {
            return redirect()->back()->with('error', 'Akun owner tidak dapat dihapus.');
        }

        $user->delete();
        return redirect()->back()->with('success', 'Karyawan berhasil dinonaktifkan/dihapus.');
    }

    public function setAlfa(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'date' => 'required|date',
        ]);

        Attendance::updateOrCreate(
            ['user_id' => $request->user_id, 'date' => $request->date],
            ['status' => 'alfa', 'daily_work_hours' => 0]
        );

        return redirect()->back()->with('success', 'Status presensi berhasil diatur menjadi Alfa.');
    }

    public function employeeDetail(Request $request, int $id)
    {
        Carbon::setLocale('id');
        $employee = User::findOrFail($id);

        $month = (int) $request->input('month', Carbon::now()->month);
        $year = (int) $request->input('year', Carbon::now()->year);

        $selectedDate = Carbon::createFromDate($year, $month, 1);
        $daysInMonth = $selectedDate->daysInMonth;
        $startOfMonth = $selectedDate->copy()->startOfMonth();
        $endOfMonth = $selectedDate->copy()->endOfMonth();

        $attendances = Attendance::where('user_id', $employee->id)
            ->whereYear('date', $year)
            ->whereMonth('date', $month)
            ->get()
            ->keyBy(function ($item) {
                return Carbon::parse($item->date)->format('Y-m-d');
            });

        $calendarDays = [];
        $presentDaysCount = 0;
        $totalWorkHours = 0;

        for ($i = 1; $i <= $daysInMonth; $i++) {
            $date = Carbon::createFromDate($year, $month, $i);
            $dateStr = $date->format('Y-m-d');
            $att = $attendances[$dateStr] ?? null;

            if ($att && $att->status === 'hadir') {
                $presentDaysCount++;
                $totalWorkHours += (float) $att->daily_work_hours;
            }

            $calendarDays[] = (object)[
                'day' => $i,
                'date' => $dateStr,
                'day_name' => $date->translatedFormat('l'),
                'is_sunday' => $date->isSunday(),
                'attendance' => $att,
            ];
        }

        $totalMealAllowance = $employee->calculateMealAllowance($startOfMonth->toDateString(), $endOfMonth->toDateString());

        return view('owner.employee_detail', compact(
            'employee',
            'calendarDays',
            'month',
            'year',
            'presentDaysCount',
            'totalWorkHours',
            'totalMealAllowance'
        ));
    }

    /**
     * Helper untuk memproses periode dan data analitik laporan
     */
    private function getReportData(Request $request): array
    {
        Carbon::setLocale('id');

        $period = $request->input('period', 'monthly'); // monthly, weekly
        $category = $request->input('category', 'person'); // person, position, late, overtime
        $userId = $request->input('user_id');
        $position = $request->input('position');

        $today = Carbon::today();

        if ($period === 'weekly') {
            $startDate = $today->copy()->startOfWeek();
            $endDate = $today->copy()->endOfWeek();
            $periodLabel = 'Minggu Ini (' . $startDate->format('d M') . ' - ' . $endDate->format('d M Y') . ')';
        } else {
            $startDate = $today->copy()->startOfMonth();
            $endDate = $today->copy()->endOfMonth();
            $periodLabel = 'Bulan Ini (' . $today->translatedFormat('F Y') . ')';
        }

        $employeesQuery = User::where('role', '!=', 'owner');
        if (!empty($userId)) {
            $employeesQuery->where('id', $userId);
        }
        if (!empty($position)) {
            $employeesQuery->where('position', $position);
        }
        $employees = $employeesQuery->orderBy('name')->get();
        $positions = User::where('role', '!=', 'owner')->whereNotNull('position')->distinct()->pluck('position');

        $attendances = Attendance::with('user')
            ->whereBetween('date', [$startDate->toDateString(), $endDate->toDateString()])
            ->when(!empty($userId), function ($q) use ($userId) {
                $q->where('user_id', $userId);
            })
            ->when(!empty($position), function ($q) use ($position) {
                $q->whereHas('user', function ($uq) use ($position) {
                    $uq->where('position', $position);
                });
            })
            ->get();

        // 1. Rekap Per Orang
        $personReport = $employees->map(function ($emp) use ($attendances, $startDate, $endDate) {
            $userAtts = $attendances->where('user_id', $emp->id);
            $hadir = $userAtts->where('status', 'hadir')->count();
            $cuti = $userAtts->where('status', 'cuti')->count();
            $sakit = $userAtts->where('status', 'sakit')->count();
            $alfa = $userAtts->where('status', 'alfa')->count();
            $totalHours = (float) $userAtts->sum('daily_work_hours');
            $mealTotal = $emp->calculateMealAllowance($startDate->toDateString(), $endDate->toDateString());
            $overtimeData = $emp->calculateOvertimePay($startDate->toDateString(), $endDate->toDateString());

            return (object)[
                'user_id' => $emp->id,
                'name' => $emp->name,
                'position' => $emp->position ?? 'Staff',
                'hadir' => $hadir,
                'cuti' => $cuti,
                'sakit' => $sakit,
                'alfa' => $alfa,
                'total_hours' => $totalHours,
                'meal_total' => $mealTotal,
                'overtime_hours' => $overtimeData['hours'],
                'overtime_rate' => $overtimeData['rate'],
                'overtime_total' => $overtimeData['total_pay'],
                'total_income' => ($mealTotal + $overtimeData['total_pay']),
            ];
        });

        // 2. Rekap Per Jabatan
        $positionReport = $positions->map(function ($pos) use ($attendances) {
            $posAtts = $attendances->filter(function ($att) use ($pos) {
                return $att->user && $att->user->position === $pos;
            });

            $hadir = $posAtts->where('status', 'hadir')->count();
            $cuti = $posAtts->where('status', 'cuti')->count();
            $sakit = $posAtts->where('status', 'sakit')->count();
            $alfa = $posAtts->where('status', 'alfa')->count();
            $totalHours = (float) $posAtts->sum('daily_work_hours');
            $totalEmp = User::where('role', '!=', 'owner')->where('position', $pos)->count();

            return (object)[
                'position' => $pos,
                'total_employees' => $totalEmp,
                'hadir' => $hadir,
                'cuti' => $cuti,
                'sakit' => $sakit,
                'alfa' => $alfa,
                'total_hours' => $totalHours,
            ];
        });

        // 3. Ringkasan Karyawan Sering Terlambat (Masuk setelah pukul 08:30)
        $lateRecords = $attendances->filter(function ($att) {
            if ($att->status !== 'hadir' || empty($att->check_in_time)) return false;
            $in = Carbon::parse($att->check_in_time);
            return $in->hour > 8 || ($in->hour === 8 && $in->minute > 30);
        });

        $lateSummary = $employees->map(function ($emp) use ($lateRecords) {
            $empLate = $lateRecords->where('user_id', $emp->id);
            $lateCount = $empLate->count();
            $latestLate = $empLate->sortByDesc('date')->first();

            return (object)[
                'user_id' => $emp->id,
                'name' => $emp->name,
                'position' => $emp->position ?? 'Staff',
                'late_count' => $lateCount,
                'last_late_date' => $latestLate ? Carbon::parse($latestLate->date)->format('d M Y') : '-',
                'last_check_in' => $latestLate ? Carbon::parse($latestLate->check_in_time)->format('H:i') : '-',
            ];
        })->filter(function ($item) {
            return $item->late_count > 0;
        })->sortByDesc('late_count')->values();

        // 4. Ringkasan Jam Lembur Paling Banyak (Jam kerja > 8 jam/hari)
        $overtimeSummary = $employees->map(function ($emp) use ($attendances) {
            $userAtts = $attendances->where('user_id', $emp->id)->where('status', 'hadir');
            $overtimeHours = 0;
            $overtimeDays = 0;

            foreach ($userAtts as $att) {
                $hrs = (float) $att->daily_work_hours;
                if ($hrs > 8) {
                    $overtimeHours += ($hrs - 8);
                    $overtimeDays++;
                }
            }

            return (object)[
                'user_id' => $emp->id,
                'name' => $emp->name,
                'position' => $emp->position ?? 'Staff',
                'overtime_hours' => round($overtimeHours, 2),
                'overtime_days' => $overtimeDays,
                'total_hours' => round((float) $userAtts->sum('daily_work_hours'), 2),
            ];
        })->filter(function ($item) {
            return $item->overtime_hours > 0;
        })->sortByDesc('overtime_hours')->values();

        return [
            'period' => $period,
            'category' => $category,
            'periodLabel' => $periodLabel,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'employees' => $employees,
            'positions' => $positions,
            'userId' => $userId,
            'selectedPosition' => $position,
            'personReport' => $personReport,
            'positionReport' => $positionReport,
            'lateSummary' => $lateSummary,
            'overtimeSummary' => $overtimeSummary,
            'attendances' => $attendances,
        ];
    }

    /**
     * Halaman laporan interaktif di website
     */
    public function reports(Request $request)
    {
        $data = $this->getReportData($request);
        return view('owner.reports', $data);
    }

    /**
     * Ekspor data laporan ke format Excel (.xls)
     */
    public function exportAttendance(Request $request)
    {
        $data = $this->getReportData($request);
        $category = $data['category'];
        $period = $data['period'];

        $filename = 'laporan_' . $category . '_' . $period . '_' . date('Ymd_His') . '.xls';

        return response()->view('owner.export_excel', $data, 200, [
            'Content-Type' => 'application/vnd.ms-excel; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'Pragma' => 'no-cache',
            'Expires' => '0',
        ]);
    }
}
