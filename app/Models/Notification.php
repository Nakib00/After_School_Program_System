<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Notification extends Model
{
    use HasFactory;

    // Mass assignable fields
    protected $fillable = [
        'user_id',
        'title',
        'message',
        'type',
        'is_read',
        'data',
    ];

    /**
     * Casts
     */
    protected $casts = [
        'is_read' => 'boolean',
        'data' => 'array', // JSON payload
    ];

    /**
     * Relationships
     */

    // Notification belongs to a user
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
