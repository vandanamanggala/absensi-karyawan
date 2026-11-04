<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;

class OwnerController extends Controller
{
    public function dashboard()
    {
        Carbon::setLocale('id');
        $today = Carbon::today();
        $startOfWeek = Carbon::now()->startOfWeek();
        $endOfWeek = Carbon::now()->endOfWeek();
        $startOfMonth = Carbon::now()->startOfMonth();
        $endOfMonth = Carbon::now()->endOfMonth();

        $employees = User::where('role', '!=', 'owner')->get();
        $todayAttendances = Attendance::with('user')->whereDate('date', $today)->get();

        // 1. Rekap Jam Kerja Karyawan
        $workHoursReport = $employees->map(function($emp) use ($today, $startOfWeek, $endOfWeek, $startOfMonth, $endOfMonth) {
            return (object)[
                'name' => $emp->name,
                'today' => Attendance::where('user_id', $emp->id)->whereDate('date', $today)->sum('daily_work_hours'),
                'weekly' => Attendance::where('user_id', $emp->id)->whereBetween('date', [$startOfWeek, $endOfWeek])->sum('daily_work_hours'),
                'monthly' => Attendance::where('user_id', $emp->id)->whereBetween('date', [$startOfMonth, $endOfMonth])->sum('daily_work_hours'),
            ];
        });

        // 2. Data Kalender Kehadiran Mingguan (Senin - Minggu)
        $weekCalendar = [];
        for ($i = 0; $i < 7; $i++) {
            $date = $startOfWeek->copy()->addDays($i);
            $weekCalendar[] = (object)[
                'day_name' => $date->translatedFormat('l'),
                'formatted' => $date->format('d M Y'),
                'is_today' => $date->isToday(),
                'attendances' => Attendance::with('user')->whereDate('date', $date)->where('status', 'hadir')->get()
            ];
        }

        return view('owner.dashboard', compact('employees', 'todayAttendances', 'workHoursReport', 'weekCalendar'));
    }

    public function setAlfa(Request $request)
    {
        $request->validate(['user_id' => 'required']);
        Attendance::create([
            'user_id' => $request->user_id,
            'date' => Carbon::today(),
            'status' => 'alfa',
        ]);
        return redirect()->back()->with('success', 'Karyawan ditandai Alfa.');
    }

    public function destroy(User $user)
    {
        $user->delete();
        return redirect()->back()->with('success', 'Akun dihapus.');
    }

    /**
     * Menampilkan detail kalender bulanan karyawan.
     */
    public function employeeDetail(Request $request, int $id)
    {
        Carbon::setLocale('id');
        $employee = User::findOrFail($id);

        $month = $request->input('month', Carbon::now()->month);
        $year = $request->input('year', Carbon::now()->year);

        $selectedDate = Carbon::createFromDate($year, $month, 1);
        $daysInMonth = $selectedDate->daysInMonth;

        $attendances = Attendance::where('user_id', $employee->id)
            ->whereYear('date', $year)
            ->whereMonth('date', $month)
            ->get()
            ->keyBy(function($item) {
                return Carbon::parse($item->date)->format('Y-m-d');
            });

        $calendarDays = [];
        for ($i = 1; $i <= $daysInMonth; $i++) {
            $date = Carbon::createFromDate($year, $month, $i);
            $dateStr = $date->format('Y-m-d');
            
            $calendarDays[] = (object)[
                'day' => $i,
                'date' => $dateStr,
                'day_name' => $date->translatedFormat('l'),
                'attendance' => $attendances[$dateStr] ?? null,
            ];
        }

        return view('owner.employee_detail', compact('employee', 'calendarDays', 'month', 'year'));
    }
}