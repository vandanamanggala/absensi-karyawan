<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Attendance extends Model
{
   protected $fillable = [
        'user_id',
        'date',
        'check_in_time',
        'check_out_time',
        'lat_in',
        'long_in',
        'address_in',    // Wajib ada
        'lat_out',
        'long_out',
        'address_out',   // Wajib ada
        'daily_work_hours',
        'status',
    ];

    public function user()
    {
        return $this->belongsTo(User::class)->withTrashed();
    }
}