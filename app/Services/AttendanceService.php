<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\Leave;
use App\Models\User;
use Carbon\Carbon;

class AttendanceService
{
    /**
     * Run automated attendance maintenance: auto-checkout at 23:00 and auto-alfa at 11:00.
     */
    public static function runAutomatedChecks(): void
    {
        self::autoCheckOutPendingAttendances();
        self::autoMarkAlfaForWorkdays();
    }

    /**
     * Automatically closes any open check-in at 23:00 (11 PM) or past days.
     */
    public static function autoCheckOutPendingAttendances(): void
    {
        $now = Carbon::now('Asia/Jakarta');
        $todayStr = $now->toDateString();

        // Previous days with unclosed check-in
        $pastAttendances = Attendance::where('date', '<', $todayStr)
            ->whereNotNull('check_in_time')
            ->whereNull('check_out_time')
            ->get();

        foreach ($pastAttendances as $attendance) {
            self::closeAttendance($attendance, '23:00:00', 'Auto Check-Out (Sistem)');
        }

        // Today: if current time is 23:00 or later
        if ($now->hour >= 23) {
            $todayOpen = Attendance::where('date', $todayStr)
                ->whereNotNull('check_in_time')
                ->whereNull('check_out_time')
                ->get();

            foreach ($todayOpen as $attendance) {
                self::closeAttendance($attendance, '23:00:00', 'Auto Check-Out 23:00 (Sistem)');
            }
        }
    }

    /**
     * Helper to close an attendance record and calculate daily work hours.
     */
    private static function closeAttendance(Attendance $attendance, string $closeTime, string $note): void
    {
        $checkIn = Carbon::parse($attendance->date . ' ' . $attendance->check_in_time);
        $checkOut = Carbon::parse($attendance->date . ' ' . $closeTime);

        $hours = max(0, round($checkIn->diffInMinutes($checkOut) / 60, 2));

        $attendance->update([
            'check_out_time' => $closeTime,
            'daily_work_hours' => $hours,
            'address_out' => $note,
        ]);
    }

    /**
     * Automatically marks active employees as alfa if not checked in by 11:00 AM (Mon - Sat).
     * Sunday is off (libur) and excluded.
     */
    public static function autoMarkAlfaForWorkdays(): void
    {
        $now = Carbon::now('Asia/Jakarta');

        // Sunday is holiday, do not mark alfa
        if ($now->isSunday()) {
            return;
        }

        // Only run after 11:00 AM
        if ($now->hour < 11) {
            return;
        }

        $todayStr = $now->toDateString();
        $employees = User::where('role', '!=', 'owner')->get();

        foreach ($employees as $employee) {
            $exists = Attendance::where('user_id', $employee->id)
                ->where('date', $todayStr)
                ->exists();

            if (!$exists) {
                // Check if employee has an approved leave covering today
                $approvedLeave = Leave::where('user_id', $employee->id)
                    ->where('status', 'approved')
                    ->where('start_date', '<=', $todayStr)
                    ->where('end_date', '>=', $todayStr)
                    ->first();

                if ($approvedLeave) {
                    Attendance::create([
                        'user_id' => $employee->id,
                        'date' => $todayStr,
                        'status' => $approvedLeave->type,
                        'daily_work_hours' => 0,
                    ]);
                } else {
                    Attendance::create([
                        'user_id' => $employee->id,
                        'date' => $todayStr,
                        'status' => 'alfa',
                        'daily_work_hours' => 0,
                    ]);
                }
            }
        }
    }

    /**
     * Synchronize attendances when a leave request is approved.
     * Marks every non-Sunday workday in the leave range as cuti or sakit.
     */
    public static function applyApprovedLeave(Leave $leave): void
    {
        $start = Carbon::parse($leave->start_date);
        $end = Carbon::parse($leave->end_date);

        for ($date = $start->copy(); $date->lte($end); $date->addDay()) {
            if ($date->isSunday()) {
                continue;
            }

            $dateStr = $date->toDateString();

            Attendance::updateOrCreate(
                [
                    'user_id' => $leave->user_id,
                    'date' => $dateStr,
                ],
                [
                    'status' => $leave->type,
                    'daily_work_hours' => 0,
                ]
            );
        }
    }

    /**
     * Reverts auto-generated attendances if a leave is cancelled or rejected.
     */
    public static function revertLeaveAttendances(Leave $leave): void
    {
        $start = Carbon::parse($leave->start_date);
        $end = Carbon::parse($leave->end_date);

        for ($date = $start->copy(); $date->lte($end); $date->addDay()) {
            if ($date->isSunday()) {
                continue;
            }

            $dateStr = $date->toDateString();

            $record = Attendance::where('user_id', $leave->user_id)
                ->where('date', $dateStr)
                ->where('status', $leave->type)
                ->whereNull('check_in_time')
                ->first();

            if ($record) {
                $record->delete();
            }
        }
    }
}
