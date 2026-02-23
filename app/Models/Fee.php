<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Fee extends Model
{
    use HasFactory;

    // Mass assignable fields
    protected $fillable = [
        'student_id',
        'center_id',
        'month',
        'amount',
        'due_date',
        'paid_date',
        'status',
        'payment_method',
        'transaction_id',
    ];

    /**
     * Relationships
     */

    // Fee belongs to a student
    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    // Fee belongs to a center
    public function center()
    {
        return $this->belongsTo(Center::class);
    }

    public function scopePaid($query)
    {
        return $query->where('status', 'paid');
    }

    public function scopeUnpaid($query)
    {
        return $query->where('status', 'unpaid');
    }
}
