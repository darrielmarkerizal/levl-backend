<?php

namespace Modules\Content\Policies;

use Modules\Auth\Models\User;
use Modules\Content\Models\Announcement;
use Modules\Content\Models\News;

class ContentPolicy
{
    /**
     * Determine if the user can view the content.
     */
    public function view(User $user, $content): bool
    {
        // Published content can be viewed by anyone
        if ($content->isPublished()) {
            return true;
        }

        // Draft or scheduled content can only be viewed by author or admin
        return $user->id === $content->author_id || $user->hasRole('admin');
    }

    /**
     * Determine if the user can create announcements.
     */
    public function createAnnouncement(User $user): bool
    {
        // Only admin can create announcements
        return $user->hasRole('admin');
    }

    /**
     * Determine if the user can create news.
     */
    public function createNews(User $user): bool
    {
        // Admin and instructor can create news
        return $user->hasRole('admin') || $user->hasRole('instructor');
    }

    /**
     * Determine if the user can create course announcements.
     */
    public function createCourseAnnouncement(User $user, int $courseId): bool
    {
        // Admin can create any course announcement
        if ($user->hasRole('admin')) {
            return true;
        }

        // Instructor can create announcement for their own courses
        if ($user->hasRole('instructor')) {
            return \Modules\Schemes\Models\Course::where('id', $courseId)
                ->where('instructor_id', $user->id)
                ->exists();
        }

        return false;
    }

    /**
     * Determine if the user can update the content.
     */
    public function update(User $user, $content): bool
    {
        // Admin can update any content
        if ($user->hasRole('admin')) {
            return true;
        }

        // Author can update their own content
        if ($user->id === $content->author_id) {
            return true;
        }

        // Instructor can update course announcements for their courses
        if ($content instanceof Announcement && $content->course_id) {
            return $user->hasRole('instructor') &&
                \Modules\Schemes\Models\Course::where('id', $content->course_id)
                    ->where('instructor_id', $user->id)
                    ->exists();
        }

        return false;
    }

    /**
     * Determine if the user can delete the content.
     */
    public function delete(User $user, $content): bool
    {
        // Admin can delete any content
        if ($user->hasRole('admin')) {
            return true;
        }

        // Author can delete their own content
        if ($user->id === $content->author_id) {
            return true;
        }

        // Instructor can delete course announcements for their courses
        if ($content instanceof Announcement && $content->course_id) {
            return $user->hasRole('instructor') &&
                \Modules\Schemes\Models\Course::where('id', $content->course_id)
                    ->where('instructor_id', $user->id)
                    ->exists();
        }

        return false;
    }

    /**
     * Determine if the user can publish the content.
     */
    public function publish(User $user, $content): bool
    {
        // Admin can publish any content
        if ($user->hasRole('admin')) {
            return true;
        }

        // Instructor can publish their own news
        if ($content instanceof News && $user->id === $content->author_id && $user->hasRole('instructor')) {
            return true;
        }

        // Instructor can publish course announcements for their courses
        if ($content instanceof Announcement && $content->course_id) {
            return $user->hasRole('instructor') &&
                \Modules\Schemes\Models\Course::where('id', $content->course_id)
                    ->where('instructor_id', $user->id)
                    ->exists();
        }

        return false;
    }

    /**
     * Determine if the user can schedule the content.
     */
    public function schedule(User $user, $content): bool
    {
        // Same as publish permission
        return $this->publish($user, $content);
    }

    /**
     * Determine if the user can view statistics.
     */
    public function viewStatistics(User $user): bool
    {
        // Admin and instructor can view statistics
        return $user->hasRole('admin') || $user->hasRole('instructor');
    }
}
