<?php

namespace App\Console\Commands;

use App\Models\Attendance;
use Carbon\Carbon;
use Illuminate\Console\Command;

class AutoCheckoutCommand extends Command
{
    protected $signature = 'attendance:auto-checkout';
    protected $description = 'Otomatis absen keluar setelah 20 jam bekerja';

    public function handle()
    {
        $limitTime = Carbon::now()->subHours(20);

        $attendances = Attendance::whereNull('check_out_time')
            ->where('check_in_time', '<=', $limitTime)
            ->get();

        foreach ($attendances as $attendance) {
            $checkIn = Carbon::parse($attendance->check_in_time);
            $checkOut = $checkIn->copy()->addHours(20);

            $attendance->update([
                'check_out_time' => $checkOut,
                'daily_work_hours' => 20,
            ]);
        }

        $this->info('Auto check-out 20 jam berhasil dijalankan.');
    }
}