<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Student extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'center_id',
        'parent_id',
        'teacher_id',
        'enrollment_no',
        'date_of_birth',
        'grade',
        'enrollment_date',
        'subjects',
        'current_level',
        'status',
    ];

    protected $casts = [
        'subjects' => 'array',
        'date_of_birth' => 'date',
        'enrollment_date' => 'date',
    ];

    /**
     * Relationships
     */

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function center()
    {
        return $this->belongsTo(Center::class);
    }

    public function parent()
    {
        return $this->belongsTo(User::class, 'parent_id');
    }

    public function teacher()
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function progress()
    {
        return $this->hasMany(StudentProgress::class);
    }

    public function assignments()
    {
        return $this->hasMany(Assignment::class);
    }

    public function attendance()
    {
        return $this->hasMany(Attendance::class);
    }

    public function fees()
    {
        return $this->hasMany(Fee::class);
    }
}
