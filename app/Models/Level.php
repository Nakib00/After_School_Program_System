<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Level extends Model
{
    use HasFactory;

    // Mass assignable fields
    protected $fillable = [
        'subject_id',
        'name',
        'order_index',
        'description',
    ];

    /**
     * Relationships
     */

    // Level belongs to a subject
    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }

    // Level has many worksheets
    public function worksheets()
    {
        return $this->hasMany(Worksheet::class);
    }

    // Level has many student progress records
    public function studentProgress()
    {
        return $this->hasMany(StudentProgress::class);
    }
}
