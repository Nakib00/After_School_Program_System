<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Submission extends Model
{
    use HasFactory;

    // Mass assignable fields
    protected $fillable = [
        'assignment_id',
        'student_id',
        'submitted_file',
        'submitted_at',
        'score',
        'time_taken_min',
        'error_count',
        'teacher_feedback',
        'graded_by',
        'graded_at',
        'status',
    ];

    // Casts for proper data types
    protected $casts = [
        'submitted_at' => 'datetime',
        'graded_at' => 'datetime',
        'score' => 'float',
        'time_taken_min' => 'integer',
        'error_count' => 'integer',
    ];

    /**
     * Relationships
     */

    // Submission belongs to an assignment
    public function assignment()
    {
        return $this->belongsTo(Assignment::class);
    }

    // Submission belongs to a student
    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    // Submission graded by a teacher (user)
    public function grader()
    {
        return $this->belongsTo(User::class, 'graded_by');
    }
}
