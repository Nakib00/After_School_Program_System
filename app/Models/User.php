<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use PHPOpenSourceSaver\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable implements JWTSubject
{
    use HasFactory, Notifiable;

    /**
     * Mass assignable attributes
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role', // super_admin,center_admin, teacher, student, parent
        'phone',
        'address',
        'profile_photo_path',
        'is_active',
    ];

    /**
     * Appended attributes
     */
    protected $appends = ['teacherid'];

    /**
     * Hidden attributes
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Casts
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    /**
     * Accessor: auto-resolve profile_photo_path to full storage URL.
     * Uses Storage::url() so it respects APP_URL in .env
     */
    public function getProfilePhotoPathAttribute($value): ?string
    {
        if (!$value) return null;
        return url(Storage::url($value));
    }

    /**
     * Accessor for teacherid (ID from teacher table).
     */
    public function getTeacheridAttribute()
    {
        return $this->teacher?->id;
    }

    /**
     * Relationships
     */

    // If user is a student
    public function student()
    {
        return $this->hasOne(Student::class, 'user_id');
    }

    // If user is a teacher
    public function teacher()
    {
        return $this->hasOne(Teacher::class, 'user_id');
    }

    // If user is a parent
    public function children()
    {
        return $this->hasMany(Student::class, 'parent_id');
    }

    // Notifications
    public function notifications()
    {
        return $this->hasMany(Notification::class);
    }

    // Center managed by the user (if center_admin)
    public function center()
    {
        return $this->hasOne(Center::class, 'admin_id');
    }

    /**
     * Helper functions
     */

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isTeacher(): bool
    {
        return $this->role === 'teacher';
    }

    public function isStudent(): bool
    {
        return $this->role === 'student';
    }

    public function isParent(): bool
    {
        return $this->role === 'parent';
    }

    /**
     * Get the identifier that will be stored in the subject claim of the JWT.
     *
     * @return mixed
     */
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    /**
     * Return a key value array, containing any custom claims to be added to the JWT.
     *
     * @return array
     */
    public function getJWTCustomClaims()
    {
        return [];
    }
}
