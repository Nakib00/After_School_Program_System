<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Subject extends Model
{
    use HasFactory;

    // Mass assignable fields
    protected $fillable = [
        'name',
        'description',
        'is_active',
    ];

    /**
     * Relationships
     */

    // Subject has many levels
    public function levels()
    {
        return $this->hasMany(Level::class);
    }

    // Subject has many worksheets
    public function worksheets()
    {
        return $this->hasMany(Worksheet::class);
    }

    // Subject has many student progress records
    public function studentProgress()
    {
        return $this->hasMany(StudentProgress::class);
    }
}
