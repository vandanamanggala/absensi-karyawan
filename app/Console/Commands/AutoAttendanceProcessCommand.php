<?php

namespace App\Console\Commands;

use App\Services\AttendanceService;
use Illuminate\Console\Command;

class AutoAttendanceProcessCommand extends Command
{
    protected $signature = 'attendance:auto-process';

    protected $description = 'Proses otomatis absensi: auto-checkout jam 23:00 dan auto-alfa jam 11:00 (Senin - Sabtu).';

    public function handle(): int
    {
        $this->info('Menjalankan proses pemeliharaan absensi otomatis...');
        AttendanceService::runAutomatedChecks();
        $this->info('Selesai diproses.');

        return self::SUCCESS;
    }
}
