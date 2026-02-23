<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Teacher extends Model
{
    use HasFactory;

    // Mass assignable fields
    protected $fillable = [
        'user_id',
        'center_id',
        'employee_id',
        'qualification',
        'join_date',
    ];

    // Casts
    protected $casts = [
        'join_date' => 'date',
    ];

    /**
     * Relationships
     */

    // Teacher belongs to a user account
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Teacher belongs to a center
    public function center()
    {
        return $this->belongsTo(Center::class);
    }

    // Teacher has many students (optional: through assignments)
    public function students()
    {
        return $this->hasManyThrough(Student::class, Assignment::class, 'teacher_id', 'id', 'id', 'student_id');
    }

    // Teacher has many assignments
    public function assignments()
    {
        return $this->hasMany(Assignment::class);
    }

    // Teacher has many submissions graded
    public function gradedSubmissions()
    {
        return $this->hasMany(Submission::class, 'graded_by');
    }
}
