<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class StudentProgress extends Model
{
    use HasFactory;

    // Mass assignable fields
    protected $fillable = [
        'student_id',
        'subject_id',
        'level_id',
        'worksheets_completed',
        'average_score',
        'average_time',
        'level_started_at',
        'level_completed_at',
        'is_level_complete',
    ];

    // Casts for proper data types
    protected $casts = [
        'worksheets_completed' => 'integer',
        'average_score' => 'float',
        'average_time' => 'float',
        'level_started_at' => 'date',
        'level_completed_at' => 'date',
        'is_level_complete' => 'boolean',
    ];

    /**
     * Relationships
     */

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }

    public function level()
    {
        return $this->belongsTo(Level::class);
    }
}
