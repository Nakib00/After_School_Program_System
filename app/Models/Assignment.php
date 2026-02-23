<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Assignment extends Model
{
    use HasFactory;

    // Mass assignable fields
    protected $fillable = [
        'student_id',
        'worksheet_id',
        'teacher_id',
        'assigned_date',
        'due_date',
        'status',
        'notes',
    ];

    /**
     * Relationships
     */

    // Assignment belongs to a student
    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    // Assignment belongs to a worksheet
    public function worksheet()
    {
        return $this->belongsTo(Worksheet::class);
    }

    // Assignment belongs to a teacher (who assigned it)
    public function teacher()
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    // Assignment has one submission
    public function submission()
    {
        return $this->hasOne(Submission::class);
    }
}