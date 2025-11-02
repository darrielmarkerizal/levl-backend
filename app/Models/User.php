<?php

namespace Modules\Auth\Entities;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;
use Tymon\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable implements JWTSubject
{
    use Notifiable, HasRoles;

    protected $guard_name = 'api';

    protected $fillable = [
        'name',
        'username',
        'email',
        'password',
        'status',
        'email_verified_at',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims(): array
    {
        return [];
    }

    /**
     * Get courses where this user is the instructor.
     */
    public function instructedCourses(): HasMany
    {
        return $this->hasMany(\Modules\Schemes\Entities\Course::class, 'instructor_id');
    }

    /**
     * Get courses where this user is an admin (many-to-many through course_admins pivot).
     */
    public function administeredCourses(): BelongsToMany
    {
        return $this->belongsToMany(
            \Modules\Schemes\Entities\Course::class,
            'course_admins',
            'user_id',
            'course_id'
        )->withTimestamps();
    }

    /**
     * Get the course admins pivot records.
     */
    public function courseAdmins(): HasMany
    {
        return $this->hasMany(\Modules\Schemes\Entities\CourseAdmin::class);
    }

    /**
     * Check if user is superadmin.
     */
    public function isSuperadmin(): bool
    {
        return $this->hasRole('superadmin');
    }

    /**
     * Check if user is admin (globally or per course).
     */
    public function isAdmin(?int $courseId = null): bool
    {
        if ($this->hasRole('admin')) {
            if ($courseId === null) {
                return true; // Global admin role
            }
            return $this->administeredCourses()->where('course_id', $courseId)->exists();
        }
        return false;
    }

    /**
     * Check if user is instructor (globally or per course).
     */
    public function isInstructor(?int $courseId = null): bool
    {
        if ($this->hasRole('instructor')) {
            if ($courseId === null) {
                return $this->instructedCourses()->exists(); // Has any instructed courses
            }
            return $this->instructedCourses()->where('id', $courseId)->exists();
        }
        return false;
    }

    /**
     * Check if user is student.
     */
    public function isStudent(): bool
    {
        return $this->hasRole('student');
    }

    /**
     * Check if user can manage a specific course (as admin or instructor).
     */
    public function canManageCourse(int $courseId): bool
    {
        return $this->isSuperadmin() 
            || $this->isAdmin($courseId) 
            || $this->isInstructor($courseId);
    }
}
