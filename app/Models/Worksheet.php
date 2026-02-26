<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Worksheet extends Model
{
    use HasFactory;

    // Mass assignable fields
    protected $fillable = [
        'subject_id',
        'level_id',
        'title',
        'worksheet_no',
        'description',
        'file_path',
        'total_marks',
        'time_limit_minutes',
        'created_by',
    ];

    // Casts
    protected $casts = [
        'total_marks' => 'integer',
        'time_limit_minutes' => 'integer',
    ];

    /**
     * Relationships
     */

    // Worksheet belongs to a subject
    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }

    // Worksheet belongs to a level
    public function level()
    {
        return $this->belongsTo(Level::class);
    }

    // Worksheet created by a user (teacher/admin)
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // Worksheet has many assignments
    public function assignments()
    {
        return $this->hasMany(Assignment::class);
    }

    /**
     * Accessor for full file URL.
     */
    public function getFilePathAttribute($value)
    {
        if (!$value) {
            return null;
        }

        // Return full URL for the worksheet file
        return url('storage/' . $value);
    }
}
