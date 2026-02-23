<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Center extends Model
{
    use HasFactory;

    // Mass assignable fields
    protected $fillable = [
        'name',
        'address',
        'city',
        'phone',
        'admin_id',
        'is_active',
    ];

    /**
     * Relationships
     */

    // Admin of the center
    public function admin()
    {
        return $this->belongsTo(User::class, 'admin_id');
    }

    // Students in the center
    public function students()
    {
        return $this->hasMany(Student::class);
    }

    // Teachers in the center
    public function teachers()
    {
        return $this->hasMany(Teacher::class);
    }

    // Attendance records for this center
    public function attendanceRecords()
    {
        return $this->hasMany(Attendance::class);
    }
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
