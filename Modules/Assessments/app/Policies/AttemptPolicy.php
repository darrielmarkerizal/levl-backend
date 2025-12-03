<?php

namespace Modules\Assessments\Policies;

use Modules\Auth\Models\User;
use Modules\Assessments\Models\Attempt;

class AttemptPolicy
{
  /**
   * Determine if the user can view the attempt
   */
  public function view(User $user, Attempt $attempt): bool
  {
    // User can view their own attempts
    if ($attempt->user_id === $user->id) {
      return true;
    }

    // Instructor/Admin can view attempts for their exercises
    if ($user->hasRole(["Instructor", "Admin", "Superadmin"])) {
      $exercise = $attempt->exercise;

      // Superadmin can view all
      if ($user->hasRole("Superadmin")) {
        return true;
      }

      // Creator of exercise can view
      if ($exercise->created_by === $user->id) {
        return true;
      }

      // Admin managing the course can view
      if ($user->hasRole("Admin") && $exercise->scope_type === "course") {
        return $user->managedCourses()->where("courses.id", $exercise->scope_id)->exists();
      }
    }

    return false;
  }

  /**
   * Determine if the user can grade the attempt (update score/feedback)
   */
  public function grade(User $user, Attempt $attempt): bool
  {
    // Only instructors/admins can grade attempts
    if (!$user->hasRole(["Instructor", "Admin", "Superadmin"])) {
      return false;
    }

    $exercise = $attempt->exercise;

    // Superadmin can grade all
    if ($user->hasRole("Superadmin")) {
      return true;
    }

    // Creator of exercise can grade
    if ($exercise->created_by === $user->id) {
      return true;
    }

    // Admin managing the course can grade
    if ($user->hasRole("Admin") && $exercise->scope_type === "course") {
      return $user->managedCourses()->where("courses.id", $exercise->scope_id)->exists();
    }

    return false;
  }
}
