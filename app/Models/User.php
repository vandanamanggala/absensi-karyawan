<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable, SoftDeletes;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'join_date',
        'position',
        'address',
        'wa_number',
        'bank_account',
        'company',
        'profile_photo',
        'meal_allowance',
        'overtime_rate_per_hour',
        'face_descriptor',
        'face_registered_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'face_registered_at' => 'datetime',
    ];

    // Helper untuk cek apakah wajah sudah didaftarkan
    public function hasRegisteredFace(): bool
    {
        return !empty($this->face_descriptor);
    }

    // Relasi ke Absensi
    public function attendances()
    {
        return $this->hasMany(Attendance::class);
    }

    // Relasi ke Cuti
    public function leaves()
    {
        return $this->hasMany(Leave::class);
    }

    // Hitung total ketidakhadiran (Sakit & Cuti) untuk Bagian Profil
    public function getAbsenceCountAttribute()
    {
        return $this->attendances()->whereIn('status', ['sakit', 'cuti'])->count();
    }

    /**
     * Hitung total uang makan berdasarkan jumlah hari hadir pada rentang tanggal tertentu.
     */
    public function calculateMealAllowance($startDate, $endDate): float
    {
        if (empty($this->meal_allowance) || $this->meal_allowance <= 0) {
            return 0;
        }

        $presentDays = $this->attendances()
            ->where('status', 'hadir')
            ->whereBetween('date', [$startDate, $endDate])
            ->count();

        return (float) ($presentDays * (float) $this->meal_allowance);
    }

    /**
     * Hitung total uang lembur (jam kerja > 8 jam/hari dikali tarif per jam)
     */
    public function calculateOvertimePay($startDate, $endDate): array
    {
        $rate = (float) ($this->overtime_rate_per_hour ?? 20000);
        $attendances = $this->attendances()
            ->where('status', 'hadir')
            ->whereBetween('date', [$startDate, $endDate])
            ->get();

        $totalOvertimeHours = 0.0;
        foreach ($attendances as $att) {
            $hours = (float) ($att->daily_work_hours ?? 0);
            if ($hours > 8) {
                $totalOvertimeHours += ($hours - 8);
            }
        }

        $totalOvertimePay = round($totalOvertimeHours * $rate, 2);

        return [
            'hours' => round($totalOvertimeHours, 2),
            'rate' => $rate,
            'total_pay' => $totalOvertimePay,
        ];
    }
}
