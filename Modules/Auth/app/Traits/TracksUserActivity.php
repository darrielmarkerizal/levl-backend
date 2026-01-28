<?php

declare(strict_types=1);


namespace Modules\Auth\Traits;


use App\Models\ActivityLog;
use Illuminate\Database\Eloquent\Model;

trait TracksUserActivity
{
    public function logActivity(string $type, array $data = [], ?Model $related = null)
    {
        $activity = activity('user_activity')
            ->causedBy($this)
            ->withProperties($data)
            ->event($type);

        if ($related) {
            $activity->performedOn($related);
        }

        return $activity->log($type);
    }

    public function getRecentActivities(int $limit = 10)
    {
        return $this->actions()->orderBy('created_at', 'desc')->limit($limit)->get();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany
     */
    public function actions()
    {
        return $this->morphMany(ActivityLog::class, 'causer');
    }

    public function latestActivity()
    {
        return $this->morphOne(ActivityLog::class, 'causer')->latestOfMany();
    }

    public function getLastActivityAttribute()
    {
        return $this->latestActivity;
    }

    public function getLastActiveRelativeAttribute(): ?string
    {
        $lastActivity = $this->lastActivity;

        if (! $lastActivity) {
            return null;
        }

        return $lastActivity->created_at->locale('id')->diffForHumans();
    }
}
