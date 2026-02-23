<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Attendance extends Model
{
    use HasFactory;

    // Mass assignable fields
    protected $fillable = [
        'student_id',
        'center_id',
        'date',
        'status',
        'marked_by',
        'notes',
    ];

    /**
     * Relationships
     */

    // Attendance belongs to a student
    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    // Attendance belongs to a center
    public function center()
    {
        return $this->belongsTo(Center::class);
    }

    // Attendance marked by a user (teacher/admin)
    public function marker()
    {
        return $this->belongsTo(User::class, 'marked_by');
    }
}
