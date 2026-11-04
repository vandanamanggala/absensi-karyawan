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
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

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
}